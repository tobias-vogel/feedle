<?php
require_once("bootstrap.php");

list($bookmarks, $feeds) = Feedle::readBookmarksFromCache();

echo Feedle::displayBookmarkPage($bookmarks);
?>
