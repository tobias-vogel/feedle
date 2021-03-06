<?php
require_once('src/ConfigLoader.class.php');
require_once('src/ConfigurationNotFoundException.class.php');
require_once('src/BookmarkDataStructure.class.php');
require_once('src/BookmarkItem.class.php');
require_once('src/BookmarkFeed.class.php');
require_once('src/BookmarkFolder.class.php');
require_once('src/FeedDataStructure.class.php');

class Feedle {

  private static $configuration;

  public static function run($parameters) {
    // what we have to do depends on the parameters
    if (isset($parameters['action'])) {
      if ($parameters['action'] == 'retrievebookmarksfromsyncserver') {
        Feedle::retrieveBookmarksFromSyncServer();
      }
      else if ($parameters['action'] == 'getbookmarks') {
        Feedle::getBookmarks();
      }
      else if ($parameters['action'] == 'refreshfeed') {
        Feedle::refreshFeed($parameters);
      }
      else if ($parameters['action'] == 'movefeeditemtoarchive') {
        Feedle::moveFeedItemToArchive($parameters);
      }
      else if ($parameters['action'] == 'storeanote') {
        Feedle::storeANote($parameters);
      }
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





  private static function retrieveBookmarksFromSyncServer() {
    // if an update is currently running, do nothing
    if (file_exists('cache/bookmarks.json.lock'))
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





  public static function readBookmarksFromCache() {
    // read the bookmarks from cache
    $json = null;
    $timestamp = null;

    // create the feeds directory (or nothing if it exists)
    if (!file_exists('cache/feeds'))
      mkdir('cache/feeds');

    if (file_exists('cache/bookmarks.json') && !file_exists('cache/bookmarks.json.lock')) {
      $json = file_get_contents('cache/bookmarks.json');
      $timestamp = filemtime('cache/bookmarks.json');
    }

    // convert json to bookmarks and feeds
    $bds = new BookmarkDataStructure($timestamp);
    $fds = new FeedDataStructure();

    if ($json != null) {
      foreach (json_decode($json, true) as $entry) {
        $type = $entry['type'];
        // only consider entries that actually are bookmarks, feeds, or folders
        if (!(
          $type == 'bookmark' or
          $type == 'livemark' or
          $type == 'folder'))
          {
            // ignore this type of bookmark, it is a query, (deleted) item, or separator
            continue;
        }
        else {
          $name = $entry['title'];
          $description = $entry['description'] == NULL ? '' : $entry['description'];
          $parentId = $entry['parentid'];
          $id = $entry['id'];

          if ($type == 'bookmark') {
            // read all bookmark specific details
            $hyperlink = $entry['bmkUri'];
            // merge tags and keywords (what's the difference?)
            $tags = array_merge(count($entry['tags']) > 0 ? explode(' ', implode(' ', $entry['tags'])) : array(), ($entry['keyword'] == null ? array() : explode(' ', $entry['keyword'])));

            // add this bookmark
            $bds->addBookmark($id, $parentId, $name, $description, $hyperlink, $tags);
          }
          else if ($type == 'folder') {
            $children = $entry['children'];
            $bds->addFolder($id, $parentId, $name, $description, $children);
            
          }
          else {
            // read all feed specific details
            $feedUri = $entry['feedUri'];

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

            // add this feed as a bookmark, too
            $bds->addBookmark($id, $parentId, $name, $description, $feedUri, array(), true);
          }
        }
      }
    }
    
    // read bookmarksnotes from disk
    $bookmarksnotesDirectoryName = 'cache/bookmarksnotes';
    $bookmarksnotesDirectory = opendir($bookmarksnotesDirectoryName);
    $notes = array();
    while ($file = readdir($bookmarksnotesDirectory)) {
      if (in_array($file, array('.', '..')))
        continue;

      $note = file_get_contents($bookmarksnotesDirectoryName . '/' . $file);
      // replace newlines by <br>
      $note = str_replace("\n", '<br>', $note);
      $notes []= $note;
    }
    closedir($bookmarksnotesDirectory);
    $bds->addNotes($notes);
    
    return array($bds, $fds);
  }





  private static function getBookmarks() {
    // return the bookmarks from the cached file (or nothing, if there is no file)
    if (file_exists('cache/bookmarks.json.lock')) {
      // tell the client that the file has not yet been fetched
      // http_response_code(204); // does not work for PHP 5.3.3
      header(':', true, 204);
    }
    else {
      list($bookmarks, $feeds) = Feedle::readBookmarksFromCache();
      echo $bookmarks->renderHTML();
    }
  }





  private static function readBookmarksFromWebAndSaveIt($configuration) {
    // start a process that does query the sync server
    $command = "touch cache/bookmarks.json.lock && lib/fxa-sync-client/bin/sync-cli.js -e " . $configuration['email'] . " -p " . $configuration['password'] . " -t bookmarks | sed -n -E -e '/::bookmarks::/,$ p' - | sed '1 d' > cache/bookmarks.json && rm cache/bookmarks.json.lock";
    exec($command);
  }




  private static function refreshFeed($parameters) {
    try {
      // (re)load the contents of the provided feed (id)

      // this feed id is used for directory lookup and should not contain . or /
      Feedle::parameterSanityCheck($parameters, 'feedid', '/[a-zA-Z0-9_-]+/');

      $feedid = $parameters['feedid'];

      // we have a proper (but not necessarily existing) feed id
      // look up the feed uri (and perhaps credentials)
      $iniFile = 'cache/feeds/' . $feedid . '/meta.ini';
      $data = parse_ini_file($iniFile);


      $uri =  $data['uri'];

      // first try
      do {
        // use CURL to fetch the feed's contents (necessary, because file_get_contents and simplepie cannot handle HTTP authorization)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'something not on a blacklist');
        // provide a (possibly empty, does not harm) password
        curl_setopt($ch, CURLOPT_USERPWD, $data['username'] . ':' . $data['password']);
        $feedBody = curl_exec($ch);
        $inspectionResult = curl_getinfo($ch);
        $httpCode = $inspectionResult['http_code'];
        curl_close($ch);

        $tryAgain = false;
        if ($httpCode != 0) {
          // request did work somehow, even if the page was not found
          // do nothing and exit loop
        }
        else {
          // request did not work (possibly due to HTTPS certificate stuff)
          // retry it without https (if this was the reason)
          $uri = preg_replace("/^https:/", "http:", $uri);
          $tryAgain = true;
        }
      } while ($tryAgain);

      if ($httpCode == 200) {
        require_once('lib/simplepie/autoloader.php');
        $simplePie = new SimplePie();
        $simplePie->set_raw_data($feedBody);
        $simplePie->init();
        $items = array();
        foreach ($simplePie->get_items() as $item) {
          // if there is no timestamp, use the feed item's permalink to have something (hopefully) unique (which is then not sorted by time)
          $timestamp = $item->get_date("U");
          $timestamp = $timestamp == null ? md5($item->get_permalink()) : $timestamp;
          $items []= array('title' => $item->get_title(), 'link' => $item->get_permalink(), 'timestamp' => $timestamp);
        }

        // save the items to file (unless they are already in the archive)
        foreach ($items as $item) {
          $safeId = $item['timestamp'];
          $itemfilename = 'cache/feeds/' . $feedid . '/' . $safeId;
          $itemInArchiveFilename = 'cache/feeds/' . $feedid . '/archive/' . $safeId;
          if (!file_exists($itemfilename) and !file_exists($itemInArchiveFilename)) {
            $itemContents = 'title = "' . addcslashes($item['title'], "\\'\"\0\n") . '"' . "\n" . 'uri = "' . $item['link'] . '"' . "\n" . 'timestampid = ' . $item['timestamp'];
            file_put_contents($itemfilename, $itemContents);
          }
        }


        // perhaps, update the feed's favicon
        if (rand(1, 10) == 10) {
          // get homepage from feed
          // do not use simplepie for that, it's currently broken or at least there is now way to fetch the <link> of the <channel>
          preg_match('/<channel>.*?<link>.*?(?=<\/link>)/s', $feedBody, $match);
          if (empty($match)) {
            // the feed did not contain a channel tag or a link tag whatsoever, so we need to go on without favicon
          }
          else {
            $match = $match[0];
            $homepage = preg_replace('/<channel>.*?<link>/s', '', $match);
            Feedle::downloadFavicon($data, $feedid, $homepage);
          }
        }

        // render the feed contents
        $files = FeedDataStructure::getListOfFilesForFeed($feedid);
        echo FeedDataStructure::renderFeedContents($files, $feedid);
      }
      else if ($httpCode == 401) {
        header(':', true, 403);
        throw new Exception('Error, credentials required (enter them manually into the corresponding ini file)');
      }
      else {
        header(':', true, 403);
        throw new Exception('Error, something went wrong with the request. (uri = ' . $data['uri'] .')');
      }
    }
    catch (Exception $e) {
      echo $e->getMessage();
    }
  }





  private static function downloadFavicon($data, $feedid, $homepage) {
    $uri = $data['uri'];
    $username = $data['username'];
    $password = $data['password'];
    $targetFilename = 'cache/feeds/' . $feedid . '/favicon.ico';

    

    // there are several ways to get the (desired) favicon
    // 1. feeduri favicon.ico (guess)
    // 2. homepage favicon.ico (guess) 
    // 3. homepage html head section (hard to find out)

    // bypass the first step if the feed goes to feedburner
    if (preg_match('/feed.+?\.feedburner.com/', $uri) === 0) {
      // get the base uri, the part from the beginning upto (including) the third slash which should be the base address
      $thirdSlashIndex = strpos($uri, '/', 8);
      $baseAddress = substr($uri, 0, $thirdSlashIndex);
      $baseAddressFaviconUri = $baseAddress . '/favicon.ico';
      if (Feedle::reallyDownloadFavicon($baseAddressFaviconUri, $username, $password, $targetFilename)) return;
    }


    // just try it with the homepage
    $homepageFaviconUri = $homepage . (substr($homepage, strlen($homepage) - 1, 1) === '/' ? '' : '/') . 'favicon.ico';
    if (Feedle::reallyDownloadFavicon($homepageFaviconUri, $username, $password, $targetFilename)) return;


    // try to parse the icon uri from the main page's html
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $homepage);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'something not on a blacklist');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // get the response as a string from curl_exec(), rather than echoing it
    // provide a (possibly empty, does not harm) password
    curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
    $body = curl_exec($ch);
    curl_close($ch);

    // look for all the <link and then href="..." where rel="something with icon"
    $parts = preg_split('/<link/', $body);
    array_shift($parts);
    foreach ($parts as $part) {
      // get the value of the rel attribute
      preg_match('/(?<=rel=([\'"])).*?(?=\1)/', $part, $match);
      // only the first match is relevant
      $match = $match[0];
      // if the rel attribute value has icon in it...
      if (preg_match('/icon/i', $match) === 1) {
        // ...get the value of the href attribute...
        preg_match('/(?<=href=([\'"])).*?(?=\1)/', $part, $match);
        $htmlFaviconUri = $match[0];
        // if the URI starts with "//", prepend it with the appropriate schema
        if (substr($htmlFaviconUri, 0, 2) === '//') {
          preg_match('/https?:/', $homepage, $schema);
          $schema = $schema[0];
          $htmlFaviconUri = $schema . $htmlFaviconUri;
        }
        else
          // if the URI does not start with "http", prepend it with the base address (from above)
          if (substr($htmlFaviconUri, 0, 4) != 'http') {
            $thirdSlashIndex = strpos($homepage, '/', 8);
            $baseAddress = substr($homepage, 0, $thirdSlashIndex);

            // if the URI does not start with "/", add the missing slash
            if (substr($htmlFaviconUri, 0, 1) != '/')
              $htmlFaviconUri = '/' . $htmlFaviconUri;

            $htmlFaviconUri = $baseAddress . $htmlFaviconUri;
          }
        // ... and query the server with that
        if (Feedle::reallyDownloadFavicon($htmlFaviconUri, $username, $password, $targetFilename)) return;
      }
    }
  }





  private static function reallyDownloadFavicon($uri, $username, $password, $targetFilename) {
    // use curl to get the file (because it can check the content type image/* and that it is no error message)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'something not on a blacklist');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // get the response as a string from curl_exec(), rather than echoing it
    // provide a (possibly empty, does not harm) password
    curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
    $targetFile = fopen($targetFilename, 'w');
    curl_setopt($ch, CURLOPT_FILE, $targetFile);
    curl_exec($ch);
    fclose($targetFile);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($httpCode != 200 or $contentLength == 0 or substr($contentType, 0, 6) != 'image/') {
      // this favicon did not exist or was somehow weird
      if (file_exists($targetFilename))
        // remove the favicon file (if a file was created at all)
        unlink($targetFilename);
      // try it again with the next candidate
      return false;
    }
    else {
      // everything seems to be ok with the favicon
      return true;
    }
  }





  private static function moveFeedItemToArchive($parameters) {
    try {
      Feedle::parameterSanityCheck($parameters, 'feedid', '/[a-zA-Z0-9_-]+/');
      Feedle::parameterSanityCheck($parameters, 'feeditemid', '/[0-9a-f]+/');

      $feedDirectory = 'cache/feeds/' . $parameters['feedid'];
      $feedArchiveDirectory = $feedDirectory . '/archive';
      if (!file_exists($feedArchiveDirectory))
        mkdir($feedArchiveDirectory);

      $filename = $parameters['feeditemid'];

      $sourceFilenameFull = $feedDirectory . '/' . $filename;
      $targetFilenameFull = $feedArchiveDirectory . '/' . $filename;


      if (!file_exists($sourceFilenameFull))
        throw new Exception('Error, the feed item could not be found, maybe it was already archived?');

      $success = rename($sourceFilenameFull, $targetFilenameFull);
      if (!$success)
        throw new Exception('Error, the feed item could not be archived for an unknown reason.');
    }
    catch (Exception $e) {
      header(':', true, 400);
      echo $e->getMessage();
    }
  }





  private static function storeANote($parameters) {
    $noteToStore = $parameters['notetostore'];
    // save the note with a current timestamp
    $bookmarksnotesDir = 'cache/bookmarksnotes';
    if (!file_exists($bookmarksnotesDir))
      mkdir($bookmarksnotesDir);
    
    $timestamp = round(microtime(true) * 1000);
    $fileToSave = $bookmarksnotesDir . "/" . $timestamp;
    file_put_contents($fileToSave, $noteToStore);

    // http redirect to the bookmarks page
    header('Location: ' . $_SERVER['HTTP_REFERER']);
  }





  private static function displayPage($title, $favicon, $appleTouchIcon, $content, $timestamp) {
    // do it with echo, later a proper template engine may be more appropriate

    $result = '';
    $result .= "<!DOCTYPE html>\n";
    $result .= "<html>\n";
    $result .= "  <head>\n";
    $result .= "    <title>$title</title>\n";
    $result .= "    <meta charset=\"utf-8\">\n";
    $result .= "    <script src=\"assets/main.js\" type=\"text/javascript\"></script>\n";
    $result .= "    <script src=\"//code.jquery.com/jquery-2.1.1.min.js\"></script>\n";
    $result .= "    <script src=\"https://raw.github.com/gabrieleromanato/jQuery-MD5/master/jquery.md5.min.js\"></script>\n";
    $result .= "    <link href=\"assets/style.css\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $result .= "    <link rel=\"icon\" href=\"assets/$favicon\" type=\"image/x-icon\"/>\n";
    $result .= "    <link rel=\"apple-touch-icon\" href=\"assets/$appleTouchIcon\"/>\n";
    $result .= "    <meta name=\"viewport\" content=\"width=device-width\"/>\n";
    $result .= "  </head>\n";
    $result .= "  <body>\n";
    $result .= "    <ul id=\"errorbar\"></ul>\n";
    $result .= "    <h1>Control</h1>\n";
    $result .= "    <p><button id=\"updatebutton\" onclick=\"updateBookmarks()\">Retrieve updated sync data</button><span style=\"display: none\" id=\"activity\"> <img src=\"assets/loader.gif\" alt=\"activity indicator\"/></span></p>\n";
    $result .= "    <p>Updated: " . $timestamp . "</p>\n";
    $result .= "    <p><a href=\"bookmarks.php\">Bookmarks</a> <a href=\"feeds.php\">Feeds</a></p>\n";
    $result .= "    <p><input type=\"checkbox\" id=\"openinnewtabtoggle\" onclick=\"toggleOpenInNewTab();\" checked=\"checked\"><label for=\"openinnewtabtoggle\">Open links in new Tab</label></p>\n";
    $result .= "    <div>\n";
    $result .= "      <h1>$title</h1>\n";
    $result .= $content;
    $result .= "    </div>\n";
    $result .= "  </body>\n";
    $result .= "</html>\n";

    return $result;
  }





  public static function displayBookmarkPage($bookmarks) {
    $title = 'Bookmarks';
    $favicon = 'bookmark.ico';
    $appleTouchIcon = 'bookmark-128.png';
    $content =
      "<form method=\"post\" action=\"endpoint.php\"><input type=\"hidden\" name=\"action\" value=\"storeanote\"/>Store a note (for a bookmark)<br/><textarea name=\"notetostore\"></textarea><br/><input type=\"submit\" value=\"Store this note\"/></form>" .
      "<ul id=\"bookmarkslist\">\n" .
      $bookmarks->renderHTML() .
      "</ul>\n";

    return Feedle::displayPage($title, $favicon, $appleTouchIcon, $content, $bookmarks->getTimestamp());
  }





  public function displayFeedPage($feeds, $timestamp) {
    $title = 'Feeds';
    $favicon = 'feed.ico';
    $appleTouchIcon = 'feed-128.png';
    $content =
      "<p id=\"feedcounter\"></p>\n" .
      "<p><div class=\"clear-block\"><a class=\"button\" href=\"#\" onclick=\"openRandomFeedItem(); return false;\" title=\"Open random feed item\"><img src=\"assets/random.png\" alt=\"Dies\" width=\"150\"/><br/>Open random feed item</a></div><div class=\"clear-block\"><a class=\"button\" href=\"#\" onclick=\"openRandomFeedItemAndRemovePreviousRandomFeedItem(); return false;\" title=\"Open random feed item and remove previous random feed item\"><img src=\"assets/random.png\" alt=\"Dies\" width=\"150\"/><br/>Open random feed item and remove previous random feed item</a></div></p>\n" . 
      "<p><duv class=\"clear-block\"><a class=\"button\" href=\"#\" id=\"feedupdatebutton\" onclick=\"updateAllFeedContents(); return false;\"><img src=\"assets/refresh.png\" alt=\"activity indicator\"> all</a></div><span style=\"display: none\" id=\"allfeedsactivity\"> <img src=\"assets/loader.gif\" alt=\"activity indicator\"/></span></p><br>\n" .
      "<p><input type=\"checkbox\" id=\"autoupdatefeedstoggle\" onclick=\"toggleAutoUpdateFeeds();\"><label for=\"autoupdatefeedstoggle\">Auto-update feed items randomly (when not in focus)</label></p>\n" .
      "<p><input type=\"checkbox\" id=\"showallfeedstoggle\" onclick=\"toggleShowAllFeeds();\"><label for=\"showallfeedstoggle\">Show all feeds</label></p>\n" .
      "<ul id=\"feedlist\">\n" .
      $feeds->renderHTML() .
      "</ul>\n" .
      "<form onsubmit=\"return false;\"><input id=\"errortester\" value=\"Some error message\"/><button onclick=\"addErrorToErrorBar($('#errortester').val(), $.md5($('#errortester').val()))\">Test this error message</button></form>";
    return Feedle::displayPage($title, $favicon, $appleTouchIcon, $content, $timestamp);
  }
}
