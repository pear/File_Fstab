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


define('FILE_FSTAB_ENTRY_DEVTYPE_BLOCKDEV', 1);
define('FILE_FSTAB_ENTRY_DEVTYPE_UUID', 2);
define('FILE_FSTAB_ENTRY_DEVTYPE_LABEL', 3);


/**
 * A single entry in a fstab file
 *
 * @package File_Fstab
 * @version @version@
 * @author Ian Eure <ieure@php.net>
 */
class File_Fstab_Entry {
    /**
     * Raw line from fstab
     *
     * @type string
     */
    var $entry;

    /**
     * Block device
     *
     * Only one of $device, $uuid, or $label will be set, based on what's in the
     * fstab entry.
     *
     * @type string
     */
    var $device = '/dev/null';

    /**
     * Device UUID
     *
     * Only one of $device, $uuid, or $label will be set, based on what's in the
     * fstab entry.
     *
     * @type string
     */
    var $uuid;

    /**
     * Device label
     *
     * Only one of $device, $uuid, or $label will be set, based on what's in the
     * fstab entry.
     *
     * @type string
     */
    var $label;

    /**
     * Device mount point
     *
     * @type string
     */
    var $mountPoint = '/mnt';

    /**
     * Device filesystem type
     *
     * @type string
     */
    var $fsType = 'auto';

    /**
     * Mount options
     *
     * @type array
     */
    var $mountOptions = array(
            'defaults' => "defaults"
    );

    /**
     * Device dump frequency
     *
     * @type int
     */
    var $dumpFrequency = 0;

    /**
     * Device fsck pass number
     *
     * @type int
     */
    var $fsckPassNo = 0;

    /**
     * Have we parsed the entry?
     *
     * @type boolean
     * @access private
     */
    var $_haveParsed = false;


    /**
     * Constructor
     *
     * @param $entry string Single entry from fstab file
     * @return void
     */
    function File_Fstab_Entry($entry = false)
    {
        if ($entry) {
            $this->setEntry($entry);
            $this->parse();
        }
    }

    /**
     * Set block device
     *
     * Only one of device, uuid, or label may be set; setting this will un-set
     * any valies in the other variables.
     *
     * @since 1.1.0rc1
     * @param $device string Value to set
     * @return void
     */
    function setDevice($device)
    {
        $this->device = $device;
        unset($this->uuid, $this->label);
    }

    /**
     * Get block device
     *
     * @since 1.1.0rc1
     * @return string
     */
    function getDevice()
    {
        return $this->device;
    }

    /**
     * Set UUID
     *
     * Only one of device, uuid, or label may be set; setting this will un-set
     * any valies in the other variables.
     *
     * @since 1.1.0rc1
     * @param $uuid string Value to set
     * @return void
     */
    function setUUID($uuid)
    {
        $this->uuid = $uuid;
        unset($this->device, $this->label);
    }

    /**
     * Get UUID
     *
     * @since 1.1.0rc1
     * @return string
     */
    function getUUID()
    {
        return $this->uuid;
    }

    /**
     * Set device label
     *
     * Only one of device, uuid, or label may be set; setting this will un-set
     * any valies in the other variables.
     *
     * @since 1.1.0rc1
     * @param $label string Value to set
     * @return void
     */
    function setLabel($label)
    {
        $this->label = $label;
        unset($this->device, $this->uuid);
    }

    /**
     * Get device label
     *
     * @since 1.1.0rc1
     * @return string
     */
    function getLabel()
    {
        return $this->label;
    }

    /**
     * Set mount point
     *
     * @since 1.1.0rc1
     * @param $dir string Value to set
     * @return void
     */
    function setMountPoint($dir)
    {
        $this->mountPoint = $dir;
    }

    /**
     * Get mount point
     *
     * @since 1.1.0rc1
     * @return string
     */
    function getMountPoint()
    {
        return $this->mountPoint;
    }

    /**
     * Set filesystem type
     *
     * @since 1.1.0rc1
     * @param $type string Value to set
     * @return void
     */
    function setFsType($type)
    {
        $this->fsType = $type;
    }

    /**
     * Get filesystem type
     *
     * @since 1.1.0rc1
     * @return string
     */
    function getFsType()
    {
        return $this->fsType;
    }

    /**
     * Set an entry
     *
     * @since 1.1.0rc1
     * @param $entry string Single entry from fstab file
     */
    function setEntry($entry)
    {
        $this->entry = $entry;
        $this->_haveParsed = false;
    }

    /**
     * Parse fstab entry
     *
     * @param $entry string Line from fstab to parse
     * @return mixed true on success, PEAR_Error on failure
     */
    function parse()
    {
        if ($this->_haveParsed || !strlen($this->entry)) {
            return true;
        }

        // Sanitize.
        $this->_cleanup();
        
        $entry = &$this->entry;

        $sections = split("\ +|\t+", $entry);
        if (count($sections) != 6) {
            return PEAR::raiseError("Invalid entry format");
        }

        list($device, $this->mountPoint, $this->fsType, $options, $this->dumpFrequence, $this->fsckPassNo) = $sections;

        // Device, UUID, or Label?
        if (strstr($device, 'UUID')) {
            list($null, $this->uuid) = split('=', $device);
        } else if (strstr($device, 'LABEL')) {
            list($null, $this->label) = split('=', $device);
        } else {
            $this->device = $device;
        }

        $this->_parseMountOptions($options);

        $this->_haveParsed = true;
        return true;
    }
    
    /**
     * Clean up prior to parsing
     *
     * @access private
     * @return void
     */
    function _cleanup()
    {
        $this->mountOptions = array();
        unset($this->device, $this->uuid, $this->label, $this->mountPoint,
              $this->fsType, $this->dumpFrequency, $this->fsckPassNo);
    }

    /**
     * Parse fstab options
     *
     * @param $options string Mount options from fstab
     *
     * @return void
     * @access protected
     */
    function _parseMountOptions($options)
    {
        foreach (explode(',', $options) as $option) {
            if (strstr($option, '=')) {
                $tmp = explode('=', $option);
                list($name, $value) = $tmp;
            } else {
                $name = $option;
                $value = $option;
            }
            $this->mountOptions[$name] = $value;
        }
    }

    /**
     * Reconstruct fstab options from $mountOptions
     *
     * @return string fstab mount options
     * @access protected
     */
    function _makeMountOptions()
    {
        // Copy.
        foreach ($this->mountOptions as $option => $value) {
            if ($option == $value) {
                $opts[] = $option;
            } else {
                $opts[] = $option.'='.$value;
            }
        }
        return implode(',', $opts);
    }

    /**
     * Get the fstab entry
     *
     * This rebuilds the entry from the class variables.
     *
     * @return string The fstab entry
     */
    function getEntry($seperator)
    {
        $entry = array(
            $this->_getDeviceUUIDOrLabel(),
            $this->mountPoint,
            $this->fsType,
            $this->_makeMountOptions(),
            $this->dumpFrequency,
            $this->fsckPassNo
        );
        return implode($entry, $seperator);
    }

    /**
     * Get device, or uuid, or label
     *
     * @return string Device/UUID/LABEL
     * @access private
     */
    function _getDeviceUUIDOrLabel()
    {
        if ($this->device) {
            return $this->device;
        } else if ($this->uuid) {
            return 'UUID='.$this->uuid;
        } else if($this->label) {
            return 'LABEL='.$this->label;
        }
    }

    /**
     * Get device type
     *
     * @return int One of FILE_FSTAB_ENTRY_DEVTYPE_BLOCKDEV, _UUID, or _LABEL
     */
    function deviceType()
    {
        if ($this->device) {
            return FILE_FSTAB_ENTRY_DEVTYPE_BLOCKDEV;
        } else if ($this->uuid) {
            return FILE_FSTAB_ENTRY_DEVTYPE_UUID;
        } else if ($this->label) {
            return FILE_FSTAB_ENTRY_DEVTYPE_LABEL;
        }
    }

    /**
     * Is an option set?
     *
     * @param $option string Option name
     * @return boolean
     */
    function hasMountOption($option)
    {
        return @array_key_exists($option, $this->mountOptions);
    }
    
    /**
     * Get a mount option
     *
     * @param string $which Option to get
     * @return string Mount option
     */
    function getMountOption($which)
    {
        if (!$this->hasMountOption($which)) {
            return false;
        }
        return $this->mountOptions[$which];
    }
    
    /**
     * Set a mount option
     *
     * @param string $name Option to set
     * @param string $value Value to give option, or blank if option takes no args
     * @return void
     */
    function setMountOption($name, $value = false)
    {
        if (!$value) {
            $value = $name;
        }
        $this->mountOptions[$name] = $value;
    }
}
?>