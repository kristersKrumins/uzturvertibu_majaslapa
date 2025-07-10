<?php
require 'Database/db.php';

$activate = true;
if($activate){

    // 1) Define all CSV files you want to process
    $csvFiles = [
        'produkti_vegani.csv',
        'produkti_gala.csv',
        'produkti_iepakoti.csv',
        'produkti_maize.csv',
        'produkti_piens.csv',
        'produkti_saldetie.csv',
        'produkti_saldumi.csv',
        'produkti_dzerieni.csv',
        'produkti_augli.csv',
        // Add more CSV files if needed...
    ];

    // 2) Loop through each CSV file
    foreach ($csvFiles as $csvFile) {

        echo "<h2>Processing file: $csvFile</h2>";

        if (($handle = fopen($csvFile, 'r')) !== FALSE) {
            // Ignore the header row (column titles)
            fgetcsv($handle, 1000, ',');

            // 3) Loop through each row in the current CSV file
            while (($data = fgetcsv($handle, 20000, ',')) !== FALSE) {
                
                // Debug: show what's being processed
                // Adjust indices if your CSV structure differs
                echo "NAME: $data[0], PICURL: $data[9], PRICE: $data[8]<br>";

                // Build an INSERT ... ON DUPLICATE KEY UPDATE query
                // Adjust column names to match your actual DB schema
                $sql_insert = "
                    INSERT INTO products 
                        (NAME, CALORIES, FAT, ACIDS, CARBOHYDRATES, SUGAR, PROTEIN, SALT, PRICE, PICTUREID, TYPE, PRICE_FULL)
                    VALUES (
                        '".mysqli_real_escape_string($conn, $data[0])."',
                        '".mysqli_real_escape_string($conn, $data[1])."',
                        '".mysqli_real_escape_string($conn, $data[2])."',
                        '".mysqli_real_escape_string($conn, $data[3])."',
                        '".mysqli_real_escape_string($conn, $data[4])."',
                        '".mysqli_real_escape_string($conn, $data[5])."',
                        '".mysqli_real_escape_string($conn, $data[6])."',
                        '".mysqli_real_escape_string($conn, $data[7])."',
                        '".mysqli_real_escape_string($conn, $data[8]/10)."',
                        '".mysqli_real_escape_string($conn, $data[9])."',
                        '".mysqli_real_escape_string($conn, $data[10])."',
                        '".mysqli_real_escape_string($conn, $data[11])."'
                    )
                    ON DUPLICATE KEY UPDATE
                        PICTUREID = VALUES(PICTUREID),
                        PRICE = VALUES(PRICE),
                        CALORIES = VALUES(CALORIES),
                        FAT = VALUES(FAT),
                        ACIDS = VALUES(ACIDS),
                        CARBOHYDRATES = VALUES(CARBOHYDRATES),
                        SUGAR = VALUES(SUGAR),
                        PROTEIN = VALUES(PROTEIN),
                        SALT = VALUES(SALT)
                ";

                try {
                    mysqli_query($conn, $sql_insert);
                } catch (Exception $e) {
                    echo 'Caught exception: ',  $e->getMessage(), "\n";
                }
            }
            // Close the file handle after finishing
            fclose($handle);
            
        } else {
            echo "Error: Unable to open the file $csvFile.<br>";
        }

        echo "<hr>";
    }
}
?>
