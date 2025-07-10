<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION["id"])) {
        header("Location: Login.php");
        exit;
    }
}
?>