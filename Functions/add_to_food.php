<?php
// Assuming the connection to the database has already been established
require '../Database/db.php';
require 'Auth.php';



// Start output buffering to capture any unexpected output
ob_start();

$response = [];

if (!isset($_SESSION['currentMenu'])) {
    $response = ['status' => 'error', 'message' => 'Menu not created'];
    echo json_encode($response);
    exit;
}
$foodID = $_SESSION['currentMenu'];  // The food ID you are inserting into the Food_Products table
$productID = $_POST['productId'];  // The product ID from the form or AJAX request
$status=$_POST['status'];
$quantity = 1;  // Default quantity (you can modify this as needed)
// Check if the FOOD_ID exists in the Food table
if ($status == 'add') {
    $stmt = $conn->prepare("SELECT ID FROM food WHERE ID = ?");
    $stmt->bind_param("i", $foodID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // FOOD_ID exists, now check PRODUCT_ID in the Products table
        $stmt = $conn->prepare("SELECT ID FROM products WHERE ID = ?");
        $stmt->bind_param("i", $productID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // PRODUCT_ID exists, check if the combination already exists in Food_Products table
            $stmt = $conn->prepare("SELECT * FROM food_products WHERE FOOD_ID = ? AND PRODUCT_ID = ?");
            $stmt->bind_param("ii", $foodID, $productID);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                // Combination does not exist, insert into Food_Products table
                $stmt = $conn->prepare("INSERT INTO food_products (FOOD_ID, PRODUCT_ID, QUANTITY) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $foodID, $productID, $quantity);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Product added to the meal successfully!'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Error: ' . $stmt->error];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'This product is already added to the meal.'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Invalid PRODUCT_ID. This product does not exist.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid FOOD_ID. This meal does not exist.'];
    }
} elseif ($status == 'delete') {
    $stmt = $conn->prepare("DELETE FROM food_products WHERE FOOD_ID = ? AND PRODUCT_ID = ?");
    $stmt->bind_param("ii", $foodID, $productID);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = ['status' => 'success', 'message' => 'Product removed from the meal successfully!'];
        } else {
            $response = ['status' => 'error', 'message' => 'No matching record found to delete.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Error: ' . $stmt->error];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid status.'];
}
$stmt->close();
$conn->close();

// Clean the output buffer and end buffering
ob_end_clean();

// Output the JSON response
echo json_encode($response);
?>