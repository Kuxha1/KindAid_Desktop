<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= SUPERADMIN CHECK ========================= */
if ($_SESSION['role'] != 'SuperAdmin') {
    header("Location: dashboard.php");
    exit();
}

/* ========================= CREATE ADMIN ========================= */
if (isset($_POST['create_admin'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $check = $conn->query("SELECT * FROM admins WHERE email='$email'");
    if ($check->num_rows > 0) {
        $formError = "An account with this email already exists.";
    } else {
        $conn->query("INSERT INTO admins (name,email,password,role) VALUES ('$name','$email','$password','$role')");
        header("Location: admin_data.php");
        exit();
    }
}

/* ========================= DELETE ADMIN ========================= */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id != $_SESSION['admin_id']) {
        $conn->query("DELETE FROM admins WHERE admin_id=$id");
    }
    header("Location: admin_data.php");
    exit();
}

/* ========================= UPDATE ADMIN ========================= */
if (isset($_POST['update_admin'])) {
    $id = (int) $_POST['admin_id'];
    $role = $_POST['role'];
    $conn->query("UPDATE admins SET role='$role' WHERE admin_id=$id");
    header("Location: admin_data.php");
    exit();
}

/* ========================= SEARCH ========================= */
$search = "";
$where = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $where = "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR role LIKE '%$search%'";
}
$admins = $conn->query("SELECT * FROM admins $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Manage Admins</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
</head>

<body>

    <?php include "includes/sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "includes/header.php"; ?>

        <div class="table-container">

            <div class="table-title">
                <h2><i class="fas fa-user-shield"></i> Manage Admins</h2>
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Name / Email / Role"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <!-- CREATE ADMIN FORM -->
            <div class="create-admin">
                <h3><i class="fas fa-plus-circle"></i> Create New Admin</h3>

                <?php if (!empty($formError)) { ?>
                    <div style="background:var(--error-soft); color:var(--error-dark); padding:9px 14px;
                            border-radius:8px; font-size:0.85rem; margin-bottom:14px;">
                        <i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($formError); ?>
                    </div>
                <?php } ?>

                <form method="POST" class="admin-form">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="role" required>
                        <option value="Moderator">Moderator</option>
                        <option value="SuperAdmin">SuperAdmin</option>
                    </select>
                    <button class="btn-view" type="submit" name="create_admin">
                        <i class="fas fa-plus"></i> Create Admin
                    </button>
                </form>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $admins->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?php echo $row['admin_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php if ($row['role'] == 'SuperAdmin')
                                        echo "<span class='badge status-verified'>SuperAdmin</span>";
                                    else
                                        echo "<span class='badge status-pending'>Moderator</span>";
                                    ?>
                                </td>
                                <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td>
                                    <form method="POST" class="edit-form-inline">
                                        <input type="hidden" name="admin_id" value="<?php echo $row['admin_id']; ?>">
                                        <select name="role">
                                            <option value="Moderator" <?php if ($row['role'] == 'Moderator')
                                                echo 'selected'; ?>>Moderator</option>
                                            <option value="SuperAdmin" <?php if ($row['role'] == 'SuperAdmin')
                                                echo 'selected'; ?>>SuperAdmin</option>
                                        </select>
                                        <button class="btn-view" type="submit" name="update_admin"
                                            style="padding:5px 10px; font-size:0.8rem;">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <?php if ($row['admin_id'] != $_SESSION['admin_id']) { ?>
                                        <a class="btn-delete" href="?delete=<?php echo $row['admin_id']; ?>"
                                            onclick="return confirm('Delete this admin?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php } else { ?>
                                        <span class="badge" style="background:var(--primary-soft); color:var(--primary-dark);">
                                            <i class="fas fa-user"></i> You
                                        </span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div><!-- /table-scroll -->
        </div><!-- /table-container -->
    </div><!-- /main-wrapper -->

</body>

</html>