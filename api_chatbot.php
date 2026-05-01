<?php
session_start();
include "admin/config/db.php";

header('Content-Type: application/json');

// ── Auth check ───────────────────────────────────────────────
if (!isset($_SESSION['account_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$account_id = $_SESSION['account_id'];

// ── Gemini API Configuration (same as mobile app) ────────────
define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');
define('GEMINI_MODEL_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent');

define(
    'SYSTEM_PROMPT',
    'You are KindAid Assistant — a warm, helpful AI inside KindAid, ' .
    'a charity and community-impact app in India. ' .
    'KindAid lets users post fundraisers, needs, community posts, mark help-point locations, and donate. ' .
    'Answer concisely (3-5 lines). Never invent fundraiser names or amounts. ' .
    'If the user asks to search for data, say you are searching and encourage them to use the search chips. ' .
    'Always be encouraging and compassionate. Format your response in plain text, not markdown.'
);

// ── Read JSON body ───────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$action = $input['action'] ?? 'ai_chat';
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

// ── Action: ai_chat → Forward to Gemini ──────────────────────
if ($action === 'ai_chat') {

    $contents = [];
    foreach ($history as $msg) {
        $role = ($msg['role'] === 'user') ? 'user' : 'model';
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $msg['text']]]
        ];
    }
    // Add current message
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $message]]
    ];

    // Keep only last 10 messages
    if (count($contents) > 10) {
        $contents = array_slice($contents, -10);
    }

    $payload = [
        'systemInstruction' => [
            'parts' => [['text' => SYSTEM_PROMPT]]
        ],
        'contents' => $contents,
        'generationConfig' => [
            'maxOutputTokens' => 3000,
            'temperature' => 0.7,
        ]
    ];

    $url = GEMINI_MODEL_URL . '?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'Connection error: ' . $curlError]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'AI service error (HTTP ' . $httpCode . ')']);
        exit;
    }

    $data = json_decode($response, true);
    $candidates = $data['candidates'] ?? [];

    if (!empty($candidates)) {
        $parts = $candidates[0]['content']['parts'] ?? [];
        if (!empty($parts)) {
            $text = trim($parts[0]['text'] ?? '');
            if (!empty($text)) {
                echo json_encode(['success' => true, 'type' => 'ai', 'reply' => $text]);
                exit;
            }
        }
    }

    echo json_encode(['success' => false, 'message' => 'No response from AI']);
    exit;
}

// ── Action: search → Query database (same logic as mobile chatbot) ──
if ($action === 'search') {

    $query = $conn->real_escape_string($message);
    $limit = 6;

    // ── App-Guide answers ────────────────────────────────────
    $guide_map = [
        'donate' => [
            'trigger' => ['how to donate', 'how do i donate', 'donate', 'donating', 'make donation', 'give money', 'contribute', 'payment'],
            'answer' => "💰 How to Donate on KindAid:\n\n1. Go to the Donate tab from the sidebar.\n2. Browse or search for a cause you care about.\n3. Enter the amount and choose your payment method.\n4. Confirm and your donation is recorded! 🎉"
        ],
        'post_fundraiser' => [
            'trigger' => ['how to post fundraiser', 'create fundraiser', 'start fundraiser', 'new fundraiser', 'raise funds', 'post a fundraiser'],
            'answer' => "📢 How to Post a Fundraiser:\n\n1. Go to the Donate tab.\n2. Fill in Title, Description, and Goal Amount.\n3. Upload images or documents.\n4. Click Post Fundraiser — it goes live immediately!"
        ],
        'post_need' => [
            'trigger' => ['how to post need', 'create need', 'post a need', 'request help', 'ask for help', 'need help'],
            'answer' => "🙏 How to Post a Need:\n\n1. Go to the Needs tab.\n2. Enter a Goal Title and Description.\n3. Add images if helpful.\n4. Click Post Need — the community can now see and help you."
        ],
        'verified' => [
            'trigger' => ['verified', 'is this verified', 'verification', 'trust', 'genuine', 'blue tick', 'checkmark', 'authentic'],
            'answer' => "✅ About Verified Accounts:\n\nThe purple checkmark next to a name means the account has been verified by the KindAid Admin.\n\nTo get verified: Go to Settings → Upload Documents and submit your ID proof."
        ],
        'report' => [
            'trigger' => ['how to report', 'report user', 'report post', 'report fraud', 'fake fundraiser', 'suspicious', 'scam'],
            'answer' => "🚩 How to Report on KindAid:\n\n1. Open the post, fundraiser, need, or user profile.\n2. Click the Flag icon.\n3. Enter a reason and submit.\n4. Our admin team reviews all reports within 24 hours."
        ],
        'greeting' => [
            'trigger' => ['hi', 'hello', 'hey', 'namaste', 'good morning', 'good afternoon', 'good evening', 'how are you', 'what can you do', 'help'],
            'answer' => "👋 Hello! I'm KindAid Assistant — your smart helper.\n\nHere's what I can do:\n🔍 Smart Search — \"Show cancer fundraisers\"\n📂 Category Browse — \"Medical help\" / \"Education\"\n📖 App Guide — \"How to donate?\" / \"How to report?\"\n\nJust type naturally — I understand plain language! 😊"
        ],
    ];

    // Check guide triggers
    $msg_lower = strtolower($message);
    foreach ($guide_map as $key => $guide) {
        foreach ($guide['trigger'] as $t) {
            if (strpos($msg_lower, $t) !== false) {
                echo json_encode([
                    'success' => true,
                    'type' => 'guide',
                    'reply' => $guide['answer'],
                    'results' => []
                ]);
                exit;
            }
        }
    }

    // ── Search database ──────────────────────────────────────
    $fundraisers = [];
    $fres = $conn->query("
        SELECT fundraiser.*, accounts.name, accounts.profile_photo, accounts.verified_status
        FROM fundraiser
        JOIN accounts ON fundraiser.account_id = accounts.account_id
        WHERE (fundraiser.title LIKE '%$query%' OR fundraiser.description LIKE '%$query%')
        ORDER BY accounts.verified_status='Verified' DESC, fundraiser.created_at DESC
        LIMIT $limit
    ");
    if ($fres) {
        while ($row = $fres->fetch_assoc()) {
            $row['goal_amount'] = (float) $row['goal_amount'];
            $row['collected_amount'] = (float) $row['collected_amount'];
            $fundraisers[] = $row;
        }
    }

    $needs = [];
    $nres = $conn->query("
        SELECT needs.*, accounts.name, accounts.profile_photo, accounts.verified_status
        FROM needs
        JOIN accounts ON needs.account_id = accounts.account_id
        WHERE (needs.goal_title LIKE '%$query%' OR needs.description LIKE '%$query%')
        ORDER BY needs.created_at DESC
        LIMIT $limit
    ");
    if ($nres) {
        while ($row = $nres->fetch_assoc()) {
            $needs[] = $row;
        }
    }

    $users = [];
    $ures = $conn->query("
        SELECT account_id, name, account_type, profile_photo, verified_status, bio
        FROM accounts
        WHERE (name LIKE '%$query%' OR bio LIKE '%$query%' OR account_type LIKE '%$query%')
        ORDER BY verified_status='Verified' DESC
        LIMIT $limit
    ");
    if ($ures) {
        while ($row = $ures->fetch_assoc()) {
            $users[] = $row;
        }
    }

    $posts = [];
    $pres = $conn->query("
        SELECT community_forum.*, accounts.name, accounts.profile_photo
        FROM community_forum
        JOIN accounts ON community_forum.account_id = accounts.account_id
        WHERE community_forum.status='Active'
        AND (community_forum.description LIKE '%$query%' OR accounts.name LIKE '%$query%')
        ORDER BY community_forum.created_at DESC
        LIMIT 5
    ");
    if ($pres) {
        while ($row = $pres->fetch_assoc()) {
            $posts[] = $row;
        }
    }

    $total = count($fundraisers) + count($needs) + count($users) + count($posts);

    if ($total === 0) {
        $bot_msg = "🔍 I searched for \"$message\" but found no results.\n\nTry:\n• A different search term\n• Asking me a question about KindAid\n• Browsing the Needs or Donate pages";
    } else {
        $parts = [];
        if (!empty($fundraisers))
            $parts[] = count($fundraisers) . ' fundraiser' . (count($fundraisers) > 1 ? 's' : '');
        if (!empty($needs))
            $parts[] = count($needs) . ' need' . (count($needs) > 1 ? 's' : '');
        if (!empty($users))
            $parts[] = count($users) . ' user' . (count($users) > 1 ? 's' : '');
        if (!empty($posts))
            $parts[] = count($posts) . ' post' . (count($posts) > 1 ? 's' : '');
        $bot_msg = "✅ Found " . implode(', ', $parts) . " matching \"$message\":\n\nHere are the results 👇";
    }

    echo json_encode([
        'success' => true,
        'type' => 'results',
        'reply' => $bot_msg,
        'results' => [
            'fundraisers' => $fundraisers,
            'needs' => $needs,
            'users' => $users,
            'posts' => $posts
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
