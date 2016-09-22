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
 * CodeIgniter Dummy Caching Class.
 *
 * @category	Core
 *
 * @author		EllisLab Dev Team
 *
 * @link
 */
class CI_Cache_dummy extends CI_Driver
{
    /**
     * Get.
     *
     * Since this is the dummy class, it's always going to return FALSE.
     *
     * @param	string
     *
     * @return bool FALSE
     */
    public function get($id)
    {
        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Cache Save.
     *
     * @param	string	Unique Key
     * @param	mixed	Data to store
     * @param	int	Length of time (in seconds) to cache the data
     *
     * @return bool TRUE, Simulating success
     */
    public function save($id, $data, $ttl = 60)
    {
        return true;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete from Cache.
     *
     * @param	mixed	unique identifier of the item in the cache
     *
     * @return bool TRUE, simulating success
     */
    public function delete($id)
    {
        return true;
    }

    // ------------------------------------------------------------------------

    /**
     * Clean the cache.
     *
     * @return bool TRUE, simulating success
     */
    public function clean()
    {
        return true;
    }

    // ------------------------------------------------------------------------

     /**
      * Cache Info.
      *
      * @param	string	user/filehits
      *
      * @return	bool	FALSE
      */
     public function cache_info($type = null)
     {
         return false;
     }

    // ------------------------------------------------------------------------

    /**
     * Get Cache Metadata.
     *
     * @param	mixed	key to get cache metadata on
     *
     * @return bool FALSE
     */
    public function get_metadata($id)
    {
        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Is this caching driver supported on the system?
     * Of course this one is.
     *
     * @return bool TRUE
     */
    public function is_supported()
    {
        return true;
    }
}

/* End of file Cache_dummy.php */
/* Location: ./system/libraries/Cache/drivers/Cache_dummy.php */
