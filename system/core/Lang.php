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
 * Language Class.
 *
 * @category	Language
 *
 * @author		EllisLab Dev Team
 *
 * @link		http://codeigniter.com/user_guide/libraries/language.html
 */
class CI_Lang
{
    /**
     * List of translations.
     *
     * @var array
     */
    public $language = [];

    /**
     * List of loaded language files.
     *
     * @var array
     */
    public $is_loaded = [];

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct()
    {
        log_message('debug', 'Language Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Load a language file.
     *
     * @param mixed  $langfile   Language file name
     * @param string $idiom      Language name (english, etc.)
     * @param bool   $return     Whether to return the loaded array of translations
     * @param bool   $add_suffix Whether to add suffix to $langfile
     * @param string $alt_path   Alternative path to look for the language file
     *
     * @return void|string[] Array containing translations, if $return is set to TRUE
     */
    public function load($langfile, $idiom = '', $return = false, $add_suffix = true, $alt_path = '')
    {
        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix === true) {
            $langfile = str_replace('_lang', '', $langfile).'_lang';
        }

        $langfile .= '.php';

        if (empty($idiom) or !ctype_alpha($idiom)) {
            $config = &get_config();
            $idiom = empty($config['language']) ? 'english' : $config['language'];
        }

        if ($return === false && isset($this->is_loaded[$langfile]) && $this->is_loaded[$langfile] === $idiom) {
            return;
        }

        // Load the base file, so any others found can override it
        $basepath = BASEPATH.'language/'.$idiom.'/'.$langfile;
        if (($found = file_exists($basepath)) === true) {
            include $basepath;
        }

        // Do we have an alternative path to look in?
        if ($alt_path !== '') {
            $alt_path .= 'language/'.$idiom.'/'.$langfile;
            if (file_exists($alt_path)) {
                include $alt_path;
                $found = true;
            }
        } else {
            foreach (get_instance()->load->get_package_paths(true) as $package_path) {
                $package_path .= 'language/'.$idiom.'/'.$langfile;
                if ($basepath !== $package_path && file_exists($package_path)) {
                    include $package_path;
                    $found = true;
                    break;
                }
            }
        }

        if ($found !== true) {
            show_error('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);
        }

        if (!isset($lang) or !is_array($lang)) {
            log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);

            if ($return === true) {
                return [];
            }

            return;
        }

        if ($return === true) {
            return $lang;
        }

        $this->is_loaded[$langfile] = $idiom;
        $this->language = array_merge($this->language, $lang);

        log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Language line.
     *
     * Fetches a single line of text from the language array
     *
     * @param string $line       Language line key
     * @param bool   $log_errors Whether to log an error message if the line is not found
     *
     * @return string Translation
     */
    public function line($line = '', $log_errors = true)
    {
        $value = ($line === '' or !isset($this->language[$line])) ? false : $this->language[$line];

        // Because killer robots like unicorns!
        if ($value === false && $log_errors === true) {
            log_message('error', 'Could not find the language line "'.$line.'"');
        }

        return $value;
    }
}

/* End of file Lang.php */
/* Location: ./system/core/Lang.php */
