<?php
/**
 * Mediawiki exporter that exports a list of all pages with their URLs and ID's for use in the Resolver application.
 * (c) 2015 PACKED vzw (Pieter De Praetere)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of version 3 the GNU General Public License
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
if (!defined ('MEDIAWIKI')) {
    echo 'This is not a valid entry point to this Mediawiki installation';
    exit(1);
}
$wgExtensionCredits['specialpage'][] = array(
    'path' => __FILE__,
    'name' => 'Resolver',
    'author' => 'PACKED vzw',
    'url' => '',
    'descriptionmsg' => 'resolver-desc',
    'version' => '1.0.0',
    'license-name' => 'GPLv3'
);

$wgAutoloadClasses['SpecialResolver'] = __DIR__ . '/SpecialResolver.php'; # Location of the SpecialResolver class (Tell MediaWiki to load this file)
$wgMessagesDirs['Resolver'] = __DIR__ . "/i18n"; # Location of localisation files (Tell MediaWiki to load them)
$wgExtensionMessagesFiles['ResolverAlias'] = __DIR__ . '/Resolver.alias.php'; # Location of an aliases file (Tell MediaWiki to load it)
$wgSpecialPages['Resolver'] = 'SpecialResolver'; # Tell MediaWiki about the new special page and its class name

/*
 * Hooks
 */
/*
This page will have all (old) page names (ex. when pages are renamed) connected to their current ids
As the resolver only accepts inserts data as delete->insert; we must remember somewhere when pages
change names, because the persistent URL's in the resolver must continue to work.
 */
$wgHooks['LoadExtensionSchemaUpdates'][] = 'createResolverTable';
function createResolverTable (DatabaseUpdater $updater) {
    $updater->addExtensionTable ('resolver_export', __DIR__.'/src/resolver_export.sql');
    return true;
}

/* Create directory for generated CSV files */
/*https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions*/
$wgExtensionFunctions[] = 'setup';
function setup () {
    global $wgUploadDirectory;
    $csv_dir = "{$wgUploadDirectory}/CSV";
    if (!file_exists ($csv_dir)) {
        if (!mkdir ($csv_dir, 0777, true)) {
            throw new Exception ('Error: failed to create CSV directory '.$csv_dir);
        }
    }
}