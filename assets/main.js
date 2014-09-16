var timeout = 3000;

function updateBookmarks() {
  // display activity indicator
  document.getElementById("activity").style.display = "inline";

  // deactivate button
  $("#updatebutton").attr("disabled", "disabled");

  // clear list
  var bookmarksList = document.getElementById("bookmarkslist");
  if (bookmarksList != null)
    bookmarksList.innerHTML = "";

  // start ajax request
  $.ajax({
    type: "POST",
    url: "endpoint.php",
    data: "action=retrievebookmarksfromsyncserver"
  });

  // start timeout to retrieve the sync data in some seconds
  window.setTimeout("getBookmarks()", timeout);
}

function getBookmarks() {
  $.ajax({
    type: "GET",
    url: "endpoint.php",
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
  var bookmarksList = document.getElementById("bookmarkslist");
  if (bookmarksList != null)
    bookmarksList.innerHTML = bookmarksHTML;
  document.getElementById("activity").style.display = "none";
  $("#updatebutton").removeAttr("disabled");
}

function toggleOpenInNewTab() {
  var openInNewTab = document.getElementById("openinnewtabtoggle").checked;
  $('ul#bookmarkslist li div.hyperlink a').each(function(index) {
    this.target = (openInNewTab ? "_blank" : "_self");
  });
}

/*
function activateBookmarksTab() {
  document.getElementById("bookmarkstab").style.display = "block";
  document.getElementById("feedstab").style.display = "none";
}

function activateFeedsTab() {
  document.getElementById("bookmarkstab").style.display = "none";
  document.getElementById("feedstab").style.display = "block";
}
*/

function refreshFeed(feedid, async) {
  $("#" + feedid + " div ul").html("Updating…");
  //document.getElementById(feedid).innerHTML = "Updating…";
  $.ajax({
    type: "POST",
    url: "endpoint.php",
    data: "action=refreshfeed&feedid=" + feedid,
    async: async,
    statusCode: {
      200: function(response) {
        updateFeedContents(feedid, response, 200);
      },
      400: function(response) {
        updateFeedContents(feedid, response);
      },
      403: function(xhr, type, info) {
        updateFeedContents(feedid, xhr.responseText, 403);
      },
//      404: function(response) {
//        updateFeedContents(feedid, response);
//      }
    },
    error: function (xhr, type, info) {
      updateFeedContents(feedid, xhr.responseText, xhr.status);
    }
  });
}

function updateFeedContents(feedid, response, httpStatus) {
  // remove the "updating…"
  $("#" + feedid + " div ul").html("");

  if (response == "")
    return;

  //document.getElementById(feedid).innerHTML = response;
  $("#" + feedid + " div ul").html(response);

  // make the feed visible (might have been hidden)
  $("#" + feedid).css("display", "list-item");

  if (httpStatus == 200)
    $("#" + feedid + " div ul").removeAttr("class"); // in case there was an error, earlier, that should be removed now
  else
    $("#" + feedid + " div ul").prop("class", "error");
}

function archiveFeedItem(feedId, feedItemId) {
  // remove the feed item from the list
  $("#" + feedId + "-" + feedItemId).remove();
  hideFeedIfPossible(feedId);

  // ajax: move item to the archive
  $.ajax({
    type: "POST",
    url: "endpoint.php",
    data: "action=movefeeditemtoarchive&feedid=" + feedId + "&feeditemid=" + feedItemId,
    error: function(xhr, textStatus, errorThrown) {
      var errorMessage = xhr.responseText;//"Feed Item could not be removed (" + xhr + ", " + textStatus + ", " + errorThrown + ").";
      addErrorToErrorBar(errorMessage, $.md5(errorMessage));
    }
  });
}

function updateAllFeedContents() {
  // show all feed update indicator
  $("#allfeedsactivity").css("display", "inline");

  $("#feedlist > li").each(function(index, feed) {
    refreshFeed(feed.id, false);
  });

  // show all feed update indicator
  $("#allfeedsactivity").css("display", "none");
}

function toggleShowAllFeeds() {
  var showAllFeeds = document.getElementById("showallfeedstoggle").checked;

  $("#feedlist > li").each(function(index, feed) {
    if (showAllFeeds) {
      showFeed(feed.id, true);
    }
    else {
      hideFeedIfPossible(feed.id);
    }
  });
}

function hideFeedIfPossible(feedId) {
  if ($("#" + feedId + " div ul li").length == 0)
    $("#" + feedId).css("display", "none");
}

function showFeed(feedId, forceShow) {
  if (forceShow || ($("#" + feedId + " div ul li").length > 0)) {
    $("#" + feedId).css("display", "list-item");
  }
}

function addErrorToErrorBar(errorMessage, errorMessageId) {

  var closeSnippet = " <span class=\"errormessageclosebutton\" onclick=\"removeErrorMessage('" + errorMessageId + "')\">[x]</span>";
  $("#errorbar").append("<li id=\"" + errorMessageId + "\">" + errorMessage + closeSnippet + "</li>");
}

function removeErrorMessage(id) {
  $("#" + id).remove();
}
