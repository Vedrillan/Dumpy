<?php
/**
 * Smile Dumpy Helper
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get from config the table in which email field need to be obfuscated
     *
     * @return array
     */
    public function getTablesToObfuscate()
    {
        $configFilePath = Mage::getModuleDir('etc', $this->_getModuleName()) . '/table.xml';
        $tableConfig = Mage::getModel('core/config');

        if (!$tableConfig->loadFile($configFilePath) || !$tableConfig->getNode('tables')) {
            return array();
        }

        return $tableConfig->getNode('tables')->asCanonicalArray();
    }

    /**
     * Return the file full path to the most recent dump
     *
     * @return bool|string dump location or false if path to dumps is wrong or if no file has been found
     */
    public function getLastDumpPath()
    {
        if (!Mage::getStoreConfigFlag('system/dumpy/dump_location')) {
            return false;
        }

        $path = Mage::getStoreConfig('system/dumpy/dump_location');
        $iterator = new DirectoryIterator(Mage::getBaseDir('var') . '/' . trim($path, '/'));

        $ctime = $latest = false;
        foreach ($iterator as $element) {
            if ($element->isFile()) {
                if ($ctime === null || $ctime < $element->getCTime()) {
                    $latest = $element->getPathname();
                    $ctime = $element->getCTime();
                }
            }
        }

        return $latest;
    }

    /**
     * Uncompress gzipped file
     *
     * @param string $path path of the compressed file
     *
     * @return string the uncompressed file path
     */
    public function gunzipFile($path)
    {
        // Validate extension
        $pathInfo = pathinfo($path);
        if ($pathInfo['extension'] != 'gz') {
            return $path;
        }

        // read 4kb at a time
        $bufferSize = 4096;
        $outFileName = $pathInfo['dirname'] . '/' . $pathInfo['filename'];

        // Open our files (in binary mode)
        $file = gzopen($path, 'rb');
        $outFile = fopen($outFileName, 'wb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($outFile, gzread($file, $bufferSize));
        }

        // Files are done, close files
        fclose($outFile);
        gzclose($file);
        unlink($path);

        return $outFileName;
    }
}