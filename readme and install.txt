Purpose
=======

This tool is meant to display the user's firefox bookmarks on a web server.
It is intended to make the bookmarks available when no local firefox installation is available, e.g., when on a trip, internet café, crashed computer.
It works with the (currently new) firefox sync 1.5, which uses firefox accounts for authentification.


Installation
============

The tool needs client functionality to connect to firefox sync. This functionality is provided by fxa-sync-client written by Edwin Wong (edmoz).
This client bases on npm (node.js package manager) which in turn bases on node.js. In particular, it requires a version 0.10.x.
In the /lib folder, there is a git submodule with the fxa-sync-client, which has to be checked out as well. (See http://git-scm.com/book/en/Git-Tools-Submodules or in German http://git-scm.com/book/de/Git-Tools-Submodule) In case it does not work anymore (updates might have destroyed compatibility to the rest of this tool) or github is not available or it has moved or…, a copy of it in the currently working version (at time of writing) is located under "/lib/_cached dependencies". It would have to be extraced and placed into the /lib/fxa-sync-client folder.

The client fxa-sync-client has to be installed. Before this can be done, an appropriate node.js installation has to be prepared. If you have one installed, you can just skip this step and try it out, but otherwise or to play safe, you should download the appropriate 0.10.x release of node.js and put it into /lib/node.js. Download it from http://blog.nodejs.org/release/ or take the copy located in "/lib/_cached dependencies" working for Linux 64 Bit (currently using Ubuntu 10.10 64 Bit). (Perhaps you have to ./configure, make, make install first.) In this directory, there is a bin subdirectory containing a node and a npm executable (or symlink or whatever).

You are now ready to install fxa-sync-client issueing a "sudo /path/to/our/custom/node.js/installation/npm install" when being in the /lib/fxa-sync-client directory.
When the installation succeeded, try the follwing command from /lib/fxa-sync-client/bin using your firefox sync credentials:
sync-cli.js -e myemailaddress -p mypassword -t bookmarks

Moreover, you need a PHP installation.

Put your credentials in a file called /config/credentials.ini:
email = youremail
password = yourpassword

The cache folder has to have write permissions for the www-data user (or just "other"): chmod o+w cache/
