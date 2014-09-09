<?php

require_once('bootstrap.php');

// consider only the parameters from the corresponding HTTP method
$parameters = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;

Feedle::run($parameters);
?>
