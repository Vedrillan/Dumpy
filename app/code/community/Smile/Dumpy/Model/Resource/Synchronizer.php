<?php
/**
 * Smile Dumpy Resource Synchronizer
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Model_Resource_Synchronizer extends Mage_Core_Model_Resource_Abstract
{
    protected $_tables = array();
    protected $_backupedTables = array();

    /**
     * Replace customer emails by a dynamic one
     *
     * @return void
     */
    public function obfuscateEmails()
    {
        $baseEmail = Mage::getStoreConfig('system/dumpy/obfuscator_base_email');
        $parts = explode('@', $baseEmail);

        foreach ($this->_tables as $table => $field) {
            $set = new Zend_Db_Expr("concat('{$parts[0]}+', sha1($field), '@{$parts[1]}')");
            $this->_getWriteAdapter()->update($this->_getTable($table), array($field => $set));
        }
    }

    /**
     * Save core_config_data table into a temporary table
     *
     * @param array $tables list of tables to backup
     *
     * @return void
     */
    public function backupTablesTemporarily($tables)
    {
        foreach ($tables as $table) {
            if (!in_array($table, $this->_backupedTables)) {
                $tableName = $this->_getTable($table);
                $tempTableName = $tableName . '_temp' ;
                $this->_getWriteAdapter()->query(
                    'CREATE TEMPORARY TABLE IF NOT EXISTS ' . $tempTableName . ' AS (SELECT * FROM ' . $tableName . ');'
                );
                $this->_backupedTables[] = $table;
            }
        }
    }

    /**
     * Restore core_config_data table from the temporary backup table
     *
     * @param array $tables list of tables to restore
     *
     * @return void
     */
    public function restoreBackupedTables($tables)
    {
        foreach ($tables as $table) {
            if (in_array($table, $this->_backupedTables)) {
                $select = $this->_getReadAdapter()
                    ->select()
                    ->from($this->_getTable($table . '_temp'));

                $query = $select->insertFromSelect($this->_getTable($table));
                $this->_getWriteAdapter()->query($query);
            }
        }
    }

    /**
     * Import dump file to database
     *
     * @param string $dumpFile dump location
     *
     * @return void
     */
    public function importDump($dumpFile)
    {
        $conConfig = Mage::getConfig()->getResourceConnectionConfig('core_write');
        $command = sprintf(
            'mysql -u%s %s -h%s %s < ' . $dumpFile,
            $conConfig->username,
            !empty($conConfig->password) ? '-p' . $conConfig->password:'',
            $conConfig->host,
            $conConfig->dbname
        );
        exec($command);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_tables = Mage::helper('smile_dumpy')->getTablesToObfuscate();
    }

    /**
     * Retrieve connection for read data
     *
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getReadAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    /**
     * Retrieve connection for write data
     *
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getWriteAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    /**
     * Resolve database table name
     *
     * @param string $name table name to resolve
     *
     * @return string
     */
    protected function _getTable($name)
    {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }

}