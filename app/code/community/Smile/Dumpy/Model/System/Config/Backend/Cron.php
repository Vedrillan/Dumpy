<?php
/**
 * Smile Dumpy System Config Backend Cron Model
 *
 * @category  Smile
 * @package   Smile_Dumpy
 * @author    David Wattier <david.wattier@smile.fr>
 * @copyright 2014 Smile
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Dumpy_Model_System_Config_Backend_Cron extends Mage_Core_Model_Config_Data
{
    const CRON_STRING_PATH  = 'system/dumpy/cron_expr';

    /**
     * Cron settings after save
     *
     * @return Smile_Dumpy_Model_System_Config_Backend_Cron
     */
    protected function _afterSave()
    {
        $enabled    = $this->getData('groups/dumpy/fields/enabled/value');
        $time       = $this->getData('groups/dumpy/fields/time/value');
        $frequency  = $this->getData('groups/dumpy/fields/frequency/value');

        $frequencyWeekly    = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
        $frequencyMonthly   = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;

        if ($enabled) {
            $cronExprArray = array(
                intval($time[1]),                                   // Minute
                intval($time[0]),                                   // Hour
                ($frequency == $frequencyMonthly) ? '1' : '*',      // Day of the Month
                '*',                                                // Month of the Year
                ($frequency == $frequencyWeekly) ? '1' : '*',       // Day of the Week
            );
            $cronExprString = join(' ', $cronExprArray);
        } else {
            $cronExprString = '';
        }

        try {
            Mage::getModel('core/config_data')
                ->load(self::CRON_STRING_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_STRING_PATH)
                ->save();
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('adminhtml')->__('Unable to save the cron expression.'));
        }
    }
}