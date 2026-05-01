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
    WHERE account_id = $account_id
")->fetch_assoc();

/* ===================================
   FETCH NEED FOR EDIT
=================================== */
$editNeed = null;

if (isset($_GET['edit_need'])) {

    $need_id = (int) $_GET['edit_need'];

    $check = $conn->query("
        SELECT * FROM needs 
        WHERE need_id=$need_id AND account_id=$account_id
    ");

    if ($check->num_rows > 0) {
        $editNeed = $check->fetch_assoc();
    }
}

/* ===================================
   CREATE NEED
=================================== */
if (isset($_POST['create_need'])) {

    $title = $conn->real_escape_string($_POST['goal_title']);
    $description = $conn->real_escape_string($_POST['description']);
    $progress = (int) $_POST['progress_percent'];
    $status = $_POST['goal_status'];

    $image1 = "";
    $image2 = "";

    if (!empty($_FILES['image_1']['name'])) {
        $image1 = time() . "_1_" . $_FILES['image_1']['name'];
        move_uploaded_file($_FILES['image_1']['tmp_name'], "uploads/" . $image1);
    }

    if (!empty($_FILES['image_2']['name'])) {
        $image2 = time() . "_2_" . $_FILES['image_2']['name'];
        move_uploaded_file($_FILES['image_2']['tmp_name'], "uploads/" . $image2);
    }

    $conn->query("
        INSERT INTO needs (account_id, goal_title, progress_percent, description, image_1, image_2, goal_status)
        VALUES ('$account_id','$title','$progress','$description','$image1','$image2','$status')
    ");

    echo "Post is uploaded";

    header("Location: needs.php");
    exit();
}

/* ===================================
   UPDATE NEED
=================================== */
if (isset($_POST['update_need'])) {

    $need_id = (int) $_POST['need_id'];

    $title = $conn->real_escape_string($_POST['goal_title']);
    $description = $conn->real_escape_string($_POST['description']);
    $progress = (int) $_POST['progress_percent'];
    $status = $_POST['goal_status'];

    // Get old images
    $old = $conn->query("
        SELECT image_1, image_2 
        FROM needs 
        WHERE need_id=$need_id AND account_id=$account_id
    ")->fetch_assoc();

    $image1 = $old['image_1'];
    $image2 = $old['image_2'];

    // Replace image 1 if new uploaded
    if (!empty($_FILES['image_1']['name'])) {
        $image1 = time() . "_1_" . $_FILES['image_1']['name'];
        move_uploaded_file($_FILES['image_1']['tmp_name'], "uploads/" . $image1);
    }

    // Replace image 2 if new uploaded
    if (!empty($_FILES['image_2']['name'])) {
        $image2 = time() . "_2_" . $_FILES['image_2']['name'];
        move_uploaded_file($_FILES['image_2']['tmp_name'], "uploads/" . $image2);
    }

    $conn->query("
        UPDATE needs SET
            goal_title='$title',
            description='$description',
            progress_percent='$progress',
            goal_status='$status',
            image_1='$image1',
            image_2='$image2'
        WHERE need_id=$need_id
        AND account_id=$account_id
    ");

    header("Location: needs.php");
    exit();
}

/* ===================================
   REMOVE IMAGE (ONLY OWNER)
=================================== */
if (isset($_GET['remove_image']) && isset($_GET['need_id'])) {

    $need_id = (int) $_GET['need_id'];
    $imageType = $_GET['remove_image']; // image_1 OR image_2

    if (in_array($imageType, ['image_1', 'image_2'])) {

        $check = $conn->query("
            SELECT $imageType 
            FROM needs 
            WHERE need_id=$need_id 
            AND account_id=$account_id
        ");

        if ($check->num_rows > 0) {

            $row = $check->fetch_assoc();
            $imageFile = $row[$imageType];

            // Delete file physically
            if (!empty($imageFile) && file_exists("uploads/" . $imageFile)) {
                unlink("uploads/" . $imageFile);
            }

            // Remove from database
            $conn->query("
                UPDATE needs 
                SET $imageType='' 
                WHERE need_id=$need_id 
                AND account_id=$account_id
            ");
        }
    }

    header("Location: needs.php?edit_need=" . $need_id);
    exit();
}

/* ===================================
   DELETE NEED (ONLY OWNER)
=================================== */
if (isset($_GET['delete_need'])) {

    $need_id = (int) $_GET['delete_need'];

    $check = $conn->query("
        SELECT account_id FROM needs
        WHERE need_id=$need_id
    ")->fetch_assoc();

    if ($check && $check['account_id'] == $account_id) {
        $conn->query("DELETE FROM needs WHERE need_id=$need_id");
    }
    header("Location: needs.php");
    exit();
}

/* =========================
FILTER NEEDS BY USER
========================= */

$userFilter = "";

if (isset($_GET['user_id'])) {

    $uid = (int) $_GET['user_id'];

    $userFilter = "AND needs.account_id=$uid";

}

/* ===================================
   SEARCH
=================================== */
$searchQuery = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {

    $search = $conn->real_escape_string($_GET['search']);

    $searchQuery = "
        AND (
            needs.goal_title LIKE '%$search%' 
            OR needs.description LIKE '%$search%'
            OR accounts.name LIKE '%$search%'
        )
    ";
}

/* ===================================
   FETCH NEEDS
=================================== */
$needs = $conn->query("
    SELECT needs.*, accounts.name, accounts.profile_photo
    FROM needs
    JOIN accounts ON needs.account_id = accounts.account_id
    WHERE 1=1
    $userFilter
    $searchQuery
    ORDER BY needs.created_at DESC
");

/* ===================================
   REPORT NEED
=================================== */

if (isset($_POST['report_need'])) {

    $reported_by = $_SESSION['account_id'];
    $target_id = (int) $_POST['need_id'];
    $reason = $conn->real_escape_string($_POST['reason']);

    // prevent duplicate reports
    $check = $conn->query("
        SELECT report_id 
        FROM reports
        WHERE reported_by=$reported_by
        AND target_type='need'
        AND target_id=$target_id
    ");

    if ($check->num_rows == 0) {

        $conn->query("
            INSERT INTO reports
            (reported_by,target_type,target_id,reason)
            VALUES
            ($reported_by,'need',$target_id,'$reason')
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
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden" onclick="toggleSidebar()">
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 dark:bg-background-dark overflow-hidden">
            <!-- Top Header -->
            <header
                class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 z-10">
                <button onclick="toggleSidebar()" class="lg:hidden mr-4 text-slate-600">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="flex-1 max-w-2xl">

                    <form method="GET" class="relative group">

                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            search
                        </span>

                        <input type="text" name="search"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                            placeholder="Search Needs or users..."
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

                            <img class="size-9 rounded-full border-2 border-primary/20 object-cover" src="<?php echo !empty($user['profile_photo'])
                                ? 'uploads/' . $user['profile_photo']
                                : 'assets/default-user.png'; ?>" alt="Profile">

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
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Kind Aid - Needs
                            Board</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-lg">Discover and support active needs from
                            users and NGOs.</p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border mb-6">

                        <form method="POST" enctype="multipart/form-data">

                            <div class="flex gap-4 mb-3">
                                <input type="text" name="goal_title" required
                                    value="<?php echo $editNeed ? htmlspecialchars($editNeed['goal_title']) : ''; ?>"
                                    placeholder="Need Title" class="w-full p-3 border rounded-lg mb-3">

                                <input type="number" name="progress_percent" min="0" max="100"
                                    value="<?php echo $editNeed ? $editNeed['progress_percent'] : ''; ?>"
                                    placeholder="Progress %" class="w-full p-3 border rounded-lg mb-3">

                                <select name="goal_status" class="w-full p-3 border rounded-lg mb-3">
                                    <?php
                                    $statuses = ["Pending", "Ongoing", "Fulfilled"];
                                    foreach ($statuses as $status) {
                                        $selected = ($editNeed && $editNeed['goal_status'] == $status) ? "selected" : "";
                                        echo "<option value='$status' $selected>$status</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <textarea name="description" required placeholder="Describe your need..."
                                class="w-full p-3 border rounded-lg mb-3"><?php
                                echo $editNeed ? htmlspecialchars($editNeed['description']) : '';
                                ?></textarea>

                            <div class="flex gap-4 mb-3">

                                <div class="file-box">
                                    <input type="file" id="file1" name="image_1" class="hidden">
                                    <label for="file1" class="cursor-pointer bg-slate-200 px-4 py-2 rounded-lg text-sm">
                                        Upload Image 1
                                    </label>
                                </div>

                                <div class="file-box">
                                    <input type="file" id="file2" name="image_2" class="hidden">
                                    <label for="file2" class="cursor-pointer bg-slate-200 px-4 py-2 rounded-lg text-sm">
                                        Upload Image 2
                                    </label>
                                </div>

                                <?php if ($editNeed) { ?>
                                    <input type="hidden" name="need_id" value="<?php echo $editNeed['need_id']; ?>">
                                    <button type="submit" name="update_need"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                                        Update Need
                                    </button>
                                <?php } else { ?>
                                    <button type="submit" name="create_need"
                                        class="bg-[var(--primary)] text-white px-6 py-2 rounded-lg">
                                        Post Need
                                    </button>
                                <?php } ?>

                            </div>

                            <?php if ($editNeed) { ?>

                                <div class="flex gap-6 mb-4">

                                    <?php if (!empty($editNeed['image_1'])) { ?>
                                        <div class="relative">
                                            <img src="uploads/<?php echo $editNeed['image_1']; ?>"
                                                class="w-28 rounded-lg shadow">

                                            <a href="?remove_image=image_1&need_id=<?php echo $editNeed['need_id']; ?>"
                                                onclick="return confirm('Remove Image 1?');"
                                                class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full text-xs">
                                                ✕
                                            </a>
                                        </div>
                                    <?php } ?>

                                    <?php if (!empty($editNeed['image_2'])) { ?>
                                        <div class="relative">
                                            <img src="uploads/<?php echo $editNeed['image_2']; ?>"
                                                class="w-28 rounded-lg shadow">

                                            <a href="?remove_image=image_2&need_id=<?php echo $editNeed['need_id']; ?>"
                                                onclick="return confirm('Remove Image 2?');"
                                                class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full text-xs">
                                                ✕
                                            </a>
                                        </div>
                                    <?php } ?>

                                </div>

                            <?php } ?>

                        </form>
                    </div>

                    <?php while ($need = $needs->fetch_assoc()) { ?>

                        <div class="bg-white rounded-xl border shadow-sm overflow-hidden mb-6">

                            <div class="p-6">

                                <!-- USER INFO -->
                                <div class="flex justify-between mb-4">

                                    <div class="flex items-center gap-3">

                                        <?php
                                        $profileImage = (!empty($need['profile_photo']) && file_exists("uploads/" . $need['profile_photo']))
                                            ? "uploads/" . $need['profile_photo']
                                            : "assets/default-user.png";
                                        ?>

                                        <img src="<?php echo $profileImage; ?>" class="size-10 rounded-full object-cover">

                                        <div>
                                            <h3 class="font-bold"><?php echo $need['name']; ?></h3>
                                            <p class="text-xs text-slate-500">
                                                <?php echo date("d M Y", strtotime($need['created_at'])); ?>
                                            </p>
                                        </div>

                                    </div>

                                    <div class="flex gap-3">

                                        <?php if ($need['account_id'] == $account_id) { ?>

                                            <!-- EDIT -->
                                            <a href="?edit_need=<?php echo $need['need_id']; ?>"
                                                class="text-blue-600 hover:text-blue-800">
                                                <span class="material-symbols-outlined">edit</span>
                                            </a>

                                            <!-- DELETE -->
                                            <a href="?delete_need=<?php echo $need['need_id']; ?>"
                                                onclick="return confirm('Delete this need?');"
                                                class="text-red-500 hover:text-red-700">
                                                <span class="material-symbols-outlined">delete</span>
                                            </a>

                                        <?php } else { ?>

                                            <!-- REPORT -->
                                            <button onclick="reportNeed(<?php echo $need['need_id']; ?>)"
                                                class="text-slate-500 hover:text-red-600">

                                                <span class="material-symbols-outlined">flag</span>

                                            </button>

                                        <?php } ?>

                                    </div>

                                </div>

                                <!-- TITLE -->
                                <h2 class="text-xl font-bold mb-2">
                                    <?php echo htmlspecialchars($need['goal_title']); ?>
                                </h2>

                                <!-- DESCRIPTION -->
                                <p class="mb-4 text-slate-600">
                                    <?php echo nl2br($need['description']); ?>
                                </p>

                                <!-- PROGRESS BAR -->
                                <div class="mb-4">

                                    <div class="flex justify-between mb-1 text-sm">
                                        <span>Progress</span>
                                        <span><?php echo $need['progress_percent']; ?>%</span>
                                    </div>

                                    <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                                        <div class="h-full bg-[var(--accent)]"
                                            style="width: <?php echo $need['progress_percent']; ?>%">
                                        </div>
                                    </div>

                                </div>

                                <!-- STATUS BADGE -->
                                <div>
                                    <?php
                                    $statusColor = [
                                        "Pending" => "bg-yellow-100 text-yellow-700",
                                        "Ongoing" => "bg-blue-100 text-blue-700",
                                        "Fulfilled" => "bg-green-100 text-green-700"
                                    ];
                                    ?>

                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusColor[$need['goal_status']]; ?>">
                                        <?php echo $need['goal_status']; ?>
                                    </span>
                                </div>

                                <!-- IMAGES -->
                                <?php if ($need['image_1'] || $need['image_2']) { ?>
                                    <div class="grid grid-cols-2 gap-2 mt-4">
                                        <?php if ($need['image_1']) { ?>
                                            <img src="uploads/<?php echo $need['image_1']; ?>" class="rounded-lg">
                                        <?php } ?>
                                        <?php if ($need['image_2']) { ?>
                                            <img src="uploads/<?php echo $need['image_2']; ?>" class="rounded-lg">
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <!-- CONTACT BUTTON (Only if not my post) -->
                                <?php if ($need['account_id'] != $account_id) { ?>

                                    <div class="mt-5 flex justify-end">

                                        <a href="view_profile.php?account_id=<?php echo $need['account_id']; ?>"
                                            class="bg-[var(--primary)] hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-1">

                                            <span class="material-symbols-outlined text-base">person</span>
                                            Contact User

                                        </a>

                                    </div>

                                <?php } ?>

                            </div>
                        </div>


                    <?php } ?>

                    <?php if ($needs->num_rows == 0) { ?>
                        <div class="bg-white p-6 rounded-xl text-center text-slate-500 shadow-sm">
                            No needs found.
                        </div>
                    <?php } ?>

                    <!-- Footer Spacer -->
                    <div class="h-20"></div>

                </div>
            </main>
        </div>
        <script>
            function reportNeed(id) {

                let reason = prompt("Report reason:");

                if (!reason) return;

                fetch("needs.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "report_need=1&need_id=" + id + "&reason=" + encodeURIComponent(reason)
                })
                    .then(res => res.text())
                    .then(data => {
                        alert("Need reported. Admin will review.");
                    });

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
                if (overlay) overlay.classList.toggle('hidden');
            }
        </script>
</body>

</html>