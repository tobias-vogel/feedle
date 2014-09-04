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
      $result .= '<li id="' . $feed['id'] . '"' /*. onclick="refreshFeed(\'' . $feed['id'] . '\')"'*/ . '><button onclick="refreshFeed(\'' . $feed['id'] . '\')"><img src="assets/refresh.png"></button> ' . $feed['name'] . ' (' . $feed['uri'] . ')' . "\n";
      $result .= '  <div>' . "\n";
      $result .= '    <ul>' . "\n";

      $feedContentDirectoryName = 'cache/feeds/' . $feed['id'];// . '/unread';
      if (file_exists($feedContentDirectoryName)) {
        // load the feed contents for this feed
        $feedContentDirectory = opendir($feedContentDirectoryName);
        $files = array();
        while ($file = readdir($feedContentDirectory)) {
          if (in_array($file, array("meta.ini", ".", "..")))
            continue;
          $files []= $file;
        }

        // sort the files
        asort($files);

#var_dump($files);
#die();
        foreach ($files as $file) {
          $data = parse_ini_file($feedContentDirectoryName . '/' . $file);
          $result .= '      <li><a href="' . $data['uri'] . '">' . $data['title'] . '</a></li>' . "\n";
        }
        closedir($feedContentDirectory);
      }
      else {
        $result .= '      <li>Feed not (yet) loaded </li>' . "\n";
      }
      $result .= '    </ul>' . "\n";
      $result .= '  </div>' . "\n";
    }
    $result .= '</li>' . "\n";
    return $result;
  }
}
?>
