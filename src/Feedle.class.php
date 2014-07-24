<?php
require_once('src/ConfigLoader.class.php');
require_once('src/ConfigurationNotFoundException.class.php');
require_once('src/BookmarkDataStructure.class.php');

class Feedle {

  private static $configuration;

  public static function run($_GET) {

    // what we have to do depends on the GET parameters
    if (isset($_GET['action'])) {
      if ($_GET['action'] == 'updatebookmarks') {
        try {
          // load the credentials
          self::$configuration = ConfigLoader::loadConfiguration();
        }
        catch (ConfigurationNotFoundException $cnfe) {
          echo $cnfe->getMessage() . "<br>\n";
          exit;
        }

        // delete the curretly cached bookmarks file (if present)
        if (file_exists('cache/bookmarks.json'))
          unlink('cache/bookmarks.json');

        // call sync server to get an updated list of bookmarks
        Feedle::readBookmarksFromWebAndSaveIt(self::$configuration);
      }
      else if ($_GET['action'] == 'getbookmarks') {
        // return the bookmarks from the cached file (or nothing, if there is no file)
        if (file_exists('cache/bookmarks.json')) {
          $bookmarks =  Feedle::readBookmarksFromCache();
          echo $bookmarks->renderHTML();
        }
        else {
          // tell the client that the file has not yet been fetched
          // http_response_code(202); // does not work for PHP 5.3.3
          header(':', true, 202);
        }
      }
    }
    else {
      // just display the (possibly) cached bookmarks together with the whole page
      $bookmarks = Feedle::readBookmarksFromCache();
      self::displayPage($bookmarks);
    }

    //TODO include something for the feeds
  }





  private static function readBookmarksFromCache() {
    // read the bookmarks from cache
    $json = null;

    if (file_exists('cache/bookmarks.json')) {
      $json = file_get_contents('cache/bookmarks.json');
    }

    // convert json to bookmarks array
    $bds = new BookmarkDataStructure($json);

    return $bds;
  }





  private static function readBookmarksFromWebAndSaveIt($configuration) {
    // start a process that does query the sync server
    $command = "lib/fxa-sync-client/bin/sync-cli.js -e " . $configuration['email'] . " -p " . $configuration['password'] . " -t bookmarks | sed -n -E -e '/::bookmarks::/,$ p' - | sed '1 d' > cache/bookmarks.json";
    exec($command);
  }





  private function displayPage($bookmarks) {
    // do it with echo, later a proper template engine may be more appropriate
    echo <<<'EOT'
<html>
  <head>
    <title>Bookmarks</title>
    <script src="assets/main.js" type="text/javascript"></script>
    <script src="http://code.jquery.com/jquery-latest.js"></script>
  </head>
  <body>
    <h1>Control</h1>
    <span onclick="update()">Retrieve updated sync data</span><span style="display: none" id="activity"> <img src="assets/loader.gif" title="activity indicator"/></span>
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
      <tbody id="bookmarkstablebody">

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
