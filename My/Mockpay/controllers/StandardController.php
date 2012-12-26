<?php
class My_Mockpay_StandardController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _isValidToken(){
        $uriToken = $this->getRequest()->getParam('token');
        $sessionToken = $this->_getApiToken();
        Mage::Log("Testing tokens(uri/session) $uriToken/$sessionToken");
        if($uriToken == $sessionToken){
            return true;
        }
        return false;
    }
    
    protected function _getApiToken(){
        $sessionToken = Mage::getSingleton('checkout/session')->getData('apiToken');
        return $sessionToken;
    }
    
    protected function _getApiQuoteId(){
        $quoteId = Mage::getSingleton('checkout/session')->getData('apiQuoteId');
        Mage::log('Returned quoteId ' . $quoteId);
        return $quoteId;
    }
    
    protected  function _getApiOrderId(){
        $orderId = Mage::getSingleton('checkout/session')->getData('apiOrderId');
        Mage::log('Returned orderId ' . $orderId);
        return $orderId;
    }
    /**
    * Builds invoice for order
    */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
    }
    /**
     * When a customer cancel payment from api
     */
    protected function _cancelAction()
    {
        Mage::Log('Called ' . __METHOD__);
        if(!$this->_isValidToken()){
            Mage::Log('Token is invalid.');
            $this->_redirect('checkout/cart');    
        }
        //TODO: add Api specific values. Copied form paypal standard.
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($this->_getApiQuoteId());
         /* @var $quote Mage_Sales_Model_Quote */
        $quote = $session->getQuote();
        $quote->setIsActive(false)->save();
        $quote->delete();
        
        $orderId = $this->_getApiOrderId();
        Mage::Log('Canceling order ' . $orderId);
        if ($orderId) {
            $order = Mage::getSingleton('sales/order');
            $order->load($orderId);
            if ($order->getId()) {
                $state = $order->getState();
                if($state == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT){
                    $order->cancel()->save();
                    Mage::getSingleton('core/session')->addNotice('Your order has been canceled.');
                }
            }
        }
        $this->_redirect('checkout/cart');
    }
    
    /**
     * When paypal returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the api.
     */
    public function  successAction()
    {
        mage::log('Called custom ' . __METHOD__);
        if(!$this->_isValidToken()){
            Mage::Log('Token is invalid.');
            $this->_redirect('checkout/cart');    
        }
        
        try{
            $wsdl = Mage::getStoreConfig('payment/mockpay/wsdl');
            $api = new My_Mockpay_Model_Api($wsdl);
            $response = $api->queryPayment($this->_getApiToken());
        }catch (Exception $e){
            Mage::throwException($e->getMessage(),$e->getCode());
        }
        
        if($response['hasErrors'] == 'N' && empty($response['errorMessage'])){
            //payment was captured successfully.
            Mage::log('Payment Captured successfully');           
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($this->_getApiQuoteId());                        
            //Change the state of the order to pending and add comment.
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getSingleton('sales/order');
            $order->load($this->_getApiOrderId());
            $state = $order->getState();
            if($state == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT){
                //sets the status to 'pending'.
                $msg = 'Payment completed via MockPay.';
                $order->setState(Mage_Sales_Model_Order::STATE_NEW ,true,$msg,false);
                $order->save();
                
                /* @var $quote Mage_Sales_Model_Quote */
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $quote->setIsActive(false)->save();
            }
            
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }
    }
    /**
     * Handles 'falures' from api
     * Failure could occur if api system failure, insufficent funds, or system error.
     * @throws Exception
     */
    public function failureAction(){
        Mage::Log('Called ' . __METHOD__);
        $this->cancelAction();
    }
    
    public function cancelAction(){
        Mage::Log('Called ' . __METHOD__);
        $this->_cancelAction();
    }
}