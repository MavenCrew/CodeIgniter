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
 * @since		Version 1.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hooks Class.
 *
 * Provides a mechanism to extend the base system without hacking.
 *
 * @category	Libraries
 *
 * @author		EllisLab Dev Team
 *
 * @link		http://codeigniter.com/user_guide/libraries/encryption.html
 */
class CI_Hooks
{
    /**
     * Determines whether hooks are enabled.
     *
     * @var bool
     */
    public $enabled = false;

    /**
     * List of all hooks set in config/hooks.php.
     *
     * @var array
     */
    public $hooks = [];

    /**
     * In progress flag.
     *
     * Determines whether hook is in progress, used to prevent infinte loops
     *
     * @var bool
     */
    protected $_in_progress = false;

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $CFG = &load_class('Config', 'core');

        log_message('debug', 'Hooks Class Initialized');

        // If hooks are not enabled in the config file
        // there is nothing else to do
        if ($CFG->item('enable_hooks') === false) {
            return;
        }

        // Grab the "hooks" definition file.
        if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/hooks.php')) {
            include APPPATH.'config/'.ENVIRONMENT.'/hooks.php';
        }

        if (file_exists(APPPATH.'config/hooks.php')) {
            include APPPATH.'config/hooks.php';
        }

        // If there are no hooks, we're done.
        if (!isset($hook) or !is_array($hook)) {
            return;
        }

        $this->hooks = &$hook;
        $this->enabled = true;
    }

    // --------------------------------------------------------------------

    /**
     * Call Hook.
     *
     * Calls a particular hook. Called by CodeIgniter.php.
     *
     * @uses	CI_Hooks::_run_hook()
     *
     * @param string $which Hook name
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function call_hook($which = '')
    {
        if (!$this->enabled or !isset($this->hooks[$which])) {
            return false;
        }

        if (isset($this->hooks[$which][0]) && is_array($this->hooks[$which][0])) {
            foreach ($this->hooks[$which] as $val) {
                $this->_run_hook($val);
            }
        } else {
            $this->_run_hook($this->hooks[$which]);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Run Hook.
     *
     * Runs a particular hook
     *
     * @param array $data Hook details
     *
     * @return bool TRUE on success or FALSE on failure
     */
    protected function _run_hook($data)
    {
        if (!is_array($data)) {
            return false;
        }

        // -----------------------------------
        // Safety - Prevents run-away loops
        // -----------------------------------

        // If the script being called happens to have the same
        // hook call within it a loop can happen
        if ($this->_in_progress === true) {
            return;
        }

        // -----------------------------------
        // Set file path
        // -----------------------------------

        if (!isset($data['filepath'], $data['filename'])) {
            return false;
        }

        $filepath = APPPATH.$data['filepath'].'/'.$data['filename'];

        if (!file_exists($filepath)) {
            return false;
        }

        // Determine and class and/or function names
        $class = empty($data['class']) ? false : $data['class'];
        $function = empty($data['function']) ? false : $data['function'];
        $params = isset($data['params']) ? $data['params'] : '';

        if ($class === false && $function === false) {
            return false;
        }

        // Set the _in_progress flag
        $this->_in_progress = true;

        // Call the requested class and/or function
        if ($class !== false) {
            if (!class_exists($class, false)) {
                require $filepath;
            }

            $HOOK = new $class();
            $HOOK->$function($params);
        } else {
            if (!function_exists($function)) {
                require $filepath;
            }

            $function($params);
        }

        $this->_in_progress = false;

        return true;
    }
}

/* End of file Hooks.php */
/* Location: ./system/core/Hooks.php */
