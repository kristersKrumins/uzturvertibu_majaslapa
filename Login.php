<?php
session_start();
require './Database/db.php';

$error_message = "";

// Check if the form has been submitted
if (isset($_POST["submit"])) {
    // Make sure both fields were sent
    if (isset($_POST["username"]) && isset($_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        // Query to get the user by username
        $sql = "SELECT * FROM users WHERE USERNAME='$username'";
        $result = mysqli_query($conn, $sql);

        // If user is found, verify password
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            // Verify the hashed password
            if (password_verify($password, $row["PASSWORD"])) {
                // Password is correct, store user id in session and redirect
                $_SESSION["id"] = $row["ID"];
                header('Location: index.php');
                exit;
            } else {
                // Password is incorrect
                $error_message = "Nepareiza parole.";
            }
        } else {
            // Username does not exist in the database
            $error_message = "Lietotājvārds vai parole nav pareiza.";
        }
    } else {
        $error_message = "Lūdzu, ievadiet lietotājvārdu un paroli.";
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pierakstīties</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <!-- Left Side: Login Form -->
            <div class="form-section">
                <h2>Pierakstīties</h2>
                
               

                <form action="Login.php" method="POST">
                    <label for="username">Lietotājvārds</label>
                    <input type="text" id="username" name="username" placeholder="Jūsu lietotājvārds" required>
                    
                    <label for="password">Parole</label>
                    <input type="password" id="password" name="password" placeholder="Jūsu parole" required>

                    <!-- Display error message if there is one -->
                 <?php if (!empty($error_message)) : ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                    <input type="submit" name="submit" value="Pierakstīties">
                </form>

                 
            </div>
            
            <!-- Right Side: Welcome Section -->
            <div class="welcome-section">
                <h2>Sveicināti pierakstīšanās lapā</h2>
                <p>Vēl nav izveidots konts ?</p>
                <a href="SignUp.php" class="sign-up-btn">Reģistrējies</a>
            </div>
        </div>
    </div>
</body>
</html>
