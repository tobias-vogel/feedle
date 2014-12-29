<?php
class BookmarkDataStructure {
  private $id2folderLookup = array();
  private $id2bookmarkLookup = array();
  private $roots = array();
  private $timestamp;
  private $notes;





  public function __construct($timestamp) {
    $this->timestamp = $timestamp;
  }





  public function addBookmark($id, $parentId, $name, $description, $hyperlink, $tags, $isFeed = false) {
    if ($isFeed) {
      $bookmark = new BookmarkFeed($id, $name, $description, $hyperlink, $tags);
    }
    else {
      $bookmark = new BookmarkItem($id, $name, $description, $hyperlink, $tags);
    }

    if ($parentId == 'places')
      $this->roots []= $bookmark;

    $this->id2bookmarkLookup[$id] = $bookmark;
  }
  
  
  
  
  
  public function addFolder($id, $parentId, $name, $description, $children) {
    $folder = new BookmarkFolder($id, $name, $description, $children);

    if ($parentId == 'places')
      $this->roots []= $folder;

    $this->id2folderLookup[$id] = $folder;
    $this->id2bookmarkLookup[$id] = $folder;
  }





  public function addNotes($notes) {
    $this->notes = $notes;
  }





  public function renderHTML() {
    $this->replaceIdStringsByObjects();

    $result = '';

    foreach ($this->notes as $index => $note) {
      $result .= '<li class="unsaved"><div class="title">Unsaved bookmark</div><div class="description">' . $note . '</div></li>' . "\n";
    }

    $newResult = '';
    foreach ($this->roots as $bookmark) {
      $newResult .= $bookmark->toHtml();
    }
    $result = $newResult;
    return $result;
  }





  public function printFolderStructureRecursively($bookmarkList) {
    $resultString = '<ul>';
    foreach ($bookmarkList as $bookmark) {
      $resultString .= '<li>';
      $resultString .= $bookmark->getName();
      if ($bookmark instanceof BookmarkFolder) {
        $resultString .= self::printFolderStructureRecursively($bookmark->getChildren());
      }
      $resultString .= '</li>';
    }
    $resultString .= '</ul>';
    return $resultString;
  }





  public function getTimestamp() {
    return strftime('%c', $this->timestamp);
  }





  private function replaceIdStringsByObjects() {
    // iteratively replace all the children strings by their corresponding objects in all folders
    foreach ($this->id2folderLookup as $id => $folder) {
      $childrenIds = $folder->getChildren();
      $objectifiedChildren = array();
      foreach ($childrenIds as $childId) {
        $child = $this->id2bookmarkLookup[$childId];
        $objectifiedChildren []= $child;
      }
      $folder->setChildren($objectifiedChildren);
    }
  }
}
?>