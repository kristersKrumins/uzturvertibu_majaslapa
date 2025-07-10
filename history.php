<?php
require 'Functions/Auth.php';
require 'Database/db.php';
require 'header.php';
checkAuth();
$userid = $_SESSION['id'];

function getLatvianDate($date) {
    $months = [
        "January" => "Janvāris",
        "February" => "Februāris",
        "March" => "Marts",
        "April" => "Aprīlis",
        "May" => "Maijs",
        "June" => "Jūnijs",
        "July" => "Jūlijs",
        "August" => "Augusts",
        "September" => "Septembris",
        "October" => "Oktobris",
        "November" => "Novembris",
        "December" => "Decembris"
    ];

    $day = date("j", strtotime($date));
    $month = date("F", strtotime($date)); // Full English month name
    $year = date("Y", strtotime($date));

    return "$day. " . $months[$month] . ", $year";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM food WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "Record deleted successfully";
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view'])) {
    $_SESSION['currentMenu'] = $_POST['view_id'];
    header('Location: menu.php');
    exit();
}
// Optimālie llīmeņi:
$userid = $_SESSION['id'];
$sql = "SELECT KALORIJAS, OLBALTUMVIELAS, TAUKI, TAUKSKABES, OGLHIDRATI, SALS, CUKURS FROM users WHERE ID=$userid";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $calorieGoal = $row['KALORIJAS'];
    $proteinGoal = $row['OLBALTUMVIELAS'];
    $fatGoal = $row['TAUKI'];
    $acidsGoal = $row['TAUKSKABES'];
    $carbGoal = $row['OGLHIDRATI'];
    $saltGoal = $row['SALS'];
    $sugarGoal = $row['CUKURS'];
} else {
    // Handle the case where no user data is found
    $calorieGoal = $proteinGoal = $fatGoal = $acidsGoal = $carbGoal = $saltGoal = $sugarGoal = 0;
    echo "<p class='error-msg'>Nav pieejami optimālie līmeņi. Lūdzu, atjaunojiet profilu.</p>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Fonts and Stylesheets -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/header.css">
    <link rel="stylesheet" href="CSS/history.css">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/2ae46db69b.js" crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ēdienkartes Optimizēšana</title>
</head>
<body>
    <?php
    renderheader();
    ?>
    <script>
function createNutrientBar(barId, consumed, goal) {
    let bar = document.getElementById(barId);
    if (consumed === null || goal === null || goal === 0 || isNaN(goal)) {
        bar.innerHTML = '<div class="bar-segment" style="width:100%; background-color:gray;">Nav pieejami dati</div>';
        return;
    }

    consumed = Math.round(consumed);
    goal = Math.round(goal);

    let consumedPercentage = Math.min(100, (consumed / goal) * 100); // Cap at 100%
    let exceeded = 0;
    let needed = 0;

    if (consumed > goal) {
        exceeded = Math.round(consumed - goal);
        let exceededPercentage = (exceeded / goal) * 100;  // Calculate exceeded percentage relative to goal

        // Set a maximum width for the exceeded bar (adjust as needed)
        exceededPercentage = Math.min(exceededPercentage, 50); // Example: Max 50% width
        
        bar.innerHTML = `
            <div class="bar-segment" style="width:${consumedPercentage}%; background-color:green; display: flex; justify-content: flex-start; /* Align text to the start (left) */">
                <span style="margin-left: 25%;">${consumed-exceeded}</span> </div>
            <div class="bar-segment" style="width:${exceededPercentage}%; background-color:#fac350; position: absolute; left: 100%; margin-left: -${exceededPercentage}%;">+${exceeded}</div>
        `;

    } else {
        needed = Math.round(goal - consumed);
        let neededPercentage = (needed / goal) * 100;
        bar.innerHTML = `
            <div class="bar-segment" style="width:${consumedPercentage}%; background-color:green;">${consumed}</div>
            <div class="bar-segment" style="width:${neededPercentage}%; background-color:red;">-${needed}</div>
        `;
    }
}

</script>
    <main>
    <div class="title-container">
        <h1>Ēdienkartes vēsture</h1>
    </div>
    <div class="main-container">
    <?php
    $sql = "SELECT * FROM food WHERE USERS_ID=$userid ORDER BY DATE_CREATED DESC"; // Order by date created
    $result = mysqli_query($conn, $sql);

    $menus_by_date = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $date = date('Y-m-d', strtotime($row['DATE_CREATED']));
            if (!isset($menus_by_date[$date])) {
                $menus_by_date[$date] = [
                    'menus' => [],
                    'total_calories' => 0,
                    'total_protein' => 0,
                    'total_fat' => 0,
                    'total_carbohydrates' => 0,
                    'total_sugar' => 0,
                    'total_salt' => 0,
                    'total_price' => 0
                ];
            }
            $menus_by_date[$date]['menus'][] = $row;
            $menus_by_date[$date]['total_calories'] += $row['TOTAL_CALORIES'];
            $menus_by_date[$date]['total_protein'] += $row['TOTAL_PROTEIN'];
            $menus_by_date[$date]['total_fat'] += $row['TOTAL_FAT'];
            $menus_by_date[$date]['total_carbohydrates'] += $row['TOTAL_CARBOHYDRATES'];
            $menus_by_date[$date]['total_sugar'] += $row['TOTAL_SUGAR'];
            $menus_by_date[$date]['total_salt'] += $row['TOTAL_SALT'];
            $menus_by_date[$date]['total_price'] += $row['TOTAL_PRICE'];
        }
        foreach ($menus_by_date as $date => $data) {
            $uniqueId = uniqid();
            echo "<div class='date-container'>";
            echo "<div class='main-info'>";
            echo "<h2>" . getLatvianDate($date) . "</h2>";
            echo "<div class='daily-summary'>";
            
            echo '
            <div class="total-price-container">
                <h2>Cena</h2><span>'.$data['total_price'].' &euro;</span>
            </div>
        <div class="nutrient-group">
            <div class="nutrient-title">
                <h2>Kopējās Kalorijas</h2>
                <span>' . $data['total_calories'] . ' kcal</span>
            </div>
            <div id="calorie-bar-' . $uniqueId . '" class="calorie-bar"></div>
        </div>
        
        <div class="nutrient-group">
            <div class="nutrient-title">
                <h2>Kopējās Olabaltumvielas</h2>
                <span>' . $data['total_protein'] . ' g</span>
            </div>
            <div id="protein-bar-' . $uniqueId . '" class="calorie-bar"></div>
        </div>
        
        <div class="nutrient-group">
            <div class="nutrient-title">
                <h2>Kopējie Tauki</h2>
                <span>' . $data['total_fat'] . ' g</span>
            </div>
            <div id="fat-bar-' . $uniqueId . '" class="calorie-bar"></div>
        </div>
        
        <div class="nutrient-group">
            <div class="nutrient-title">
                <h2>Kopējie Ogļhidrāti</h2>
                <span>' . $data['total_carbohydrates'] . ' g</span>
            </div>
            <div id="carb-bar-' . $uniqueId . '" class="calorie-bar"></div>
        </div>
        
        <div class="nutrient-group">
            <div class="nutrient-title">
                <h2>Kopējais Sāls</h2>
                <span>' . $data['total_salt'] . ' g</span>
            </div>
            <div id="salt-bar-' . $uniqueId . '" class="calorie-bar"></div>
        </div>
        
        <div class="nutrient-group">
            <div class="nutrient-title">
                <h2>Kopējais Cukurs</h2>
                <span>' . $data['total_sugar'] . ' g</span>
            </div>
            <div id="sugar-bar-' . $uniqueId . '" class="calorie-bar"></div>
        </div>
        ';
            echo "<script>
                createNutrientBar('calorie-bar-$uniqueId', {$data['total_calories']}, $calorieGoal);
                createNutrientBar('protein-bar-$uniqueId', {$data['total_protein']}, $proteinGoal);
                createNutrientBar('fat-bar-$uniqueId', {$data['total_fat']}, $fatGoal);
                createNutrientBar('carb-bar-$uniqueId', {$data['total_carbohydrates']}, $carbGoal);
                createNutrientBar('salt-bar-$uniqueId', {$data['total_salt']}, $saltGoal);
                createNutrientBar('sugar-bar-$uniqueId', {$data['total_sugar']}, $sugarGoal);
            </script>";
            echo "</div>
            <hr>
            <h1>Ēdienkartes:</h1>
            </div>";

            foreach ($data['menus'] as $menu) {
                echo "
                <div class='food-container'>
                    <h3>Ēdienkarte " . $menu['EDIENKARTE_NR'] . "</h3> 
                    <div class='nutrient-info'>
                        <p><strong>Kalorijas:</strong> " . $menu['TOTAL_CALORIES'] . " kcal</p>
                        <p><strong>Olbaltumvielas:</strong> " . $menu['TOTAL_PROTEIN'] . " g</p>
                        <p><strong>Tauki:</strong> " . $menu['TOTAL_FAT'] . "g</p>
                        <p><strong>Ogļbidrāti:</strong> " . $menu['TOTAL_CARBOHYDRATES'] . " g</p>
                        <p><strong>Cukurs:</strong> " . $menu['TOTAL_SUGAR'] . " g</p>
                        <p><strong>Sāls:</strong> " . $menu['TOTAL_SALT'] . " g</p>
                        <p><strong>Cena:</strong> " . $menu['TOTAL_PRICE'] . " €</p>
                    </div>
                    <p class='date'>Izveidots: " . getLatvianDate($menu['DATE_CREATED']) . "</p> 
                    <form class='view-food' action='history.php' method='Post'>
                        <input type='hidden' name='view_id' value='" . $menu['ID'] . "'>
                        <button type='submit' name='view'>Apskatīt</button>
                    </form>
                    <form class='delete-food' method='post' action=''>
                        <input type='hidden' name='delete_id' value='" . $menu['ID'] . "'>
                        <button type='submit' name='delete'>Dzēst</button>
                    </form>
                </div>";
            }
            echo "</div>";
        }
    } else {
        echo "<p class='no-menus'>No menus created yet.</p>";
    }
    ?>
    </div>
    </main>
    
</body>
</html>
