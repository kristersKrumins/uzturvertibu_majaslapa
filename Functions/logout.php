<?php
session_start();
if(isset($_POST['submit'])){
    session_destroy();
    header('Location: ../Login.php');
    echo 'hello';
}