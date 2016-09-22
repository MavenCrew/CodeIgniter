<?php
/**
 * CodeIgniter.
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2013, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *
 * @link		http://codeigniter.com
 * @since		Version 2.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CodeIgniter File Caching Class.
 *
 * @category	Core
 *
 * @author		EllisLab Dev Team
 *
 * @link
 */
class CI_Cache_file extends CI_Driver
{
    /**
     * Directory in which to save cache files.
     *
     * @var string
     */
    protected $_cache_path;

    /**
     * Initialize file-based cache.
     *
     * @return void
     */
    public function __construct()
    {
        $CI = &get_instance();
        $CI->load->helper('file');
        $path = $CI->config->item('cache_path');
        $this->_cache_path = ($path === '') ? APPPATH.'cache/' : $path;
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch from cache.
     *
     * @param	mixed	unique key id
     *
     * @return mixed data on success/false on failure
     */
    public function get($id)
    {
        if (!file_exists($this->_cache_path.$id)) {
            return false;
        }

        $data = unserialize(file_get_contents($this->_cache_path.$id));

        if ($data['ttl'] > 0 && time() > $data['time'] + $data['ttl']) {
            unlink($this->_cache_path.$id);

            return false;
        }

        return $data['data'];
    }

    // ------------------------------------------------------------------------

    /**
     * Save into cache.
     *
     * @param	string	unique key
     * @param	mixed	data to store
     * @param	int	length of time (in seconds) the cache is valid
     *				- Default is 60 seconds
     *
     * @return bool true on success/false on failure
     */
    public function save($id, $data, $ttl = 60)
    {
        $contents = [
            'time'        => time(),
            'ttl'         => $ttl,
            'data'        => $data,
        ];

        if (write_file($this->_cache_path.$id, serialize($contents))) {
            @chmod($this->_cache_path.$id, 0660);

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete from Cache.
     *
     * @param	mixed	unique identifier of item in cache
     *
     * @return bool true on success/false on failure
     */
    public function delete($id)
    {
        return file_exists($this->_cache_path.$id) ? unlink($this->_cache_path.$id) : false;
    }

    // ------------------------------------------------------------------------

    /**
     * Clean the Cache.
     *
     * @return bool false on failure/true on success
     */
    public function clean()
    {
        return delete_files($this->_cache_path, false, true);
    }

    // ------------------------------------------------------------------------

    /**
     * Cache Info.
     *
     * Not supported by file-based caching
     *
     * @param	string	user/filehits
     *
     * @return mixed FALSE
     */
    public function cache_info($type = null)
    {
        return get_dir_file_info($this->_cache_path);
    }

    // ------------------------------------------------------------------------

    /**
     * Get Cache Metadata.
     *
     * @param	mixed	key to get cache metadata on
     *
     * @return mixed FALSE on failure, array on success.
     */
    public function get_metadata($id)
    {
        if (!file_exists($this->_cache_path.$id)) {
            return false;
        }

        $data = unserialize(file_get_contents($this->_cache_path.$id));

        if (is_array($data)) {
            $mtime = filemtime($this->_cache_path.$id);

            if (!isset($data['ttl'])) {
                return false;
            }

            return [
                'expire'    => $mtime + $data['ttl'],
                'mtime'     => $mtime,
            ];
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Is supported.
     *
     * In the file driver, check to see that the cache directory is indeed writable
     *
     * @return bool
     */
    public function is_supported()
    {
        return is_really_writable($this->_cache_path);
    }
}

/* End of file Cache_file.php */
/* Location: ./system/libraries/Cache/drivers/Cache_file.php */
