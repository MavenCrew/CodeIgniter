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
 * @since		Version 3.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Firebird/Interbase Database Adapter Class.
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @category	Database
 *
 * @author		EllisLab Dev Team
 *
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_ibase_driver extends CI_DB
{
    /**
     * Database driver.
     *
     * @var string
     */
    public $dbdriver = 'ibase';

    // --------------------------------------------------------------------

    /**
     * ORDER BY random keyword.
     *
     * @var array
     */
    protected $_random_keyword = ['RAND()', 'RAND()'];

    /**
     * IBase Transaction status flag.
     *
     * @var resource
     */
    protected $_ibase_trans;

    // --------------------------------------------------------------------

    /**
     * Non-persistent database connection.
     *
     * @return resource
     */
    public function db_connect()
    {
        return @ibase_connect($this->hostname.':'.$this->database, $this->username, $this->password, $this->char_set);
    }

    // --------------------------------------------------------------------

    /**
     * Persistent database connection.
     *
     * @return resource
     */
    public function db_pconnect()
    {
        return @ibase_pconnect($this->hostname.':'.$this->database, $this->username, $this->password, $this->char_set);
    }

    // --------------------------------------------------------------------

    /**
     * Database version number.
     *
     * @return string
     */
    public function version()
    {
        if (isset($this->data_cache['version'])) {
            return $this->data_cache['version'];
        }

        if (($service = ibase_service_attach($this->hostname, $this->username, $this->password))) {
            $this->data_cache['version'] = ibase_server_info($service, IBASE_SVC_SERVER_VERSION);

            // Don't keep the service open
            ibase_service_detach($service);

            return $this->data_cache['version'];
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Execute the query.
     *
     * @param string $sql an SQL query
     *
     * @return resource
     */
    protected function _execute($sql)
    {
        return @ibase_query($this->conn_id, $sql);
    }

    // --------------------------------------------------------------------

    /**
     * Begin Transaction.
     *
     * @param bool $test_mode
     *
     * @return bool
     */
    public function trans_begin($test_mode = false)
    {
        // When transactions are nested we only begin/commit/rollback the outermost ones
        if (!$this->trans_enabled or $this->_trans_depth > 0) {
            return true;
        }

        // Reset the transaction failure flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->_trans_failure = ($test_mode === true);

        $this->_ibase_trans = @ibase_trans($this->conn_id);

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Commit Transaction.
     *
     * @return bool
     */
    public function trans_commit()
    {
        // When transactions are nested we only begin/commit/rollback the outermost ones
        if (!$this->trans_enabled or $this->_trans->depth > 0) {
            return true;
        }

        return @ibase_commit($this->_ibase_trans);
    }

    // --------------------------------------------------------------------

    /**
     * Rollback Transaction.
     *
     * @return bool
     */
    public function trans_rollback()
    {
        // When transactions are nested we only begin/commit/rollback the outermost ones
        if (!$this->trans_enabled or $this->_trans_depth > 0) {
            return true;
        }

        return @ibase_rollback($this->_ibase_trans);
    }

    // --------------------------------------------------------------------

    /**
     * Affected Rows.
     *
     * @return int
     */
    public function affected_rows()
    {
        return @ibase_affected_rows($this->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * Insert ID.
     *
     * @param string $generator_name
     * @param int    $inc_by
     *
     * @return int
     */
    public function insert_id($generator_name, $inc_by = 0)
    {
        //If a generator hasn't been used before it will return 0
        return ibase_gen_id('"'.$generator_name.'"', $inc_by);
    }

    // --------------------------------------------------------------------

    /**
     * List table query.
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param bool $prefix_limit
     *
     * @return string
     */
    protected function _list_tables($prefix_limit = false)
    {
        $sql = 'SELECT "RDB$RELATION_NAME" FROM "RDB$RELATIONS" WHERE "RDB$RELATION_NAME" NOT LIKE \'RDB$%\' AND "RDB$RELATION_NAME" NOT LIKE \'MON$%\'';

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql.' AND "RDB$RELATION_NAME" LIKE \''.$this->escape_like_str($this->dbprefix)."%' "
                .sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Show column query.
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param string $table
     *
     * @return string
     */
    protected function _list_columns($table = '')
    {
        return 'SELECT "RDB$FIELD_NAME" FROM "RDB$RELATION_FIELDS" WHERE "RDB$RELATION_NAME" = '.$this->escape($table);
    }

    // --------------------------------------------------------------------

    /**
     * Returns an object with field data.
     *
     * @param string $table
     *
     * @return array
     */
    public function field_data($table = '')
    {
        if ($table === '') {
            return ($this->db_debug) ? $this->display_error('db_field_param_missing') : false;
        }

        $sql = 'SELECT "rfields"."RDB$FIELD_NAME" AS "name",
				CASE "fields"."RDB$FIELD_TYPE"
					WHEN 7 THEN \'SMALLINT\'
					WHEN 8 THEN \'INTEGER\'
					WHEN 9 THEN \'QUAD\'
					WHEN 10 THEN \'FLOAT\'
					WHEN 11 THEN \'DFLOAT\'
					WHEN 12 THEN \'DATE\'
					WHEN 13 THEN \'TIME\'
					WHEN 14 THEN \'CHAR\'
					WHEN 16 THEN \'INT64\'
					WHEN 27 THEN \'DOUBLE\'
					WHEN 35 THEN \'TIMESTAMP\'
					WHEN 37 THEN \'VARCHAR\'
					WHEN 40 THEN \'CSTRING\'
					WHEN 261 THEN \'BLOB\'
					ELSE NULL
				END AS "type",
				"fields"."RDB$FIELD_LENGTH" AS "max_length",
				"rfields"."RDB$DEFAULT_VALUE" AS "default"
			FROM "RDB$RELATION_FIELDS" "rfields"
				JOIN "RDB$FIELDS" "fields" ON "rfields"."RDB$FIELD_SOURCE" = "fields"."RDB$FIELD_NAME"
			WHERE "rfields"."RDB$RELATION_NAME" = '.$this->escape($table).'
			ORDER BY "rfields"."RDB$FIELD_POSITION"';

        return (($query = $this->query($sql)) !== false)
            ? $query->result_object()
            : false;
    }

    // --------------------------------------------------------------------

    /**
     * Error.
     *
     * Returns an array containing code and message of the last
     * database error that has occured.
     *
     * @return array
     */
    public function error()
    {
        return ['code' => ibase_errcode(), 'message' => ibase_errmsg()];
    }

    // --------------------------------------------------------------------

    /**
     * Update statement.
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param string $table
     * @param array  $values
     *
     * @return string
     */
    protected function _update($table, $values)
    {
        $this->qb_limit = false;

        return parent::_update($table, $values);
    }

    // --------------------------------------------------------------------

    /**
     * Truncate statement.
     *
     * Generates a platform-specific truncate string from the supplied data
     *
     * If the database does not support the TRUNCATE statement,
     * then this method maps to 'DELETE FROM table'
     *
     * @param string $table
     *
     * @return string
     */
    protected function _truncate($table)
    {
        return 'DELETE FROM '.$table;
    }

    // --------------------------------------------------------------------

    /**
     * Delete statement.
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @param string $table
     *
     * @return string
     */
    protected function _delete($table)
    {
        $this->qb_limit = false;

        return parent::_delete($table);
    }

    // --------------------------------------------------------------------

    /**
     * LIMIT.
     *
     * Generates a platform-specific LIMIT clause
     *
     * @param string $sql SQL Query
     *
     * @return string
     */
    protected function _limit($sql)
    {
        // Limit clause depends on if Interbase or Firebird
        if (stripos($this->version(), 'firebird') !== false) {
            $select = 'FIRST '.$this->qb_limit
                .($this->qb_offset ? ' SKIP '.$this->qb_offset : '');
        } else {
            $select = 'ROWS '
                .($this->qb_offset ? $this->qb_offset.' TO '.($this->qb_limit + $this->qb_offset) : $this->qb_limit);
        }

        return preg_replace('`SELECT`i', 'SELECT '.$select, $sql, 1);
    }

    // --------------------------------------------------------------------

    /**
     * Close DB Connection.
     *
     * @return void
     */
    protected function _close()
    {
        @ibase_close($this->conn_id);
    }
}

/* End of file ibase_driver.php */
/* Location: ./system/database/drivers/ibase/ibase_driver.php */
