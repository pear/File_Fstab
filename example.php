<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Ian Eure <ieure@php.net>                                     |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'File/Fstab.php';

// Get an instance for the system's fstab.
$sysTab = File_Fstab::singleton('/etc/fstab');

// Get the entry for the root partition.
$rootEnt = &$sysTab->getEntryForPath('/');


// See how error handling is set.
if ($rootEnt->hasMountOption('errors')) {
    print "Error handling for / is: ".$rootEnt->mountOptions['errors']."\n";
} else {
    print "Error handling for / is undefined.\n";
}

// Change fstype for /
print "Chanking fstype for ".$rootEnt->getDeviceUUIDOrLabel()." (mounted on ".$rootEnt->getMountPoint().") to reiserfs\n";
$rootEnt->fsType = 'reiserfs';

// Create a new entry.
print "Adding entry for CD-ROM\n";
$ent = new File_Fstab_Entry;
$ent->device = '/dev/cdrom';
$ent->mountPoint = '/cdrom';
$ent->fsType = 'iso9660';
$ent->setMountOption('user');

// Add the entry
$sysTab->addEntry(&$ent);

// Write to a temp file.
print "Saving modified fstab to /tmp/newtab\n";
$res = $sysTab->save('/tmp/newtab');
if (PEAR::isError($res)) {
    die($res->getMessage()."\n");
}

?>