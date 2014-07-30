<?php
require_once('src/ConfigLoader.class.php');
require_once('src/ConfigurationNotFoundException.class.php');
require_once('src/BookmarkDataStructure.class.php');

class Feedle {

  private static $configuration;

  public static function run($parameters) {
    // what we have to do depends on the parameters
    if (isset($parameters['action'])) {
      if ($parameters['action'] == 'retrievebookmarksfromsyncserver') {
        // if an update is currently running, do nothing
        if (file_exists('cache/.inprogress'))
          return;

        try {
          // load the credentials
          self::$configuration = ConfigLoader::loadConfiguration();
        }
        catch (ConfigurationNotFoundException $cnfe) {
          echo $cnfe->getMessage() . "<br>\n";
          exit;
        }

        // delete the currently cached bookmarks file (if present)
        //if (file_exists('cache/bookmarks.json')) {
        //  unlink('cache/bookmarks.json');
        //  unlink('cache/.completed');
        //}

        // call sync server to get an updated list of bookmarks
        Feedle::readBookmarksFromWebAndSaveIt(self::$configuration);
      }
      else if ($parameters['action'] == 'getbookmarks') {
        // return the bookmarks from the cached file (or nothing, if there is no file)
        if (file_exists('cache/.completed')) {
          $bookmarks =  Feedle::readBookmarksFromCache();
          echo $bookmarks->renderHTML();
        }
        else {
          // tell the client that the file has not yet been fetched
          // http_response_code(204); // does not work for PHP 5.3.3
          header(':', true, 204);
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

    if (file_exists('cache/bookmarks.json') && file_exists('cache/.completed')) {
      $json = file_get_contents('cache/bookmarks.json');
    }

    // convert json to bookmarks array
    $bds = new BookmarkDataStructure($json);

    return $bds;
  }





  private static function readBookmarksFromWebAndSaveIt($configuration) {
    // start a process that does query the sync server
    $command = "rm cache/.completed; touch cache/.inprogress && lib/fxa-sync-client/bin/sync-cli.js -e " . $configuration['email'] . " -p " . $configuration['password'] . " -t bookmarks | sed -n -E -e '/::bookmarks::/,$ p' - | sed '1 d' > cache/bookmarks.json && touch cache/.completed && rm cache/.inprogress";
    exec($command);
  }





  private function displayPage($bookmarks) {
    // do it with echo, later a proper template engine may be more appropriate
    echo <<<'EOT'
<!DOCTYPE html>
<html>
  <head>
    <title>Bookmarks</title>
    <meta charset="utf-8">
    <script src="assets/main.js" type="text/javascript"></script>
    <script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
    <link href="assets/style.css" rel="stylesheet" type="text/css"/>
    <link href="assets/favicon.ico" rel="icon" type="image/x-icon"/>
    <meta name="viewport" content="width=device-width"/>
  </head>
  <body>
    <h1>Control</h1>
    <button id="updatebutton" onclick="update()">Retrieve updated sync data</button><span style="display: none" id="activity"> <img src="assets/loader.gif" alt="activity indicator"/></span>
    <h1>Bookmarks</h1>
    <ul id="bookmarkslist">

EOT;
    echo $bookmarks->renderHTML();
    echo <<<'EOT'
    
    </ul>
  </body>
</html>
EOT;
  }
}
?>
