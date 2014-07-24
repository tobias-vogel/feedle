<?php
class BookmarkReader {
  public static function readBookmarks() {

  }





  private static function readBookmarkJsonFromWebAndSaveIt() {
    // start a process that does query the sync server
    // query: lib/fxa-sync-client/bin/sync-cli.js -e email -p password -t bookmarks | sed -n -E -e '/::bookmarks::/,$ p' - | sed '1 d' > cache/bookmarks.json
  }
}
?>
