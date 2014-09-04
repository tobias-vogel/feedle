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
      else if ($parameters['action'] == 'refreshfeed') {
        try {
          // (re)load the contents of the provided feed (id)

          // this feed id is used for directory lookup and should not contain . or /
          Feedle::parameterSanityCheck($parameters, 'feedid', '/[a-zA-Z0-9_-]+/');

          $feedid = $parameters['feedid'];

          // we have a proper (but not necessarily existing) feed id
          // look up the feed uri (and perhaps credentials)
          $iniFile = 'cache/feeds/' . $feedid . '/meta.ini';
          $data = parse_ini_file($iniFile);


          // for non-empty credentials, another call must be made
          if ($data['username'] == '' and $data['password'] == '') {
            $feedBody = file_get_contents($data['uri']);
            require_once('lib/simplepie/autoloader.php');
            $simplePie = new SimplePie();
            $simplePie->set_raw_data($feedBody);
            $simplePie->init();
            $items = array();
            foreach ($simplePie->get_items() as $item) {
              $items []= array('title' => $item->get_title(), 'link' => $item->get_permalink(), 'timestamp' => $item->get_date("U"));
            }

            // save the items to file (unless they are)
            foreach ($items as $item) {
              #$safeId = preg_replace('/[^a-zA-Z0-9]/', "", $item['id']);
              $safeId = $item['timestamp'];
              $itemfilename = 'cache/feeds/' . $feedid . '/' . $safeId;
              if (!file_exists($itemfilename)) {
                $itemContents = 'title = "' . addslashes($item['title']) . '"' . "\n" . 'uri = "' . $item['link'] . '"' . "\n" . 'timestampid = ' . $item['timestamp'];
                file_put_contents($itemfilename, $itemContents);
              }
            }

            // render the feed contents
            echo FeedDataStructure::renderFeedContents($feedid);
          }
          else {
            header(':', true, 403);
            throw new Exception('Error, credentials required (enter them manually into the corresponding ini file)');
          }
        }
        catch (Exception $e) {
          echo '<li>' . $e->getMessage() . '</li>' . "\n";
        }
      }
      else if ($parameters['action'] == 'movefeeditemtoarchive') {
        try {
          Feedle::parameterSanityCheck($parameters, 'feedid', '/[a-zA-Z0-9_-]+/');
          Feedle::parameterSanityCheck($parameters, 'feeditemid', '/[0-9]+/');

          $feedDirectory = 'cache/feeds/' . $parameters['feedid'];
          $feedArchiveDirectory = $feedDirectory . '/archive';
          if (!file_exists($feedArchiveDirectory))
            mkdir($feedArchiveDirectory);

          $filename = $parameters['feeditemid'];

          rename($feedDirectory . '/' . $filename, $feedArchiveDirectory . '/' . $filename);
        }
        catch (Exception $e) {
          header(':', true, 400);
          throw new Exception('Error, feed item could not be archived.');
        }
      }
    }
    else {
      // just display the (possibly) cached bookmarks together with the whole page
      list($bookmarks, $feeds) = Feedle::readBookmarksFromCache();
      self::displayPage($bookmarks, $feeds);
    }
  }





  private static function parameterSanityCheck($parameterArray, $parameterName, $parameterRegex) {
    if (!isset($parameterArray[$parameterName])) {
      header(':', true, 400);
      throw new Exception('Error, no ' . $parameterName . ' provided');
    }

    $parameterValue = $parameterArray[$parameterName];
    if ($parameterValue == '') {
      header(':', true, 400);
      throw new Exception('Error, empty ' . $parameterName . ' provided');
    }

    if (preg_replace($parameterRegex, '', $parameterValue) != '') {
      header(':', true, 400);
      throw new Exception('Error, invalid ' . $parameterName . ' provided (strange characters in it)');
    }
  }





  private static function readBookmarksFromCache() {
    // read the bookmarks from cache
    $json = null;
    $timestamp = null;

    // create the feeds directory (or nothing is it exists)
    if (!file_exists('cache/feeds'))
      mkdir('cache/feeds');

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
            $fds->addFeed($id, $name, $feedUri);

            // create a cache subdirectory for this feed (if it does not exist)
            $feedDir = 'cache/feeds/' . $id;
            if (!file_exists($feedDir))
              mkdir($feedDir);

            // create an ini file for this feed (if it does not yet exist)
            $feedIniFilename = $feedDir . '/meta.ini';
            if (!file_exists($feedIniFilename)) {
              $feedIniContents = 'uri = "' . $feedUri . '"' . "\n" . 'username = ""' . "\n" . 'password = ""' . "\n";
              file_put_contents($feedIniFilename, $feedIniContents);
            }
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
    <h1>View</h1>
    <span onclick="activateBookmarksTab()">Bookmarks</span>
    <span onclick="activateFeedsTab()">Feeds</span>
    <div id="bookmarkstab" style="display: none;">
      <h1>Bookmarks</h1>

EOT;
      echo 'Updated: ' . $bookmarks->getTimestamp();
      echo <<<'EOT'
      <br>
      <input type="checkbox" id="openinnewtabtoggle" onclick="toggleOpenInNewTab();">
      <label for="openinnewtabtoggle">Open links in new Tab</label>
      <ul id="bookmarkslist">

EOT;
//      echo $bookmarks->renderHTML();
      echo <<<'EOT'
    
      </ul>
    </div>
    <div id="feedstab" style="xdisplay: none;">
      <h1>Feeds</h1>
      <button id="feedupdatebutton" onclick="alert('not yet implemented')//queryFeedsForNewItems()"><img src="assets/refresh.png"> all<!--Retrieve all feed items--></button><span style="display: none" id="activity"> <img src="assets/loader.gif" alt="activity indicator"/></span>
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
