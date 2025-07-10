<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link 
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" 
        rel="stylesheet"
    >
    <link rel="stylesheet" href="CSS/signup.css">
    <title>Sign Up</title>
</head>
<body>
    <div class="signup-container">
        <h2>Reģistrācija</h2>
        <form action="SignUp.php" method="POST">
            <label for="username">Lietotājvārds</label>
            <input 
                type="text" 
                id="username" 
                name="username" 
                placeholder="Jūsu lietotājvārds"
            >
            
            <label for="password">Parole</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                placeholder="Jūsu parole"
            >
            
            <label for="repassword">Atkārtot paroli</label>
            <input 
                type="password" 
                id="repassword" 
                name="repassword" 
                placeholder="Atkārtot paroli"
            >
            
            <button type="submit" name="submit">Reģistrēties</button>
        </form>

        <!-- New "Already have an account?" row -->
        <div class="already-have-account">
            <span>Jau ir konts?</span>
            <a href="Login.php">Pieraksties</a>
        </div>

        <div class="notification">
            <?php
            require './Database/db.php';

            if (isset($_POST["submit"])) {
                if (!empty($_POST["username"]) && !empty($_POST["password"]) && !empty($_POST["repassword"])) {
                    $username = $_POST["username"];
                    $password = $_POST["password"];
                    $repassword = $_POST["repassword"];

                    if ($password !== $repassword) {
                        echo "Paroles nesakrīt";
                        return;
                    }

                    // Check if the username already exists
                    $sql = "SELECT * FROM users WHERE USERNAME='$username'";
                    $result = mysqli_query($conn, $sql);

                    if (!$result) {
                        echo "Query error: " . mysqli_error($conn);
                        return;
                    }

                    if (mysqli_num_rows($result) > 0) {
                        echo "Lietotājvārds jau eksistē";
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql_insert = "INSERT INTO users (USERNAME, PASSWORD) VALUES ('$username', '$hash')";

                        if (mysqli_query($conn, $sql_insert)) {
                            echo "Reģistrācija veiksmīga!";
                            mysqli_close($conn);
                            header('Location: Login.php');
                            exit;
                        } else {
                            echo "Nevarēja reģistrēties: " . mysqli_error($conn);
                        }
                    }
                } else {
                    echo "Aizpildiet visus lauciņus";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
