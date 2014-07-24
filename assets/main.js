var timeout = 3000;

function update() {
  // display activity indicator
  document.getElementById("activity").style.display = "inline";

  // clear table
  document.getElementById("bookmarkstablebody").innerHTML = "";

  // start ajax request
  $.ajax({
    type: "GET",
    url: "index.php",
    data: "action=updatebookmarks"
  });

  // start timeout to retrieve the sync data in some seconds
  window.setTimeout("getBookmarks()", timeout);
}

function getBookmarks() {
  $.ajax({
    type: "GET",
    url: "index.php",
    data: "action=getbookmarks",
    statusCode: {
      200: function(response) {
        if (response == "") {
          // wait one more cycle, for some reason
          window.setTimeout("getBookmarks()", timeout);
        }
        else
          updateTableWithBookmarks(response);
      },
      202: function() {
        window.setTimeout("getBookmarks()", timeout);
      }
    }
  });
}

function updateTableWithBookmarks(bookmarksHTML) {
  document.getElementById("bookmarkstablebody").innerHTML = bookmarksHTML;
  document.getElementById("activity").style.display = "none";
}
