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

require_once 'PEAR.php';
require_once 'File/Fstab/Entry.php';

define('FILE_FSTAB_ERROR_NOENT', -1);
define('FILE_FSTAB_PERMISSION_DENIED', -2);
define('FILE_FSTAB_WRONG_CLASS', -3);

/**
 * Class to read, write, and manipulate fstab files
 *
 * @package File_Fstab
 * @version @version@
 * @author Ian Eure <ieure@php.net>
 */
class File_Fstab {
    /**
     * Array of fstab entries
     *
     * @type array
     */
    var $entries = array();

    /**
     * Class options.
     *
     * @type array
     */
    var $options = array();

    /**
     * Default options
     *
     * @type array
     * @access private
     */
    var $_defaultOptions = array(
        'entryClass' => "File_Fstab_Entry",
        'file' => "/etc/fstab",
        'fieldSeperator' => "\t"
    );

    /**
     * Has the fstab been parsed?
     *
     * @type boolean
     * @access private
     */
    var $_isLoaded = false;

    /**
     * Last-modified time of the fstab file
     *
     * This is used by load() to determine if the fstab has been changed since
     * it was last loaded.
     *
     * @type int
     * @access protected
     * @see load()
     */
    var $_mtime = 0;

    /**
     * Constructor
     *
     * @param $options array Associative array of options to set
     * @return void
     */
    function File_Fstab($options = false)
    {
        $this->setOptions($options);
        if ($this->options['file']) {
            $this->load();
        }
    }

    /**
     * Return a single instance to handle a fstab file
     *
     * @param $fstab string Path to the fstab file
     * @return object File_Fstab instance
     */
    function &singleton($fstab)
    {
        static $instances;
        if (!isset($instances)) {
            $instances = array();
        }

        if (!isset($instances[$fstab])) {
            $instances[$fstab] = &new File_Fstab(array('file' => $fstab));
        }

        return $instances[$fstab];
    }

    /**
     * Parse fstab file
     *
     *
     *
     * @param  $force boolean Force re-loading fstab, even if it hasn't changed.
     * @return void
     * @since 1.0.1
     */
    function load($force = false)
    {
        // Dont re-load if everything is up-to-date
        if (filemtime($this->options['file']) < $this->_mtime &&
            !$force) {
            return;
        }

        // Clear things out before we load.
        $this->entries = array();
        
        $fp = fopen($this->options['file'], 'r');
        while ($line = fgets($fp, 1024)) {

            // Strip comments & trim whitespace
            $line = trim(ereg_replace('#.*$', '', $line));

            // Ignore blank lines
            if (!strlen($line)) {
                continue;
            }

            $class = $this->options['entryClass'];
            $this->entries[] = new $class($line);

        }

        $this->_isLoaded = true;
        $this->_mtime = filemtime($this->options['file']);
    }

    /**
     * Update entries
     *
     * This will dump all the entries and re-parse the fstab. There's probably
     * a better way of doing this, like forcing the extant entries to re-parse,
     * and adding/removing entries as needed, but I don't feel like doing that
     * right now.
     *
     * @return void
     */
    function update()
    {
        unset($this->entries);
        $this->load();
    }

    /**
     * Get a File_Fstab_Entry object for a path
     *
     * @param $path string Mount point
     * @return mixed File_Fstab_Entry instance on success, PEAR_Error otherwise
     */
    function &getEntryForPath($path)
    {
        foreach ($this->entries as $key => $entry) {
            if ($entry->mountPoint == $path) {
                // Foreach makes copies - make sure we return a reference
                return $this->entries[$key];
            }
        }
        return PEAR::raiseError("No entry for path \"{$path}\"", PEAR_ERROR_NOENT);
    }

    /**
     * Get a File_Fstab_Entry object for a block device
     *
     * @param $blockdev string Block device
     * @return mixed File_Fstab_Entry instance on success, PEAR_Error otherwise
     */
    function &getEntryForDevice($blockdev)
    {
        foreach ($this->entries as $key => $entry) {
            if ($entry->getDeviceType() == FILE_FSTAB_ENTRY_DEVTYPE_BLOCKDEV &&
                $entry->device == $blockdev) {
                // Foreach makes copies - make sure we return a reference
                return $this->entries[$key];
            }
        }
        return PEAR::raiseError("No entry for device \"{$blockdev}\"", PEAR_ERROR_NOENT);
    }

    /**
     * Get a File_Fstab_Entry object for a UUID
     *
     * @param $uuid string UUID device
     * @return mixed File_Fstab_Entry instance on success, PEAR_Error otherwise
     */
    function &getEntryForUUID($uuid)
    {
        foreach ($this->entries as $key => $entry) {
            if ($entry->getDeviceType() == FILE_FSTAB_ENTRY_DEVTYPE_UUID &&
                $entry->uuid == $uuid) {
                // Foreach makes copies - make sure we return a reference
                return $this->entries[$key];
            }
        }
        return PEAR::raiseError("No entry for UUID \"{$uuid}\"", PEAR_ERROR_NOENT);
    }

    /**
     * Get a File_Fstab_Entry object for a label
     *
     * @param $label string Label
     * @return mixed File_Fstab_Entry instance on success, PEAR_Error otherwise
     */
    function &getEntryForLabel($label)
    {
        foreach ($this->entries as $key => $entry) {
            if ($entry->getDeviceType() == FILE_FSTAB_ENTRY_DEVTYPE_LABEL &&
                $entry->label == $label) {
                // Foreach makes copies - make sure we return a reference
                return $this->entries[$key];
            }
        }
        return PEAR::raiseError("No entry for label \"{$label}\"", PEAR_ERROR_NOENT);
    }
    
    /**
     * Add a new entry
     *
     * @param $entry object Reference to a File_Fstab_Entry-derived class
     * @return mixed boolean true on success, PEAR_Error otherwise.
     */
    function addEntry(&$entry)
    {
        if (!is_a($entry, 'File_Fstab_Entry')) {
            return PEAR::raiseError("Entry must be derived from File_Fstab_Entry.",
                                    FILE_FSTAB_WRONG_CLASS);
        }
        
        $this->entries[] = $entry;
        return true;
    }

    /**
     * Set class options
     *
     * @param $options array Associative array of options to set
     * @return void
     */
    function setOptions($options = false)
    {
        if (!is_array($options)) {
            $options = array();
        }

        $this->options = array_merge($this->_defaultOptions, $options);
    }

    /**
     * Write out a modified fstab
     *
     * WARNING: This will strip comments and blank lines from the original fstab.
     *
     * @return mixed true on success, PEAR_Error on failure
     * @since 1.0.1
     */
    function save($output = false)
    {
        $output = $output ? $output : $this->options['file'];
        if (file_exists($output) && !is_writable($output)) {
            return PEAR::raiseError("Can't write to {$output}",
                                    FILE_FSTAB_PERMISSION_DENIED);
        }
        
        $fp = @fopen($output, 'w');
        if (!$fp) {
            return PEAR::raiseError("Can't open {$output}");
        }

        foreach($this->entries as $entry) {
            fwrite($fp, $entry->getEntry($this->options['fieldSeperator']));
        }
        fclose($fp);
        return true;
    }
}
?>
