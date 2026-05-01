<?php
session_start();
include "admin/config/db.php";

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$account_id = $_SESSION['account_id'];

$user = null;

if (isset($_SESSION['account_id'])) {

    $account_id = $_SESSION['account_id'];

    $result = $conn->query("
        SELECT name, profile_photo 
        FROM accounts 
        WHERE account_id = $account_id
    ");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
}

/* ===================================
   CREATE POST
=================================== */
if (isset($_POST['create_post'])) {

    $description = $conn->real_escape_string($_POST['description']);

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
        INSERT INTO community_forum (account_id, description, image_1, image_2)
        VALUES ('$account_id','$description','$image1','$image2')
    ");

    header("Location: community.php");
    exit();
}

/* ===================================
   LIKE POST
=================================== */
if (isset($_GET['like'])) {
    $post_id = (int)$_GET['like'];
    $conn->query("UPDATE community_forum SET likes = likes + 1 WHERE post_id=$post_id");
    header("Location: community.php");
    exit();
}

/* =========================
FILTER POSTS BY USER
========================= */

$userFilter = "";

if(isset($_GET['user_id'])){

    $uid = (int)$_GET['user_id'];

    $userFilter = "AND community_forum.account_id=$uid";

}

/* ===================================
   FETCH POSTS WITH SEARCH
=================================== */

$searchQuery = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {

    $search = $conn->real_escape_string($_GET['search']);

    $searchQuery = "
        AND (
            community_forum.description LIKE '%$search%' 
            OR accounts.name LIKE '%$search%'
        )
    ";
}

$posts = $conn->query("
    SELECT community_forum.*, accounts.name, accounts.profile_photo, accounts.verified_status
    FROM community_forum
    JOIN accounts ON community_forum.account_id = accounts.account_id
    WHERE community_forum.status='Active'
    $userFilter
    $searchQuery
    ORDER BY community_forum.created_at DESC
");

/* ===================================
   DELETE POST (ONLY OWNER)
=================================== */
if (isset($_GET['delete_post'])) {

    $post_id = (int)$_GET['delete_post'];

    $checkOwner = $conn->query("
        SELECT account_id FROM community_forum 
        WHERE post_id=$post_id
    ")->fetch_assoc();

    if ($checkOwner && $checkOwner['account_id'] == $_SESSION['account_id']) {

        $conn->query("DELETE FROM community_forum WHERE post_id=$post_id");
    }

    header("Location: community.php");
    exit();
}


/* ===================================
   ADD COMMENT
=================================== */
if (isset($_POST['add_comment'])) {

    $post_id = (int)$_POST['post_id'];
    $comment_text = $conn->real_escape_string($_POST['comment_text']);
    $account_id = $_SESSION['account_id'];

    $conn->query("
        INSERT INTO comments (post_id, account_id, comment_text)
        VALUES ('$post_id','$account_id','$comment_text')
    ");

    header("Location: community.php");
    exit();
}

/* ===================================
   AJAX LIKE (SAME FILE)
=================================== */
if (isset($_POST['ajax_like'])) {

    $post_id = (int)$_POST['post_id'];
    $account_id = $_SESSION['account_id'];

    $post = $conn->query("
        SELECT likes, liked_users
        FROM community_forum
        WHERE post_id=$post_id
    ")->fetch_assoc();

    $liked_users = $post['liked_users'];
    $liked_array = $liked_users ? explode(",", $liked_users) : [];

    if (in_array($account_id, $liked_array)) {

        // UNLIKE
        $liked_array = array_diff($liked_array, [$account_id]);
        $new_likes = max(0, $post['likes'] - 1);

        $liked_string = implode(",", $liked_array);

        $conn->query("
            UPDATE community_forum
            SET likes=$new_likes,
                liked_users='$liked_string'
            WHERE post_id=$post_id
        ");

        $liked = 0;
    } else {

        // LIKE
        $liked_array[] = $account_id;
        $liked_string = implode(",", $liked_array);
        $new_likes = $post['likes'] + 1;

        $conn->query("
            UPDATE community_forum
            SET likes=$new_likes,
                liked_users='$liked_string'
            WHERE post_id=$post_id
        ");

        $liked = 1;
    }

    echo json_encode([
        "likes" => $new_likes,
        "liked" => $liked
    ]);

    exit();
}


/* ===================================
   AJAX COMMENT (SAME FILE)
=================================== */
if (isset($_POST['ajax_comment'])) {

    $post_id = (int)$_POST['post_id'];
    $comment_text = $conn->real_escape_string($_POST['comment_text']);
    $account_id = $_SESSION['account_id'];

    $conn->query("
        INSERT INTO comments (post_id, account_id, comment_text)
        VALUES ('$post_id','$account_id','$comment_text')
    ");

    // ✅ THIS WAS MISSING
    $comment_id = $conn->insert_id;

    $user = $conn->query("
        SELECT name, profile_photo 
        FROM accounts 
        WHERE account_id=$account_id
    ")->fetch_assoc();

    $profileImage = (!empty($user['profile_photo']) && file_exists("uploads/" . $user['profile_photo']))
        ? "uploads/" . $user['profile_photo']
        : "assets/default-user.png";

    echo '
    <div class="flex gap-2 mb-2 items-start" id="comment-' . $comment_id . '">

        <img src="' . $profileImage . '" class="w-7 h-7 rounded-full object-cover">

        <div class="bg-slate-100 px-3 py-2 rounded-lg text-sm">

            <strong>' . $user['name'] . '</strong><br>
            ' . htmlspecialchars($comment_text) . '

            <button onclick="deleteComment(' . $comment_id . ')"
                    class="text-red-500 text-xs ml-2 hover:underline">
                Delete
            </button>

        </div>
    </div>';

    exit();
}

/* ===================================
   AJAX DELETE COMMENT
=================================== */
if (isset($_POST['ajax_delete_comment'])) {

    $comment_id = (int)$_POST['comment_id'];
    $account_id = $_SESSION['account_id'];

    // Check ownership
    $check = $conn->query("
        SELECT account_id 
        FROM comments 
        WHERE comment_id=$comment_id
    ")->fetch_assoc();

    if ($check && $check['account_id'] == $account_id) {

        $conn->query("DELETE FROM comments WHERE comment_id=$comment_id");

        echo "deleted";
    }

    exit();
}

/* ===================================
   REPORT SYSTEM
=================================== */

if (isset($_POST['report_target'])) {

    $reported_by = $_SESSION['account_id'];
    $target_type = $conn->real_escape_string($_POST['target_type']);
    $target_id = (int)$_POST['target_id'];
    $reason = $conn->real_escape_string($_POST['reason']);

    // prevent duplicate reports by same user
    $check = $conn->query("
        SELECT report_id FROM reports
        WHERE reported_by=$reported_by
        AND target_type='$target_type'
        AND target_id=$target_id
    ");

    if ($check->num_rows == 0) {

        $conn->query("
            INSERT INTO reports (reported_by, target_type, target_id, reason)
            VALUES ($reported_by,'$target_type',$target_id,'$reason')
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
                            placeholder="Search posts or users..."
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
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Community Feed</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-lg">Connecting NGOs, donors, and volunteers in real-time.</p>
                    </div>
                    <!-- CREATE POST -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border mb-6">
                        <form method="POST" enctype="multipart/form-data">
                            <textarea name="description" required
                                class="w-full p-3 border rounded-lg mb-3"
                                placeholder="Share something with community..."></textarea>

                            <div class="flex gap-3 mb-3">
                                <div class="file-box">
                                    <input type="file" id="file1" name="image_1">
                                    <label for="file1">Upload Image 1</label>
                                </div>

                                <div class="file-box">
                                    <input type="file" id="file2" name="image_2">
                                    <label for="file2">Upload Image 2</label>
                                </div>
                                <button type="submit" name="create_post"
                                    class="bg-[var(--primary)] text-white px-6 py-2 rounded-lg">
                                    Post
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php while ($post = $posts->fetch_assoc()) { ?>

                        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                            <div class="p-6">

                                <!-- USER INFO -->
                                <div class="flex items-center justify-between mb-4">

                                    <div class="flex items-center gap-3">

                                        <?php
                                        $profileImage = (!empty($post['profile_photo']) && file_exists("uploads/" . $post['profile_photo']))
                                            ? "uploads/" . $post['profile_photo']
                                            : "assets/default-user.png";
                                        ?>

                                        <a href="view_profile.php?account_id=<?php echo $post['account_id']; ?>">
                                            <img src="<?php echo $profileImage; ?>"
                                                class="size-10 rounded-full object-cover hover:opacity-80 cursor-pointer">
                                        </a>

                                        <div>
                                            <div class="flex items-center gap-1">
                                                <h3 class="font-bold"><?php echo $post['name']; ?></h3>

                                                <?php if ($post['verified_status'] == 'Verified') { ?>
                                                    <span class="material-symbols-outlined text-primary text-sm"
                                                        style="font-variation-settings: 'FILL' 1">
                                                        verified
                                                    </span>
                                                <?php } ?>
                                            </div>

                                            <p class="text-xs text-slate-500">
                                                <?php echo date("d M Y, h:i A", strtotime($post['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- DELETE BUTTON (ONLY OWNER) -->

                                    <div class="flex items-center gap-2">

                                        <?php if ($post['account_id'] == $_SESSION['account_id']) { ?>

                                            <!-- DELETE BUTTON (OWNER ONLY) -->
                                            <a href="?delete_post=<?php echo $post['post_id']; ?>"
                                                onclick="return confirm('Delete this post?');"
                                                class="text-red-500 hover:text-red-700 text-sm">

                                                <span class="material-symbols-outlined">delete</span>

                                            </a>

                                        <?php } else { ?>

                                            <!-- REPORT BUTTON -->
                                            <button onclick="reportTarget('Post',<?php echo $post['post_id']; ?>)"
                                                class="text-slate-500 hover:text-red-800 text-sm">

                                                <span class="material-symbols-outlined">flag</span>

                                            </button>

                                        <?php } ?>

                                    </div>


                                </div>

                                <!-- DESCRIPTION -->
                                <p class="mb-4">
                                    <?php echo nl2br($post['description']); ?>
                                </p>

                                <!-- IMAGES -->
                                <?php if ($post['image_1'] || $post['image_2']) { ?>
                                    <div class="grid grid-cols-2 gap-2 mb-4">
                                        <?php if ($post['image_1']) { ?>
                                            <img src="uploads/<?php echo $post['image_1']; ?>" class="rounded-lg">
                                        <?php } ?>
                                        <?php if ($post['image_2']) { ?>
                                            <img src="uploads/<?php echo $post['image_2']; ?>" class="rounded-lg">
                                        <?php } ?>
                                    </div>
                                <?php } ?>


                                <?php
                                $liked = false;

                                if (!empty($post['liked_users'])) {
                                    $liked_array = explode(",", $post['liked_users']);
                                    if (in_array($_SESSION['account_id'], $liked_array)) {
                                        $liked = true;
                                    }
                                }
                                ?>
                                <!-- LIKE + COMMENT COUNT -->
                                <div class="flex justify-between items-center border-t pt-3">

                                    <div class="flex gap-4">

                                        <button onclick="likePost(<?php echo $post['post_id']; ?>)"
                                            id="like-btn-<?php echo $post['post_id']; ?>"
                                            class="flex items-center gap-1 <?php echo $liked ? 'text-red-500' : 'text-slate-500'; ?> hover:text-red-500 transition">

                                            <span class="material-symbols-outlined">favorite</span>

                                            <span id="like-count-<?php echo $post['post_id']; ?>">
                                                <?php echo $post['likes']; ?>
                                            </span>

                                        </button>

                                        <?php
                                        $commentCount = $conn->query(
                                            "
            SELECT COUNT(*) as total 
            FROM comments 
            WHERE post_id=" . $post['post_id']
                                        )->fetch_assoc()['total'];
                                        ?>

                                        <div class="flex items-center gap-1 text-slate-500">
                                            <span class="material-symbols-outlined">chat_bubble</span>
                                            <?php echo $commentCount; ?>
                                        </div>

                                    </div>

                                </div>

                                <!-- ===========================
     COMMENT SECTION
=========================== -->

                                <div class="mt-4">

                                    <!-- ADD COMMENT -->
                                    <form onsubmit="addComment(event, <?php echo $post['post_id']; ?>)"
                                        class="flex gap-2 mb-3">

                                        <input type="text"
                                            id="comment-input-<?php echo $post['post_id']; ?>"
                                            required
                                            placeholder="Write a comment..."
                                            class="flex-1 border rounded-lg px-3 py-2 text-sm">

                                        <button type="submit"
                                            class="bg-[var(--primary)] text-white px-4 rounded-lg text-sm">
                                            Post
                                        </button>
                                    </form>

                                    <!-- VIEW COMMENTS -->
                                    <div id="comments-container-<?php echo $post['post_id']; ?>" class="flex gap-2">

                                        <?php
                                        $comments = $conn->query("
    SELECT comments.*, accounts.name, accounts.profile_photo
    FROM comments
    JOIN accounts ON comments.account_id = accounts.account_id
    WHERE post_id=" . $post['post_id'] . "
    ORDER BY comments.created_at ASC
");

                                        while ($comment = $comments->fetch_assoc()) {

                                            $commentPfp = (!empty($comment['profile_photo']) && file_exists("uploads/" . $comment['profile_photo']))
                                                ? "uploads/" . $comment['profile_photo']
                                                : "assets/default-user.png";
                                        ?>

                                            <div class="flex gap-2 mb-2 items-start"
                                                id="comment-<?php echo $comment['comment_id']; ?>">

                                                <a href="view_profile.php?account_id=<?php echo $comment['account_id']; ?>">
                                                    <img src="<?php echo $commentPfp; ?>"
                                                        class="w-7 h-7 rounded-full object-cover"></a>

                                                <div class="bg-slate-100 px-3 py-2 rounded-lg text-sm">

                                                    <strong><?php echo $comment['name']; ?></strong><br>
                                                    <?php echo htmlspecialchars($comment['comment_text']); ?>

                                                    <div class="flex items-center gap-2 mt-1">

                                                        <?php if ($comment['account_id'] == $_SESSION['account_id']) { ?>

                                                            <button onclick="deleteComment(<?php echo $comment['comment_id']; ?>)"
                                                                class="text-red-500 text-xs">

                                                                <span class="material-symbols-outlined text-sm">delete</span>

                                                            </button>

                                                        <?php } else { ?>

                                                            <button onclick="reportTarget('Comment',<?php echo $comment['comment_id']; ?>)"
                                                                class="text-slate-500 hover:text-red-500 text-xs">

                                                                <span class="material-symbols-outlined text-sm">flag</span>

                                                            </button>

                                                        <?php } ?>

                                                    </div>

                                                </div>
                                            </div>

                                        <?php } ?>

                                    </div> <!-- 🔥 THIS WAS MISSING -->
                                </div>

                            </div> <!-- p-6 -->
                        </div> <!-- card -->
                    <?php } ?>

                    <?php if ($posts->num_rows == 0) { ?>
                        <div class="bg-white p-6 rounded-xl text-center text-slate-500">
                            No posts found.
                        </div>
                    <?php } ?>

                    <!-- Footer Spacer -->
                    <div class="h-20"></div>
            </main>
        </div>
        <!-- Right Side: Trending & Stats (Optional for Desktop Completeness) -->

    </div>
    <script>
        function deleteComment(commentId) {

            if (!confirm("Delete this comment?")) return;

            fetch("community.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "ajax_delete_comment=1&comment_id=" + commentId
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "deleted") {
                        document.getElementById("comment-" + commentId).remove();
                    }
                });
        }

        function likePost(postId) {

            fetch("community.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "ajax_like=1&post_id=" + postId
                })
                .then(response => response.json())
                .then(data => {

                    document.getElementById("like-count-" + postId).innerText = data.likes;

                    let btn = document.getElementById("like-btn-" + postId);

                    if (data.liked == 1) {
                        btn.classList.remove("text-slate-500");
                        btn.classList.add("text-red-500");
                    } else {
                        btn.classList.remove("text-red-500");
                        btn.classList.add("text-slate-500");
                    }

                });
        }

        function addComment(event, postId) {

            event.preventDefault();

            let input = document.getElementById("comment-input-" + postId);
            let commentText = input.value;

            fetch("community.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "ajax_comment=1&post_id=" + postId + "&comment_text=" + encodeURIComponent(commentText)
                })
                .then(response => response.text())
                .then(data => {

                    document.getElementById("comments-container-" + postId)
                        .insertAdjacentHTML("beforeend", data);

                    input.value = "";
                });
        }

        function reportTarget(type, id) {

            let reason = prompt("Report reason:");

            if (!reason) return;

            fetch("community.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "report_target=1&target_type=" + type + "&target_id=" + id + "&reason=" + encodeURIComponent(reason)
                })
                .then(res => res.text())
                .then(data => {
                    alert("Report submitted. Admin will review it.");
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