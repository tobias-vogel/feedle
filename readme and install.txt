Purpose
=======

This tool is meant to display the user's firefox bookmarks on a web server.
It is intended to make the bookmarks available when no local firefox installation is available, e.g., when on a trip, internet café, crashed computer.
It works with the (currently new) firefox sync 1.5, which uses firefox accounts for authentification.


Installation
============

The installation requires some steps. They are accompanied with shell commands.
I assume that you have a working system with apache2 and git installed and php enabled.

Create a directory for feedle.

sudo mkdir -r /var/www/feedle

This directory should not have root restrictions, but belong to the current user.

cd /var/www/feedle
sudo chown `whoami` .
sudo chgrp `whoami` .

Now, feedle can be cloned into this directory.

git clone https://github.com/tobias-vogel/feedle.git .

For the webserver to write cached files, the cache directory access rights have to be changed appropriately.

chmod o+w cache

Now we start with the (not too) tough part. 
Feedle needs client functionality to connect to firefox sync. This functionality is provided by the github tool "fxa-sync-client" written by Edwin Wong (edmoz).
This client bases on npm (node.js package manager) which in turn bases on node.js. In particular, at time of writing, it requires a version 0.10.x.
In the /lib directory, there are two git submodules, node.js and the fxa-sync-client. (See http://git-scm.com/book/en/Git-Tools-Submodules)
Both have to be checked out and built/installed. Further, simplepie is another submodule, used for feed crawling.
We start with initializing these submodules. That actually downloads them, they are not cloned automatically.

git submodule init
git submodule update

We first build node.js. We have to checkout an appropriate version and build it.

cd lib/node.js
git checkout v0.10.29
./configure
make #takes some minutes
sudo make install

Node.js should now work. The following command should print the version:

node -v

If it did not work, download a pre-compiled v0.10.29 release from http://nodejs.org/dist/v0.10.29/, extract it and you are done.

The tutorial I used (https://ariejan.net/2011/10/24/installing-node-js-and-npm-on-ubuntu-debian/) says that npm also needs to be installed. For unknown reasons, not in my case.
Try npm with the following command, which should print the version:

npm -v

If it does not work, install it in the following way (commented out, here):

#curl https://npmjs.org/install.sh | sudo sh   # install npm, however for me, that was not necessary, it is already included

However, also this command did not work, download install.sh with the following command:

cd ..
wget --no-check-certificate https://npmjs.org/install.sh
chmod u+x install.sh
sudo ./install.sh

You are now ready to install (i.e., configure) fxa-sync-client.

cd ../fxa-sync-client
sudo ../node.js/bin/npm install #npm might (only) work without the path

When the installation succeeded, try the following command from /lib/fxa-sync-client/bin using your firefox sync credentials (It should give you a longly output, ending with a lot of json containing your bookmarks.):

bin/sync-cli.js -e myemailaddress -p mypassword -t bookmarks

Put your sync credentials in a file called /config/credentials.ini:
email = youremail
password = yourpassword

cd ../../config
joe credentials.ini # or any other editor of your choice

You should now add a .htaccess to the project to secure the contents from eavesdroppers.
