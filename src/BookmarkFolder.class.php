<?php
require_once('src/Bookmark.class.php');

class BookmarkFolder extends Bookmark {
  private $children = array();





  function __construct($id, $name, $description, $children) {
    parent::__construct($id, $name, $description);
    $this->children = $children;
  }





  public function getChildren() {
    return $this->children;
  }





  public function setChildren($children) {
    $this->children = $children;
  }





  public function toHtml() {
    $resultString = '<li class="bookmarkFolder">';
    $resultString .= '  <div class="title">' .  $this->name . "</div>\n";
    $resultString .= '  <div class="description">' . $this->description . "</div>\n";
    
    $resultString .= '<ul>';
    foreach ($this->children as $child) {
      $resultString .= $child->toHtml();
    }
    $resultString .= '</ul>';
    $resultString .= '</li>';

    return $resultString;
  }
}
?>