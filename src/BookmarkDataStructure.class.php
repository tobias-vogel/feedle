<?php
class BookmarkDataStructure {
  private $structure;





  public function __construct($json = null) {
    $this->structure = array();

    if ($json != null) {
      foreach (json_decode($json, true) as $entry) {
        // only consider entries that actually are bookmarks
        if ($entry['type'] == 'bookmark') {
          $bookmark = array(
            'name' => $entry['title'],
            'hyperlink' => $entry['bmkUri'],
            'tags' => implode(', ', $entry['tags']),
            'keywords' => $entry['keyword'],
            'description' => $entry['description']
          );
          $this->structure []= $bookmark;
        }
      }
    }
  }






  public function renderHTML() {
    $result = '';
    foreach ($this->structure as $item) {
      $result .=  "<tr>\n";
      $result .= '  <td>' . $item['name'] . "</td>\n";
      $result .= '  <td>< href="' . $item['hyperlink'] . '">' . $item['hyperlink'] . "</a></td>\n";
      $result .= '  <td>' . $item['tags'] . "</td>\n";
      $result .= '  <td>' . $item['keywords'] . "</td>\n";
      $result .= '  <td>' . $item['description'] . "</td>\n";
      $result .=  "</tr>\n";
    }
    return $result;
  }
}
?>
