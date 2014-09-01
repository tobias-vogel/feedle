var timeout = 3000;

function update() {
  // display activity indicator
  document.getElementById("activity").style.display = "inline";

  // deactivate button
  $("#updatebutton").attr("disabled", "disabled");

  // clear list
  document.getElementById("bookmarkslist").innerHTML = "";

  // start ajax request
  $.ajax({
    type: "POST",
    url: "index.php",
    data: "action=retrievebookmarksfromsyncserver"
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
        updateTableWithBookmarks(response);
      },
      204: function() {
        window.setTimeout("getBookmarks()", timeout);
      }
    }
  });
}

function updateTableWithBookmarks(bookmarksHTML) {
  document.getElementById("bookmarkslist").innerHTML = bookmarksHTML;
  document.getElementById("activity").style.display = "none";
  $("#updatebutton").removeAttr("disabled");
}

function toggleOpenInNewTab() {
  var openInNewTab = document.getElementById("openinnewtabtoggle").checked;
  $('ul#bookmarkslist li div.hyperlink a').each(function(index) {
    this.target = (openInNewTab ? "_blank" : "_self");
  });
}

function activateBookmarksTab() {
  document.getElementById("bookmarkstab").style.display = "block";
  document.getElementById("feedstab").style.display = "none";
}

function activateFeedsTab() {
  document.getElementById("bookmarkstab").style.display = "none";
  document.getElementById("feedstab").style.display = "block";
}
