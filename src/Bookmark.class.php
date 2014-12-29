<?php
abstract class Bookmark {
  protected $id;
  protected $name;
  protected $description;

  function __construct($id, $name, $description) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
  }





  public function getId() {
    return $this->id;
  }





  public function getName() {
    return $this->name;
  }





  public function getDescription() {
    return $this->description;
  }





  abstract public function toHtml();
}
?>