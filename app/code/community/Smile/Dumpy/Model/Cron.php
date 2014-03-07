<?php
/**
 * Smile Dumpy Cron Model
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Model_Cron
{
    /**
     * Run database synchronization
     *
     * @return void
     */
    public function synchronize()
    {
        if (!Mage::getStoreConfig('system/dumpy/enabled')) {
            return;
        }

        Mage::getModel('smile_dumpy/synchronizer')->run();

        if (Mage::helper('core')->isModuleEnabled('Smile_Varnish')) {
            Mage::getSingleton('smile_varnish/processor')->flushAll();
        }

        Mage::dispatchEvent('dumpy_synchronization_after');
        Mage::app()->getCacheInstance()->flush();
    }
}