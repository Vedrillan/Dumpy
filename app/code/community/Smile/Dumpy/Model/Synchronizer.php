<?php

/**
 * Smile Dumpy Synchronizer Model
 *
 * Possible improvements:
 * - backup whole database before importing the dump
 * - create an old dumps cleaner
 *
 * There is a possibility that the database connection is closed before
 * the end of the dump import, which would result in the loose of the temporary table
 * so we might need to change the way data are backuped
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Model_Synchronizer extends Mage_Core_Model_Abstract
{
    /**
     *  Will execute all steps necessary to the synchronization
     *
     * @return void
     */
    public function run()
    {
        $this->_log('Begin synchronization');

        // get dump path and check that some exists and get the last dump
        $this->_log('Getting most recent dump');
        $path = Mage::helper('smile_dumpy')->getLastDumpPath();

        if ($path == false) {
            Mage::throwException('No dump file found.');
        }

        // uncompress dump if ziped
        $this->_log('Gunzip the file');
        $uncompressedFile = Mage::helper('smile_dumpy')->gunzipFile($path);

        $uncompressedInfo = pathinfo($uncompressedFile);
        if ($uncompressedInfo['extension'] != 'sql') {
            Mage::throwException('The dump file does not have the sql extension.');
        }

        // remove log from dump etc
        $this->_log('Cleaning dump');
        $this->cleanUpDump($uncompressedFile);

        // replace url in dump
        $this->_log('Replace url in dump');
        $this->replaceUrl($uncompressedFile);

        // backup necessary tables
        $this->_log('Backuping the necessary tables into temporary tables');
        $this->backupTablesTemporarily(array('core_config_data'));

        // stop crons
        $this->_log('Locking cron jobs');
        Mage::getSingleton('smile_dumpy/observer')->lock();

        // import the dump
        $this->_log('Import sql dump file');
        $this->importDump($uncompressedFile);

        // restore backuped tables
        $this->_log('Restore backuped tables from the temporary tables');
        $this->restoreBackupedTables(array('core_config_data'));

        // obfuscate emails if enabled
        if (Mage::getStoreConfig('system/dumpy/obfuscator_enabled')) {
            $this->_log('Obfuscating customer emails in database');
            $this->obfuscateEmails();
        }

        // run crons again
        $this->_log('Unlocking cron jobs');
        Mage::getSingleton('smile_dumpy/observer')->unlock();

        // Regenerate cron_schedule table
        $this->_log('Regenerate cron_schedule table');
        Mage::getSingleton('smile_dumpy/observer')->generate();

        $this->_log('Synchronization finished');
    }

    /**
     * Replace customer emails by a dynamic one
     *
     * @return void
     */
    public function obfuscateEmails()
    {
        $this->getResource()->obfuscateEmails();
    }

    /**
     * Remove from the dump data that do not need to be imported
     *
     * @param string $dumpFile dump file location
     *
     * @return void
     */
    public function cleanUpDump($dumpFile)
    {
        exec('sed -i \'/INSERT INTO `log_/d\' ' . $dumpFile);
        exec('sed -i \'/INSERT INTO `dataflow_/d\' ' . $dumpFile);
        exec('sed -i \'/INSERT INTO `core_config_data/d\' ' . $dumpFile);
        exec('sed -i \'/INSERT INTO `cron_schedule/d\' ' . $dumpFile);
    }

    /**
     * Replace the URL used in the dump by a new one
     *
     * @param string $dumpFile dump file location
     *
     * @return void
     */
    public function replaceUrl($dumpFile)
    {
        if (!Mage::getStoreConfigFlag('system/dumpy/url_replaced')) {
            Mage::throwException('URL replacement not set in configuration.');
        }

        $mask = Mage::getStoreConfig('system/dumpy/url_replaced');
        exec('sed -i \'s/' . $mask . '/g\' ' . $dumpFile);
    }

    /**
     * Backup some tables for latter use
     *
     * @param array $tables list of tables to backup
     *
     * @return void
     */
    public function backupTablesTemporarily($tables)
    {
        $this->getResource()->backupTablesTemporarily($tables);
    }

    /**
     * Restore tables data from paviously created backup
     *
     * @param array $tables list of tables to restore
     *
     * @return void
     */
    public function restoreBackupedTables($tables)
    {
        $this->getResource()->restoreBackupedTables($tables);
    }

    /**
     * Import dump file to the database
     *
     * @param string $dumpFile dump file location
     *
     * @return void
     */
    public function importDump($dumpFile)
    {
        $this->getResource()->importDump($dumpFile);
    }

    /**
     * Resource init
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('smile_dumpy/synchronizer');
    }

    /**
     * Log message to a specific file
     *
     * @param string $message message to log
     *
     * @return void
     */
    protected function _log($message)
    {
        Mage::log($message, Zend_Log::INFO, 'dumpy_synchronization.log');
    }
}