<?php
session_start();
include "admin/config/db.php";

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$account_id = $_SESSION['account_id'];

/* ===================================
   FETCH LOGGED USER
=================================== */

$user = $conn->query("
SELECT name, profile_photo
FROM accounts
WHERE account_id=$account_id
")->fetch_assoc();


/* ===================================
   GLOBAL SEARCH / DEFAULT RESULTS
=================================== */

$search = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {

    $search = $conn->real_escape_string($_GET['search']);

    /* USERS / NGOs */
    $users = $conn->query("
        SELECT *
        FROM accounts
        WHERE name LIKE '%$search%'
        OR email LIKE '%$search%'
        OR bio LIKE '%$search%'
        OR account_type LIKE '%$search%'
        ORDER BY verified_status='Verified' DESC
        LIMIT 6
    ");

    /* NEEDS */
    $needs = $conn->query("
        SELECT *
        FROM needs
        WHERE goal_title LIKE '%$search%'
        OR description LIKE '%$search%'
        ORDER BY created_at DESC
        LIMIT 6
    ");

    /* FUNDRAISERS */
    $fundraisers = $conn->query("
        SELECT *
        FROM fundraiser
        WHERE title LIKE '%$search%'
        OR description LIKE '%$search%'
        ORDER BY created_at DESC
        LIMIT 6
    ");

    /* LOCATIONS */
    $locations = $conn->query("
        SELECT *
        FROM geo_locations
        WHERE description LIKE '%$search%'
        ORDER BY created_at DESC
        LIMIT 6
    ");

    /* COMMUNITY POSTS */
    $posts = $conn->query("
        SELECT community_forum.*, accounts.name, accounts.profile_photo
        FROM community_forum
        JOIN accounts ON community_forum.account_id = accounts.account_id
        WHERE community_forum.status='Active'
        AND community_forum.description LIKE '%$search%'
        ORDER BY community_forum.created_at DESC
        LIMIT 6
    ");
} else {

    /* DEFAULT RESULTS WHEN PAGE LOADS */

    $users = $conn->query("
        SELECT *
        FROM accounts
        ORDER BY verified_status='Verified' DESC, created_at DESC
        LIMIT 6
    ");

    $needs = $conn->query("
        SELECT *
        FROM needs
        ORDER BY created_at DESC
        LIMIT 6
    ");

    $fundraisers = $conn->query("
        SELECT *
        FROM fundraiser
        ORDER BY created_at DESC
        LIMIT 6
    ");

    $locations = $conn->query("
        SELECT *
        FROM geo_locations
        ORDER BY created_at DESC
        LIMIT 6
    ");

    $posts = $conn->query("
        SELECT community_forum.*, accounts.name, accounts.profile_photo
        FROM community_forum
        JOIN accounts ON community_forum.account_id = accounts.account_id
        WHERE community_forum.status='Active'
        ORDER BY community_forum.created_at DESC
        LIMIT 6
    ");
}
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Kind Aid - Social Impact Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
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
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .sidebar-item-active {
            background-color: rgba(99, 11, 162, 0.2);
            border-left: 4px solid #630ba2;
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
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" href="chatbot.php">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">smart_toy</span>
                    <span class="text-sm font-medium">AI Assistant</span>
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
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 dark:bg-background-dark overflow-hidden">
            <!-- Top Header -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 z-10">
                <button onclick="toggleSidebar()"
                    class="lg:hidden mr-4 text-slate-600">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="flex-1 max-w-2xl">

                    <form method="GET" class="relative group">

                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            search
                        </span>

                        <input
                            type="text"
                            name="search"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                            placeholder="Search...."
                            class="w-full bg-slate-100 border-none rounded-xl pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-primary focus:bg-white transition-all">

                    </form>
                </div>

                <div class="flex items-center gap-4 ml-4">

                    <button onclick="window.location.href='settings.php'"
                        class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                        <span class="material-symbols-outlined">settings</span>
                    </button>

                    <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-700 mx-2"></div>

                    <?php if ($user) { ?>

                        <!-- LOGGED IN USER -->
                        <div class="flex items-center gap-3 pl-2">

                            <div class="text-right hidden sm:block">
                                <p class="text-xs font-bold text-slate-900 dark:text-slate-100">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </p>
                            </div>

                            <img class="size-9 rounded-full border-2 border-primary/20 object-cover"
                                src="<?php echo !empty($user['profile_photo'])
                                            ? 'uploads/' . $user['profile_photo']
                                            : 'assets/default-user.png'; ?>"
                                alt="Profile">

                        </div>

                    <?php } else { ?>

                        <!-- NOT LOGGED IN -->
                        <a href="login.php"
                            class="bg-[var(--primary)] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[var(--primary-hover)] transition">
                            Login
                        </a>

                    <?php } ?>

                </div>

            </header>
            <!-- Scrollable Feed Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto space-y-6">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">

                            <?php if (!empty($search)) { ?>

                                Search Results for "<?php echo htmlspecialchars($search); ?>"

                            <?php } else { ?>

                                Discover NGOs, Volunteers & Community Support

                            <?php } ?>

                        </h2>
                    </div>

                    <div class="space-y-10 bg-white p-6 rounded-xl shadow-sm border mb-6">




                        <!-- USERS -->
                        <div>
                            <h3 class="text-xl font-bold mb-4">Users & NGOs</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                <?php if ($users && $users->num_rows > 0) {
                                    while ($u = $users->fetch_assoc()) {

                                        $pfp = (!empty($u['profile_photo']) && file_exists("uploads/" . $u['profile_photo']))
                                            ? "uploads/" . $u['profile_photo']
                                            : "assets/default-user.png";
                                ?>

                                        <div class="bg-white border rounded-xl p-4 shadow-sm">

                                            <div class="flex items-center gap-3">

                                                <img src="<?php echo $pfp; ?>"
                                                    class="w-12 h-12 rounded-full object-cover">

                                                <div>

                                                    <div class="flex items-center gap-1">

                                                        <strong><?php echo $u['name']; ?></strong>

                                                        <?php if ($u['verified_status'] == "Verified") { ?>
                                                            <span class="material-symbols-outlined text-primary text-sm"
                                                                style="font-variation-settings:'FILL'1">verified</span>
                                                        <?php } ?>

                                                    </div>

                                                    <p class="text-xs text-gray-500">
                                                        <?php echo $u['account_type']; ?>
                                                    </p>

                                                </div>

                                            </div>

                                            <a href="view_profile.php?account_id=<?php echo $u['account_id']; ?>"
                                                class="bg-[var(--primary)] text-white px-4 py-2 text-sm rounded-lg inline-flex w-full items-center gap-1 mt-3">
                                                View Profile
                                            </a>

                                        </div>

                                <?php }
                                } ?>

                            </div>
                        </div>



                        <!-- NEEDS -->
                        <div>
                            <h3 class="text-xl font-bold mb-4">Needs</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                <?php if ($needs && $needs->num_rows > 0) {
                                    while ($n = $needs->fetch_assoc()) { ?>

                                        <a href="needs.php?user_id=<?php echo $n['account_id']; ?>"
                                            class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col h-full">

                                            <!-- IMAGE AREA -->
                                            <div class="w-full h-48 bg-gray-100 overflow-hidden">

                                                <?php if ($n['image_1'] || $n['image_2']) { ?>

                                                    <div class="grid <?php echo ($n['image_1'] && $n['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-full">

                                                        <?php if ($n['image_1']) { ?>
                                                            <img src="uploads/<?php echo $n['image_1']; ?>" class="w-full h-full object-cover">
                                                        <?php } ?>

                                                        <?php if ($n['image_2']) { ?>
                                                            <img src="uploads/<?php echo $n['image_2']; ?>" class="w-full h-full object-cover">
                                                        <?php } ?>

                                                    </div>

                                                <?php } ?>

                                            </div>

                                            <div class="p-4 flex flex-col flex-grow">

                                                <h3 class="font-bold text-sm line-clamp-1">
                                                    <?php echo htmlspecialchars($n['goal_title']); ?>
                                                </h3>

                                                <p class="text-sm text-gray-600 mt-1 line-clamp-3 flex-grow">
                                                    <?php echo htmlspecialchars($n['description']); ?>
                                                </p>

                                            </div>

                                        </a>

                                <?php }
                                } ?>

                            </div>
                        </div>



                        <!-- FUNDRAISERS -->
                        <div>
                            <h3 class="text-xl font-bold mb-4">Fundraisers</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                <?php if ($fundraisers && $fundraisers->num_rows > 0) {
                                    while ($f = $fundraisers->fetch_assoc()) { ?>

                                        <a href="fundraiser.php?user_id=<?php echo $f['account_id']; ?>"
                                            class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col h-full">

                                            <!-- IMAGE AREA -->
                                            <div class="w-full h-48 bg-gray-100 overflow-hidden">

                                                <?php if ($f['image_1'] || $f['image_2']) { ?>

                                                    <div class="grid <?php echo ($f['image_1'] && $f['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-full">

                                                        <?php if ($f['image_1']) { ?>
                                                            <img src="uploads/<?php echo $f['image_1']; ?>" class="w-full h-full object-cover">
                                                        <?php } ?>

                                                        <?php if ($f['image_2']) { ?>
                                                            <img src="uploads/<?php echo $f['image_2']; ?>" class="w-full h-full object-cover">
                                                        <?php } ?>

                                                    </div>

                                                <?php } ?>

                                            </div>
                                            <div class="p-4 flex flex-col flex-grow">

                                                <h4 class="font-semibold">
                                                    <?php echo htmlspecialchars($f['title']); ?>
                                                </h4>

                                                <p class="text-sm text-gray-500 line-clamp-2 mt-1">
                                                    <?php echo htmlspecialchars($f['description']); ?>
                                                </p>

                                                <div class="text-xs mt-2 text-gray-600">
                                                    ₹<?php echo number_format($f['collected_amount']); ?> /
                                                    ₹<?php echo number_format($f['goal_amount']); ?>
                                                </div>
                                            </div>

                                        </a>

                                <?php }
                                } ?>

                            </div>
                        </div>



                        <!-- LOCATIONS -->
                        <div>
                            <h3 class="text-xl font-bold mb-4">Locations</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                <?php if ($locations && $locations->num_rows > 0) {
                                    while ($l = $locations->fetch_assoc()) { ?>

                                        <a href="location.php?user_id=<?php echo $l['account_id']; ?>"
                                            class="bg-white border rounded-xl p-4 hover:shadow-md transition block">

                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($l['description']); ?>
                                            </p>

                                            <div class="text-xs text-gray-400 mt-2">
                                                <?php echo $l['geo_type']; ?>
                                            </div>

                                        </a>

                                <?php }
                                } ?>

                            </div>
                        </div>

                        <!-- COMMUNITY POSTS -->
                        <div>
                            <h3 class="text-xl font-bold mb-4">Community Posts</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                <?php if ($posts && $posts->num_rows > 0) {
                                    while ($p = $posts->fetch_assoc()) {

                                        $pfp = (!empty($p['profile_photo']) && file_exists("uploads/" . $p['profile_photo']))
                                            ? "uploads/" . $p['profile_photo']
                                            : "assets/default-user.png";
                                ?>

                                        <a href="community.php?user_id=<?php echo $p['account_id']; ?>" class="bg-white border rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">

                                            <!-- IMAGE -->
                                            <?php if ($p['image_1'] || $p['image_2']) { ?>

                                                <div class="grid <?php echo ($p['image_1'] && $p['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-auto">

                                                    <?php if ($p['image_1']) { ?>
                                                        <img src="uploads/<?php echo $p['image_1']; ?>"
                                                            class="w-full h-full object-cover">
                                                    <?php } ?>

                                                    <?php if ($p['image_2']) { ?>
                                                        <img src="uploads/<?php echo $p['image_2']; ?>"
                                                            class="w-full h-full object-cover">
                                                    <?php } ?>

                                                </div>

                                            <?php } ?>

                                            <div class="p-4">

                                                <!-- USER -->
                                                <div class="flex items-center gap-2 mb-2">

                                                    <img src="<?php echo $pfp; ?>"
                                                        class="w-7 h-7 rounded-full object-cover">

                                                    <span class="text-sm font-semibold">
                                                        <?php echo $p['name']; ?>
                                                    </span>

                                                </div>

                                                <!-- DESCRIPTION -->
                                                <p class="text-sm text-gray-600 line-clamp-2">
                                                    <?php echo htmlspecialchars($p['description']); ?>
                                                </p>

                                                <!-- META -->
                                                <div class="flex justify-between text-xs text-gray-400 mt-3">

                                                    <span>
                                                        <?php echo date("d M Y", strtotime($p['created_at'])); ?>
                                                    </span>

                                                    <div class="flex items-center gap-1">

                                                        <span class="material-symbols-outlined text-sm text-red-500">
                                                            favorite
                                                        </span>

                                                        <?php echo $p['likes']; ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </a>

                                <?php }
                                } ?>

                            </div>
                        </div>




                        <?php
                        if (
                            $users->num_rows == 0 &&
                            $needs->num_rows == 0 &&
                            $fundraisers->num_rows == 0 &&
                            $locations->num_rows == 0 &&
                            $posts->num_rows == 0
                        ) {
                        ?>

                            <div class="text-center py-12 text-gray-500">

                                <span class="material-symbols-outlined text-5xl mb-2">
                                    search_off
                                </span>

                                <p class="text-lg font-semibold">
                                    No results found
                                </p>

                                <p class="text-sm">
                                    Try searching for users, NGOs, posts, needs or locations
                                </p>

                            </div>

                        <?php } ?>

                    </div>

            </main>
        </div>
        <script>
            function toggleSidebar() {
                document.getElementById('sidebar')
                    .classList.toggle('-translate-x-full');
                const overlay = document.getElementById('sidebarOverlay');
                if(overlay) overlay.classList.toggle('hidden');
            }
        </script>
</body>

</html>