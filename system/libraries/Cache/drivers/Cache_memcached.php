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
 * CodeIgniter Memcached Caching Class.
 *
 * @category	Core
 *
 * @author		EllisLab Dev Team
 *
 * @link
 */
class CI_Cache_memcached extends CI_Driver
{
    /**
     * Holds the memcached object.
     *
     * @var object
     */
    protected $_memcached;

    /**
     * Memcached configuration.
     *
     * @var array
     */
    protected $_memcache_conf = [
        'default' => [
            'host'        => '127.0.0.1',
            'port'        => 11211,
            'weight'      => 1,
        ],
    ];

    /**
     * Fetch from cache.
     *
     * @param	mixed	unique key id
     *
     * @return mixed data on success/false on failure
     */
    public function get($id)
    {
        $data = $this->_memcached->get($id);

        return is_array($data) ? $data[0] : false;
    }

    // ------------------------------------------------------------------------

    /**
     * Save.
     *
     * @param	string	unique identifier
     * @param	mixed	data being cached
     * @param	int	time to live
     *
     * @return bool true on success, false on failure
     */
    public function save($id, $data, $ttl = 60)
    {
        if (get_class($this->_memcached) === 'Memcached') {
            return $this->_memcached->set($id, [$data, time(), $ttl], $ttl);
        } elseif (get_class($this->_memcached) === 'Memcache') {
            return $this->_memcached->set($id, [$data, time(), $ttl], 0, $ttl);
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete from Cache.
     *
     * @param	mixed	key to be deleted.
     *
     * @return bool true on success, false on failure
     */
    public function delete($id)
    {
        return $this->_memcached->delete($id);
    }

    // ------------------------------------------------------------------------

    /**
     * Clean the Cache.
     *
     * @return bool false on failure/true on success
     */
    public function clean()
    {
        return $this->_memcached->flush();
    }

    // ------------------------------------------------------------------------

    /**
     * Cache Info.
     *
     * @return mixed array on success, false on failure
     */
    public function cache_info()
    {
        return $this->_memcached->getStats();
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
        $stored = $this->_memcached->get($id);

        if (count($stored) !== 3) {
            return false;
        }

        list($data, $time, $ttl) = $stored;

        return [
            'expire'       => $time + $ttl,
            'mtime'        => $time,
            'data'         => $data,
        ];
    }

    // ------------------------------------------------------------------------

    /**
     * Setup memcached.
     *
     * @return bool
     */
    protected function _setup_memcached()
    {
        // Try to load memcached server info from the config file.
        $CI = &get_instance();

        if ($CI->config->load('memcached', true, true)) {
            if (is_array($CI->config->config['memcached'])) {
                $defaults = $this->_memcache_conf['default'];
                $this->_memcache_conf = [];

                foreach ($CI->config->config['memcached'] as $name => $conf) {
                    $this->_memcache_conf[$name] = $conf;
                }
            }
        }

        if (class_exists('Memcached', false)) {
            $this->_memcached = new Memcached();
        } elseif (class_exists('Memcache', false)) {
            $this->_memcached = new Memcache();
        } else {
            log_message('error', 'Failed to create object for Memcached Cache; extension not loaded?');

            return false;
        }

        foreach ($this->_memcache_conf as $cache_server) {
            isset($cache_server['hostname']) or $cache_server['hostname'] = $defaults['host'];
            isset($cache_server['port']) or $cache_server['port'] = $defaults['host'];
            isset($cache_server['weight']) or $cache_server['weight'] = $defaults['weight'];

            if (get_class($this->_memcached) === 'Memcache') {
                // Third parameter is persistance and defaults to TRUE.
                $this->_memcached->addServer(
                    $cache_server['hostname'],
                    $cache_server['port'],
                    true,
                    $cache_server['weight']
                );
            } else {
                $this->_memcached->addServer(
                    $cache_server['hostname'],
                    $cache_server['port'],
                    $cache_server['weight']
                );
            }
        }

        return true;
    }

    // ------------------------------------------------------------------------

    /**
     * Is supported.
     *
     * Returns FALSE if memcached is not supported on the system.
     * If it is, we setup the memcached object & return TRUE
     *
     * @return bool
     */
    public function is_supported()
    {
        if (!extension_loaded('memcached') && !extension_loaded('memcache')) {
            log_message('debug', 'The Memcached Extension must be loaded to use Memcached Cache.');

            return false;
        }

        return $this->_setup_memcached();
    }
}

/* End of file Cache_memcached.php */
/* Location: ./system/libraries/Cache/drivers/Cache_memcached.php */
