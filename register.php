<?php
session_start();
include "admin/config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $password = $_POST['password'];
    $re_password = $_POST['re_password'];
    $role = $_POST['role'];

    // Password match check
    if ($password !== $re_password) {
        $error = "Passwords do not match!";
    } else {

        // Check duplicate email
        $checkQuery = "SELECT * FROM accounts WHERE email='$email'";
        $checkResult = mysqli_query($conn, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            $error = "Email already registered!";
        } else {

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Default verification status
            $verifiedStatus = ($role == "ngo") ? "Pending" : "Verified";

/* =============================
   FILE UPLOAD SECTION
=============================*/

            // Set document folder
            $uploadDir = "uploads/doc/";

            // Create folder if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $doc1Name = "";
            $doc2Name = "";

            // Upload Document 1
            if (!empty($_FILES['file1']['name'])) {

                $ext1 = pathinfo($_FILES['file1']['name'], PATHINFO_EXTENSION);
                $doc1Name = "doc1_" . time() . "_" . rand(1000, 9999) . "." . $ext1;

                move_uploaded_file($_FILES['file1']['tmp_name'], $uploadDir . $doc1Name);
            }

            // Upload Document 2
            if (!empty($_FILES['file2']['name'])) {

                $ext2 = pathinfo($_FILES['file2']['name'], PATHINFO_EXTENSION);
                $doc2Name = "doc2_" . time() . "_" . rand(1000, 9999) . "." . $ext2;

                move_uploaded_file($_FILES['file2']['tmp_name'], $uploadDir . $doc2Name);
            }

            /* =============================
               INSERT INTO DATABASE
            ==============================*/

            $insertQuery = "INSERT INTO accounts
            (account_type, name, email, contact_no, password, address, verified_status, document_1, document_2)
            VALUES
            ('$role', '$name', '$email', '$contact', '$hashedPassword', '$address', '$verifiedStatus', '$doc1Name', '$doc2Name')";

            if (mysqli_query($conn, $insertQuery)) {

                // Auto login after register
                $newUserId = mysqli_insert_id($conn);

                $_SESSION['account_id'] = $newUserId;
                $_SESSION['account_type'] = $role;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;

                header("Location: index.php");
                exit();
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kind Aid Register</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/main-style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <img src="assets/logo(1).png">
        <br>

        <?php if ($error != ""): ?>
            <p style="color:red; margin-bottom:15px;">
                <?php echo $error; ?>
            </p><?php endif; ?>

        <br>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <input type="text" name="name" placeholder="Enter Name" required>
            </div>

            <div class="form-group">
                <input type="text" name="contact" placeholder="Enter Contact" required>
            </div>

            <div class="form-group">
                <input type="email" name="email" placeholder="Enter Email" required>
            </div>

            <div class="form-group">
                <input type="text" name="address" placeholder="Enter Address" required>
            </div>

            <div class="form-group">
                <input type="password" name="password" placeholder="Enter Password" required>
            </div>

            <div class="form-group">
                <input type="password" name="re_password" placeholder="Re Enter Password" required>
            </div>

            <!-- Role Selection -->
            <div class="role-selection">

                <label class="role-option">
                    <input type="radio" name="role" value="user">
                    <span>User</span>
                </label>

                <label class="role-option">
                    <input type="radio" name="role" value="ngo" checked>
                    <span>NGO</span>
                </label>

            </div>


            <!-- File Upload Section -->
            <div class="file-upload-grid">

                <div class="file-box">
                    <input type="file" id="file1" name="file1">
                    <label for="file1">File 1</label>
                </div>

                <div class="file-box">
                    <input type="file" id="file2" name="file2">
                    <label for="file2">File 2</label>
                </div>

            </div>

            <button type="submit" class="btn">Register</button>

        </form>


        <a href="login.php" class="forget-pass">Already have an account? Login</a>

    </div>

</body>

<script>
    document.querySelectorAll('.file-box input').forEach(input => {
        input.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.files.length > 0) {
                label.innerHTML = "Uploaded ✓";
                label.style.backgroundColor = "var(--accent)";
            }
        });
    });
</script>

</html>