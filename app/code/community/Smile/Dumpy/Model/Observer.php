<?php
/**
 * Smile Dumpy Observer Model
 *
 * Extension of the Magento default Observer to add a locking system
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Model_Observer extends Mage_Cron_Model_Observer
{
    const CRON_OBSERVER_LOCK_FILE = 'cron_observer';

    /**
     * Rewrite to prevent the cron jobs to run if locked
     *
     * @param Varien_Event_Observer $observer observer object
     *
     * @return void
     */
    public function dispatch($observer)
    {
        if (!$this->isLocked()) {
            parent::dispatch($observer);
        }
    }

    /**
     * Lock the cron jobs
     *
     * @return void
     */
    public function lock()
    {
        Mage::helper('smile_dumpy/locker')->lock(self::CRON_OBSERVER_LOCK_FILE);
    }

    /**
     * Unlock the cron jobs
     *
     * @return void
     */
    public function unlock()
    {
        Mage::helper('smile_dumpy/locker')->unlock(self::CRON_OBSERVER_LOCK_FILE);
    }

    /**
     * Check lock on cron jobs
     *
     * @return bool true if locked, false otherwise
     */
    public function isLocked()
    {
        return Mage::helper('smile_dumpy/locker')->isLocked(self::CRON_OBSERVER_LOCK_FILE);
    }
}