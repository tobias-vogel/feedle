<?php
class BookmarkReader {
  public static function readBookmarks() {
    // read the bookmarks from cache or from the web, if not available (and then cache it)

    if (!file_exists('cache/bookmarks.json')) {
      // the cached file is not available, read it from the web and save it
      readBookmarkJsonFromWebAndSaveIt();
    }

    $json = file_get_contents('cache/bookmarks.json');

    //TODO convert json to bookmarks array
  }





  private static function readBookmarkJsonFromWebAndSaveIt() {
    // start a process that does query the sync server
    // query: lib/fxa-sync-client/bin/sync-cli.js -e email -p password -t bookmarks | sed -n -E -e '/::bookmarks::/,$ p' - | sed '1 d' > cache/bookmarks.json
  }
}
?>
