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
 * Interbase/Firebird Forge Class.
 *
 * @category	Database
 *
 * @author		EllisLab Dev Team
 *
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_ibase_forge extends CI_DB_forge
{
    /**
     * CREATE TABLE IF statement.
     *
     * @var string
     */
    protected $_create_table_if = false;

    /**
     * RENAME TABLE statement.
     *
     * @var string
     */
    protected $_rename_table = false;

    /**
     * DROP TABLE IF statement.
     *
     * @var string
     */
    protected $_drop_table_if = false;

    /**
     * UNSIGNED support.
     *
     * @var array
     */
    protected $_unsigned = [
        'SMALLINT'     => 'INTEGER',
        'INTEGER'      => 'INT64',
        'FLOAT'        => 'DOUBLE PRECISION',
    ];

    /**
     * NULL value representation in CREATE/ALTER TABLE statements.
     *
     * @var string
     */
    protected $_null = 'NULL';

    // --------------------------------------------------------------------

    /**
     * Create database.
     *
     * @param string $db_name
     *
     * @return string
     */
    public function create_database($db_name)
    {
        // Firebird databases are flat files, so a path is required

        // Hostname is needed for remote access
        empty($this->db->hostname) or $db_name = $this->hostname.':'.$db_name;

        return parent::create_database('"'.$db_name.'"');
    }

    // --------------------------------------------------------------------

    /**
     * Drop database.
     *
     * @param string $db_name (ignored)
     *
     * @return bool
     */
    public function drop_database($db_name = '')
    {
        if (!ibase_drop_db($this->conn_id)) {
            return ($this->db->db_debug) ? $this->db->display_error('db_unable_to_drop') : false;
        } elseif (!empty($this->db->data_cache['db_names'])) {
            $key = array_search(strtolower($this->db->database), array_map('strtolower', $this->db->data_cache['db_names']), true);
            if ($key !== false) {
                unset($this->db->data_cache['db_names'][$key]);
            }
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * ALTER TABLE.
     *
     * @param string $alter_type ALTER type
     * @param string $table      Table name
     * @param mixed  $field      Column definition
     *
     * @return string|string[]
     */
    protected function _alter_table($alter_type, $table, $field)
    {
        if (in_array($alter_type, ['DROP', 'ADD'], true)) {
            return parent::_alter_table($alter_type, $table, $field);
        }

        $sql = 'ALTER TABLE '.$this->db->escape_identifiers($table);
        $sqls = [];
        for ($i = 0, $c = count($field); $i < $c; $i++) {
            if ($field[$i]['_literal'] !== false) {
                return false;
            }

            if (isset($field[$i]['type'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escape_identififers($field[$i]['name'])
                    .' TYPE '.$field[$i]['type'].$field[$i]['length'];
            }

            if (!empty($field[$i]['default'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escape_identifiers($field[$i]['name'])
                    .' SET DEFAULT '.$field[$i]['default'];
            }

            if (isset($field[$i]['null'])) {
                $sqls[] = 'UPDATE "RDB$RELATION_FIELDS" SET "RDB$NULL_FLAG" = '
                    .($field[$i]['null'] === true ? 'NULL' : '1')
                    .' WHERE "RDB$FIELD_NAME" = '.$this->db->escape($field[$i]['name'])
                    .' AND "RDB$RELATION_NAME" = '.$this->db->escape($table);
            }

            if (!empty($field[$i]['new_name'])) {
                $sqls[] = $sql.' ALTER COLUMN '.$this->db->escape_identifiers($field[$i]['name'])
                    .' TO '.$this->db->escape_identifiers($field[$i]['new_name']);
            }
        }

        return $sqls;
    }

    // --------------------------------------------------------------------

    /**
     * Process column.
     *
     * @param array $field
     *
     * @return string
     */
    protected function _process_column($field)
    {
        return $this->db->escape_identifiers($field['name'])
            .' '.$field['type'].$field['length']
            .$field['null']
            .$field['unique']
            .$field['default'];
    }

    // --------------------------------------------------------------------

    /**
     * Field attribute TYPE.
     *
     * Performs a data type mapping between different databases.
     *
     * @param array &$attributes
     *
     * @return void
     */
    protected function _attr_type(&$attributes)
    {
        switch (strtoupper($attributes['TYPE'])) {
            case 'TINYINT':
                $attributes['TYPE'] = 'SMALLINT';
                $attributes['UNSIGNED'] = false;

                return;
            case 'MEDIUMINT':
                $attributes['TYPE'] = 'INTEGER';
                $attributes['UNSIGNED'] = false;

                return;
            case 'INT':
                $attributes['TYPE'] = 'INTEGER';

                return;
            case 'BIGINT':
                $attributes['TYPE'] = 'INT64';

                return;
            default: return;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Field attribute AUTO_INCREMENT.
     *
     * @param array &$attributes
     * @param array &$field
     *
     * @return void
     */
    protected function _attr_auto_increment(&$attributes, &$field)
    {
        // Not supported
    }
}

/* End of file ibase_forge.php */
/* Location: ./system/database/drivers/ibase/ibase_forge.php */
