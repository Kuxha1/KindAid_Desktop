<?php
session_start();
include "admin/config/db.php";

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$account_id = $_SESSION['account_id'];

$user = $conn->query("
    SELECT name, profile_photo
    FROM accounts
    WHERE account_id=$account_id
")->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>KindAid — AI Assistant</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/main-style.css" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "var(--primary)",
                        secondary: "var(--secondary)",
                        "secondary-dark": "var(--secondary-dark)",
                        accent: "var(--accent)",
                        background: "var(--bg-main)",
                        textmain: "var(--text-main)"
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        /* ── Chatbot Styles ─────────────────────── */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 64px);
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px 16px;
            scroll-behavior: smooth;
        }
        .chat-messages::-webkit-scrollbar { width: 6px; }
        .chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        .msg-row { display: flex; margin-bottom: 16px; animation: msgSlideIn 0.3s ease; }
        .msg-row.user { justify-content: flex-end; }
        .msg-row.bot { justify-content: flex-start; }

        @keyframes msgSlideIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .msg-bubble {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.55;
            word-wrap: break-word;
            white-space: pre-wrap;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .msg-bubble.user-bubble {
            background: linear-gradient(135deg, #610C9F, #8C3FD4);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .msg-bubble.bot-bubble {
            background: white;
            color: #1e293b;
            border-bottom-left-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .bot-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #610C9F, #8C3FD4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-right: 8px; margin-top: 2px;
        }
        .bot-avatar .material-symbols-outlined { color: white; font-size: 18px; }

        /* Typing indicator */
        .typing-dots { display: flex; gap: 4px; padding: 8px 0; }
        .typing-dots span {
            width: 8px; height: 8px;
            background: #94a3b8;
            border-radius: 50%;
            animation: dotPulse 1.2s infinite;
        }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse {
            0%, 60%, 100% { transform: scale(0.6); opacity: 0.4; }
            30% { transform: scale(1); opacity: 1; }
        }

        /* Suggestion chips */
        .chip {
            display: inline-block;
            padding: 8px 16px;
            background: white;
            border: 1.5px solid rgba(97,12,159,0.3);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #610C9F;
            cursor: pointer;
            transition: all 0.2s;
            margin: 4px;
        }
        .chip:hover {
            background: rgba(97,12,159,0.08);
            border-color: #610C9F;
            transform: translateY(-1px);
        }

        /* Result cards */
        .result-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            margin-top: 8px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .result-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border-color: #c9a6f2;
        }

        /* Input bar */
        .chat-input-bar {
            border-top: 1px solid #e2e8f0;
            padding: 12px 16px;
            background: white;
            display: flex; gap: 10px; align-items: center;
        }
        .chat-input-bar input {
            flex: 1;
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 24px;
            padding: 12px 20px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .chat-input-bar input:focus {
            border-color: #610C9F;
            background: white;
            box-shadow: 0 0 0 3px rgba(97,12,159,0.1);
        }
        .send-btn {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #610C9F, #8C3FD4);
            border: none; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .send-btn:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(97,12,159,0.3); }
        .send-btn:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; box-shadow: none; }
        .send-btn .material-symbols-outlined { color: white; font-size: 20px; }

        .progress-bar-mini {
            height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-top: 6px;
        }
        .progress-bar-mini .fill {
            height: 100%; background: linear-gradient(90deg, #54C6EB, #16A34A); border-radius: 3px;
            transition: width 0.5s;
        }

        @media (max-width: 768px) {
            .msg-bubble { max-width: 88%; }
        }
    </style>
</head>

<body class="bg-[var(--bg-main)] text-[var(--text-main)] font-display">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar Navigation -->
        <aside id="sidebar"
            class="w-64 bg-[var(--secondary-dark)] fixed lg:relative
                   left-0 top-0 h-full z-50
                   transform -translate-x-full lg:translate-x-0
                   transition-transform duration-300 ease-in-out
                   flex flex-col">

            <div class="p-6 flex justify-center items-center">
                <div class="logo">
                    <img src="assets/logo(4).png" class="h-10 sm:h-12 object-contain">
                </div>
            </div>

            <nav class="flex-1 px-4 space-y-1">
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="index.php">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-sm font-medium">Home</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="needs.php">
                    <span class="material-symbols-outlined">handshake</span>
                    <span class="text-sm font-medium">Needs</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="fundraiser.php">
                    <span class="material-symbols-outlined">favorite</span>
                    <span class="text-sm font-medium">Donate</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="location.php">
                    <span class="material-symbols-outlined">map</span>
                    <span class="text-sm font-medium">Map</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="community.php">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">dynamic_feed</span>
                    <span class="text-sm font-semibold">Posts</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="view_profile.php">
                    <span class="material-symbols-outlined">account_circle</span>
                    <span class="text-sm font-medium">Profile</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-white bg-white/15 rounded-lg transition-colors" href="chatbot.php">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">smart_toy</span>
                    <span class="text-sm font-semibold">AI Assistant</span>
                </a>
            </nav>

            <div class="p-4 border-t border-white/10">
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white transition-colors" href="logout.php">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="text-sm font-medium">LOGOUT</span>
                </a>
            </div>
        </aside>

        <!-- Sidebar Overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-8 z-10 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden mr-2 text-slate-600">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bot-avatar" style="width:36px;height:36px;">
                        <span class="material-symbols-outlined" style="font-size:20px;">smart_toy</span>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-slate-900">KindAid Assistant</h1>
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-xs text-slate-500">Online · Gemini AI</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="clearChat()" class="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition" title="Clear chat">
                        <span class="material-symbols-outlined">refresh</span>
                    </button>

                    <div class="h-8 w-[1px] bg-slate-200 mx-1"></div>

                    <?php if ($user) { ?>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-900 hidden sm:block">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </span>
                            <img class="w-8 h-8 rounded-full border-2 border-primary/20 object-cover"
                                src="<?php echo !empty($user['profile_photo'])
                                    ? 'uploads/' . $user['profile_photo']
                                    : 'assets/default-user.png'; ?>"
                                alt="Profile">
                        </div>
                    <?php } ?>
                </div>
            </header>

            <!-- Chat Area -->
            <div class="chat-container bg-slate-50">
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages injected by JS -->
                </div>

                <!-- Suggestion Chips -->
                <div id="suggestionChips" class="px-4 pb-2 flex flex-wrap gap-1">
                    <button class="chip" onclick="sendMessage('Show medical fundraisers')">🏥 Medical fundraisers</button>
                    <button class="chip" onclick="sendMessage('How to donate?')">💰 How to donate?</button>
                    <button class="chip" onclick="sendMessage('Show education needs')">📚 Education needs</button>
                    <button class="chip" onclick="sendMessage('How to report?')">🚩 How to report?</button>
                    <button class="chip" onclick="sendMessage('What is KindAid?')">❓ What is KindAid?</button>
                    <button class="chip" onclick="sendMessage('Show verified NGOs')">✅ Verified NGOs</button>
                </div>

                <!-- Input Bar -->
                <div class="chat-input-bar">
                    <input type="text" id="chatInput" placeholder="Search or ask anything..."
                        onkeypress="if(event.key==='Enter') sendMessage()">
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendBtn');
        const chipContainer = document.getElementById('suggestionChips');

        let conversationHistory = [];
        let isTyping = false;

        // ── Init ────────────────────────────────────
        addBotMessage("👋 Hi! I'm *KindAid Assistant* 🤖\n\nI can help you:\n🔍 Search fundraisers, needs, users & posts\n📂 Browse by category (Medical, Food, Education...)\n🤖 Chat with AI for guidance\n📖 Learn how to use KindAid\n\nType naturally — just tell me what you're looking for!");

        // ── Send Message ────────────────────────────
        function sendMessage(override) {
            const text = (override || chatInput.value).trim();
            if (!text || isTyping) return;

            chatInput.value = '';
            addUserMessage(text);
            chipContainer.style.display = 'none';
            showTyping();

            // Determine action
            const lower = text.toLowerCase();
            const isSearch = /show|find|search|list|get|fundraiser|need|ngo|user|post|location|medical|education|food|animal|emergency|clothes|shelter/.test(lower);
            const action = isSearch ? 'search' : 'ai_chat';

            fetch('api_chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: action,
                    message: text,
                    history: conversationHistory.slice(-10)
                })
            })
            .then(res => res.json())
            .then(data => {
                removeTyping();

                if (data.success) {
                    conversationHistory.push({ role: 'user', text: text });

                    if (data.type === 'ai' || data.type === 'guide') {
                        addBotMessage(data.reply);
                        conversationHistory.push({ role: 'assistant', text: data.reply });
                    } else if (data.type === 'results') {
                        addBotMessage(data.reply);
                        renderResults(data.results);
                        conversationHistory.push({ role: 'assistant', text: data.reply });
                    }
                } else {
                    // Fallback: try AI
                    if (action === 'search') {
                        fetchAI(text);
                    } else {
                        addBotMessage('⚠️ ' + (data.message || 'Something went wrong. Please try again.'));
                    }
                }

                chipContainer.style.display = 'flex';
            })
            .catch(err => {
                removeTyping();
                addBotMessage('⚠️ Connection error. Please check your network.');
                chipContainer.style.display = 'flex';
            });
        }

        function fetchAI(text) {
            fetch('api_chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'ai_chat',
                    message: text,
                    history: conversationHistory.slice(-10)
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    addBotMessage(data.reply);
                } else {
                    addBotMessage('🤖 I\'m having trouble connecting right now. Try again later.');
                }
            });
        }

        // ── Render Messages ─────────────────────────
        function addUserMessage(text) {
            const row = document.createElement('div');
            row.className = 'msg-row user';
            row.innerHTML = `<div class="msg-bubble user-bubble">${escapeHtml(text)}</div>`;
            chatMessages.appendChild(row);
            scrollToBottom();
        }

        function addBotMessage(text) {
            const row = document.createElement('div');
            row.className = 'msg-row bot';
            row.innerHTML = `
                <div class="bot-avatar">
                    <span class="material-symbols-outlined">smart_toy</span>
                </div>
                <div class="msg-bubble bot-bubble">${formatBotText(text)}</div>
            `;
            chatMessages.appendChild(row);
            scrollToBottom();
        }

        function showTyping() {
            isTyping = true;
            sendBtn.disabled = true;
            const row = document.createElement('div');
            row.className = 'msg-row bot';
            row.id = 'typingIndicator';
            row.innerHTML = `
                <div class="bot-avatar">
                    <span class="material-symbols-outlined">smart_toy</span>
                </div>
                <div class="msg-bubble bot-bubble">
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            `;
            chatMessages.appendChild(row);
            scrollToBottom();
        }

        function removeTyping() {
            isTyping = false;
            sendBtn.disabled = false;
            const el = document.getElementById('typingIndicator');
            if (el) el.remove();
        }

        function renderResults(results) {
            const container = document.createElement('div');
            container.className = 'msg-row bot';
            let html = '<div style="margin-left:40px; width: calc(100% - 56px);">';

            // Fundraisers
            if (results.fundraisers && results.fundraisers.length > 0) {
                results.fundraisers.forEach(f => {
                    const progress = f.goal_amount > 0 ? Math.round((f.collected_amount / f.goal_amount) * 100) : 0;
                    const pfp = f.profile_photo ? 'uploads/' + f.profile_photo : 'assets/default-user.png';
                    html += `
                        <a href="fundraiser.php?user_id=${f.account_id}" class="result-card block" style="text-decoration:none;color:inherit;">
                            <div class="flex items-center gap-2 mb-1">
                                <img src="${pfp}" class="w-6 h-6 rounded-full object-cover">
                                <span class="text-xs font-semibold">${escapeHtml(f.name)}</span>
                                ${f.verified_status === 'Verified' ? '<span class="material-symbols-outlined text-primary text-xs" style="font-variation-settings:\'FILL\' 1">verified</span>' : ''}
                            </div>
                            <div class="font-bold text-sm">${escapeHtml(f.title)}</div>
                            <div class="text-xs text-gray-500 mt-1">₹${Number(f.collected_amount).toLocaleString()} / ₹${Number(f.goal_amount).toLocaleString()}</div>
                            <div class="progress-bar-mini"><div class="fill" style="width:${Math.min(progress, 100)}%"></div></div>
                        </a>`;
                });
            }

            // Needs
            if (results.needs && results.needs.length > 0) {
                results.needs.forEach(n => {
                    const pfp = n.profile_photo ? 'uploads/' + n.profile_photo : 'assets/default-user.png';
                    html += `
                        <a href="needs.php?user_id=${n.account_id}" class="result-card block" style="text-decoration:none;color:inherit;">
                            <div class="flex items-center gap-2 mb-1">
                                <img src="${pfp}" class="w-6 h-6 rounded-full object-cover">
                                <span class="text-xs font-semibold">${escapeHtml(n.name)}</span>
                            </div>
                            <div class="font-bold text-sm">${escapeHtml(n.goal_title)}</div>
                            <div class="text-xs text-gray-500 mt-1">${n.goal_status} · ${n.progress_percent || 0}%</div>
                        </a>`;
                });
            }

            // Users
            if (results.users && results.users.length > 0) {
                results.users.forEach(u => {
                    const pfp = u.profile_photo ? 'uploads/' + u.profile_photo : 'assets/default-user.png';
                    html += `
                        <a href="view_profile.php?account_id=${u.account_id}" class="result-card block" style="text-decoration:none;color:inherit;">
                            <div class="flex items-center gap-2">
                                <img src="${pfp}" class="w-8 h-8 rounded-full object-cover">
                                <div>
                                    <div class="flex items-center gap-1">
                                        <span class="font-bold text-sm">${escapeHtml(u.name)}</span>
                                        ${u.verified_status === 'Verified' ? '<span class="material-symbols-outlined text-primary text-xs" style="font-variation-settings:\'FILL\' 1">verified</span>' : ''}
                                    </div>
                                    <span class="text-xs text-gray-500">${u.account_type}</span>
                                </div>
                            </div>
                        </a>`;
                });
            }

            // Posts
            if (results.posts && results.posts.length > 0) {
                results.posts.forEach(p => {
                    const pfp = p.profile_photo ? 'uploads/' + p.profile_photo : 'assets/default-user.png';
                    const desc = (p.description || '').substring(0, 100);
                    html += `
                        <a href="community.php?user_id=${p.account_id}" class="result-card block" style="text-decoration:none;color:inherit;">
                            <div class="flex items-center gap-2 mb-1">
                                <img src="${pfp}" class="w-6 h-6 rounded-full object-cover">
                                <span class="text-xs font-semibold">${escapeHtml(p.name)}</span>
                            </div>
                            <div class="text-sm text-gray-600">${escapeHtml(desc)}...</div>
                        </a>`;
                });
            }

            html += '</div>';
            container.innerHTML = html;
            chatMessages.appendChild(container);
            scrollToBottom();
        }

        // ── Utilities ───────────────────────────────
        function formatBotText(text) {
            let escaped = escapeHtml(text);
            // Bold *text*
            escaped = escaped.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
            // Line breaks
            escaped = escaped.replace(/\n/g, '<br>');
            return escaped;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function scrollToBottom() {
            setTimeout(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 100);
        }

        function clearChat() {
            chatMessages.innerHTML = '';
            conversationHistory = [];
            chipContainer.style.display = 'flex';
            addBotMessage("👋 Hi! I'm *KindAid Assistant* 🤖\n\nI can help you:\n🔍 Search fundraisers, needs, users & posts\n📂 Browse by category (Medical, Food, Education...)\n🤖 Chat with AI for guidance\n📖 Learn how to use KindAid\n\nType naturally — just tell me what you're looking for!");
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>

</html>
