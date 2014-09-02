<?php
class FeedDataStructure {
  private $structure = array();





  public function addFeed($id, $title, $uri) {
    $feed = array();
    $feed['id'] = $id;
    $feed['name'] = $title;
    $feed['uri'] = $uri;
    $this->structure []= $feed;
  }



  public function renderHTML() {
    $result = '';
    foreach ($this->structure as $feed) {
      $result .= '<li>' . $feed['name'] . '</li>' . "\n";
      // load the feed contents for each feed
    }
    return $result;
  }
}
?>
