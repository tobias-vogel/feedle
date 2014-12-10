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

      $result .= '<li id="' . $feed['id'] . '"' . (empty($files) ? ' style="display: none;"' : '') . '>';
      $result .= '<div class="clear-block">';
      $result .= '<a href="#" class="button" title="Request new feed items" onclick="refreshFeed(\'' . $feed['id'] . '\', true); return false;">';
      $result .= '<img src="assets/refresh.png" alt="activity indicator"/>';
      $result .= '</a>';
      $result .= (file_exists('cache/feeds/' . $feed['id'] . '/favicon.ico') ? '<img width="16" src="cache/feeds/' . $feed['id'] . '/favicon.ico" alt="favicon for this feed"/>' : '');
      $result .= ' ' . $feed['name'] . ' (' . $feed['id'] . '; ' . $feed['uri'] . ')';
      $result .= '</div> ';
      $result .= "\n";
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
      $result .= '      <li class="feeditem" id="' . $feedId . '-' . $data['timestampid'] . '"><div class="clear-block"><a class="button" href="#" onclick="archiveFeedItem(\'' . $feedId . '\', \'' . $data['timestampid'] . '\'); return false;"><img src="assets/recycle.png" alt="activity indicator"/></a> ' . (file_exists('cache/feeds/' . $feedId . '/favicon.ico') ? '<img width="16" src="cache/feeds/' . $feedId . '/favicon.ico" alt="favicon for this feed"/>' : '') . ' <a href="' . $data['uri'] . '" class="newtabbablehref" target="_blank">' . stripcslashes($data['title']) . '</a></div></li>' . "\n";
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
      if (in_array($file, array('meta.ini', 'meta.ini~', '.', '..', 'archive', 'favicon.ico')))
        continue;
      $files []= $file;
    }
    closedir($feedContentDirectory);

    return $files;
  }
}
?>
