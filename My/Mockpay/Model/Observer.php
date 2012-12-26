<?php
/**
 * 
 * An event model.
 * @author dkilgo
 *
 */
class My_Mockpay_Model_Observer
{   
    /**
     * See etc/config.xml
     * Triggered by: Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order'=>$order, 'quote'=>$this->getQuote())); 
     * @param Varien_Event_Observer $observer
     */
    public function saveOrderQuoteToSession($observer){
        /* @var $event Varien_Event */
        $event = $observer->getEvent();
        /* @var $order Mage_Sales_Model_Order */
        $order = $event->getOrder();
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $event->getQuote();
              
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $quote->getId();
        $orderId = $order->getId();
        $incrId = $order->getIncrementId();
        Mage::log("Saving quote  [$quoteId] and order [$incrId] to checkout/session");

        $session->setData('apiOrderId',$orderId);
        $session->setData('apiOrderIncrementId',$incrId);
        
        unset($event);
        unset($order);
        unset($quote);
        unset($session);
        
        return $this;
    }
}