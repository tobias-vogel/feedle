<?php
require_once('src/Bookmark.class.php');

class BookmarkItem extends Bookmark {
  private $hyperlink;
  private $tags;
  
  function __construct($id, $name, $description, $hyperlink, $tags) {
    parent::__construct($id, $name, $description);
    $this->hyperlink = $hyperlink;
    $this->tags = $tags;
  }





  public function getHyperlink() {
    return $this->hyperlink;
  }





  public function getTags() {
    return $this->tags;
  }





  public function toHtml($feed = false) {
    $result = '<li class="bookmarkItem' . ($feed ? ' feed': '') . '"' . '>' . "\n";
    $result .= '  <div class="title">' .  $this->name . "</div>\n";
    $result .= '  <div class="hyperlink"><a href="' . $this->hyperlink . '"  class="newtabbablehref" target="_blank">' . $this->hyperlink . "</a></div>\n";
    $result .= '  <ul class="tags">' . "\n";
    foreach ($this->tags as $tag) {
      $result .= '    <li class="tag">' . $tag . "</li>\n";
    }
    $result .= "  </ul>\n";
    $result .= '  <div class="description">' . $this->description . "</div>\n";
    $result .= "</li>\n";

    return $result;
  }
}
?>