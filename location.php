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


/* =========================
ADD LOCATION
========================= */

if (isset($_POST['add_location'])) {

    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $type = $_POST['geo_type'];
    $desc = $conn->real_escape_string($_POST['description']);

    $image1 = "";
    $image2 = "";

    if (!empty($_FILES['image_1']['name'])) {
        $image1 = time() . "_" . $_FILES['image_1']['name'];
        move_uploaded_file($_FILES['image_1']['tmp_name'], "uploads/" . $image1);
    }

    if (!empty($_FILES['image_2']['name'])) {
        $image2 = time() . "_" . $_FILES['image_2']['name'];
        move_uploaded_file($_FILES['image_2']['tmp_name'], "uploads/" . $image2);
    }

    $conn->query("
INSERT INTO geo_locations
(account_id,latitude,longitude,geo_type,description,image_1,image_2)
VALUES
('$account_id','$lat','$lng','$type','$desc','$image1','$image2')
");

    header("Location: location.php");
    exit();
}

/* =========================
DELETE LOCATION
========================= */

if (isset($_GET['delete_geo'])) {

    $id = (int)$_GET['delete_geo'];

    $conn->query("
DELETE FROM geo_locations
WHERE geo_id=$id AND account_id=$account_id
");

    header("Location: location.php");
    exit();
}

/* =========================
FILTER LOCATIONS BY USER
========================= */

$userFilter = "";

if(isset($_GET['user_id'])){

    $uid = (int)$_GET['user_id'];

    $userFilter = "AND geo_locations.account_id=$uid";

}

/* =========================
SEARCH
========================= */

$searchQuery = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {

    $search = $conn->real_escape_string($_GET['search']);

    $searchQuery = "
    AND (
        geo_locations.description LIKE '%$search%'
        OR geo_locations.geo_type LIKE '%$search%'
        OR accounts.name LIKE '%$search%'
    )
    ";
}

/* =========================
FETCH LOCATIONS
========================= */

$locations = $conn->query("
SELECT geo_locations.*, accounts.name, accounts.profile_photo
FROM geo_locations
JOIN accounts ON geo_locations.account_id = accounts.account_id
WHERE 1=1
$searchQuery
$userFilter
ORDER BY geo_locations.created_at DESC
");

/* =========================
REPORT LOCATION
========================= */

if (isset($_POST['report_location'])) {

    $reported_by = $_SESSION['account_id'];
    $target_id = (int)$_POST['geo_id'];
    $reason = $conn->real_escape_string($_POST['reason']);

    // prevent duplicate report
    $check = $conn->query("
        SELECT report_id 
        FROM reports
        WHERE reported_by=$reported_by
        AND target_type='location'
        AND target_id=$target_id
    ");

    if ($check->num_rows == 0) {

        $conn->query("
            INSERT INTO reports
            (reported_by,target_type,target_id,reason)
            VALUES
            ($reported_by,'location',$target_id,'$reason')
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

        /* Hide scrollbar but keep scrolling */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            /* IE & Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>
</head>

<body class="bg-[var(--bg-main)] text-[var(--text-main)] font-display">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside id="sidebar"
            class="w-64 bg-[var(--secondary-dark)] fixed lg:relative
left-0 top-0 h-full z-[2000]
transform -translate-x-full lg:translate-x-0
transition-transform duration-300 ease-in-out
flex flex-col shadow-lg">
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
                            placeholder="Search Address & Locations..."
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
            <main class="flex-1 overflow-y-auto p-4 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


                        <!-- =========================
MAP AREA
========================= -->

                        <div class="lg:col-span-2 p-2 lg:p-4">
                            <div class="flex flex-col gap-2">
                                <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Kind Aid - Community Map</h2>
                                <p class="text-slate-500 dark:text-slate-400 text-lg">Discover and share locations where help is needed.</p>
                            </div><br> <br>

                            <div id="map" class="w-full h-[320px] sm:h-[420px] lg:h-[520px] rounded-lg shadow"></div>

                        </div>

                        <!-- =========================
LOCATION LIST
========================= -->

                        <div class="space-y-4 overflow-y-auto lg:h-[650px] pr-2 hide-scrollbar">

                            <?php
                            if ($locations->num_rows == 0) {
                                echo "<div class='bg-white p-4 rounded shadow text-gray-500'>
    No locations added yet.
    </div>";
                            }

                            $locations->data_seek(0);

                            while ($loc = $locations->fetch_assoc()) {

                                $profileImage = (!empty($loc['profile_photo']) && file_exists("uploads/" . $loc['profile_photo']))
                                    ? "uploads/" . $loc['profile_photo']
                                    : "assets/default-user.png";
                            ?>

                                <div class="bg-white p-4 rounded-xl shadow">

                                    <!-- USER INFO -->
                                    <div class="flex items-center gap-3 mb-2">
                                        <a href="view_profile.php?account_id=<?php echo $loc['account_id']; ?>">
                                            <img src="<?php echo $profileImage; ?>"
                                                class="w-9 h-9 rounded-full object-cover border">
                                        </a>

                                        <h3 class="font-bold text-sm">
                                            <?php echo htmlspecialchars($loc['name']); ?>
                                        </h3>

                                    </div>


                                    <p class="text-sm text-gray-600 mb-2">
                                        <?php echo htmlspecialchars($loc['description']); ?>
                                    </p>

                                    <span class="text-xs bg-gray-200 px-2 py-1 rounded">
                                        <?php echo $loc['geo_type']; ?>
                                    </span>

                                    <!-- STATUS -->
    <?php
        if ($loc['goal_status'] == 'Pending') {
    echo "<span class='text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded'>Pending</span>";
} elseif ($loc['goal_status'] == 'Ongoing') {
    echo "<span class='text-xs bg-green-100 text-green-700 px-2 py-1 rounded'>Ongoing</span>";
} else {
    echo "<span class='text-xs bg-red-100 text-red-700 px-2 py-1 rounded'>Completed</span>";
}
    ?>


                                    <?php if ($loc['image_1']) { ?>
                                        <img src="uploads/<?php echo $loc['image_1']; ?>"
                                            class="rounded mt-2 w-full h-40 object-cover">
                                    <?php } ?>

                                    <?php if ($loc['image_2']) { ?>
                                        <img src="uploads/<?php echo $loc['image_2']; ?>"
                                            class="rounded mt-2 w-full h-40 object-cover">
                                    <?php } ?>


                                    <div class="mt-2 flex gap-3">

                                        <?php if ($loc['account_id'] == $account_id) { ?>

                                            <!-- DELETE LOCATION -->
                                            <a href="?delete_geo=<?php echo $loc['geo_id']; ?>"
                                                onclick="return confirm('Delete location?')"
                                                class="text-red-500 text-sm">

                                                <span class="material-symbols-outlined">delete</span>

                                            </a>

                                        <?php } else { ?>

                                            <!-- REPORT LOCATION -->
                                            <button onclick="reportLocation(<?php echo $loc['geo_id']; ?>)"
                                                class="text-slate-500 hover:text-red-600 text-sm">

                                                <span class="material-symbols-outlined">flag</span>

                                            </button>

                                        <?php } ?>

                                    </div>

                                </div>

                            <?php } ?>

                        </div>

                    </div>

                </div>
        </div>



        <!-- =========================
ADD LOCATION MODAL
========================= -->

        <div id="locationModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-[9999]">

            <div class="bg-white p-6 rounded-xl w-96 h-96">

                <h2 class="text-lg font-bold mb-3">
                    Add Location
                </h2>

                <form method="POST" enctype="multipart/form-data">

                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">


                    <select name="geo_type" class="border w-full mb-2 p-2 rounded">

                        <option value="MARK">Point</option>
                        <option value="ADDRESS">Address</option>

                    </select>


                    <textarea name="description" placeholder="Describe location"
                        class="border w-full mb-2 p-2 rounded"></textarea>

                    <div class="file-box mb-2">
                        <input type="file" id="file1" name="image_1" class="hidden">
                        <label for="file1"
                            class="cursor-pointer bg-slate-200 px-4 py-2 rounded-lg text-sm">
                            Upload Image 1
                        </label>
                    </div>

                    <div class="file-box mb-2">
                        <input type="file" id="file2" name="image_2" class="hidden">
                        <label for="file2"
                            class="cursor-pointer bg-slate-200 px-4 py-2 rounded-lg text-sm">
                            Upload Image 2
                        </label>
                    </div>



                    <div class="flex justify-end gap-2">

                        <button type="button" onclick="document.getElementById('locationModal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-300 rounded">

                            Cancel

                        </button>


                        <button type="submit" name="add_location" class="px-4 py-2 bg-green-600 text-white rounded">

                            Save

                        </button>


                    </div>

                </form>

            </div>

        </div>

    </div>
    </div>


    </main>

    </div>


</body>
<!-- =========================
MAP SCRIPT
========================= -->

<script>
    var map = L.map('map').setView([23.0225, 72.5714], 13); // Ahmedabad


    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);



    /* =========================
    LOAD MARKERS FROM DATABASE
    ========================= */

    <?php

    $locations->data_seek(0);

    while ($loc = $locations->fetch_assoc()) {

        echo "

L.marker([" . $loc['latitude'] . "," . $loc['longitude'] . "])
.addTo(map)
.bindPopup(`
<b>" . $loc['name'] . "</b><br>
" . $loc['description'] . "<br>
" . $loc['geo_type'] . "
`);

";
    }

    ?>



    /* =========================
    CLICK MAP → ADD LOCATION
    ========================= */

    map.on('click', function(e) {

        var lat = e.latlng.lat;
        var lng = e.latlng.lng;

        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('locationModal').classList.remove("hidden");

    });

    function reportLocation(id) {

        let reason = prompt("Report reason:");

        if (!reason) return;

        fetch("location.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "report_location=1&geo_id=" + id + "&reason=" + encodeURIComponent(reason)
            })
            .then(res => res.text())
            .then(data => {
                alert("Location reported. Admin will review.");
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

</html>