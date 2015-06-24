Mediawiki exporter that exports a list of all pages with their URLs and ID's for use in the Resolver application.

Installation
============

Clone this repository (using git) into a folder on your hard drive (e.g. `/home/myname/resolver`).
	git clone https://github.com/PACKED-vzw/resolver-extensions.git /home/myname/resolver

Move the contents of the `mediawiki`-folder to the extensions folder of your Mediawiki installation (e.g. `/var/www/mediawiki/extensions`) into a new folder called `Resolver`.

Add the following lines to `LocalSettings.php`:
	require_once "$IP/extensions/Resolver/Resolver.php";

Execute the `maintenance/update.php`-script to create the databases:
	php maintenance/update.php
