<?php
session_start();
include "admin/config/db.php";

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}
/* ======================
LOGGED USER
====================== */

$account_id = $_SESSION['account_id'];

$user = $conn->query("
SELECT name, profile_photo
FROM accounts
WHERE account_id=$account_id
")->fetch_assoc();


/* ======================
PROFILE ID
====================== */

if (isset($_GET['account_id'])) {

    $profile_id = (int) $_GET['account_id'];
} else {

    $profile_id = $account_id; // show logged user profile

}


/* ======================
PROFILE DATA
====================== */

$profile = $conn->query("
SELECT *
FROM accounts
WHERE account_id=$profile_id
")->fetch_assoc();


/* ======================
USER POSTS
====================== */

$posts = $conn->query("
SELECT *
FROM community_forum
WHERE account_id=$profile_id
AND status='Active'
ORDER BY created_at DESC
");


/* ======================
USER FUNDRAISERS
====================== */

$fundraisers = $conn->query("
SELECT *
FROM fundraiser
WHERE account_id=$profile_id
ORDER BY created_at DESC
");


/* ======================
USER LOCATIONS
====================== */

$locations = $conn->query("
SELECT *
FROM geo_locations
WHERE account_id=$profile_id
ORDER BY created_at DESC
");


/* ======================
USER NEEDS
====================== */

$needs = $conn->query("
SELECT *
FROM needs
WHERE account_id=$profile_id
ORDER BY created_at DESC
");

/* ======================
REPORT USER
====================== */

if (isset($_POST['report_user'])) {

    $reported_by = $_SESSION['account_id'];
    $target_id = (int) $_POST['target_id'];
    $reason = $conn->real_escape_string($_POST['reason']);

    $check = $conn->query("
    SELECT report_id
    FROM reports
    WHERE reported_by=$reported_by
    AND target_type='Account'
    AND target_id=$target_id
    ");

    if ($check->num_rows == 0) {

        $conn->query("
        INSERT INTO reports (reported_by,target_type,target_id,reason)
        VALUES ($reported_by,'Account',$target_id,'$reason')
        ");
    }

    echo "reported";
    exit();
}
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Kind Aid - Social Impact Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style/main-style.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        <aside id="sidebar" class="w-64 bg-[var(--secondary-dark)] fixed lg:relative
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
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="index.php">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-sm font-medium">Home</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="needs.php">
                    <span class="material-symbols-outlined">handshake</span>
                    <span class="text-sm font-medium">Needs</span>
                </a>

                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="fundraiser.php">
                    <span class="material-symbols-outlined">favorite</span>
                    <span class="text-sm font-medium">Donate</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="location.php">
                    <span class="material-symbols-outlined">map</span>
                    <span class="text-sm font-medium">Map</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="community.php">
                    <span class="material-symbols-outlined"
                        style="font-variation-settings: 'FILL' 1">dynamic_feed</span>
                    <span class="text-sm font-semibold">Posts</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="view_profile.php">
                    <span class="material-symbols-outlined">account_circle</span>
                    <span class="text-sm font-medium">Profile</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    href="chatbot.php">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">smart_toy</span>
                    <span class="text-sm font-medium">AI Assistant</span>
                </a>
            </nav>
            <div class="p-4 border-t border-white/10">
                <a class="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white transition-colors"
                    href="logout.php">
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
            <header
                class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 z-10">
                <button onclick="toggleSidebar()" class="lg:hidden mr-4 text-slate-600">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="flex-1 max-w-2xl">
                </div>

                <?php if ($account_id != $profile_id) { ?>

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

                                <img class="size-9 rounded-full border-2 border-primary/20 object-cover" src="<?php echo !empty($user['profile_photo'])
                                    ? 'uploads/' . $user['profile_photo']
                                    : 'assets/default-user.png'; ?>" alt="Profile">

                            </div>

                        <?php } else { ?>

                            <a href="login.php"
                                class="bg-[var(--primary)] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[var(--primary-hover)] transition">
                                Login
                            </a>

                        <?php } ?>

                    </div>

                <?php } ?>

            </header>
            <!-- Scrollable Feed Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <!-- PROFILE -->
                    <div class="bg-white p-8 rounded-xl shadow">

                        <div class="flex flex-col lg:flex-row gap-8 items-start">

                            <!-- PROFILE PHOTO -->

                            <div class="flex flex-col items-center">

                                <img src="<?php echo !empty($profile['profile_photo']) ? 'uploads/' . $profile['profile_photo'] : 'assets/default-user.png'; ?>"
                                    class="w-32 h-32 rounded-full object-cover border-4 border-primary/20 shadow">

                                <!-- ACCOUNT TYPE -->
                                <span class="mt-3 px-3 py-1 text-xs font-semibold rounded-full
            <?php echo $profile['account_type'] == 'NGO'
                ? 'bg-green-100 text-green-700'
                : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo $profile['account_type']; ?>
                                </span>

                            </div>


                            <!-- PROFILE INFO -->

                            <div class="flex-1">

                                <!-- NAME + VERIFIED -->

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">

                                        <h2 class="text-2xl font-bold text-slate-900">
                                            <?php echo htmlspecialchars($profile['name']); ?>
                                        </h2>

                                        <?php if ($profile['verified_status'] == "Verified") { ?>
                                            <span class="material-symbols-outlined text-blue-500 text-lg">
                                                verified
                                            </span>
                                        <?php } ?>

                                    </div>
                                    <div>

                                        <?php if ($account_id == $profile_id) { ?>

                                            <!-- SETTINGS BUTTON (OWN PROFILE) -->
                                            <a href="settings.php"
                                                class="flex items-center gap-1 bg-[var(--primary)] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition">

                                                <span class="material-symbols-outlined text-sm">settings</span>
                                                Edit Profile

                                            </a>


                                        <?php } else { ?>

                                            <!-- REPORT USER BUTTON -->
                                            <button onclick="reportUser(<?php echo $profile_id; ?>)"
                                                class="flex items-center gap-1 bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-600 transition">

                                                <span class="material-symbols-outlined text-sm">flag</span>
                                                Report User

                                            </button>

                                        <?php } ?>
                                    </div>

                                </div>




                                <!-- VERIFICATION STATUS -->

                                <p class="text-xs mt-1
            <?php
            if ($profile['verified_status'] == "Verified")
                echo "text-green-600";
            elseif ($profile['verified_status'] == "Rejected")
                echo "text-red-500";
            else
                echo "text-yellow-600";
            ?>">
                                    <?php echo $profile['verified_status']; ?> Account
                                </p>


                                <!-- BIO -->

                                <?php if (!empty($profile['bio'])) { ?>
                                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($profile['bio'])); ?>
                                    </p>
                                <?php } ?>


                                <!-- CONTACT INFO -->

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4 text-sm">

                                    <?php if (!empty($profile['email'])) { ?>
                                        <p class="flex items-center gap-2 text-gray-600">
                                            <span class="material-symbols-outlined text-base">mail</span>
                                            <?php echo htmlspecialchars($profile['email']); ?>
                                        </p>
                                    <?php } ?>


                                    <?php if (!empty($profile['contact_no'])) { ?>
                                        <p class="flex items-center gap-2 text-gray-600">
                                            <span class="material-symbols-outlined text-base">call</span>
                                            <?php echo htmlspecialchars($profile['contact_no']); ?>
                                        </p>
                                    <?php } ?>


                                    <?php if (!empty($profile['address'])) { ?>
                                        <p class="flex items-center gap-2 text-gray-600">
                                            <span class="material-symbols-outlined text-base">location_on</span>
                                            <?php echo htmlspecialchars($profile['address']); ?>
                                        </p>
                                    <?php } ?>


                                    <p class="flex items-center gap-2 text-gray-500">
                                        <span class="material-symbols-outlined text-base">calendar_month</span>
                                        Joined <?php echo date("F Y", strtotime($profile['created_at'])); ?>
                                    </p>

                                </div>


                                <!-- DOCUMENTS (Only if exist) -->

                                <?php if ($profile['document_1'] || $profile['document_2']) { ?>

                                    <div class="flex flex-wrap gap-3 mt-5">

                                        <?php if ($profile['document_1']) { ?>
                                            <a href="uploads/doc/<?php echo $profile['document_1']; ?>" target="_blank"
                                                class="text-xs bg-blue-600 text-white px-3 py-1 rounded-lg">
                                                View Document 1
                                            </a>
                                        <?php } ?>

                                        <?php if ($profile['document_2']) { ?>
                                            <a href="uploads/doc/<?php echo $profile['document_2']; ?>" target="_blank"
                                                class="text-xs bg-blue-600 text-white px-3 py-1 rounded-lg">
                                                View Document 2
                                            </a>
                                        <?php } ?>

                                    </div>

                                <?php } ?>

                            </div>

                        </div>

                    </div>



                    <!-- Style Tabs -->
                    <!-- PROFILE CONTENT TABS -->

                    <div class="mt-8 border-b border-slate-200">

                        <div class="flex justify-center gap-auto text-md font-semibold text-secondary">

                            <button onclick="showTab('posts')"
                                class="tab-btn justify-center flex items-center gap-1 pb-3 w-full border-b-2 border-transparent hover:text-primary transition">

                                <span class="material-symbols-outlined text-base">grid_view</span>
                                Posts

                            </button>

                            <button onclick="showTab('fundraisers')"
                                class="tab-btn justify-center flex items-center gap-1 pb-3 w-full border-b-2 border-transparent hover:text-primary transition">

                                <span class="material-symbols-outlined text-base">volunteer_activism</span>
                                Fundraisers

                            </button>

                            <button onclick="showTab('locations')"
                                class="tab-btn justify-center flex items-center gap-1 pb-3 w-full border-b-2 border-transparent hover:text-primary transition">

                                <span class="material-symbols-outlined text-base">location_on</span>
                                Locations

                            </button>

                            <button onclick="showTab('needs')"
                                class="tab-btn justify-center flex items-center gap-1 pb-3 w-full  border-b-2 border-transparent hover:text-primary transition">

                                <span class="material-symbols-outlined text-base">handshake</span>
                                Needs

                            </button>

                        </div>

                    </div>


                    <!-- POSTS GRID -->
                    <div id="posts" class="tab-content grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">

                        <?php while ($p = $posts->fetch_assoc()) {

                            /* COMMENT COUNT */
                            $commentCount = $conn->query(
                                "
SELECT COUNT(*) as total 
FROM comments 
WHERE post_id=" . $p['post_id']
                            )->fetch_assoc()['total'];

                            ?>

                            <a href="community.php?user_id=<?php echo $p['account_id']; ?>"
                                class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col h-full">

                                <!-- IMAGE AREA -->
                                <div class="w-full h-48 bg-gray-100 overflow-hidden">

                                    <?php if ($p['image_1'] || $p['image_2']) { ?>

                                        <div
                                            class="grid <?php echo ($p['image_1'] && $p['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-full">

                                            <?php if ($p['image_1']) { ?>
                                                <img src="uploads/<?php echo $p['image_1']; ?>" class="w-full h-full object-cover">
                                            <?php } ?>

                                            <?php if ($p['image_2']) { ?>
                                                <img src="uploads/<?php echo $p['image_2']; ?>" class="w-full h-full object-cover">
                                            <?php } ?>

                                        </div>

                                    <?php } else { ?>

                                        <div class="flex items-center justify-center h-full">
                                            <span class="material-symbols-outlined text-gray-400 text-4xl">
                                                image
                                            </span>
                                        </div>

                                    <?php } ?>

                                </div>


                                <!-- POST CONTENT -->
                                <div class="p-4 flex flex-col flex-grow">

                                    <!-- DESCRIPTION -->
                                    <p class="text-sm text-gray-700 line-clamp-3 flex-grow">

                                        <?php
                                        echo !empty($p['description'])
                                            ? htmlspecialchars($p['description'])
                                            : "No description provided.";
                                        ?>

                                    </p>


                                    <!-- META -->
                                    <div class="flex justify-between items-center mt-4 text-xs text-gray-500">

                                        <!-- DATE -->
                                        <span>
                                            <?php echo date("d M Y", strtotime($p['created_at'])); ?>
                                        </span>

                                        <!-- LIKE + COMMENT -->
                                        <div class="flex items-center gap-4">

                                            <!-- LIKES -->
                                            <div class="flex items-center gap-1">

                                                <span class="material-symbols-outlined text-sm text-red-500">
                                                    favorite
                                                </span>

                                                <?php echo $p['likes']; ?>

                                            </div>

                                            <!-- COMMENTS -->
                                            <div class="flex items-center gap-1">

                                                <span class="material-symbols-outlined text-sm text-gray-500">
                                                    chat_bubble
                                                </span>

                                                <?php echo $commentCount; ?>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </a>

                        <?php } ?>

                    </div>


                    <!-- FUNDRAISERS GRID -->
                    <div id="fundraisers"
                        class="tab-content hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">

                        <?php while ($f = $fundraisers->fetch_assoc()) {

                            $progress = 0;
                            if ($f['goal_amount'] > 0) {
                                $progress = ($f['collected_amount'] / $f['goal_amount']) * 100;
                            }

                            ?>

                            <a href="fundraiser.php?user_id=<?php echo $f['account_id']; ?>">
                                <div
                                    class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col h-full">


                                    <!-- IMAGE AREA -->
                                    <div class="w-full h-48 bg-gray-100 overflow-hidden">

                                        <?php if ($f['image_1'] || $f['image_2']) { ?>

                                            <div
                                                class="grid <?php echo ($f['image_1'] && $f['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-full">

                                                <?php if ($f['image_1']) { ?>
                                                    <img src="uploads/<?php echo $f['image_1']; ?>"
                                                        class="w-full h-full object-cover">
                                                <?php } ?>

                                                <?php if ($f['image_2']) { ?>
                                                    <img src="uploads/<?php echo $f['image_2']; ?>"
                                                        class="w-full h-full object-cover">
                                                <?php } ?>

                                            </div>

                                        <?php } else { ?>

                                            <div class="flex items-center justify-center h-full">
                                                <span class="material-symbols-outlined text-gray-400 text-4xl">
                                                    volunteer_activism
                                                </span>
                                            </div>

                                        <?php } ?>

                                    </div>


                                    <!-- CONTENT -->
                                    <div class="p-4 flex flex-col flex-grow">

                                        <!-- TITLE -->
                                        <div class="flex items-center gap-2">

                                            <h3 class="font-bold text-sm line-clamp-1">
                                                <?php echo htmlspecialchars($f['title']); ?>
                                            </h3>

                                            <?php if ($f['verified_status'] == "Verified") { ?>
                                                <span class="material-symbols-outlined text-primary text-sm"
                                                    style="font-variation-settings:'FILL'1">
                                                    verified
                                                </span>
                                            <?php } ?>

                                        </div>


                                        <!-- DESCRIPTION -->
                                        <p class="text-xs text-gray-600 mt-1 line-clamp-2 flex-grow">
                                            <?php echo htmlspecialchars($f['description']); ?>
                                        </p>


                                        <!-- PROGRESS BAR -->
                                        <div class="mt-3">

                                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                                <span>Raised</span>
                                                <span><?php echo round($progress); ?>%</span>
                                            </div>

                                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">

                                                <div class="bg-[var(--accent)] h-full"
                                                    style="width:<?php echo $progress; ?>%">
                                                </div>

                                            </div>

                                        </div>


                                        <!-- AMOUNT -->
                                        <div class="flex justify-between items-center text-xs text-gray-500 mt-3">

                                            <span>
                                                ₹<?php echo number_format($f['collected_amount']); ?>
                                            </span>

                                            <span>
                                                Goal ₹<?php echo number_format($f['goal_amount']); ?>
                                            </span>

                                        </div>

                                        <!-- DOCUMENT LINKS -->
                                        <?php if ($f['document_1'] || $f['document_2']) { ?>

                                            <div class="flex gap-2 mt-3 flex-wrap">

                                                <?php if ($f['document_1']) { ?>
                                                    <a href="uploads/<?php echo $f['document_1']; ?>" target="_blank"
                                                        class="flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200">

                                                        <span class="material-symbols-outlined text-sm">description</span>
                                                        Doc 1
                                                    </a>
                                                <?php } ?>

                                                <?php if ($f['document_2']) { ?>
                                                    <a href="uploads/<?php echo $f['document_2']; ?>" target="_blank"
                                                        class="flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200">

                                                        <span class="material-symbols-outlined text-sm">description</span>
                                                        Doc 2
                                                    </a>
                                                <?php } ?>

                                            </div>

                                        <?php } ?>


                                        <!-- DATE -->
                                        <div class="text-xs text-gray-400 mt-2">
                                            <?php echo date("d M Y", strtotime($f['created_at'])); ?>
                                        </div>

                                    </div>
                                </div>


                            </a>

                        <?php } ?>

                    </div>



                    <!-- LOCATIONS GRID -->
                    <div id="locations"
                        class="tab-content hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">

                        <?php while ($l = $locations->fetch_assoc()) { ?>

                            <a href="location.php?user_id=<?php echo $l['account_id']; ?>"
                                class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col h-full">

                                <!-- IMAGE AREA -->
                                <div class="w-full h-48 bg-gray-100 overflow-hidden">

                                    <?php if ($l['image_1'] || $l['image_2']) { ?>

                                        <div
                                            class="grid <?php echo ($l['image_1'] && $l['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-full">

                                            <?php if ($l['image_1']) { ?>
                                                <img src="uploads/<?php echo $l['image_1']; ?>" class="w-full h-full object-cover">
                                            <?php } ?>

                                            <?php if ($l['image_2']) { ?>
                                                <img src="uploads/<?php echo $l['image_2']; ?>" class="w-full h-full object-cover">
                                            <?php } ?>

                                        </div>

                                    <?php } else { ?>

                                        <div class="flex items-center justify-center h-full">
                                            <span class="material-symbols-outlined text-gray-400 text-4xl">
                                                location_on
                                            </span>
                                        </div>

                                    <?php } ?>

                                </div>


                                <!-- CONTENT -->
                                <div class="p-4 flex flex-col flex-grow">

                                    <!-- TYPE + STATUS -->
                                    <div class="flex justify-between items-center mb-2">

                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded">
                                            <?php echo $l['geo_type']; ?>
                                        </span>

                                        <?php
                                        $statusColor = [
                                            "Pending" => "bg-yellow-100 text-yellow-700",
                                            "Ongoing" => "bg-blue-100 text-blue-700",
                                            "Completed" => "bg-green-100 text-green-700"
                                        ];
                                        ?>

                                        <span
                                            class="text-xs px-2 py-1 rounded <?php echo $statusColor[$l['goal_status']]; ?>">
                                            <?php echo $l['goal_status']; ?>
                                        </span>

                                    </div>


                                    <!-- DESCRIPTION -->
                                    <p class="text-sm text-gray-600 line-clamp-3 flex-grow">
                                        <?php echo htmlspecialchars($l['description']); ?>
                                    </p>


                                    <!-- DATE -->
                                    <div class="text-xs text-gray-400 mt-3">
                                        <?php echo date("d M Y", strtotime($l['created_at'])); ?>
                                    </div>


                                    <!-- MAP BUTTON -->
                                    <button
                                        onclick="event.preventDefault(); event.stopPropagation(); openMapModal(<?php echo $l['latitude']; ?>, <?php echo $l['longitude']; ?>)"
                                        class="mt-3 bg-[var(--primary)] text-white text-xs px-3 py-2 rounded-lg flex items-center justify-center gap-1">

                                        <span class="material-symbols-outlined text-sm">map</span>
                                        View on Map

                                    </button>

                                </div>

                            </a>

                        <?php } ?>

                    </div>


                    <!-- NEEDS GRID -->
                    <div id="needs"
                        class="tab-content hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">

                        <?php while ($n = $needs->fetch_assoc()) { ?>
                            <a href="needs.php?user_id=<?php echo $n['account_id']; ?>">
                                <div
                                    class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col h-full">

                                    <!-- IMAGE AREA -->
                                    <div class="w-full h-48 bg-gray-100 overflow-hidden">

                                        <?php if ($n['image_1'] || $n['image_2']) { ?>

                                            <div
                                                class="grid <?php echo ($n['image_1'] && $n['image_2']) ? 'grid-cols-2' : 'grid-cols-1'; ?> h-full">

                                                <?php if ($n['image_1']) { ?>
                                                    <img src="uploads/<?php echo $n['image_1']; ?>"
                                                        class="w-full h-full object-cover">
                                                <?php } ?>

                                                <?php if ($n['image_2']) { ?>
                                                    <img src="uploads/<?php echo $n['image_2']; ?>"
                                                        class="w-full h-full object-cover">
                                                <?php } ?>

                                            </div>

                                        <?php } else { ?>

                                            <div class="flex items-center justify-center h-full">
                                                <span class="material-symbols-outlined text-gray-400 text-4xl">
                                                    handshake
                                                </span>
                                            </div>

                                        <?php } ?>

                                    </div>


                                    <!-- CONTENT -->
                                    <div class="p-4 flex flex-col flex-grow">

                                        <!-- TITLE -->
                                        <h3 class="font-bold text-sm line-clamp-1">
                                            <?php echo htmlspecialchars($n['goal_title']); ?>
                                        </h3>


                                        <!-- DESCRIPTION -->
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-3 flex-grow">
                                            <?php echo htmlspecialchars($n['description']); ?>
                                        </p>


                                        <!-- STATUS -->
                                        <?php
                                        $statusColor = [
                                            "Pending" => "bg-yellow-100 text-yellow-700",
                                            "Ongoing" => "bg-blue-100 text-blue-700",
                                            "Fulfilled" => "bg-green-100 text-green-700"
                                        ];
                                        ?>

                                        <div class="mt-3">
                                            <span
                                                class="text-xs px-2 py-1 rounded <?php echo $statusColor[$n['goal_status']]; ?>">
                                                <?php echo $n['goal_status']; ?>
                                            </span>
                                        </div>


                                        <!-- PROGRESS BAR -->
                                        <div class="mt-3">

                                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                                <span>Progress</span>
                                                <span><?php echo $n['progress_percent']; ?>%</span>
                                            </div>

                                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">

                                                <div class="bg-[var(--accent)] h-full"
                                                    style="width:<?php echo $n['progress_percent']; ?>%">
                                                </div>

                                            </div>

                                        </div>


                                        <!-- DATE -->
                                        <div class="text-xs text-gray-400 mt-3">
                                            <?php echo date("d M Y", strtotime($n['created_at'])); ?>
                                        </div>

                                    </div>

                                </div>

                            <?php } ?>
                        </a>
                    </div>
                </div>
            </main>

            <?php if ($needs->num_rows == 0) { ?>
                <div class="bg-white p-6 rounded-xl text-center text-slate-500 rounded-xl shadow-sm">
                    No needs found.
                </div>
            <?php } ?>

        </div>
        </main>
    </div>

    <!-- MAP MODAL -->
    <div id="mapModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-[9999]">

        <div class="bg-white rounded-xl shadow-xl w-[95%] max-w-3xl p-4 relative">

            <!-- HEADER -->
            <div class="flex justify-between items-center mb-3 border-b pb-2">

                <h3 class="font-semibold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-500">location_on</span>
                    Location Preview
                </h3>

                <button onclick="closeMapModal()" class="hover:bg-gray-100 rounded-full p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>

            </div>

            <!-- MAP -->
            <div id="locationMap" class="w-full h-[420px] rounded-lg border"></div>

        </div>

    </div>

    <script>
        function reportUser(userId) {

            let reason = prompt("Report reason:");

            if (!reason) return;

            fetch("view_profile.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "report_user=1&target_id=" + userId + "&reason=" + encodeURIComponent(reason)
            })
                .then(res => res.text())
                .then(data => {
                    alert("User reported. Admin will review.");
                });

        }


        let modalMap;
        let marker;

        function openMapModal(lat, lng) {

            const modal = document.getElementById("mapModal");
            modal.classList.remove("hidden");

            setTimeout(() => {

                if (!modalMap) {

                    modalMap = L.map('locationMap').setView([lat, lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19
                    }).addTo(modalMap);

                }

                modalMap.invalidateSize();

                if (marker) {
                    modalMap.removeLayer(marker);
                }

                const redIcon = L.icon({
                    iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
                    shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34]
                });

                marker = L.marker([lat, lng], {
                    icon: redIcon
                }).addTo(modalMap);

                modalMap.flyTo([lat, lng], 15);

            }, 200);

        }

        function closeMapModal() {

            document.getElementById("mapModal").classList.add("hidden");

        }

        document.getElementById("mapModal").addEventListener("click", function (e) {
            if (e.target.id === "mapModal") {
                closeMapModal();
            }
        });


        function showTab(tab) {

            document.querySelectorAll(".tab-content").forEach(el => {
                el.classList.add("hidden")
            })

            document.getElementById(tab).classList.remove("hidden")


            /* active tab style */

            document.querySelectorAll(".tab-btn").forEach(btn => {
                btn.classList.remove("border-primary", "text-primary")
            })

            event.currentTarget.classList.add("border-primary", "text-primary")

        }
        document.querySelectorAll('.file-box input').forEach(input => {
            input.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (this.files.length > 0) {
                    label.innerHTML = "Uploaded ✓";
                    label.style.backgroundColor = "var(--accent)";
                }
            });
        });

        function toggleSidebar() {
            document.getElementById('sidebar')
                .classList.toggle('-translate-x-full');
            const overlay = document.getElementById('sidebarOverlay');
            if(overlay) overlay.classList.toggle('hidden');
        }
    </script>
</body>

</html>