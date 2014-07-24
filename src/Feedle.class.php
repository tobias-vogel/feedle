<?php
require_once('src/ConfigLoader.class.php');
require_once('src/ConfigurationNotFoundException.class.php');

class Feedle {

  private static $configuration;

  public static function run() {
    try {
      self::$configuration = ConfigLoader::loadConfiguration();
    }
    catch (ConfigurationNotFoundException $cnfe) {
      echo $cnfe->getMessage() . "<br>\n";
      exit;
    }


    $bookmarks = self::readBookmarks();



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
      self::readBookmarkJsonFromWebAndSaveIt();
    }

    $json = file_get_contents('cache/bookmarks.json');

    //TODO convert json to bookmarks array
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











}
?>
