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
 * FTP Class.
 *
 * @category	Libraries
 *
 * @author		EllisLab Dev Team
 *
 * @link		http://codeigniter.com/user_guide/libraries/ftp.html
 */
class CI_FTP
{
    /**
     * FTP Server hostname.
     *
     * @var string
     */
    public $hostname = '';

    /**
     * FTP Username.
     *
     * @var string
     */
    public $username = '';

    /**
     * FTP Password.
     *
     * @var string
     */
    public $password = '';

    /**
     * FTP Server port.
     *
     * @var int
     */
    public $port = 21;

    /**
     * Passive mode flag.
     *
     * @var bool
     */
    public $passive = true;

    /**
     * Debug flag.
     *
     * Specifies whether to display error messages.
     *
     * @var bool
     */
    public $debug = false;

    /**
     * Connection.
     *
     * @var resource
     */
    public $conn_id = false;

    // --------------------------------------------------------------------

    /**
     * Constructor.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct($config = [])
    {
        if (count($config) > 0) {
            $this->initialize($config);
        }

        log_message('debug', 'FTP Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Initialize preferences.
     *
     * @param array $config
     *
     * @return void
     */
    public function initialize($config = [])
    {
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }

        // Prep the hostname
        $this->hostname = preg_replace('|.+?://|', '', $this->hostname);
    }

    // --------------------------------------------------------------------

    /**
     * FTP Connect.
     *
     * @param array $config Connection values
     *
     * @return bool
     */
    public function connect($config = [])
    {
        if (count($config) > 0) {
            $this->initialize($config);
        }

        if (false === ($this->conn_id = @ftp_connect($this->hostname, $this->port))) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_connect');
            }

            return false;
        }

        if (!$this->_login()) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_login');
            }

            return false;
        }

        // Set passive mode if needed
        if ($this->passive === true) {
            ftp_pasv($this->conn_id, true);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * FTP Login.
     *
     * @return bool
     */
    protected function _login()
    {
        return @ftp_login($this->conn_id, $this->username, $this->password);
    }

    // --------------------------------------------------------------------

    /**
     * Validates the connection ID.
     *
     * @return bool
     */
    protected function _is_conn()
    {
        if (!is_resource($this->conn_id)) {
            if ($this->debug === true) {
                $this->_error('ftp_no_connection');
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Change directory.
     *
     * The second parameter lets us momentarily turn off debugging so that
     * this function can be used to test for the existence of a folder
     * without throwing an error. There's no FTP equivalent to is_dir()
     * so we do it by trying to change to a particular directory.
     * Internally, this parameter is only used by the "mirror" function below.
     *
     * @param string $path
     * @param bool   $supress_debug
     *
     * @return bool
     */
    public function changedir($path = '', $supress_debug = false)
    {
        if ($path === '' or !$this->_is_conn()) {
            return false;
        }

        $result = @ftp_chdir($this->conn_id, $path);

        if ($result === false) {
            if ($this->debug === true && $supress_debug === false) {
                $this->_error('ftp_unable_to_changedir');
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int    $permissions
     *
     * @return bool
     */
    public function mkdir($path = '', $permissions = null)
    {
        if ($path === '' or !$this->_is_conn()) {
            return false;
        }

        $result = @ftp_mkdir($this->conn_id, $path);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_makdir');
            }

            return false;
        }

        // Set file permissions if needed
        if ($permissions !== null) {
            $this->chmod($path, (int) $permissions);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Upload a file to the server.
     *
     * @param string $locpath
     * @param string $rempath
     * @param string $mode
     * @param int    $permissions
     *
     * @return bool
     */
    public function upload($locpath, $rempath, $mode = 'auto', $permissions = null)
    {
        if (!$this->_is_conn()) {
            return false;
        }

        if (!file_exists($locpath)) {
            $this->_error('ftp_no_source_file');

            return false;
        }

        // Set the mode if not specified
        if ($mode === 'auto') {
            // Get the file extension so we can set the upload type
            $ext = $this->_getext($locpath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode === 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_put($this->conn_id, $rempath, $locpath, $mode);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_upload');
            }

            return false;
        }

        // Set file permissions if needed
        if ($permissions !== null) {
            $this->chmod($rempath, (int) $permissions);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Download a file from a remote server to the local server.
     *
     * @param string $rempath
     * @param string $locpath
     * @param string $mode
     *
     * @return bool
     */
    public function download($rempath, $locpath, $mode = 'auto')
    {
        if (!$this->_is_conn()) {
            return false;
        }

        // Set the mode if not specified
        if ($mode === 'auto') {
            // Get the file extension so we can set the upload type
            $ext = $this->_getext($rempath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode === 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_get($this->conn_id, $locpath, $rempath, $mode);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_download');
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Rename (or move) a file.
     *
     * @param string $old_file
     * @param string $new_file
     * @param bool   $move
     *
     * @return bool
     */
    public function rename($old_file, $new_file, $move = false)
    {
        if (!$this->_is_conn()) {
            return false;
        }

        $result = @ftp_rename($this->conn_id, $old_file, $new_file);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_'.($move === false ? 'rename' : 'move'));
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Move a file.
     *
     * @param string $old_file
     * @param string $new_file
     *
     * @return bool
     */
    public function move($old_file, $new_file)
    {
        return $this->rename($old_file, $new_file, true);
    }

    // --------------------------------------------------------------------

    /**
     * Rename (or move) a file.
     *
     * @param string $filepath
     *
     * @return bool
     */
    public function delete_file($filepath)
    {
        if (!$this->_is_conn()) {
            return false;
        }

        $result = @ftp_delete($this->conn_id, $filepath);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_delete');
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Delete a folder and recursively delete everything (including sub-folders)
     * containted within it.
     *
     * @param string $filepath
     *
     * @return bool
     */
    public function delete_dir($filepath)
    {
        if (!$this->_is_conn()) {
            return false;
        }

        // Add a trailing slash to the file path if needed
        $filepath = preg_replace('/(.+?)\/*$/', '\\1/', $filepath);

        $list = $this->list_files($filepath);

        if ($list !== false && count($list) > 0) {
            foreach ($list as $item) {
                // If we can't delete the item it's probaly a folder so
                // we'll recursively call delete_dir()
                if (!@ftp_delete($this->conn_id, $item)) {
                    $this->delete_dir($item);
                }
            }
        }

        $result = @ftp_rmdir($this->conn_id, $filepath);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_delete');
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Set file permissions.
     *
     * @param string $path File path
     * @param int    $perm Permissions
     *
     * @return bool
     */
    public function chmod($path, $perm)
    {
        if (!$this->_is_conn()) {
            return false;
        }

        $result = @ftp_chmod($this->conn_id, $perm, $path);

        if ($result === false) {
            if ($this->debug === true) {
                $this->_error('ftp_unable_to_chmod');
            }

            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * FTP List files in the specified directory.
     *
     * @param string $path
     *
     * @return array
     */
    public function list_files($path = '.')
    {
        if (!$this->_is_conn()) {
            return false;
        }

        return ftp_nlist($this->conn_id, $path);
    }

    // ------------------------------------------------------------------------

    /**
     * Read a directory and recreate it remotely.
     *
     * This function recursively reads a folder and everything it contains
     * (including sub-folders) and creates a mirror via FTP based on it.
     * Whatever the directory structure of the original file path will be
     * recreated on the server.
     *
     * @param string $locpath Path to source with trailing slash
     * @param string $rempath Path to destination - include the base folder with trailing slash
     *
     * @return bool
     */
    public function mirror($locpath, $rempath)
    {
        if (!$this->_is_conn()) {
            return false;
        }

        // Open the local file path
        if ($fp = @opendir($locpath)) {
            // Attempt to open the remote file path and try to create it, if it doesn't exist
            if (!$this->changedir($rempath, true) && (!$this->mkdir($rempath) or !$this->changedir($rempath))) {
                return false;
            }

            // Recursively read the local directory
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($locpath.$file) && $file[0] !== '.') {
                    $this->mirror($locpath.$file.'/', $rempath.$file.'/');
                } elseif ($file[0] !== '.') {
                    // Get the file extension so we can se the upload type
                    $ext = $this->_getext($file);
                    $mode = $this->_settype($ext);

                    $this->upload($locpath.$file, $rempath.$file, $mode);
                }
            }

            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Extract the file extension.
     *
     * @param string $filename
     *
     * @return string
     */
    protected function _getext($filename)
    {
        if (false === strpos($filename, '.')) {
            return 'txt';
        }

        $x = explode('.', $filename);

        return end($x);
    }

    // --------------------------------------------------------------------

    /**
     * Set the upload type.
     *
     * @param string $ext Filename extension
     *
     * @return string
     */
    protected function _settype($ext)
    {
        $text_types = [
                    'txt',
                    'text',
                    'php',
                    'phps',
                    'php4',
                    'js',
                    'css',
                    'htm',
                    'html',
                    'phtml',
                    'shtml',
                    'log',
                    'xml',
                ];

        return in_array($ext, $text_types) ? 'ascii' : 'binary';
    }

    // ------------------------------------------------------------------------

    /**
     * Close the connection.
     *
     * @return bool
     */
    public function close()
    {
        if (!$this->_is_conn()) {
            return false;
        }

        return @ftp_close($this->conn_id);
    }

    // ------------------------------------------------------------------------

    /**
     * Display error message.
     *
     * @param string $line
     *
     * @return void
     */
    protected function _error($line)
    {
        $CI = &get_instance();
        $CI->lang->load('ftp');
        show_error($CI->lang->line($line));
    }
}

/* End of file Ftp.php */
/* Location: ./system/libraries/Ftp.php */
