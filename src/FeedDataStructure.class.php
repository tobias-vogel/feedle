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
      $result .= '<li id="' . $feed['id'] . '"' /*. onclick="refreshFeed(\'' . $feed['id'] . '\')"'*/ . '><button title="Request new feed items" onclick="refreshFeed(\'' . $feed['id'] . '\')"><img src="assets/refresh.png"></button> ' . $feed['name'] . ' (' . $feed['uri'] . ')' . "\n";
      $result .= '  <div>' . "\n";

      $result .= FeedDataStructure::renderFeedContents($feed['id']);

      $result .= '  </div>' . "\n";
    }
    $result .= '</li>' . "\n";
    return $result;
  }





  public static function renderFeedContents($feedId) {
    $result = '';
    $result .= '    <ul>' . "\n";

    $feedContentDirectoryName = 'cache/feeds/' . $feedId;// . '/unread';
    if (file_exists($feedContentDirectoryName)) {
      // load the feed contents for this feed
      $feedContentDirectory = opendir($feedContentDirectoryName);
      $files = array();
      while ($file = readdir($feedContentDirectory)) {
        if (in_array($file, array('meta.ini', '.', '..', 'archive')))
          continue;
        $files []= $file;
      }

      // sort the files
      asort($files);

      foreach ($files as $file) {
        $data = parse_ini_file($feedContentDirectoryName . '/' . $file);
#if (count($data) != 2) var_dump($data, $file);
        $result .= '      <li id="' . $feedId . '-' . $data['timestampid'] . '"><button onclick="archiveFeedItem(\'' . $feedId . '\', \'' . $data['timestampid'] . '\')"><img src="assets/recycle.png"></button><a href="' . $data['uri'] . '">' . stripcslashes($data['title']) . '</a></li>' . "\n";
      }
      closedir($feedContentDirectory);
    }
    else {
      $result .= '      <li>Feed not (yet) loaded </li>' . "\n";
    }
    $result .= '    </ul>' . "\n";
    return $result;
  }
}
?>
