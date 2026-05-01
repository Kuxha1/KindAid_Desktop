<?php
session_start();
include "admin/config/db.php";

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$account_id = $_SESSION['account_id'];

/* ======================
FETCH USER DATA
====================== */
$user = $conn->query("
SELECT *
FROM accounts
WHERE account_id=$account_id
")->fetch_assoc();


/* ======================
UPDATE PROFILE
====================== */

if (isset($_POST['update_profile'])) {

    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $bio = $conn->real_escape_string($_POST['bio']);

    $profile_photo = $user['profile_photo'];
    $doc1 = $user['document_1'];
    $doc2 = $user['document_2'];


    /* PROFILE PHOTO */
    if (!empty($_FILES['profile_photo']['name'])) {

        $profile_photo = time() . "_" . $_FILES['profile_photo']['name'];

        move_uploaded_file(
            $_FILES['profile_photo']['tmp_name'],
            "uploads/" . $profile_photo
        );
    }


    /* DOCUMENT 1 */
    if (!empty($_FILES['document_1']['name'])) {

        $doc1 = time() . "_" . $_FILES['document_1']['name'];

        move_uploaded_file(
            $_FILES['document_1']['tmp_name'],
            "uploads/doc/" . $doc1
        );
    }


    /* DOCUMENT 2 */
    if (!empty($_FILES['document_2']['name'])) {

        $doc2 = time() . "_" . $_FILES['document_2']['name'];

        move_uploaded_file(
            $_FILES['document_2']['tmp_name'],
            "uploads/doc/" . $doc2
        );
    }


    /* PASSWORD UPDATE */


    $password_query = "";

    if (!empty($_POST['password'])) {

        $current_password = $_POST['current_password'];
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        /* CHECK CURRENT PASSWORD */

        $check = $conn->query("
SELECT password
FROM accounts
WHERE account_id=$account_id
")->fetch_assoc();

        if (!password_verify($current_password, $check['password'])) {

            die("Current password is incorrect.");
        }

        /* CONFIRM PASSWORD */

        if ($new_password !== $confirm_password) {

            die("New passwords do not match.");
        }

        /* HASH NEW PASSWORD */

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $password_query = ", password='$hashed_password'";
    }


    /* UPDATE DATABASE */

    $conn->query("
UPDATE accounts SET
name='$name',
email='$email',
contact_no='$contact',
address='$address',
bio='$bio',
profile_photo='$profile_photo',
document_1='$doc1',
document_2='$doc2'
$password_query
WHERE account_id=$account_id
");

    header("Location: settings.php?updated=1");
    exit();
}

/* ======================
USER TRANSACTIONS
====================== */

$transactions = $conn->query("
SELECT transactions.*, fundraiser.title
FROM transactions
JOIN fundraiser ON transactions.fundraiser_id = fundraiser.fundraiser_id
WHERE donor_id = $account_id
ORDER BY transaction_date DESC
");


/* ======================
USER REPORTS
====================== */

$reports = $conn->query("
SELECT *
FROM reports
WHERE reported_by = $account_id
ORDER BY created_at DESC
");


/* ======================
USER COMMENTS
====================== */

$comments = $conn->query("
SELECT comments.*, community_forum.description
FROM comments
JOIN community_forum ON comments.post_id = community_forum.post_id
WHERE comments.account_id = $account_id
ORDER BY comments.created_at DESC
");

/* ======================
DELETE COMMENT
====================== */

if (isset($_GET['delete_comment'])) {

    $comment_id = (int)$_GET['delete_comment'];

    $conn->query("
        DELETE FROM comments
        WHERE comment_id=$comment_id
        AND account_id=$account_id
    ");

    header("Location: settings.php");
    exit();
}

/* ======================
DELETE REPORT
====================== */

if (isset($_GET['delete_report'])) {

    $report_id = (int)$_GET['delete_report'];

    $conn->query("
        DELETE FROM reports
        WHERE report_id=$report_id
        AND reported_by=$account_id
    ");

    header("Location: settings.php");
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
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Kind Aid - Settings</h2>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow border">

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">

                            <!-- PROFILE PHOTO COVER -->

                            <div class="bg-slate-100 rounded-xl p-6 flex flex-col sm:flex-row items-center gap-6">

                                <!-- PROFILE IMAGE -->
                                <div class="relative">

                                    <img id="profilePreview"
                                        src="<?php echo !empty($user['profile_photo']) ? 'uploads/' . $user['profile_photo'] : 'assets/default-user.png'; ?>"
                                        class="w-24 h-24 rounded-full object-cover border-4 border-white shadow">

                                    <label for="profileUpload"
                                        class="absolute bottom-0 right-0 bg-[var(--primary)] text-white p-1 rounded-full cursor-pointer">

                                        <span class="material-symbols-outlined text-sm">photo_camera</span>

                                    </label>

                                    <input
                                        type="file"
                                        id="profileUpload"
                                        name="profile_photo"
                                        accept="image/*"
                                        class="hidden">

                                </div>


                                <!-- USER INFO -->
                                <div class="flex-1">

                                    <h3 class="font-bold text-lg">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </h3>

                                    <p class="text-sm text-gray-500">
                                        Update your profile picture and personal details.
                                    </p>

                                    <p class="text-xs text-gray-400 mt-1">
                                        Recommended: Square image, JPG or PNG
                                    </p>

                                </div>

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <input type="text"
                                    name="name"
                                    value="<?php echo htmlspecialchars($user['name']); ?>"
                                    placeholder="Full Name"
                                    class="border p-3 rounded-lg w-full">

                                <input type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    placeholder="Email"
                                    class="border p-3 rounded-lg w-full">

                            </div>


                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <input type="text"
                                    name="contact_no"
                                    value="<?php echo htmlspecialchars($user['contact_no']); ?>"
                                    placeholder="Contact Number"
                                    class="border p-3 rounded-lg w-full">

                                <input
                                    type="password"
                                    name="current_password"
                                    id="current_password"
                                    placeholder="Current Password"
                                    class="border p-3 rounded-lg w-full">

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    placeholder="New Password"
                                    class="border p-3 rounded-lg w-full">

                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm_password"
                                    placeholder="Confirm Password"
                                    class="border p-3 rounded-lg w-full">

                            </div>

                            <p id="passwordError" class="text-red-500 text-sm hidden">
                                Passwords do not match
                            </p>


                            <textarea name="address"
                                placeholder="Address"
                                class="border p-3 rounded-lg w-full"><?php echo htmlspecialchars($user['address']); ?></textarea>


                            <textarea name="bio"
                                placeholder="Bio"
                                class="border p-3 rounded-lg w-full"><?php echo htmlspecialchars($user['bio']); ?></textarea>

                            <!-- DOCUMENTS -->

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <div class="file-box w-full">
                                    <input type="file" id="doc1" name="document_1" class="hidden">
                                    <label for="doc1"
                                        class="block text-center cursor-pointer bg-slate-200 px-3 py-2 rounded-lg text-sm w-full">
                                        Update Document 1
                                    </label>

                                    <?php if ($user['document_1']) { ?>

                                        <a href="uploads/doc/<?php echo $user['document_1']; ?>"
                                            target="_blank"
                                            class="text-blue-600 text-sm">
                                            View Document 1
                                        </a>

                                    <?php } ?>
                                </div>


                                <div class="file-box w-full">
                                    <input type="file" id="doc2" name="document_2" class="hidden">
                                    <label for="doc2"
                                        class="block text-center cursor-pointer bg-slate-200 px-3 py-2 rounded-lg text-sm w-full">
                                        Update Document 2
                                    </label>

                                    <?php if ($user['document_2']) { ?>

                                        <a href="uploads/doc/<?php echo $user['document_2']; ?>"
                                            target="_blank"
                                            class="text-blue-600 text-sm">
                                            View Document 2
                                        </a>

                                    <?php } ?>
                                </div>

                            </div>

                            <button
                                type="submit"
                                name="update_profile"
                                class="bg-[var(--primary)] text-white px-6 py-3 rounded-lg">

                                Update Profile

                            </button>

                        </form>

                    </div>



                    <!-- Transaction History Area -->
                    <div class="bg-white p-6 rounded-xl shadow border mt-6">

                        <h3 class="text-xl font-bold mb-4">Transaction History</h3>

                        <?php if ($transactions->num_rows == 0) { ?>
                            <p class="text-gray-500">No transactions yet.</p>
                        <?php } ?>

                        <div class="space-y-3">

                            <?php while ($t = $transactions->fetch_assoc()) { ?>

                                <div class="border p-4 rounded-lg flex justify-between items-center">

                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($t['title']); ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date("d M Y", strtotime($t['transaction_date'])); ?>
                                        </p>
                                    </div>

                                    <div class="text-right">

                                        <p class="font-bold text-green-600">
                                            ₹<?php echo number_format($t['amount']); ?>
                                        </p>

                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo $t['payment_method']; ?>
                                        </p>

                                        <span class="text-xs px-2 py-1 rounded
<?php
                                $statusColor = [
                                    "Pending" => "bg-yellow-100 text-yellow-700",
                                    "Completed" => "bg-green-100 text-green-700",
                                    "Failed" => "bg-red-100 text-red-700"
                                ];
                                echo $statusColor[$t['payment_status']];
?>">
                                            <?php echo $t['payment_status']; ?>
                                        </span>

                                    </div>

                                </div>

                            <?php } ?>

                        </div>

                    </div>


                    <!-- Reports History Area -->

                    <div class="bg-white p-6 rounded-xl shadow border mt-6">

                        <h3 class="text-xl font-bold mb-4">Your Reports</h3>

                        <?php if ($reports->num_rows == 0) { ?>
                            <p class="text-gray-500">You haven't reported anything.</p>
                        <?php } ?>

                        <div class="space-y-3">

                            <?php while ($r = $reports->fetch_assoc()) { ?>

                                <div class="border p-4 rounded-lg flex justify-between items-start">

                                    <div>

                                        <p class="font-semibold">
                                            Reported <?php echo $r['target_type']; ?> (ID: <?php echo $r['target_id']; ?>)
                                        </p>

                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($r['reason']); ?>
                                        </p>

                                        <p class="text-xs text-gray-400">
                                            <?php echo date("d M Y", strtotime($r['created_at'])); ?>
                                        </p>

                                    </div>

                                    <div class="flex items-center gap-3">

                                        <span class="text-xs px-3 py-1 rounded
<?php
                                $statusColor = [
                                    "Pending" => "bg-yellow-100 text-yellow-700",
                                    "Reviewed" => "bg-blue-100 text-blue-700",
                                    "Action_Taken" => "bg-green-100 text-green-700"
                                ];
                                echo $statusColor[$r['status']];
?>">
                                            <?php echo $r['status']; ?>
                                        </span>

                                        <a href="?delete_report=<?php echo $r['report_id']; ?>"
                                            onclick="return confirm('Delete this report?')"
                                            class="text-red-500 hover:text-red-700">

                                            <span class="material-symbols-outlined">delete</span>

                                        </a>

                                    </div>

                                </div>

                            <?php } ?>

                        </div>

                    </div>






                    <div class="bg-white p-6 rounded-xl shadow border mt-6">

                        <h3 class="text-xl font-bold mb-4">Your Comments</h3>

                        <?php if ($comments->num_rows == 0) { ?>
                            <p class="text-gray-500">You haven't commented yet.</p>
                        <?php } ?>

                        <div class="space-y-3">

                            <?php while ($c = $comments->fetch_assoc()) { ?>

                                <div class="border p-4 rounded-lg flex justify-between items-start">

                                    <div>

                                        <p class="text-sm text-gray-500 mb-1">
                                            <?php echo date("d M Y", strtotime($c['created_at'])); ?>
                                        </p>

                                        <p class="font-medium">
                                            <?php echo htmlspecialchars($c['comment_text']); ?>
                                        </p>

                                        <p class="text-xs text-gray-400 mt-1">
                                            On Post: <?php echo substr($c['description'], 0, 40); ?>...
                                        </p>

                                    </div>

                                    <a href="?delete_comment=<?php echo $c['comment_id']; ?>"
                                        onclick="return confirm('Delete this comment?')"
                                        class="text-red-500 hover:text-red-700">

                                        <span class="material-symbols-outlined">delete</span>

                                    </a>

                                </div>

                            <?php } ?>

                        </div>

                    </div>
            </main>
        </div>
        <script>
            const uploadInput = document.getElementById("profileUpload");
            const preview = document.getElementById("profilePreview");

            uploadInput.addEventListener("change", function() {

                const file = this.files[0];

                if (file) {

                    const reader = new FileReader();

                    reader.onload = function(e) {

                        preview.src = e.target.result;

                    }

                    reader.readAsDataURL(file);

                }

            });

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

            const password = document.getElementById("password");
            const confirmPassword = document.getElementById("confirm_password");
            const error = document.getElementById("passwordError");

            confirmPassword.addEventListener("input", function() {

                if (password.value !== confirmPassword.value) {

                    error.classList.remove("hidden");

                } else {

                    error.classList.add("hidden");

                }

            });
        </script>
</body>

</html>