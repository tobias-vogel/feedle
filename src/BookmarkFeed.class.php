<?php
require_once('src/BookmarkItem.class.php');

class BookmarkFeed extends BookmarkItem {
  public function toHtml() {
    return parent::toHtml(true);
  }
}
?>