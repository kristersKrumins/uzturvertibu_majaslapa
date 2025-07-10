<?php
require 'Functions/Auth.php';
require 'Database/db.php';
require 'header.php';
checkAuth();

/*********************************************************************
 * 1) CREATE A NEW MENU (ĒDIENKARTE) WITH PER-USER COUNTER
 *********************************************************************/
if (isset($_POST['newMenu'])) {
    $userID = $_SESSION['id'];
    $food_id = $_SESSION['currentMenu'];

    // 1. Fetch the user's next ēdienkarte number
    $sqlGetEdien = "SELECT EDIENKARTE_NR
                    FROM food
                    WHERE ID = $food_id";
    $resultEdien = mysqli_query($conn, $sqlGetEdien);
    $rowEdien    = mysqli_fetch_assoc($resultEdien);

    // If the user’s EDIENKARTES is NULL or <1, default to 1
    $menu_num = $rowEdien['EDIENKARTE_NR'];
    $menu_num = $menu_num + 1;

    // 2. Insert into food, storing USERS_ID and EDIENKARTE_NR
    // Make sure your "food" table has a column "EDIENKARTE_NR" (INT).
    $sql = "INSERT INTO food (USERS_ID, EDIENKARTE_NR)
            VALUES ($userID, $menu_num)";
    if (mysqli_query($conn, $sql)) {
        $newMenuId = mysqli_insert_id($conn);
        $_SESSION['currentMenu'] = $newMenuId;
        // Redirect
        header("Location: index.php");
        exit();
    }
}


/*********************************************************************
 * 3) DELETE PRODUCT FROM THE MENU
 *********************************************************************/
if (isset($_POST['delete'])) {
    $productID = $_POST['delete'];
    $menuid    = $_SESSION['currentMenu'];

    // Prepare the SQL DELETE query
    $sql = "DELETE FROM food_products
            WHERE FOOD_ID    = ?
              AND PRODUCT_ID = ?";

    // Prepare and execute the statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $menuid, $productID);
        if ($stmt->execute()) {
            header("Location: menu.php#product_section");
            exit();
        } else {
            echo "Error deleting product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

/*********************************************************************
 * 4) AUTOFILL VALUES FROM USER PROFILE
 *********************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fill_recommended'])) {
    $userID = $_SESSION['id'];
    $sql = "SELECT KALORIJAS, OLBALTUMVIELAS, TAUKI, TAUKSKABES,
                   OGLHIDRATI, SALS, CUKURS
            FROM users
            WHERE ID = $userID";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row       = mysqli_fetch_assoc($result);
        $calories  = $row['KALORIJAS'];
        $protein   = $row['OLBALTUMVIELAS'];
        $fat       = $row['TAUKI'];
        $fatacids  = $row['TAUKSKABES'];
        $carb      = $row['OGLHIDRATI'];
        $salt      = $row['SALS'];
        $sugar     = $row['CUKURS'];

        // Redirect with these DB values as GET parameters
        header("Location: menu.php?calories=$calories&protein=$protein&fat=$fat&fatacids=$fatacids&carb=$carb&salt=$salt&sugar=$sugar#menu_settings");
        exit();
    }
}

/*********************************************************************
 * 5) READ MINIMUM VALUES FROM GET (IF ANY)
 *********************************************************************/
$calories = isset($_GET['calories']) ? $_GET['calories'] : 0;
$protein  = isset($_GET['protein'])  ? $_GET['protein']  : 0;
$fat      = isset($_GET['fat'])      ? $_GET['fat']      : 0;
$fatacids = isset($_GET['fatacids']) ? $_GET['fatacids'] : 0;
$carb     = isset($_GET['carb'])     ? $_GET['carb']     : 0;
$salt     = isset($_GET['salt'])     ? $_GET['salt']     : 0;
$sugar    = isset($_GET['sugar'])    ? $_GET['sugar']    : 0;

$edienkarte_nr='none';
$sql = "SELECT EDIENKARTE_NR FROM food WHERE USERS_ID={$_SESSION['id']} AND ID={$_SESSION['currentMenu']}";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $edienkarte_nr = $row['EDIENKARTE_NR'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/header.css">
    <link rel="stylesheet" href="CSS/menu.css">
    <script src="https://kit.fontawesome.com/2ae46db69b.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php
    renderheader();
    ?>
    <!-- The error modal (if optimization fails) -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Optimizācijas kļūda</h2>
            <p>Nevar sasniegt rezultātu ar iestatītajiem ierobežojumiem</p>
            <div>
                <button id="closeModalBtn">OK</button>
            </div>
        </div>
    </div>

    <main>
        <div class="container-newMenu">
            <?php 
            // Display the current menu or fallback
            if (isset($_SESSION['currentMenu'])) {
                // If we have the user's ēdienkarte number stored
                if (isset($edienkarte_nr)) {
                    echo "<h1>Ēdienkarte <span>".$edienkarte_nr."</span></h1>";
                } else {
                    // Fallback: show the food.ID
                    echo "<h1>Ēdienkarte <span>".$_SESSION['currentMenu']."</span></h1>";
                }

                echo '<form action="menu.php" method="POST" class="new-menu-button-container">
                        <button type="submit" name="newMenu">Izveidot Jaunu Ēdienkarti</button>
                      </form>';
            } else {
                echo "<h1 class='error-msg'>Nav izvēlēta vai izveidota Ēdienkarte</h1>";
                echo '<form action="menu.php" method="POST" class="new-menu-button-container">
                        <button type="submit" name="newMenu">Izveidot Ēdienkarti</button>
                      </form>';
            }
            ?>
        </div>

        <?php
        // If we have a current menu, we might show the "Optimizēšanas iestatījumi" if there are products
        if (isset($_SESSION['currentMenu'])) {
            $sql = "SELECT p.ID, p.NAME, p.CALORIES, p.FAT, p.ACIDS, p.CARBOHYDRATES,
                           p.SUGAR, p.PROTEIN, p.SALT, p.PRICE, p.PICTUREID, fp.QUANTITY, fp.MIN_QUANTITY
                    FROM food_products fp
                    INNER JOIN products p ON fp.PRODUCT_ID = p.ID
                    WHERE fp.FOOD_ID = {$_SESSION['currentMenu']}";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                // If we have products, show the optimization form
                echo '
                <div class="Menu-container">
                    <div class="menu-edit" id="menu_settings">
                        <h3>Optimizēšanas iestatījumi</h3>';

                // Let them fill recommended from user’s profile (if user’s data exists)
                $userID = $_SESSION['id'];
                $sqlRec = "SELECT * FROM users WHERE ID = $userID";
                $resRec = mysqli_query($conn, $sqlRec);
                if ($resRec) {
                    $rowRec = mysqli_fetch_assoc($resRec);
                    if (isset($rowRec['KALORIJAS'])) {
                        echo '
                        <div class="buttons-top">
                            <form action="menu.php" method="POST">
                                <button type="submit" name="fill_recommended">Aizpildīt ar Ieteicamiem</button>
                            </form>
                        </div>';
                    } else {
                        echo '<div class="button-space"></div>';
                    }
                }

                echo '
                <div class="edit-value-container">
                    <form action="Functions/optimize.php" method="POST">
                        <div class="values-container">
                            <div class="value">
                                <label for="min_calories">Kaloriju minimums</label>
                                <input id="min_calories" name="min_calories" type="number" min="0" step="any"
                                       value="' . $calories . '">
                            </div>
                            <div class="value">
                                <label for="min_protein">Obaltumvielu minimumu</label>
                                <input id="min_protein" name="min_protein" type="number" min="0" step="any"
                                       value="' . $protein . '">
                            </div>
                            <div class="value">
                                <label for="min_fat">Tauku minimumu</label>
                                <input id="min_fat" name="min_fat" type="number" min="0" step="any"
                                       value="' . $fat . '">
                            </div>
                            <div class="value">
                                <label for="min_acids">Piesātinātās taukuskābes minimumu</label>
                                <input id="min_acids" name="min_acids" type="number" min="0" step="any"
                                       value="' . $fatacids . '">
                            </div>
                            <div class="value">
                                <label for="min_carbs">Ogļhidrātu minimumu</label>
                                <input id="min_carbs" name="min_carbs" type="number" min="0" step="any"
                                       value="' . $carb . '">
                            </div>
                            <div class="value">
                                <label for="min_salt">Sāls minimumu</label>
                                <input id="min_salt" name="min_salt" type="number" min="0" step="any"
                                       value="' . $salt . '">
                            </div>
                            <div class="value">
                                <label for="min_sugar">Cukuru minimumu</label>
                                <input id="min_sugar" name="min_sugar" type="number" min="0" step="any"
                                       value="' . $sugar . '">
                            </div>
                        </div>
                        <div class="Menu-optimize">
                            <button type="submit" name="optimize-menu">Optimizēt ēdienkarti</button>
                        </div>
                    </form>
                </div>
            </div>';
            }
        }
        ?>

        <!-- END OF EDIT VALUES -->

        <?php
        /*********************************************************************
         * 6) SHOW THE MENU SUMMARY (CALORIES, PRICE, ETC.)
         *********************************************************************/
        if (isset($_SESSION['currentMenu'])) {
            $sql = "SELECT p.ID, p.NAME, p.ACIDS, p.CALORIES, p.PROTEIN, p.PRICE,
                           p.SUGAR, p.FAT, p.CARBOHYDRATES, p.SALT, fp.QUANTITY
                    FROM food_products fp
                    INNER JOIN products p ON fp.PRODUCT_ID = p.ID
                    WHERE fp.FOOD_ID = {$_SESSION['currentMenu']}";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                $consumedCalories = 0;
                $consumedProtein  = 0;
                $totalPrice       = 0;
                $consumedFat      = 0;
                $consumedFatacids = 0;
                $consumedCarb     = 0;
                $consumedSalt     = 0;
                $consumedSugar    = 0;

                while($row = mysqli_fetch_assoc($result)) {
                    $calories = (float)$row['CALORIES'];
                    $protein  = (float)$row['PROTEIN'];
                    $price    = (float)$row['PRICE'];
                    $quantity = (float)$row['QUANTITY'];
                    $sugar    = (float)$row['SUGAR'];
                    $fat      = (float)$row['FAT'];
                    $acids    = (float)$row['ACIDS'];
                    $carb     = (float)$row['CARBOHYDRATES'];
                    $salt     = (float)$row['SALT'];

                    $consumedCalories += round($calories * $quantity);
                    $consumedProtein  += round($protein  * $quantity);
                    $totalPrice       += round($price    * $quantity, 2);
                    $consumedFat      += round($fat      * $quantity);
                    $consumedFatacids += round($acids    * $quantity);
                    $consumedCarb     += round($carb     * $quantity);
                    $consumedSalt     += round($salt     * $quantity);
                    $consumedSugar    += round($sugar    * $quantity);
                }

                // Now compare to user’s recommended values
                $userID = $_SESSION['id'];
                $sql    = "SELECT * FROM users WHERE ID = $userID";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    echo '<div class="result-container" id="result_section">
                            <div class="result-title-container">
                                <h1>Ēdienkartes Rezultāti</h1>
                            </div>
                            <div class="result">';

                    $row             = mysqli_fetch_assoc($result);
                    $optimalcalories = (float)$row['KALORIJAS'];
                    $optimalprotein  = (float)$row['OLBALTUMVIELAS'];
                    $optimalfat      = (float)$row['TAUKI'];
                    $optimalfatacids = (float)$row['TAUKSKABES'];
                    $optimalcarb     = (float)$row['OGLHIDRATI'];
                    $optimalsalt     = (float)$row['SALS'];
                    $optimalsugar    = (float)$row['CUKURS'];

                    if ($optimalcalories == null || $optimalcalories == 0) {
                        echo "<p class='error-msg'>Atjaunojiet profilu lai redzētu ieteicamos līmeņus</p>";
                    }

                    echo '
                            <div class="total-price-container">
                                <h2>Cena</h2><span>'.$totalPrice.' &euro;</span>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Kalorijas</h2>
                                    <span>' . $consumedCalories . ' kcal</span>
                                </div>
                                <div id="calorie-bar" class="calorie-bar"></div>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Olabaltumvielas</h2>
                                    <span>' . $consumedProtein . ' g</span>
                                </div>
                                <div id="protein-bar" class="calorie-bar"></div>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Tauki</h2>
                                    <span>' . $consumedFat . ' g</span>
                                </div>
                                <div id="fat-bar" class="calorie-bar"></div>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Piesātinātās taukskābes</h2>
                                    <span>' . $consumedFatacids . ' g</span>
                                </div>
                                <div id="acids-bar" class="calorie-bar"></div>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Ogļhidrāti</h2>
                                    <span>' . $consumedCarb . ' g</span>
                                </div>
                                <div id="carb-bar" class="calorie-bar"></div>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Sāls</h2>
                                    <span>' . $consumedSalt . ' g</span>
                                </div>
                                <div id="salt-bar" class="calorie-bar"></div>
                            </div>
                            <div class="nutrient-group">
                                <div class="nutrient-title">
                                    <h2>Cukurs</h2>
                                    <span>' . $consumedSugar . ' g</span>
                                </div>
                                <div id="sugar-bar" class="calorie-bar"></div>
                            </div>
                        </div>
                    </div>';

                    // Finally, update the totals in the food table
                    $menuID = $_SESSION['currentMenu'];
                    $updateFoodSql = "UPDATE food
                                      SET TOTAL_CALORIES       = $consumedCalories,
                                          TOTAL_PROTEIN        = $consumedProtein,
                                          TOTAL_PRICE          = $totalPrice,
                                          TOTAL_FAT            = $consumedFat,
                                          TOTAL_FAT_ACIDS      = $consumedFatacids,
                                          TOTAL_CARBOHYDRATES  = $consumedCarb,
                                          TOTAL_SALT           = $consumedSalt,
                                          TOTAL_SUGAR          = $consumedSugar
                                      WHERE ID = $menuID";
                    mysqli_query($conn, $updateFoodSql);
                }
            }
        }
        ?>

        <?php
        /*********************************************************************
         * 7) SHOW THE LIST OF PRODUCTS IN THE MENU + PIE CHART
         *********************************************************************/
        if (isset($_SESSION['currentMenu'])) {
            $sql = "SELECT p.ID, p.NAME, p.CALORIES, p.FAT, p.ACIDS, p.CARBOHYDRATES,
                           p.SUGAR, p.PROTEIN, p.SALT, p.PRICE, p.PICTUREID,
                           fp.QUANTITY, fp.MIN_QUANTITY, fp.MAX_QUANTITY
                    FROM food_products fp
                    INNER JOIN products p ON fp.PRODUCT_ID = p.ID
                    WHERE fp.FOOD_ID = {$_SESSION['currentMenu']}
                    ORDER BY QUANTITY DESC";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                echo '
                <div class="Product-pie-contaier">
                    <h1>Ēdienkartes Produktu Daudzums</h1>
                    <h3>Kopējais daudzums: <span id="quantity_sum"></span></h3>
                    <div class="pie-container">
                        <canvas id="productPieChart"></canvas>
                    </div>
                </div>

                <form class="product-container-main" id="product_section" action="menu.php" method="POST">
                    <div class="menu-component-title">
                        <h2>Ēdienkartes Produkti</h2>
                        <div class="save-btn-container">
                            <button type="submit" name="update">Saglabāt izamiņas</button>
                        </div>
                    </div>
                ';

                while($row = mysqli_fetch_assoc($result)){
                    $calories     = (float)$row['CALORIES'];
                    $protein      = (float)$row['PROTEIN'];
                    $price        = (float)$row['PRICE'];
                    $quantity     = (float)$row['QUANTITY'];
                    $sugar        = (float)$row['SUGAR'];
                    $fat          = (float)$row['FAT'];
                    $carb         = (float)$row['CARBOHYDRATES'];
                    $salt         = (float)$row['SALT'];
                    $acids        = (float)$row['ACIDS'];
                    $food_id      = $row['ID'];
                    $MAX_QUANTITY = $row['MAX_QUANTITY'];

                    $totalCalories = $calories * $quantity;
                    $totalProtein  = $protein  * $quantity;
                    $totalPriceCalc= round($price * $quantity, 2);
                    $totalFat      = $fat      * $quantity;
                    $totalCarb     = $carb     * $quantity;
                    $totalSalt     = $salt     * $quantity;
                    $totalSugar    = $sugar    * $quantity;
                    $totalAcids    = $acids    * $quantity;

                    echo "<div class='product'>
                            <div class='image-container'><img src='{$row['PICTUREID']}' alt='Product Image'></div>
                            <h2 class='product-title'>{$row['NAME']}</h2>
                            <div class='product-info'>
                                <h3>Sastāvs</h3>
                                <div class='product-content'>
                                    <p>Kalorijas: <span>$totalCalories kcal</span></p>
                                    <p>Tauki: <span>$totalFat g</span></p>
                                    <p>Taukskābes: <span>$totalAcids g</span></p>
                                    <p>Ogļhidrāti: <span>$totalCarb g</span></p>
                                    <p>Olbaltumvielas: <span>$totalProtein g</span></p>
                                    <p>Sāls: <span>$totalSalt g</span></p>
                                    <p>Cukurs: <span>$totalSugar g</span></p>
                                </div>
                            </div>
                            <div class='amount-edit'>
                                <h4>Izvēlies daudzumu (100g)</h4>
                                <p>
                                    Izmantot daudzumu kā minimumu 
                                    <input name='is_minimum_amount_{$food_id}' type='checkbox'"
                                    . ($row['MIN_QUANTITY'] == 1 ? 'checked' : '') . ">
                                </p>
                                <input type='number' class='quantityInput' name='amount_{$food_id}' 
                                       min='0' step='any' value='{$row['QUANTITY']}'>
                                <p>
                                    Izveidot maksimālo daudzumu
                                    <input type='checkbox' class='maxQuantityCheckbox' name='check_max_{$food_id}'"
                                    . ($MAX_QUANTITY !== null ? 'checked' : '') . ">
                                    <input type='number' class='maxQuantityInput' step='any' 
                                           name='max_input_{$food_id}' 
                                           value='" . ($MAX_QUANTITY !== null ? $MAX_QUANTITY : $quantity) . "'
                                           style='visibility:" . ($MAX_QUANTITY !== null ? 'visible' : 'hidden') . ";'>
                                </p>
                            </div>
                            <div class='product-end'>
                                <div class='product-buttons'>
                                    <button type='submit' name='delete' value='{$row['ID']}'>Dzēst</button>
                                </div>
                                <p>Cena: <span>$totalPriceCalc €</span></p>
                            </div>
                          </div>";
                }

                echo "</form>";
            } else {
                echo "<p class='error-msg no-product-msg'>Nav pievienoti produkti</p>";
            }
        }
        ?>
    </main>

    <!-- 8) PIE CHART JS -->
    <script>
    let products = [
        <?php
        if (isset($_SESSION['currentMenu'])) {
            $sql = "SELECT p.ID, p.NAME, p.PICTUREID, fp.QUANTITY
                    FROM food_products fp
                    INNER JOIN products p ON fp.PRODUCT_ID = p.ID
                    WHERE fp.FOOD_ID = {$_SESSION['currentMenu']}
                    ORDER BY QUANTITY DESC";
            $res = mysqli_query($conn, $sql);
            if (mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    $gramsum  = $row['QUANTITY'] * 100;
                    $imagePath= $row['PICTUREID'];
                    echo "{ 
                        name: '{$gramsum} g', 
                        quantity: {$row['QUANTITY']}, 
                        image: '$imagePath' 
                    },";
                }
            }
        }
        ?>
    ];

    // Filter out products with quantity = 0
    products = products.filter(product => product.quantity > 0);

    const totalQuantity = products.reduce((sum, product) => sum + product.quantity, 0);
    const totalGrams = (totalQuantity * 100).toFixed(0);
    let qtyElem = document.getElementById('quantity_sum');
    if (qtyElem) {
        qtyElem.innerText = totalGrams + ' g';
    }

    const vibrantColors = [
        'rgba(255, 99, 132, 0.5)', 
        'rgba(54, 162, 235, 0.5)',
        'rgba(75, 192, 192, 0.5)',
        'rgba(255, 206, 86, 0.5)',
        'rgba(153, 102, 255, 0.5)',
        'rgba(255, 159, 64, 0.5)',
        'rgba(199, 199, 199, 0.5)',
        'rgba(255, 99, 71, 0.5)',
        'rgba(144, 238, 144, 0.5)',
        'rgba(173, 216, 230, 0.5)'
    ];
    const vibrantBorderColors = [
        'rgba(255, 99, 132, 1)',  
        'rgba(54, 162, 235, 1)',  
        'rgba(75, 192, 192, 1)',  
        'rgba(255, 206, 86, 1)',  
        'rgba(153, 102, 255, 1)', 
        'rgba(255, 159, 64, 1)',  
        'rgba(199, 199, 199, 1)',
        'rgba(255, 99, 71, 1)',   
        'rgba(144, 238, 144, 1)',
        'rgba(173, 216, 230, 1)'
    ];

    const data = {
        labels: products.map(product => product.name),
        datasets: [{
            data: products.map(product => product.quantity),
            backgroundColor: products.map((_, i) => vibrantColors[i % vibrantColors.length]),
            borderColor: products.map((_, i) => vibrantBorderColors[i % vibrantBorderColors.length]),
            borderWidth: 1
        }]
    };

    // Plugin for drawing images in the pie slices
    const imagePlugin = {
        id: 'imagePlugin',
        afterDatasetsDraw: function(chart) {
            const ctx = chart.ctx;
            chart.data.datasets.forEach(function(dataset, i) {
                const meta = chart.getDatasetMeta(i);
                meta.data.forEach(function(element, index) {
                    const product = products[index];
                    const img = new Image();
                    img.src = product.image;

                    const position = element.tooltipPosition();
                    const width    = element.outerRadius * 0.4;
                    const height   = element.outerRadius * 0.4;

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(position.x, position.y, width/2, 0, 2*Math.PI);
                    ctx.clip();
                    ctx.drawImage(img, position.x - width/2, position.y - height/2, width, height);
                    ctx.restore();
                });
            });
        }
    };

    if (products.length > 0) {
        const config = {
            type: 'pie',
            data: data,
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const product = products[context.dataIndex];
                                return `${product.name}: ${(product.quantity*100)}g`;
                            }
                        }
                    }
                }
            },
            plugins: [imagePlugin]
        };
        const productPieChart = new Chart(document.getElementById('productPieChart'), config);
    }
    </script>

    <!-- 9) NUTRIENT BARS JS -->
    <script>
    let consumedCalories = parseFloat(<?php echo json_encode(isset($consumedCalories) ? $consumedCalories : 0); ?>);
    let calorieGoal      = parseFloat(<?php echo json_encode(isset($optimalcalories) ? $optimalcalories : 0); ?>);

    let totalProtein     = parseFloat(<?php echo json_encode(isset($consumedProtein) ? $consumedProtein : 0); ?>);
    let protein          = parseFloat(<?php echo json_encode(isset($optimalprotein)  ? $optimalprotein  : 0); ?>);

    let consumedFat      = parseFloat(<?php echo json_encode(isset($consumedFat)     ? $consumedFat     : 0); ?>);
    let fatGoal          = parseFloat(<?php echo json_encode(isset($optimalfat)      ? $optimalfat      : 0); ?>);

    let consumedAcids    = parseFloat(<?php echo json_encode(isset($consumedFatacids)? $consumedFatacids : 0); ?>);
    let acidsGoal        = parseFloat(<?php echo json_encode(isset($optimalfatacids) ? $optimalfatacids : 0); ?>);

    let consumbedCarbs   = parseFloat(<?php echo json_encode(isset($consumedCarb)    ? $consumedCarb    : 0); ?>);
    let carbGoal         = parseFloat(<?php echo json_encode(isset($optimalcarb)     ? $optimalcarb     : 0); ?>);

    let consumedSalt     = parseFloat(<?php echo json_encode(isset($consumedSalt)    ? $consumedSalt    : 0); ?>);
    let saltGoal         = parseFloat(<?php echo json_encode(isset($optimalsalt)     ? $optimalsalt     : 0); ?>);

    let consumedSugar    = parseFloat(<?php echo json_encode(isset($consumedSugar)   ? $consumedSugar   : 0); ?>);
    let sugarGoal        = parseFloat(<?php echo json_encode(isset($optimalsugar)    ? $optimalsugar    : 0); ?>);

    function createNutrientBar(barId, consumed, goal) {
        let bar = document.getElementById(barId);
        if (!bar) return; // no element found
        if (!goal || goal <= 0) {
            bar.innerHTML = '<div class="bar-segment" style="width:100%; background-color:gray;">Nav pieejami dati</div>';
            return;
        }

        consumed = Math.round(consumed);
        goal     = Math.round(goal);

        let consumedPercentage = Math.min(100, (consumed / goal) * 100);
        let exceeded           = 0;
        let needed             = 0;

        if (consumed > goal) {
            exceeded = consumed - goal;
            let exceededPercentage = (exceeded / goal) * 100;
            exceededPercentage     = Math.min(exceededPercentage, 50); // optional max

            bar.innerHTML = `
                <div class="bar-segment" style="width:${consumedPercentage}%; background-color:green; display:flex; justify-content:flex-start;">
                    <span style="margin-left:25%;">${consumed - exceeded}</span>
                </div>
                <div class="bar-segment" style="width:${exceededPercentage}%; background-color:#fac350; position:absolute; left:100%; margin-left:-${exceededPercentage}%;">
                    +${exceeded}
                </div>
            `;
        } else {
            needed = goal - consumed;
            let neededPercentage = (needed / goal) * 100;
            bar.innerHTML = `
                <div class="bar-segment" style="width:${consumedPercentage}%; background-color:green;">
                    ${consumed}
                </div>
                <div class="bar-segment" style="width:${neededPercentage}%; background-color:red;">
                    -${needed}
                </div>
            `;
        }
    }

    createNutrientBar('calorie-bar', consumedCalories, calorieGoal);
    createNutrientBar('protein-bar', totalProtein, protein);
    createNutrientBar('fat-bar', consumedFat, fatGoal);
    createNutrientBar('acids-bar', consumedAcids, acidsGoal);
    createNutrientBar('carb-bar', consumbedCarbs, carbGoal);
    createNutrientBar('salt-bar', consumedSalt, saltGoal);
    createNutrientBar('sugar-bar', consumedSugar, sugarGoal);
    </script>

    <!-- 10) MAX QUANTITY JS -->
    <script>
    // Limit quantity and maxQuantity
    document.querySelectorAll('.maxQuantityInput').forEach(function(maxInput) {
        maxInput.addEventListener('input', function() {
            var quantityInput = this.parentElement.parentElement.querySelector('.quantityInput');
            let maxVal = parseFloat(this.value || '0');
            let qtyVal = parseFloat(quantityInput.value || '0');

            if (maxVal < 0) {
                this.value = 0;
                quantityInput.value = 0;
            } else if (maxVal < qtyVal) {
                quantityInput.value = this.value;
            }
        });
    });

    document.querySelectorAll('.quantityInput').forEach(function(quantityInput) {
        quantityInput.addEventListener('input', function() {
            let val = parseFloat(this.value || '0');
            if (val < 0) {
                this.value = 0;
            }
            // if quantity > max, update max
            let maxInput = this.parentElement.parentElement.querySelector('.maxQuantityInput');
            if (maxInput) {
                let maxVal = parseFloat(maxInput.value || '0');
                if (val > maxVal) {
                    maxInput.value = val;
                }
            }
        });
    });

    document.querySelectorAll('.maxQuantityCheckbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var maxQuantityInput = this.parentElement.querySelector('.maxQuantityInput');
            if (this.checked) {
                maxQuantityInput.style.visibility = 'visible';
            } else {
                maxQuantityInput.style.visibility = 'hidden';
            }
        });
    });
    </script>

    <!-- 11) MODAL CLOSE HANDLER -->
    <script>
    var modal          = document.getElementById("errorModal");
    var span           = document.getElementsByClassName("close")[0];
    var closeModalBtn  = document.getElementById("closeModalBtn");

    span.onclick = function() {
        modal.style.display = "none";
    }
    closeModalBtn.onclick = function() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // If optimize failed, show modal
    <?php if (isset($_GET['optimization_failed']) && $_GET['optimization_failed'] == 'true') { ?>
        modal.style.display = "block";
    <?php } ?>
    </script>
</body>
</html>
