<?php
error_reporting(E_ALL);

require_once('src/Feedle.class.php');

header('Content-Type: text/html; charset=utf-8');

// consider only the parameters from the corresponding HTTP method
Feedle::run($_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST);
?>
