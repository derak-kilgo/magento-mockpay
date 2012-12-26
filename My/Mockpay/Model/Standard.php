<?php
class My_Mockpay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{ 
    
    //payment method code. Used in XML config and Db.
    protected $_code = 'mockpay';
    //flag which causes initalize() to run when checkout is completed. 
    protected $_isInitializeNeeded      = true;
    //Disable payment method in admin/order pages.
    protected $_canUseInternal          = false;
    //Disable multi-shipping for this payment module.
    protected $_canUseForMultishipping  = false;
    //token provided by mockpay api when communicating back to magento.
    protected $_apiToken = null;
    /**
    * Return URL to redirect the customer to.
    * Called after 'place order' button is clicked.
    * Called after order is created and saved.
    * @return string
    */
    public function getOrderPlaceRedirectUrl()
    {
        /* Mage log is your friend. 
         * While it shouldn't be on in production, 
         * it makes debugging problems with your api much easier.
         * The file is in magento-root/var/log/system.log
         */
        mage::log('Called custom ' . __METHOD__);
        $url = $this->getConfigData('redirecturl');
        
        if(!isset($this->_apiToken)){
           $this->_apiToken = Mage::getSingleton('checkout/session')->getData('apiToken');
        }
         
        return $url . '?token=' . $this->_apiToken;
    }
    /**
     * 
     * <payment_action>Sale</payment_action>
     * Initialize payment method. Called when purchase is complete.
     * Order is created after this method is called.
     *
     * @param string $paymentAction
     * @param Varien_Object $stateObject
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {       
        Mage::log('Called ' . __METHOD__ . ' with payment ' . $paymentAction);
        parent::initialize($paymentAction, $stateObject);
        
        //Payment is also used for refund and other backend functions.
        //Verify this is a sale before continuing.
        if($paymentAction != 'sale'){
            return $this;
        }
        
        //Set the default state of the new order.
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT; // state now = 'pending_payment'
        $stateObject->setState($state); 
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
        
        //Extract order details and send to mockpay api. Get api token and save it to checkout/session.
        try{
            $this->_customBeginPayment();
        }catch (Exception $e){
            Mage::log($e);    
            Mage::throwException($e->getMessage());
        }
        return $this;
    }
    /**
     * 
     * Extract cart/quote details and send to api.
     * Respond with token
     * @throws SoapFault
     * @throws Mage_Exception
     * @throws Exception
     */
    protected  function _customBeginPayment(){
        //Retrieve the wsdl and endpoint from the magento global config table.
        $api = new My_Mockpay_Model_Api($this->getConfigData('wsdl'));
        
        //Most API's require an id and key to access them. Mock pay is unauthenticated.
        //This information should be stored in the configData. You configure these options in the system.xml file.
        //$api->setMerchantKey($this->getConfigData('merchantkey'));
        //$api->setMerchantId($this->getConfigData('merchantid'));

        //Retrieve cart/quote information.
        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quoteId = $sessionCheckout->getQuoteId();
        //The quoteId will be removed from the session once the order is placed. 
        //If you need it, save it to the session yourself.
        $sessionCheckout->setData('mockpayQuoteId',$quoteId);
        
        $quote = Mage::getModel("sales/quote")->load($quoteId);
        $grandTotal = $quote->getData('grand_total');
        $subTotal = $quote->getSubtotal();
        $shippingHandling = ($grandTotal-$subTotal);
        Mage::Log("Sub Total: $subTotal | Shipping & Handling: $shippingHandling | Grand Total $grandTotal");
        
        //Set required items.
        $billingData = $quote->getBillingAddress()->getData();
        $apiEmail = $billingData['email'];
        $apiAmount = $grandTotal;
        $apiOrderId = (str_pad($quoteId, 9,0,STR_PAD_LEFT));
        //Retrieve items from the quote.
        $items = $quote->getItemsCollection()->getItems();
        $apiDesc = '';
        foreach($items as $item){
           $sku = $item->getSku();
           $unitPrice = $item->getPrice();
           $qty = $item->getQty();
           $desc = $item->getName();
           Mage::log("LINEITEM: $sku - $unitPrice - $qty - $desc \n");
           //since our simple api doesn't support line items.
           //we can provide more info via the description field.
           $apiDesc .= $sku . '(x' . $qty . ') ';
        }
        //Add Shipping as line item so total matches magento's charge.
        if($shippingHandling > 0){
            $apiDesc .= ' + S&H';
        }
        //Build urls back to our modules controller actions as required by the api.
        $oUrl = Mage::getModel('core/url');
        $apiHrefSuccess = $oUrl->getUrl("mockpay/standard/success");
        $apiHrefFailure = $oUrl->getUrl("mockpay/standard/failure");
        $apiHrefCancel = $oUrl->getUrl("mockpay/standard/cancel");
        //Soap Call
        $response = $api->beginPayment($apiEmail,$apiAmount,$apiOrderId,$apiDesc,$apiHrefSuccess,$apiHrefFailure,$apiHrefCancel);
        if(!empty($response['token'])){
            Mage::log("Successfully preparePayment with mockpay");
            $token = $response['token'];
            $sessionCheckout->setData('apiToken',$token);
        }else{
           $msg = '(' . $response['hasErrors'] . ') ' . $response['errorMessage'];
           Mage::log("BeginPayment failed with [$msg]");
           //You can add error messages to the checkout process. These show up when you view the cart.
           $sessionCheckout->addError('An unrecoverable error occured while processing your payment information. ' . $msg);
           Mage::throwException($msg);
        }
        return $this;
    }
}