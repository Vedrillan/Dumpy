<?php
/**
 * Smile Dumpy Helper Locker
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Helper_Locker extends Mage_Core_Helper_Abstract
{
    protected $_lockFile = array();
    protected $_isLocked = array();

    /**
     * Get lock file resource
     *
     * @param string $name the lock name
     *
     * @return resource
     */
    protected function _getLockFile($name)
    {
        if (!isset($this->_lockFile[$name])) {
            $varDir = Mage::getConfig()->getVarDir('locks');
            $file = $varDir . DS . $name . '.lock';
            if (is_file($file)) {
                $this->_lockFile[$name] = fopen($file, 'w');
            } else {
                $this->_lockFile[$name] = fopen($file, 'x');
            }
            fwrite($this->_lockFile[$name], date('r'));
        }
        return $this->_lockFile[$name];
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process runing and fast lock validation.
     *
     * @param string $name the lock name
     *
     * @return Smile_Dumpy_Helper_Locker
     */
    public function lock($name)
    {
        $this->_isLocked[$name] = true;
        flock($this->_getLockFile($name), LOCK_EX | LOCK_NB);
        return $this;
    }

    /**
     * Unlock process
     *
     * @param string $name the lock name
     *
     * @return Smile_Dumpy_Helper_Locker
     */
    public function unlock($name)
    {
        $this->_isLocked[$name] = false;
        flock($this->_getLockFile($name), LOCK_UN);
        return $this;
    }

    /**
     * Check if process is locked
     *
     * @param string $name the lock name
     *
     * @return bool
     */
    public function isLocked($name)
    {
        if (isset($this->_isLocked[$name])) {
            return $this->_isLocked[$name];
        } else {
            $fp = $this->_getLockFile($name);
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                flock($fp, LOCK_UN);
                return false;
            }
            return true;
        }
    }

    /**
     * Close file resource if it was opened
     *
     * @return void
     */
    public function __destruct()
    {
        if (!empty($this->_lockFile)) {
            foreach ($this->_lockFile as $handle) {
                fclose($handle);
            }
        }
    }
}