<?php
require_once('src/ConfigLoader.class.php');
require_once('src/ConfigurationNotFoundException.class.php');
require_once('src/BookmarkDataStructure.class.php');

class Feedle {

  private static $configuration;

  public static function run() {
    try {
      // load the credentials
      self::$configuration = ConfigLoader::loadConfiguration();
    }
    catch (ConfigurationNotFoundException $cnfe) {
      echo $cnfe->getMessage() . "<br>\n";
      exit;
    }


    $bookmarks = self::readBookmarks();

    self::displayPage($bookmarks);




    /*
      Workflow:
      1. check for last update, if older than a day, retrieve feed list
      2. update feed contents
   */

//    $this->readFeedsFromSync();
    //is_dir("caches/" . $this->configuration['username']);
//    var_dump($this->configuration);
  }




  private function readBookmarks() {
    // read the bookmarks from cache or from the web, if not available (and then cache it)

    if (!file_exists('cache/bookmarks.json')) {
      // the cached file is not available, read it from the web and save it
      readBookmarkJsonFromWebAndSaveIt();
    }

    $json = file_get_contents('cache/bookmarks.json');

    //TODO convert json to bookmarks array
    $bds = new BookmarkDataStructure($json);

    return $bds;
  }





  private static function readBookmarkJsonFromWebAndSaveIt() {
    // start a process that does query the sync server
    $command = 'sync-cli.js -e ' . self::$configuration['email'] . ' -p ' . self::$configuration['password'] . ' -t bookmarks';

    $command = 'which ls';
    exec($command, $output, $xxx);

var_dump($output);
var_dump($xxx);

die();
  }




//  private function readFeedsFromSync() {
//    $sync = new Firefox_Sync($this->configuration['username'], $this->configuration['password'], $this->configuration['synckey'], $this->configuration['syncnode']);
//    var_dump($sync->collection_full($collection));
//  }






  // updates the cached bookmarks
  private function updateCachedBookmarks() {
    // bookmarks are saved in a flat file with a last-updated flag
  }




  private function displayPage($bookmarks) {
    // do it with echo, later a proper template engine may be more appropriate
    echo <<<'EOT'
<html>
  <head>
    <title>Bookmarks</title>
  </head>
  <body>
    <h1>Bookmarks</h1>
    <table border=1>
      <thead>
        <tr>
          <th>Name</th>
          <th>Hyperlink</th>
          <th>Tags</th>
          <th>Keywords</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>

EOT;
    echo $bookmarks->renderHTML();
    echo <<<'EOT'
      </tbody>
    </table>
  </body>
</html>
EOT;
  }
}
?>
