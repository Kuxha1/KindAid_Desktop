<?php
// =============================================
// POST /mobile_api/chatbot/chatbot.php
// KindAid Smart Chatbot Query Endpoint
// =============================================

// Suppress display but log to file
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Catch fatal PHP errors and return as JSON (instead of empty body)
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']
        ]);
    }
});

require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth = require_auth();

// ── Input ──────────────────────────────────────────────────
$intent = trim($_POST['intent'] ?? 'search');
$query = trim($_POST['query'] ?? '');
$category = trim($_POST['category'] ?? '');
$max_amount = isset($_POST['max_amount']) && is_numeric($_POST['max_amount'])
    ? (float) $_POST['max_amount'] : null;
$city = trim($_POST['city'] ?? '');
$limit = 8;

// ── Category → keyword map ──────────────────────────────────
$category_keywords = [
    'medical' => [
        'cancer',
        'medical',
        'hospital',
        'health',
        'surgery',
        'disease',
        'treatment',
        'medicine',
        'doctor',
        'illness',
        'operation',
        'transplant',
        'kidney',
        'heart',
        'blood'
    ],
    'education' => [
        'school',
        'education',
        'children',
        'study',
        'college',
        'scholarship',
        'fees',
        'tuition',
        'student',
        'learning',
        'books',
        'uniform',
        'coaching',
        'university',
        'exam'
    ],
    'food' => [
        'food',
        'hunger',
        'meal',
        'nutrition',
        'feed',
        'water',
        'groceries',
        'ration',
        'starving',
        'eating',
        'kitchen',
        'langar',
        'bhojan',
        'mid-day',
        'vegetables'
    ],
    'animal' => [
        'animal',
        'dog',
        'cat',
        'pet',
        'wildlife',
        'cow',
        'rescue',
        'stray',
        'birds',
        'cattle',
        'shelter',
        'pup'
    ],
    'emergency' => [
        'emergency',
        'flood',
        'fire',
        'earthquake',
        'disaster',
        'accident',
        'homeless',
        'cyclone',
        'storm',
        'urgent',
        'crisis',
        'relief',
        'victim',
        'affected'
    ],
    'clothes' => [
        'clothes',
        'clothing',
        'blanket',
        'winter',
        'warm',
        'dress',
        'shoes',
        'garment',
        'fabric',
        'wear'
    ],
    'shelter' => [
        'shelter',
        'home',
        'house',
        'housing',
        'roof',
        'slum',
        'orphan',
        'widow',
        'old age',
        'elderly'
    ],
];

// ── App-Guide answers ───────────────────────────────────────
$guide_map = [
    'donate' => [
        'trigger' => [
            'how to donate',
            'how do i donate',
            'donate',
            'donating',
            'make donation',
            'give money',
            'contribute',
            'payment'
        ],
        'answer' => "💰 *How to Donate on KindAid:*\n\n1. Go to the **Fundraisers** tab from the bottom navigation.\n2. Browse or search for a cause you care about.\n3. Tap on a fundraiser to open its detail page.\n4. Press the **Donate Now** button.\n5. Enter the amount and choose your payment method.\n6. Confirm and your donation is recorded! 🎉\n\nYou can also search specifically by type — like \"Medical fundraisers\" or \"Education help\"."
    ],
    'post_fundraiser' => [
        'trigger' => [
            'how to post fundraiser',
            'create fundraiser',
            'start fundraiser',
            'new fundraiser',
            'raise funds',
            'post a fundraiser'
        ],
        'answer' => "📢 *How to Post a Fundraiser:*\n\n1. Go to the **Fundraisers** tab.\n2. Tap the **+** button (bottom-right).\n3. Fill in the Title, Description, and Goal Amount.\n4. Upload images or documents (optional but recommended for trust).\n5. Tap **Create** — your fundraiser goes live immediately!\n\n✅ Tip: Get **verified** by uploading your documents in Profile → Settings to build donor trust."
    ],
    'post_need' => [
        'trigger' => [
            'how to post need',
            'create need',
            'post a need',
            'request help',
            'ask for help',
            'need help'
        ],
        'answer' => "🙏 *How to Post a Need:*\n\n1. Go to the **Needs** tab.\n2. Tap the **+** (create) button.\n3. Enter a Goal Title and Description of what you need.\n4. Add images if helpful.\n5. Tap **Submit** — the community can now see and help you.\n\nYou can update the progress % as help arrives!"
    ],
    'verified' => [
        'trigger' => [
            'verified',
            'is this verified',
            'verification',
            'trust',
            'genuine',
            'blue tick',
            'checkmark',
            'authentic'
        ],
        'answer' => "✅ *About Verified Accounts:*\n\nThe **purple checkmark ✓** next to a name means the account has been verified by the KindAid Admin.\n\nVerification means:\n• The user's identity documents were reviewed\n• The cause is genuine and legitimate\n• Higher trust for donations\n\nTo get verified: Go to **Profile → Settings → Upload Documents** and submit your ID proof. The admin will review within 1–3 days."
    ],
    'report' => [
        'trigger' => [
            'how to report',
            'report user',
            'report post',
            'report fraud',
            'fake fundraiser',
            'suspicious',
            'scam'
        ],
        'answer' => "🚩 *How to Report on KindAid:*\n\n1. Open the post, fundraiser, need, or user profile.\n2. Tap the **⋮ menu** or **Flag icon** (top-right).\n3. Select **Report** and choose a reason.\n4. Submit — our admin team reviews all reports within 24 hours.\n\n⚠️ False reporting is tracked and may lead to account suspension."
    ],
    'search' => [
        'trigger' => ['how to search', 'find fundraiser', 'search for'],
        'answer' => "🔍 *How to Search on KindAid:*\n\nYou can search in multiple ways:\n• Use the **Search bar** at the top of Fundraisers, Needs, or Community tabs.\n• Or just **tell me here** — type things like:\n  - \"Cancer fundraisers\"\n  - \"Education help near me\"\n  - \"Emergency under ₹10,000\"\n\nI'll find the right results for you! 😊"
    ],
    'location' => [
        'trigger' => ['location', 'map', 'geo', 'nearby', 'near me', 'add location', 'help point'],
        'answer' => "📍 *About KindAid Locations / Help Points:*\n\nThe **Map tab** shows real help points added by users — these are places where help is available (like food distribution points, medical camps, etc.).\n\nTo add a location:\n1. Go to the **Map tab**.\n2. Tap the **+** button.\n3. Long-press on the map to pin the exact spot.\n4. Add a description and geo type.\n5. Submit!"
    ],
    'greeting' => [
        'trigger' => [
            'hi',
            'hello',
            'hey',
            'namaste',
            'good morning',
            'good afternoon',
            'good evening',
            'how are you',
            'what can you do',
            'help'
        ],
        'answer' => "👋 Hello! I'm *KindAid Assistant* — your smart helper for this app.\n\nHere's what I can do:\n🔍 **Smart Search** — \"Show cancer fundraisers\"\n📂 **Category Browse** — \"Medical help\" / \"Education\"\n📍 **Location Filter** — \"Fundraisers in Ahmedabad\"\n💰 **Smart Filter** — \"Medical cases under ₹5000\"\n📖 **App Guide** — \"How to donate?\" / \"How to report?\"\n\nJust type naturally — I understand plain language! 😊"
    ],
];

// ── Helper: escape for SQL ─────────────────────────────────
function esc($conn, $val)
{
    return $conn->real_escape_string($val);
}

// ── Check guide intent ───────────────────────────────────────
$msg_lower = strtolower($query);
foreach ($guide_map as $key => $guide) {
    foreach ($guide['trigger'] as $t) {
        if (strpos($msg_lower, $t) !== false) {
            send_success([
                'type' => 'guide',
                'bot_message' => $guide['answer'],
                'results' => [],
                'category' => null,
                'city' => null,
            ]);
            exit;
        }
    }
}

// ── Resolve search keywords ──────────────────────────────────
// Build WHERE addition for category
$cat_where = '';
$active_cat = null;
if (!empty($category) && isset($category_keywords[strtolower($category)])) {
    $active_cat = strtolower($category);
    $kws = $category_keywords[$active_cat];
    $parts = [];
    foreach ($kws as $kw) {
        $parts[] = esc($conn, $kw);
    }
    $cat_conditions = implode("' OR title LIKE '%", $parts);
    $cat_conditions2 = implode("' OR goal_title LIKE '%", $parts);
    $cat_conditions3 = implode("' OR description LIKE '%", $parts);
    // Will be appended per query
}

// Resolve city filter
$city_where = '';
if (!empty($city)) {
    $c = esc($conn, $city);
    $city_where = " AND (description LIKE '%$c%' OR title LIKE '%$c%') ";
}

// Amount filter
$amount_where = '';
if ($max_amount !== null) {
    $amount_where = " AND goal_amount <= $max_amount ";
}

// Generic search keyword
$search_where = '';
if (!empty($query)) {
    $sq = esc($conn, $query);
    $search_where = " AND (title LIKE '%$sq%' OR description LIKE '%$sq%') ";
}

// ── Build category-specific keyword conditions ───────────────
function build_cat_where_title_desc($conn, $keywords)
{
    $parts = [];
    foreach ($keywords as $kw) {
        $k = $conn->real_escape_string($kw);
        $parts[] = "(title LIKE '%$k%' OR description LIKE '%$k%')";
    }
    return " AND (" . implode(' OR ', $parts) . ") ";
}

function build_cat_where_goal_desc($conn, $keywords)
{
    $parts = [];
    foreach ($keywords as $kw) {
        $k = $conn->real_escape_string($kw);
        $parts[] = "(goal_title LIKE '%$k%' OR description LIKE '%$k%')";
    }
    return " AND (" . implode(' OR ', $parts) . ") ";
}

// Determine what extra WHERE to apply for category
$fund_cat_where = '';
$need_cat_where = '';
if ($active_cat !== null) {
    $kws = $category_keywords[$active_cat];
    $fund_cat_where = build_cat_where_title_desc($conn, $kws);
    $need_cat_where = build_cat_where_goal_desc($conn, $kws);
    $search_where = ''; // category is more specific, skip generic search
}

// ── Query fundraisers ────────────────────────────────────────
$fsql = "
    SELECT fundraiser.fundraiser_id, fundraiser.account_id, fundraiser.title, fundraiser.description,
           fundraiser.goal_amount, fundraiser.collected_amount, fundraiser.image_1,
           fundraiser.image_2, fundraiser.document_1, fundraiser.document_2, fundraiser.created_at,
           ROUND((fundraiser.collected_amount / NULLIF(fundraiser.goal_amount, 0)) * 100) as progress_percent,
           accounts.name, accounts.profile_photo, accounts.verified_status
    FROM fundraiser
    JOIN accounts USING (account_id)
    WHERE 1=1
    $fund_cat_where
    $search_where
    $amount_where
    $city_where
    ORDER BY accounts.verified_status='Verified' DESC, fundraiser.created_at DESC
    LIMIT $limit
";
$fres = $conn->query($fsql);
$fundraisers = [];
if ($fres) {
    while ($row = $fres->fetch_assoc()) {
        $row['profile_photo'] = !empty($row['profile_photo']) ? UPLOADS_URL . $row['profile_photo'] : null;
        $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
        $row['image_2'] = !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null;
        $row['document_1'] = !empty($row['document_1']) ? UPLOADS_URL . $row['document_1'] : null;
        $row['document_2'] = !empty($row['document_2']) ? UPLOADS_URL . $row['document_2'] : null;
        $row['goal_amount'] = (float) $row['goal_amount'];
        $row['collected_amount'] = (float) $row['collected_amount'];
        $row['progress_percent'] = (int) ($row['progress_percent'] ?? 0);
        $row['is_owner'] = ((int)$row['account_id'] === (int)$auth['account_id']);
        $fundraisers[] = $row;
    }
}

// ── Query needs ──────────────────────────────────────────────
$nsql = "
    SELECT needs.need_id, needs.account_id, needs.goal_title, needs.description,
           needs.goal_status, needs.progress_percent, needs.image_1, needs.image_2, needs.created_at,
           accounts.name, accounts.profile_photo, accounts.verified_status
    FROM needs
    JOIN accounts ON needs.account_id = accounts.account_id
    WHERE needs.goal_status != 'Completed'
    $need_cat_where
    " . (!empty($search_where) ? str_replace(
        ['title LIKE', 'description LIKE'],
        ['goal_title LIKE', 'description LIKE'],
        $search_where
    ) : '') . "
    ORDER BY needs.created_at DESC
    LIMIT $limit
";
$nres = $conn->query($nsql);
$needs = [];
if ($nres) {
    while ($row = $nres->fetch_assoc()) {
        $row['profile_photo'] = !empty($row['profile_photo']) ? UPLOADS_URL . $row['profile_photo'] : null;
        $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
        $row['image_2'] = !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null;
        $row['progress_percent'] = (int) ($row['progress_percent'] ?? 0);
        $row['is_owner'] = ((int)$row['account_id'] === (int)$auth['account_id']);
        $needs[] = $row;
    }
}

// ── Query users / NGOs ───────────────────────────────────────
$users = [];
if (!empty($query) || !empty($category)) {
    $user_search = '';
    if (!empty($query)) {
        $sq = esc($conn, $query);
        $user_search = "AND (accounts.name LIKE '%$sq%' OR accounts.bio LIKE '%$sq%' OR accounts.address LIKE '%$sq%')";
    }
    $usql = "
        SELECT account_id, name, account_type, profile_photo, verified_status, bio, address
        FROM accounts
        WHERE account_type IN ('Individual','NGO','Organization')
        $user_search
        ORDER BY verified_status='Verified' DESC, name ASC
        LIMIT 6
    ";
    $ures = $conn->query($usql);
    if ($ures) {
        while ($row = $ures->fetch_assoc()) {
            $row['profile_photo'] = !empty($row['profile_photo']) ? UPLOADS_URL . $row['profile_photo'] : null;
            $users[] = $row;
        }
    }
}

// ── Query posts ───────────────────────────────────────────────
$posts = [];
if (!empty($query)) {
    $sq = esc($conn, $query);
    $psql = "
        SELECT community_forum.post_id, community_forum.account_id,
               community_forum.description, community_forum.image_1,
               community_forum.likes, community_forum.created_at,
               accounts.name, accounts.profile_photo, accounts.verified_status
        FROM community_forum
        JOIN accounts ON community_forum.account_id = accounts.account_id
        WHERE community_forum.status='Active'
          AND (community_forum.description LIKE '%$sq%' OR accounts.name LIKE '%$sq%')
        ORDER BY community_forum.created_at DESC
        LIMIT 5
    ";
    $pres = $conn->query($psql);
    if ($pres) {
        while ($row = $pres->fetch_assoc()) {
            $row['profile_photo'] = !empty($row['profile_photo']) ? UPLOADS_URL . $row['profile_photo'] : null;
            $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
            $row['likes'] = (int) $row['likes'];
            $row['is_liked'] = false;
            $row['is_owner'] = false;
            $row['comment_count'] = 0;
            $posts[] = $row;
        }
    }
}

// ── Query locations ───────────────────────────────────────────
$locations = []; {
    $loc_search = '';
    if (!empty($query)) {
        $sq = esc($conn, $query);
        $loc_search = "AND (geo_locations.description LIKE '%$sq%' OR geo_locations.geo_type LIKE '%$sq%')";
    }
    if (!empty($city)) {
        $c = esc($conn, $city);
        $loc_search .= " AND geo_locations.description LIKE '%$c%' ";
    }
    if (!empty($loc_search) || !empty($fund_cat_where)) {
        $lsql = "
            SELECT geo_locations.geo_id, geo_locations.account_id,
                   geo_locations.geo_type, geo_locations.description,
                   geo_locations.latitude, geo_locations.longitude,
                   geo_locations.image_1, geo_locations.goal_status,
                   geo_locations.created_at,
                   accounts.name, accounts.profile_photo, accounts.verified_status
            FROM geo_locations
            JOIN accounts ON geo_locations.account_id = accounts.account_id
            WHERE geo_locations.goal_status != 'Completed'
            $loc_search
            ORDER BY geo_locations.created_at DESC
            LIMIT 5
        ";
        $lres = $conn->query($lsql);
        if ($lres) {
            while ($row = $lres->fetch_assoc()) {
                $row['profile_photo'] = !empty($row['profile_photo']) ? UPLOADS_URL . $row['profile_photo'] : null;
                $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
                $row['latitude'] = (float) $row['latitude'];
                $row['longitude'] = (float) $row['longitude'];
                $row['is_owner'] = false;
                $locations[] = $row;
            }
        }
    }
}

// ── Build bot message ────────────────────────────────────────
$total = count($fundraisers) + count($needs) + count($users) + count($posts) + count($locations);
$cat_label = $active_cat ? ucfirst($active_cat) : null;
$city_label = !empty($city) ? " in $city" : '';
$amount_label = $max_amount !== null ? " under ₹" . number_format($max_amount) : '';

if ($total === 0) {
    $bot_msg = "🔍 I searched for " .
        ($cat_label ? "*$cat_label*" : "\"$query\"") .
        "$city_label$amount_label but found no results.\n\n" .
        "Try:\n• A different category (Medical, Food, Education)\n" .
        "• Removing the amount filter\n• A broader search term";
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
    if (!empty($locations))
        $parts[] = count($locations) . ' location' . (count($locations) > 1 ? 's' : '');
    $found_str = implode(', ', $parts);

    $bot_msg = "✅ Found $found_str" .
        ($cat_label ? " in *$cat_label*" : (!empty($query) ? " matching \"$query\"" : '')) .
        $city_label . $amount_label . ":\n\nHere are the results 👇";
}

send_success([
    'type' => 'results',
    'bot_message' => $bot_msg,
    'fundraisers' => $fundraisers,
    'needs' => $needs,
    'users' => $users,
    'posts' => $posts,
    'locations' => $locations,
    'category' => $active_cat,
    'city' => $city ?: null,
]);
