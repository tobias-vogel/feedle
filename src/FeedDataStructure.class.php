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
    uasort ($this->structure, function($entry1, $entry2) {
      return strtolower($entry1['name']) > strtolower($entry2['name']);
    });

    foreach ($this->structure as $feed) {
      $files = FeedDataStructure::getListOfFilesForFeed($feed['id']);

      $result .= '<li id="' . $feed['id'] . '"' . (empty($files) ? ' style="display: none;"' : '') . '><button title="Request new feed items" onclick="refreshFeed(\'' . $feed['id'] . '\', true)"' . '><img src="assets/refresh.png" alt="activity indicator"></button> ' . $feed['name'] . ' (' . $feed['id'] . '; ' . $feed['uri'] . ')' . "\n";
      $result .= '  <div>' . "\n";
      $result .= '    <ul>' . "\n";
      $result .= FeedDataStructure::renderFeedContents($files, $feed['id']);
      $result .= '    </ul>' . "\n";
      $result .= '  </div>' . "\n";
    }
    $result .= '</li>' . "\n";
    return $result;
  }





  public static function renderFeedContents($files, $feedId) {
    $feedContentDirectoryName = 'cache/feeds/' . $feedId;

    $result = '';
//    $result .= '    <ul>' . "\n";

    // sort the files
    asort($files);

    foreach ($files as $file) {
      $data = parse_ini_file($feedContentDirectoryName . '/' . $file);
      $result .= '      <li id="' . $feedId . '-' . $data['timestampid'] . '"><button onclick="archiveFeedItem(\'' . $feedId . '\', \'' . $data['timestampid'] . '\')"><img src="assets/recycle.png" alt="activity indicator"></button><a href="' . $data['uri'] . '">' . stripcslashes($data['title']) . '</a></li>' . "\n";
    }

/*  else {
    $result .= '      <li>Feed not (yet) loaded </li>' . "\n";
  }*/
//    $result .= '    </ul>' . "\n";
    return $result;
  }





  public static function getListOfFilesForFeed($feedId) {
    $feedContentDirectoryName = 'cache/feeds/' . $feedId;
      // load the feed contents for this feed
    $feedContentDirectory = opendir($feedContentDirectoryName);
    $files = array();
    while ($file = readdir($feedContentDirectory)) {
      if (in_array($file, array('meta.ini', 'meta.ini~', '.', '..', 'archive')))
        continue;
      $files []= $file;
    }
    closedir($feedContentDirectory);

    return $files;
  }
}
?>
