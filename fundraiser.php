<?php
session_start();
include "admin/config/db.php";

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$account_id = $_SESSION['account_id'];

/* ==========================
FETCH LOGGED USER
==========================*/
$user = $conn->query("
SELECT name, profile_photo 
FROM accounts 
WHERE account_id=$account_id
")->fetch_assoc();


/* ==========================
FETCH FUNDRAISER FOR EDIT
==========================*/
$editFund = null;

if (isset($_GET['edit_fundraiser'])) {

    $id = (int)$_GET['edit_fundraiser'];

    $check = $conn->query("
    SELECT * FROM fundraiser
    WHERE fundraiser_id=$id AND account_id=$account_id
    ");

    if ($check->num_rows > 0) {
        $editFund = $check->fetch_assoc();
    }
}


/* ==========================
CREATE FUNDRAISER
==========================*/
if (isset($_POST['create_fundraiser'])) {

    $title = $conn->real_escape_string($_POST['title']);
    $desc  = $conn->real_escape_string($_POST['description']);
    $goal  = (float)$_POST['goal_amount'];

    $image1 = "";
    $image2 = "";
    $doc1 = "";
    $doc2 = "";

    if (!empty($_FILES['image_1']['name'])) {
        $image1 = time() . "_" . $_FILES['image_1']['name'];
        move_uploaded_file($_FILES['image_1']['tmp_name'], "uploads/" . $image1);
    }

    if (!empty($_FILES['image_2']['name'])) {
        $image2 = time() . "_" . $_FILES['image_2']['name'];
        move_uploaded_file($_FILES['image_2']['tmp_name'], "uploads/" . $image2);
    }

    if (!empty($_FILES['document_1']['name'])) {
        $doc1 = time() . "_" . $_FILES['document_1']['name'];
        move_uploaded_file($_FILES['document_1']['tmp_name'], "uploads/doc/" . $doc1);
    }

    if (!empty($_FILES['document_2']['name'])) {
        $doc2 = time() . "_" . $_FILES['document_2']['name'];
        move_uploaded_file($_FILES['document_2']['tmp_name'], "uploads/doc/" . $doc2);
    }

    $conn->query("
INSERT INTO fundraiser
(account_id,title,description,goal_amount,image_1,image_2,document_1,document_2)
VALUES
('$account_id','$title','$desc','$goal','$image1','$image2','$doc1','$doc2')
");

    header("Location:fundraiser.php");
    exit();
}



/* ==========================
UPDATE FUNDRAISER
==========================*/
if (isset($_POST['update_fundraiser'])) {

    $id = (int)$_POST['fundraiser_id'];

    $title = $conn->real_escape_string($_POST['title']);
    $desc  = $conn->real_escape_string($_POST['description']);
    $goal  = (float)$_POST['goal_amount'];

    $conn->query("
UPDATE fundraiser
SET title='$title',
description='$desc',
goal_amount='$goal'
WHERE fundraiser_id=$id
AND account_id=$account_id
");

    header("Location:fundraiser.php");
    exit();
}

/* ==========================
UPDATE COLLECTED AMOUNT
==========================*/

if (isset($_POST['donate_amount'])) {

    $fundraiser_id = (int)$_POST['fundraiser_id'];
    $amount = (float)$_POST['amount'];

    $conn->query("
UPDATE fundraiser
SET collected_amount = collected_amount + $amount
WHERE fundraiser_id = $fundraiser_id
");

    header("Location: fundraiser.php");
    exit();
}

/* ==========================
DELETE FUNDRAISER
==========================*/
if (isset($_GET['delete_fundraiser'])) {

    $id = (int)$_GET['delete_fundraiser'];

    $conn->query("
DELETE FROM fundraiser
WHERE fundraiser_id=$id
AND account_id=$account_id
");

    header("Location:fundraiser.php");
    exit();
}


/* ==========================
FILTER FUNDRAISERS BY USER
==========================*/

$userFilter = "";

if(isset($_GET['user_id'])){

    $uid = (int)$_GET['user_id'];

    $userFilter = "AND fundraiser.account_id=$uid";
}

/* ==========================
SEARCH
==========================*/

$searchQuery = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {

    $search = $conn->real_escape_string($_GET['search']);

    $searchQuery = "
AND(
fundraiser.title LIKE '%$search%'
OR fundraiser.description LIKE '%$search%'
OR accounts.name LIKE '%$search%'
)
";
}



/* ==========================
FETCH FUNDRAISERS
==========================*/

$fundraisers = $conn->query("
SELECT fundraiser.*,accounts.name,accounts.profile_photo
FROM fundraiser
JOIN accounts ON fundraiser.account_id=accounts.account_id
WHERE 1=1
$userFilter
$searchQuery
ORDER BY fundraiser.created_at DESC
");

/* ==========================
REPORT FUNDRAISER
==========================*/

if (isset($_POST['report_fundraiser'])) {

    $reported_by = $_SESSION['account_id'];
    $target_id = (int)$_POST['fundraiser_id'];
    $reason = $conn->real_escape_string($_POST['reason']);

    // Prevent duplicate reports
    $check = $conn->query("
        SELECT report_id
        FROM reports
        WHERE reported_by=$reported_by
        AND target_type='Fundraiser'
        AND target_id=$target_id
    ");

    if ($check->num_rows == 0) {

        $conn->query("
            INSERT INTO reports
            (reported_by,target_type,target_id,reason)
            VALUES
            ($reported_by,'Fundraiser',$target_id,'$reason')
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
                            placeholder="Search Fundraisers..."
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
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Kind Aid - Fundraiser Board</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-lg">Discover and support active fundraising campaigns.</p>
                    </div>

                    <!-- CREATE POST -->
                    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border mb-6">

                        <form method="POST" enctype="multipart/form-data">

                            <!-- TITLE + GOAL -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">

                                <input type="text"
                                    name="title"
                                    required
                                    value="<?php echo $editFund ? htmlspecialchars($editFund['title']) : ''; ?>"
                                    placeholder="Fundraiser Title"
                                    class="w-full p-3 border rounded-lg">

                                <input type="number"
                                    name="goal_amount"
                                    required
                                    value="<?php echo $editFund ? $editFund['goal_amount'] : ''; ?>"
                                    placeholder="Goal Amount"
                                    class="w-full p-3 border rounded-lg">

                            </div>


                            <!-- DESCRIPTION -->
                            <textarea name="description"
                                required
                                placeholder="Describe your need..."
                                class="w-full p-3 border rounded-lg mb-4"><?php
                                                                            echo $editFund ? htmlspecialchars($editFund['description']) : '';
                                                                            ?></textarea>


                            <!-- FILE UPLOADS -->
                            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-4">

                                <div class="file-box w-full">
                                    <input type="file" id="file1" name="image_1" class="hidden">
                                    <label for="file1"
                                        class="block text-center cursor-pointer bg-slate-200 px-3 py-2 rounded-lg text-sm w-full">
                                        Upload Image 1
                                    </label>
                                </div>

                                <div class="file-box w-full">
                                    <input type="file" id="file2" name="image_2" class="hidden">
                                    <label for="file2"
                                        class="block text-center cursor-pointer bg-slate-200 px-3 py-2 rounded-lg text-sm w-full">
                                        Upload Image 2
                                    </label>
                                </div>

                                <div class="file-box w-full">
                                    <input type="file" id="doc1" name="document_1" class="hidden">
                                    <label for="doc1"
                                        class="block text-center cursor-pointer bg-slate-200 px-3 py-2 rounded-lg text-sm w-full">
                                        Upload Document 1
                                    </label>
                                </div>

                                <div class="file-box w-full">
                                    <input type="file" id="doc2" name="document_2" class="hidden">
                                    <label for="doc2"
                                        class="block text-center cursor-pointer bg-slate-200 px-3 py-2 rounded-lg text-sm w-full">
                                        Upload Document 2
                                    </label>
                                </div>

                            </div>


                            <!-- SUBMIT BUTTON -->
                            <div class="flex">

                                <?php if ($editFund) { ?>

                                    <input type="hidden" name="fundraiser_id"
                                        value="<?php echo $editFund['fundraiser_id']; ?>">

                                    <button type="submit"
                                        name="update_fundraiser"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg w-full w-auto">
                                        Update Fund
                                    </button>

                                <?php } else { ?>

                                    <button type="submit"
                                        name="create_fundraiser"
                                        class="bg-[var(--primary)] text-white px-6 py-2 rounded-lg w-full w-auto">
                                        Post Fundraiser
                                    </button>

                                <?php } ?>

                            </div>


                            <!-- EDIT IMAGES -->
                            <?php if ($editFund) { ?>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">

                                    <?php if (!empty($editFund['image_1'])) { ?>
                                        <div class="relative">
                                            <img src="uploads/<?php echo $editFund['image_1']; ?>"
                                                class="w-full h-24 object-cover rounded-lg shadow">

                                            <a href="?remove_image=image_1&fundraiser_id=<?php echo $editFund['fundraiser_id']; ?>"
                                                onclick="return confirm('Remove Image 1?');"
                                                class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full text-xs">
                                                ✕
                                            </a>
                                        </div>
                                    <?php } ?>

                                    <?php if (!empty($editFund['image_2'])) { ?>
                                        <div class="relative">
                                            <img src="uploads/<?php echo $editFund['image_2']; ?>"
                                                class="w-full h-24 object-cover rounded-lg shadow">

                                            <a href="?remove_image=image_2&fundraiser_id=<?php echo $editFund['fundraiser_id']; ?>"
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

                    <?php while ($fund = $fundraisers->fetch_assoc()) { ?>

                        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                            <div class="p-6">

                                <!-- USER INFO -->
                                <div class="flex items-center justify-between mb-4">

                                    <div class="flex items-center gap-3">

                                        <?php
                                        $profileImage = (!empty($fund['profile_photo']) && file_exists("uploads/" . $fund['profile_photo']))
                                            ? "uploads/" . $fund['profile_photo']
                                            : "assets/default-user.png";
                                        ?>

                                        <a href="view_profile.php?account_id=<?php echo $fund['account_id']; ?>">
                                            <img src="<?php echo $profileImage; ?>"
                                                class="size-10 rounded-full object-cover hover:opacity-80 cursor-pointer">
                                        </a>

                                        <div>
                                            <h3 class="font-bold"><?php echo $fund['name']; ?></h3>

                                            <p class="text-xs text-slate-500">
                                                <?php echo date("d M Y, h:i A", strtotime($fund['created_at'])); ?>
                                            </p>
                                        </div>

                                    </div>

                                    <!-- EDIT / DELETE OR REPORT -->
                                    <div class="flex gap-2">

                                        <?php if ($fund['account_id'] == $account_id) { ?>

                                            <!-- EDIT -->
                                            <a href="?edit_fundraiser=<?php echo $fund['fundraiser_id']; ?>">
                                                <span class="material-symbols-outlined text-blue-600">edit</span>
                                            </a>

                                            <!-- DELETE -->
                                            <a href="?delete_fundraiser=<?php echo $fund['fundraiser_id']; ?>"
                                                onclick="return confirm('Delete fundraiser?');">
                                                <span class="material-symbols-outlined text-red-500">delete</span>
                                            </a>

                                        <?php } else { ?>

                                            <!-- REPORT -->
                                            <button onclick="reportFundraiser(<?php echo $fund['fundraiser_id']; ?>)"
                                                class="text-slate-500 hover:text-red-600">

                                                <span class="material-symbols-outlined">flag</span>

                                            </button>

                                        <?php } ?>

                                    </div>

                                </div>


                                <!-- TITLE -->
                                <h2 class="text-xl font-bold mb-2">
                                    <?php echo htmlspecialchars($fund['title']); ?>
                                </h2>


                                <!-- DESCRIPTION -->
                                <p class="mb-4 text-slate-700">
                                    <?php echo nl2br($fund['description']); ?>
                                </p>


                                <!-- PROGRESS BAR -->

                                <?php
                                $percent = $fund['goal_amount'] > 0
                                    ? ($fund['collected_amount'] / $fund['goal_amount']) * 100
                                    : 0;
                                ?>

                                <div class="mb-4">

                                    <div class="flex justify-between text-sm mb-1">

                                        <span>Raised</span>

                                        <span>
                                            ₹<?php echo number_format($fund['collected_amount']); ?> /
                                            ₹<?php echo number_format($fund['goal_amount']); ?>
                                            (<?php echo round($percent); ?>%)
                                        </span>

                                    </div>

                                    <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">

                                        <div class="bg-green-500 h-full"
                                            style="width:<?php echo min($percent, 100); ?>%">
                                        </div>

                                    </div>

                                </div>


                                <!-- VERIFIED STATUS -->

                                <?php
                                $statusColor = [
                                    "Pending" => "bg-yellow-100 text-yellow-700",
                                    "Verified" => "bg-green-100 text-green-700",
                                    "Rejected" => "bg-red-100 text-red-700"
                                ];
                                ?>

                                <span class="px-3 py-1 text-xs rounded-full <?php echo $statusColor[$fund['verified_status']]; ?>">
                                    <?php echo $fund['verified_status']; ?>
                                </span>



                                <!-- IMAGES -->

                                <?php if ($fund['image_1'] || $fund['image_2']) { ?>

                                    <div class="grid grid-cols-2 gap-2 mt-4 mb-4">

                                        <?php if ($fund['image_1']) { ?>
                                            <img src="uploads/<?php echo $fund['image_1']; ?>" class="rounded-lg">
                                        <?php } ?>

                                        <?php if ($fund['image_2']) { ?>
                                            <img src="uploads/<?php echo $fund['image_2']; ?>" class="rounded-lg">
                                        <?php } ?>

                                    </div>

                                <?php } ?>


                                <!-- DOCUMENTS -->

                                <?php if ($fund['document_1'] || $fund['document_2']) { ?>

                                    <div class="flex gap-2 mb-4">

                                        <?php if ($fund['document_1']) { ?>
                                            <a href="uploads/doc/<?php echo $fund['document_1']; ?>"
                                                target="_blank"
                                                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                                                Document 1
                                            </a>
                                        <?php } ?>

                                        <?php if ($fund['document_2']) { ?>
                                            <a href="uploads/doc/<?php echo $fund['document_2']; ?>"
                                                target="_blank"
                                                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                                                Document 2
                                            </a>
                                        <?php } ?>

                                    </div>

                                <?php } ?>


                                <!-- DONATE SECTION -->

                                <?php if ($fund['account_id'] != $account_id) { ?>

                                    <form action="donate.php" method="POST"
                                        class="flex gap-2 border-t pt-4">

                                        <input type="hidden"
                                            name="fundraiser_id"
                                            value="<?php echo $fund['fundraiser_id']; ?>">

                                        <input type="number"
                                            name="amount"
                                            placeholder="Amount"
                                            required
                                            min="1"
                                            class="border rounded-lg px-3 py-2 w-sm">

                                        <select name="payment_method"
                                            class="border rounded-lg px-8">

                                            <option value="Online">Online</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Card">Card</option>

                                        </select>

                                        <button type="submit" name="donate_amount"
                                            class="bg-green-600 text-white px-4 py-2 rounded-lg">

                                            Donate

                                        </button>

                                    </form>

                                <?php } ?>

                            </div>
                        </div>

                    <?php } ?>


                    <?php if ($fundraisers->num_rows == 0) { ?>
                        <div class="bg-white p-6 rounded-xl text-center text-slate-500 rounded-xl shadow-sm">
                            No needs found.
                        </div>
                    <?php } ?>
                    <!-- Footer Spacer -->
                    <div class="h-20"></div>

                </div>
            </main>
        </div>
        <script>
            function reportFundraiser(id) {

                let reason = prompt("Report reason:");

                if (!reason) return;

                fetch("fundraiser.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "report_fundraiser=1&fundraiser_id=" + id + "&reason=" + encodeURIComponent(reason)
                    })
                    .then(res => res.text())
                    .then(data => {
                        alert("Fundraiser reported. Admin will review.");
                    });

            }
            document.querySelectorAll('.file-box input').forEach(input => {
                input.addEventListener('change', function() {
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