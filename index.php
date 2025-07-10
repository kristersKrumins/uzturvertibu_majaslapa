<?php
require 'Functions/Auth.php';
require 'Database/db.php';
require 'header.php';
checkAuth();

// --------------------------
// 1) Handle creation of a new menu
// --------------------------
if (isset($_POST['newMenu'])) {
    $userID = $_SESSION['id'];
    $sql = "INSERT INTO food (USERS_ID) VALUES ('$userID')";
    if ($result = mysqli_query($conn, $sql)) {
        $_SESSION["currentMenu"] = mysqli_insert_id($conn);
        header("Location: index.php");
        exit();
    }
}

// Make sure we have a current menu in the session
if (!isset($_SESSION['currentMenu'])) {
    // Optionally, you can force creation of a new menu or show a message
    // For demonstration, we’ll just create one automatically if none is found
    $userID = $_SESSION['id'];
    $sql = "INSERT INTO food (USERS_ID) VALUES ('$userID')";
    mysqli_query($conn, $sql);
    $_SESSION["currentMenu"] = mysqli_insert_id($conn);
}

// --------------------------
// 2) Handle Add/Remove product toggles
// --------------------------
if (isset($_POST['addProduct'])) {
    // Add the product to the current menu
    $productID = (int)$_POST['addProduct'];
    $foodID = $_SESSION['currentMenu'];

    // INSERT IGNORE - so we don't add duplicates if they exist 
    $sql = "INSERT IGNORE INTO food_products (FOOD_ID, PRODUCT_ID) VALUES ($foodID, $productID)";
    mysqli_query($conn, $sql);

    // Reload the page to update the UI
    header("Location: index.php");
    exit();
}

if (isset($_POST['removeProduct'])) {
    // Remove the product from the current menu
    $productID = (int)$_POST['removeProduct'];
    $foodID = $_SESSION['currentMenu'];

    $sql = "DELETE FROM food_products WHERE FOOD_ID = $foodID AND PRODUCT_ID = $productID";
    mysqli_query($conn, $sql);

    // Reload the page to update the UI
    header("Location: index.php");
    exit();
}

// ------------------------
// 3) Fetch distinct product types
// ------------------------
$typeSQL = "SELECT DISTINCT TYPE FROM products";
$typeResult = mysqli_query($conn, $typeSQL);

// Keep track of the currently selected type (if any)
$selectedType = isset($_GET['typeFilter']) ? $_GET['typeFilter'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Fonts and Stylesheets -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/header.css">
    <link rel="stylesheet" href="CSS/index.css">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/2ae46db69b.js" crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ēdienkartes Optimizēšana</title>
</head>
<body>
    <?php
    // Render the header (your existing function)
    renderheader();
    ?>
    <main>
        <div class="main-container">

            <!-- Search container -->
            <div class="search-container">
                <form action="index.php" method="GET">
                    <label>Atrodi sev vēlamos produktus!</label>
                    <input type="text" name="search" placeholder="Ieraksti produktu..."
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
                <hr>
            </div>

            <!-- Filter Menu + "Izveidot jaunu ēdienkarti" in one line -->
            <div class="filter-menu">
                <?php
                // We keep the current 'search' parameter (if any) so it persists after clicking a filter
                $searchParam = '';
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $searchParam = '&search=' . urlencode($_GET['search']);
                }
                ?>

                <!-- "Visi" button that removes any typeFilter -->
                <a href="index.php?<?php echo ltrim($searchParam, '&'); ?>"
                   class="filter-button <?php echo empty($selectedType) ? 'active' : ''; ?>">
                    Visi
                </a>

                <?php
                // Create a button (link) for each distinct TYPE
                if ($typeResult && mysqli_num_rows($typeResult) > 0) {
                    mysqli_data_seek($typeResult, 0);
                    while ($rowType = mysqli_fetch_assoc($typeResult)) {
                        $typeValue = $rowType['TYPE'];
                        $isActive = ($selectedType === $typeValue) ? 'active' : '';
                        // Build the URL for each filter
                        $filterUrl = 'index.php?typeFilter=' . urlencode($typeValue) . $searchParam;
                        echo "<a href='{$filterUrl}' class='filter-button {$isActive}'>{$typeValue}</a>";
                    }
                }
                ?>

                <!-- Create new menu form -->
                <form id="menuForm" action="index.php" method="POST">
                    <button type="submit"
                            <?php if (isset($_SESSION["currentMenu"])) { echo "style='display:none;'"; } ?>
                            name="newMenu">
                        Izveidot jaunu ēdienkarti
                    </button>
                </form>
            </div>

            <?php
            // --------------------------
            // 4) Build up the query (search + filter) + pagination
            // --------------------------

            // 4.1) Handle Search
            $search = '';
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = mysqli_real_escape_string($conn, $_GET['search']);
            }

            // 4.2) Handle type filter
            $typeFilter = '';
            if (isset($_GET['typeFilter']) && !empty($_GET['typeFilter'])) {
                $typeFilter = mysqli_real_escape_string($conn, $_GET['typeFilter']);
            }

            // 4.3) Determine the current page
            if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                $currentPage = (int)$_GET['page'];
            } else {
                $currentPage = 1;
            }

            // 4.4) Number of items per page
            $itemsPerPage = 18;
            $offset = ($currentPage - 1) * $itemsPerPage;

            // 4.5) Build the WHERE conditions
            $whereClauses = [];
            if (!empty($search)) {
                $whereClauses[] = "NAME LIKE '%$search%'";
            }
            if (!empty($typeFilter)) {
                $whereClauses[] = "TYPE = '$typeFilter'";
            }
            $whereSQL = '';
            if (!empty($whereClauses)) {
                $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
            }

            // 4.6) Build the main product query + the count query
            $productQuery = "SELECT * FROM products $whereSQL 
                             LIMIT $itemsPerPage OFFSET $offset";
            $countQuery = "SELECT COUNT(*) as total FROM products $whereSQL";

            // 5) Fetch products
            $result = mysqli_query($conn, $productQuery);

            // 6) Get total product count
            $countResult = mysqli_query($conn, $countQuery);
            $totalItems = mysqli_fetch_assoc($countResult)['total'];

            // 7) Calculate total pages
            $totalPages = ceil($totalItems / $itemsPerPage);
            ?>

            <!-- Product container -->
            <div class="product-container">
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $productID = $row['ID'];

                        // Check if this product is in the current menu
                        $foodID = $_SESSION['currentMenu'];
                        $checkQuery = "SELECT * FROM food_products 
                                       WHERE FOOD_ID = $foodID 
                                         AND PRODUCT_ID = $productID";
                        $checkResult = mysqli_query($conn, $checkQuery);
                        $isActive = mysqli_num_rows($checkResult) > 0;
                        
                        echo "<div class='product'>";
                            echo "<div class='img-container'>";
                                // Display product image
                                echo "<img src='{$row['PICTUREID']}' alt='Product Image'>";
                            echo "</div>";

                            // Product title
                            echo "<h2>{$row['NAME']}</h2>";

                            // Price container
                            echo "<div class='product-content'>";
                                echo "<div class='product-info'>";
                                    // Show PRICE_FULL as main price
                                    echo "<p class='price-full'>{$row['PRICE_FULL']}€</p>";
                                    // If TYPE is 'Dzērieni' -> €/100ml, else €/100g
                                    if ($row['TYPE'] === 'Dzērieni') {
                                        echo "<p class='price-small'>{$row['PRICE']} €/100ml</p>";
                                    } else {
                                        echo "<p class='price-small'>{$row['PRICE']} €/100g</p>";
                                    }
                                echo "</div>";

                                // Toggling button
                                echo "<div class='product-buttons'>";
                                    echo "<button name='submitFood' onclick='addToFood(" . $row['ID'] . ", this)' class='" . ($isActive ? "active" : "") . "'>" . ($isActive ? "Noņemt no Ēdienkartes" : "Pievienot Ēdienkartei") . "</button>";
                                echo "</div>";
                            echo "</div>"; // end .product-content

                        echo "</div>"; // end .product
                    }
                } else {
                    echo "Nav atrasts neviens produkts.";
                }
                ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php
                // Only show pagination if there's more than 1 page
                if ($totalPages > 1) {
                    // Build base URL
                    $url = '?';
                    $params = [];
                    if (!empty($search)) {
                        $params[] = 'search=' . urlencode($search);
                    }
                    if (!empty($typeFilter)) {
                        $params[] = 'typeFilter=' . urlencode($typeFilter);
                    }
                    if (!empty($params)) {
                        $url .= implode('&', $params) . '&';
                    }

                    // Previous Page Link
                    if ($currentPage > 1) {
                        echo '<a href="'.$url.'page='.($currentPage - 1).'">&lt;</a>';
                    }

                    // First Page Link (with "..." if needed)
                    if ($currentPage > 3) {
                        echo '<a href="'.$url.'page=1">1</a>';
                        if ($currentPage > 4) {
                            echo '<span class="ellipsis">...</span>';
                        }
                    }

                    // Page Links Around Current Page
                    $startPage = max(1, $currentPage - 1);
                    $endPage = min($totalPages, $currentPage + 1);
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        if ($i == $currentPage) {
                            echo '<span class="current-page">'.$i.'</span>';
                        } else {
                            echo '<a href="'.$url.'page='.$i.'">'.$i.'</a>';
                        }
                    }

                    // Last Page Link (with "..." if needed)
                    if ($currentPage < $totalPages - 2) {
                        if ($currentPage < $totalPages - 3) {
                            echo '<span class="ellipsis">...</span>';
                        }
                        echo '<a href="'.$url.'page='.$totalPages.'">'.$totalPages.'</a>';
                    }

                    // Next Page Link
                    if ($currentPage < $totalPages) {
                        echo '<a href="'.$url.'page='.($currentPage + 1).'">&gt;</a>';
                    }
                }
                ?>
            </div>

        </div><!-- .main-container -->
    </main>
    <script>
        function addToFood(productId, buttonElement) {
    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('productId', productId);
    if (buttonElement.classList.contains('active')) {
        formData.append('status', 'delete');
        console.log('deleting');
    } else {
    formData.append('status', 'add');
    console.log('adding');
    }
    // Send AJAX request to add_to_food.php
    fetch('Functions/add_to_food.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get the response as text
    .then(text => {
        console.log('Server response:', text); // Log the response
        return JSON.parse(text); // Parse the response as JSON
    })
    .then(data => {
        if (data.status === 'error') {
            // Show notification
            alert(data.message);
        } else {
            // Change the button class to active and disable it
            if (buttonElement.classList.contains('active')) {
                buttonElement.classList.remove('active');
                buttonElement.textContent = 'Pievienot Ēdienkartei';
            } else {
                buttonElement.classList.add('active');
                buttonElement.textContent = 'Noņemt no Ēdienkartes';
            }
            //buttonElement.textContent = 'Pievienots';
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
    </script>
</body>
</html>
