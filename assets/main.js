function update() {
  // display activity indicator
  document.getElementById("activity").style.display = "inline";

  // start ajax request
  $.ajax({
    type: "GET",
    url: "index.php",
    data: "update",
    success: function(response) {
      // start timeout to retrieve the sync data in some seconds
      // TODO timeout
    }
});
}
