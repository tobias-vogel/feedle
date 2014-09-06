var timeout = 3000;

function updateBookmarks() {
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
  //getFeeds();
}

function refreshFeed(feedid, async = true) {
  $("#" + feedid + " div").html("Updating…");
  //document.getElementById(feedid).innerHTML = "Updating…";
  $.ajax({
    type: "POST",
    url: "index.php",
    data: "action=refreshfeed&feedid=" + feedid,
    async: async,
    statusCode: {
      200: function(response) {
        updateFeedContents(feedid, response);
      },
      400: function() {
        updateFeedContents(feedid, response);
      },
      404: function() {
        updateFeedContents(feedid, response);
      }
    }
  });
}

function updateFeedContents(feedid, response) {
  //document.getElementById(feedid).innerHTML = response;
  $("#" + feedid + " div").html(response);
}

function archiveFeedItem(feedId, feedItemId) {
  // ajax: move item to the archive
   $.ajax({
    type: "POST",
    url: "index.php",
    data: "action=movefeeditemtoarchive&feedid=" + feedId + "&feeditemid=" + feedItemId,
    statusCode: {
      200: function(response) {
        // remove the feed item from the list
        $("#" + feedId + "-" + feedItemId).remove();
      }
    }
  });
}

function updateAllFeedContents() {
  var feedIds = new Array();
  $("#feedlist > li").each(function(index, feed) {
    feedIds.push(feed.id);
    refreshFeed(feed.id, false);
  });
}
