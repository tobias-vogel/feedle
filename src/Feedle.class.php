<?php
require_once('src/ConfigLoader.class.php');
require_once('src/ConfigurationNotFoundException.class.php');
require_once('src/BookmarkDataStructure.class.php');
require_once('src/FeedDataStructure.class.php');

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

        // call sync server to get an updated list of bookmarks
        Feedle::readBookmarksFromWebAndSaveIt(self::$configuration);
      }
      else if ($parameters['action'] == 'getbookmarks') {
        // return the bookmarks from the cached file (or nothing, if there is no file)
        if (file_exists('cache/.completed')) {
          $bookmarks = Feedle::readBookmarksFromCache();
          echo $bookmarks->renderHTML();
        }
        else {
          // tell the client that the file has not yet been fetched
          // http_response_code(204); // does not work for PHP 5.3.3
          header(':', true, 204);
        }
      }
      else if ($parameters['action'] == 'getfeeds') {
        // return the feeds from the cached file (or nothing, if there is no file)
        if (file_exists('cache/.completed')) {
          $bookmarks = Feedle::readBookmarksFromCache();
          $feeds = $bookmarks->filterFeeds();
          echo $feeds->renderHTML();
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
      list($bookmarks, $feeds) = Feedle::readBookmarksFromCache();
      //$feeds = $bookmarks->filterFeeds();
//var_dump($feeds);
//die();
      self::displayPage($bookmarks, $feeds);
    }
  }





  private static function readBookmarksFromCache() {
    // read the bookmarks from cache
    $json = null;
    $timestamp = null;

    if (file_exists('cache/bookmarks.json') && file_exists('cache/.completed')) {
      $json = file_get_contents('cache/bookmarks.json');
      $timestamp = filemtime('cache/bookmarks.json');
    }

    // convert json to bookmarks and feeds
    $bds = new BookmarkDataStructure($timestamp);
    $fds = new FeedDataStructure();

    if ($json != null) {
      foreach (json_decode($json, true) as $entry) {

        // only consider entries that actually are bookmarks or feeds
        if ($entry['type'] == 'bookmark' or $entry['type'] == 'livemark') {
          $name = $entry['title'];

          if ($entry['type'] == 'bookmark') {
            // read all bookmark specific details
            $hyperlink = $entry['bmkUri'];
            $description = $entry['description'];
            // merge tags and keywords (what's the difference?)
            $tags = array_merge(count($entry['tags']) > 0 ? explode(' ', implode(' ', $entry['tags'])) : array(), ($entry['keyword'] == null ? array() : explode(' ', $entry['keyword'])));

            // add this bookmark
            $bds->addBookmark($name, $hyperlink, $description, $tags);
          }
          else {
            // read all feed specific details
            $feedUri = $entry['feedUri'];
            $id = $entry['id'];

            // add this feed
            $fds->addFeed($name, $feedUri, $id);
          }
        }
      }
    }
    return array($bds, $fds);
  }





  private static function readBookmarksFromWebAndSaveIt($configuration) {
    // start a process that does query the sync server
    $command = "rm cache/.completed; touch cache/.inprogress && lib/fxa-sync-client/bin/sync-cli.js -e " . $configuration['email'] . " -p " . $configuration['password'] . " -t bookmarks | sed -n -E -e '/::bookmarks::/,$ p' - | sed '1 d' > cache/bookmarks.json && touch cache/.completed && rm cache/.inprogress";
    exec($command);
  }





  private function displayPage($bookmarks, $feeds) {
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
    <button id="updatebutton" onclick="updateBookmarks()">Retrieve updated sync data</button><span style="display: none" id="activity"> <img src="assets/loader.gif" alt="activity indicator"/></span>
    <br>
    <span onclick="activateBookmarksTab()">Bookmarks</span>
    <span onclick="activateFeedsTab()">Feeds</span>
    <div id="bookmarkstab">
      <h1>Bookmarks</h1>

EOT;
      echo 'Updated: ' . $bookmarks->getTimestamp();
      echo <<<'EOT'
      <br>
      <input type="checkbox" id="openinnewtabtoggle" onclick="toggleOpenInNewTab();">
      <label for="openinnewtabtoggle">Open links in new Tab</label>
      <ul id="bookmarkslist">

EOT;
      echo $bookmarks->renderHTML();
      echo <<<'EOT'
    
      </ul>
    </div>
    <div id="feedstab" style="xdisplay: none;">
      <h1>Feeds</h1>
      <ul id="feedlist">

EOT;
      echo $feeds->renderHTML();
      echo <<<'EOT'
      </ul>
    </div>
  </body>
</html>
EOT;
  }
}
?>
