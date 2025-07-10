<?php
ob_start(); // Start output buffering
require '../Database/db.php';
require '../Functions/Auth.php';
checkAuth();

if (!isset($_SESSION['currentMenu'])) {
    echo "No current menu selected.";
    exit();
}

$menuid = $_SESSION['currentMenu'];

// User constraints from form submission
$minCalories = isset($_POST['min_calories']) ? $_POST['min_calories'] : 0;
$minProtein = isset($_POST['min_protein']) ? $_POST['min_protein']: 0 ;
$minFat = isset($_POST['min_fat']) ? $_POST['min_fat'] : 0;
$minAcids = isset($_POST['min_acids']) ? $_POST['min_acids'] : 0;
$minCarbs = isset($_POST['min_carbs']) ? $_POST['min_carbs'] : 0;
$minSalt = isset($_POST['min_salt']) ? $_POST['min_salt'] : 0;
$minSugar = isset($_POST['min_sugar']) ? $_POST['min_sugar'] : 0;

// Retrieve products connected to the current menu
$sql = "SELECT p.ID, p.NAME, p.CALORIES, p.PROTEIN, p.PRICE, p.SUGAR,p.ACIDS, p.FAT, p.CARBOHYDRATES, p.SALT, fp.QUANTITY, fp.MIN_QUANTITY, fp.MAX_QUANTITY
        FROM food_products fp
        INNER JOIN products p ON fp.PRODUCT_ID = p.ID
        WHERE fp.FOOD_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $menuid);
$stmt->execute();
$result = $stmt->get_result();

$selectedProducts = [];
while ($row = $result->fetch_assoc()) {
    $selectedProducts[$row['ID']] = [
        'calories' => $row['CALORIES'],
        'protein' => $row['PROTEIN'],
        'price' => $row['PRICE'],
        'sugar' => $row['SUGAR'],
        'fat' => $row['FAT'],
        'carbs' => $row['CARBOHYDRATES'],
        'salt' => $row['SALT'],
        'acids' => $row['ACIDS'], // Assuming 'acids' is the same as 'fat'
        'quantity' => $row['QUANTITY'],
        'max_quantity'=>$row['MAX_QUANTITY'],
        'is_minimum'=>$row['MIN_QUANTITY']
    ];
}

// Prepare data to send to Python script
$data = [
    'min_calories' => $minCalories,
    'min_protein' => $minProtein,
    'min_fat' => $minFat,
    'min_acids' => $minAcids,
    'min_carbs' => $minCarbs,
    'min_salt' => $minSalt,
    'min_sugar' => $minSugar,
    'products' => $selectedProducts,
    'currentMenu' => $menuid 
];

// Encode data as JSON
$json_data = json_encode($data);

// Write JSON data to a temporary file
$temp_file = tempnam(sys_get_temp_dir(), 'optimize_data_');
file_put_contents($temp_file, $json_data);

// Run the Python script using the correct path to the Python executable
$python_path = "C:/Python312/python.exe";
$script_path = "C:/xampp/htdocs/Projektesanas_labratorija/Functions/optimize.py";
$command = escapeshellcmd("$python_path $script_path $temp_file");
$output = shell_exec("$command 2>&1"); // Capture both stdout and stderr

// Log the output for debugging
file_put_contents('optimize_log.txt', $output);

// Handle Python output
if ($output === NULL) {
    echo "Error running Python script.";
    ob_end_flush(); // End buffering before exiting
    exit();
}

// Check for redirection header in Python output
if (preg_match('/^Location: (.+)$/m', $output, $matches)) {
    $location = trim($matches[1]);
    header("Location: $location");
    exit();
}

echo "Error processing response from Python: $output";
ob_end_flush(); // End buffering before exiting
exit();
?>