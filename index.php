<?php
error_reporting(E_ALL);

require_once('src/Feedle.class.php');

header('Content-Type: text/html; charset=utf-8');

var_dump($_REQUEST);
Feedle::run();
?>

<!--

TODO:
- set up htaccess
-->
