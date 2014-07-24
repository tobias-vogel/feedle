<?php
error_reporting(E_ALL);

require_once('src/Feedle.class.php');

header('Content-Type: text/html; charset=utf-8');

Feedle::run($_GET);
?>

<!--

TODO:
- set up htaccess
-->
