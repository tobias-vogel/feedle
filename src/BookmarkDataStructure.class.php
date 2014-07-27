<?php
class BookmarkDataStructure {
  private $structure;





  public function __construct($json = null) {
    $this->structure = array();

    if ($json != null) {
      foreach (json_decode($json, true) as $entry) {
        // only consider entries that actually are bookmarks
        if ($entry['type'] == 'bookmark') {
          $bookmark = array();
          $bookmark['name'] = $entry['title'];
          $bookmark['hyperlink'] = $entry['bmkUri'];
          $bookmark['description'] = $entry['description'];
          // merge tags and keywords (what's the difference?)
          $bookmark['tags'] = array_merge(count($entry['tags']) > 0 ? explode(' ', implode(' ', $entry['tags'])) : array(), ($entry['keyword'] == null ? array() : explode(' ', $entry['keyword'])));

          $this->structure []= $bookmark;
        }
      }
    }
  }






  public function renderHTML() {
    $result = '';
    foreach ($this->structure as $item) {

      $result .= '<li>' . "\n";
      $result .= '  <div class="title">' .  $item['name'] . "</div>\n";
      $result .= '  <div class="hyperlink"><a href="' . $item['hyperlink'] . '">' . $item['hyperlink'] . "</a></div>\n";
      $result .= '  <ul class="tags">' . "\n";
      if (count($item['tags']) > 0) {
        foreach ($item['tags'] as $tag) {
          $result .= '    <li class="tag">' . $tag . "</li>\n";
        }
      }
      $result .= "  </ul>\n";  
      $result .= '  <div class="description">' . $item['description'] . "</div>\n";
      $result .= "</li>\n";
    return $result;
  }
}
?>
