<?php
/**
 * You're required to override this helper in your module.
 * If you don't, you'll get the following error.
 * PHP Fatal error:  Class 'Mage_Mockpay_Helper_Data' not found
 */
class My_Mockpay_Helper_Data extends Mage_Payment_Helper_Data
{
    //STUB - Add helper methods here
    /*
        array getAllBillingAgreementMethods ()
        array getBillingAgreementMethods ([mixed $store = null], [Mage_Sales_Model_Quote $quote = null])
        Mage_Core_Block_Template getInfoBlock (Mage_Payment_Model_Info $info)
        Mage_Payment_Block_Form getMethodFormBlock (Mage_Payment_Model_Method_Abstract $method)
        Mage_Payment_Model_Method_Abstract|false getMethodInstance (string $code)
        array getPaymentMethodList ([bool $sorted = true], [bool $asLabelValue = false], [bool $withGroups = false], [ $store = null])
        array getPaymentMethods ([mixed $store = null])
        array getRecurringProfileMethods ([mixed $store = null])
        array getStoreMethods ([mixed $store = null], [Mage_Sales_Model_Quote $quote = null])
        void _sortMethods ( $a,  $b)
        @see http://docs.magentocommerce.com/Mage_Payment/Mage_Payment_Helper_Data.html
     */
}