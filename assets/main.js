var timeout = 3000;

var windowHasFocus = true;

var autoUpdateAllFeeds = false;

var lastRandomlySelectedFeedItemId = null;

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
  $('a.newtabbablehref').each(function(index) {
    this.target = (openInNewTab ? "_blank" : "_self");
  });
}

function toggleAutoUpdateFeeds() {
  autoUpdateAllFeeds = document.getElementById("autoupdatefeedstoggle").checked;
}

function refreshFeed(feedid, async, displayUpdatedDataOnlyWhenWindowInactive) {
  if (displayUpdatedDataOnlyWhenWindowInactive) {
    // do not display an updating message, because this is a hidden action
  }
  else {
    $("#" + feedid + " div ul").html("Updating…");
  }

  $.ajax({
    type: "POST",
    url: "endpoint.php",
    data: "action=refreshfeed&feedid=" + feedid,
    async: async,
    statusCode: {
      200: function(response) {
        if (displayUpdatedDataOnlyWhenWindowInactive && windowHasFocus) {
          // discard the update for not interfering with the user
        }
        else {
          updateFeedContents(feedid, response, 200);
        }
      },
      400: function(response) {
        if (displayUpdatedDataOnlyWhenWindowInactive && windowHasFocus) {
          // discard the update for not interfering with the user
        }
        else {
          updateFeedContents(feedid, response);
        }
      },
//      403: function(xhr, type, info) {
//        updateFeedContents(feedid, xhr.responseText, 403);
//      },
//      404: function(response) {
//        updateFeedContents(feedid, response);
//      }
    },
    error: function (xhr, type, info) {
//      updateFeedContents(feedid, xhr.responseText, xhr.status);
      var errorMessage = xhr.responseText;
      addErrorToErrorBar(errorMessage, $.md5(errorMessage));
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
  // get the feed item's title
  var title = $("#" + feedId + "-" + feedItemId + " div a.newtabbablehref").text();

  // remove the feed item from the list
  $("#" + feedId + "-" + feedItemId).remove();
  hideFeedIfPossible(feedId);

  // ajax: move item to the archive
  $.ajax({
    type: "POST",
    url: "endpoint.php",
    data: "action=movefeeditemtoarchive&feedid=" + feedId + "&feeditemid=" + feedItemId,
    error: function(xhr, textStatus, errorThrown) {
      var responseStatus = xhr.status;
      var errorMessage = "";
      if (responseStatus == 0)
        errorMessage = "The server could not be reached.";
      else
        errorMessage = xhr.responseText;//"Feed Item could not be removed (" + xhr + ", " + textStatus + ", " + errorThrown + ").";
        errorMessage += " (" + title + ")";
      addErrorToErrorBar(errorMessage, $.md5(errorMessage));
    }
  });
}

function updateAllFeedContents() {
  // show all feed update indicator
  $("#allfeedsactivity").css("display", "inline");

  $("#feedlist > li").each(function(index, feed) {
    refreshFeed(feed.id, false, false);
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
  if (typeof errorMessage === "undefined") {
    errorMessage = "undefined error message!";
    errorMessageId = $.md5(errorMessage);
  }
  
  // check whether the error message id is already displayed
  if ($("#" + errorMessageId).length == 1) {
    // this error message is already displayed
    return;
  }

  var closeSnippet = " <span class=\"errormessageclosebutton\" onclick=\"removeErrorMessage('" + errorMessageId + "')\">[x]</span>";
  $("#errorbar").append("<li id=\"" + errorMessageId + "\">" + errorMessage + closeSnippet + "</li>");
}

function removeErrorMessage(id) {
  $("#" + id).remove();
}

function openRandomFeedItem() {
  var feedItems = $("a.newtabbablehref");
  var randomIndex = pickRandomNumber(feedItems.length);
  var feedItem = feedItems[randomIndex];
  lastRandomlySelectedFeedItemId = feedItem.parentNode.parentNode.id;
  var href = feedItem.href;
  window.open(href);
}

function openRandomFeedItemAndRemovePreviousRandomFeedItem() {
  if (lastRandomlySelectedFeedItemId != null) {
    var temp = lastRandomlySelectedFeedItemId.split("-");
    var feedId = temp[0];
    var feedItemId = temp[1];
    archiveFeedItem(feedId, feedItemId);
  }
  openRandomFeedItem();
}

window.onblur = function() {
  windowHasFocus = false;
}

window.onmouseout = function() {
  windowHasFocus = false;
}

window.onfocus = function() {
  windowHasFocus = true;
}

window.onmouseover = function() {
  windowHasFocus = true;
//  addErrorToErrorBar('onmouseover hat gefeuert', 'dasdasfasfas');
//  $("h1")[0].style.background = "green";
}

function pickRandomNumber(largestValue) {
  var randomNumber = Math.floor(Math.random() * largestValue);
  return randomNumber;
}

function autoUpdateAllFeedsPacemaker() {
  // only do something, if the auto update flag is set
  if (autoUpdateAllFeeds) {
    // pick a feed
    var feeds = $("#feedlist > li");
    var index = pickRandomNumber(feeds.length);
    var feed = feeds[index];
    var feedid = feed.id;

    // issue a feed update (but with careful result printing)
    refreshFeed(feedid, true, true);
  }

  // call this method over and over again (but it does nothing, if the flag is not set)
  window.setTimeout(function() {autoUpdateAllFeedsPacemaker();}, 60 * 1000);
}

window.onload = function() {
  autoUpdateAllFeedsPacemaker();
}
