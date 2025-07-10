<?php
require '../Database/db.php';
session_start();

// Log the raw input data
$input = file_get_contents('php://input');


// Decode the JSON input data
$data = json_decode($input, true);

$currentMenu = $data['currentMenu'];
$optimized_products = $data['optimized_products'];


if ($optimized_products) {
    echo "running";
    foreach ($optimized_products as $product) {
        $product_id = $product['id'];
        $quantity = $product['quantity'];
        $food_id = $currentMenu;

        
        $update_query = "UPDATE food_products SET QUANTITY = ? WHERE PRODUCT_ID = ? AND FOOD_ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("dii", $quantity, $product_id, $food_id);
        if ($stmt->execute()) {
            file_put_contents('update_success.log', "Updated product ID $product_id with quantity $quantity\n", FILE_APPEND);
        } else {
            file_put_contents('update_error.log', "Failed to update product ID $product_id\n", FILE_APPEND);
        }
    }
    echo "Quantities updated successfully.";
    //header("Location: ../menu.php");
    exit();
} else {
    echo "No data received.";
    //file_put_contents('error.log', "No data received\n", FILE_APPEND);
}
?>