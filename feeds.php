<?php
require_once("bootstrap.php");

list($bookmarks, $feeds) = Feedle::readBookmarksFromCache();

echo Feedle::displayFeedPage($feeds, $bookmarks->getTimestamp());

?>
