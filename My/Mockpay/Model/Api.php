<?php
Class My_Mockpay_Model_Api {
  /**
   * @var string
   */
   protected $_wsdl;
 /**
   * @var SoapClient
   */
   public $_client;
 
   /**
    * provide the wsdl and endpoint so we can construct the soap object.
    * @param string $wsdl
    */
   public function __construct($wsdl){
        $this->_wsdl = $wsdl;
        $options = array('trace'=>true);
        $this->_client = new SoapClient($this->_wsdl, $options);
    }
    /**
     * Soap wrapper for remote method.
     * @param string $email
     * @param string $amount
     * @param string $orderId
     * @param string $desc
     * @param string $uriSuccess
     * @param string $uriFailure
     * @param string $uriCancel
     * @return array
     */
    public function beginPayment($email,$amount,$orderId,$desc,$uriSuccess,$uriFailure,$uriCancel){
        $response = $this->_client->beginPayment($email,$amount,$orderId,$desc,$uriSuccess,$uriFailure,$uriCancel);
        if($response == false || !is_array($response)){
            return array('hasErrors'=>'-1','errorMessage'=>'Failure');
        }
        return $response;
    }
    /**
     * Soap wrapper for remote method
     * @param string $token
     * @return array 
     */
    public function queryPayment($token){
        $response = $this->_client->queryPayment($token);
        if($response == false || !is_array($response)){
            return array('hasErrors'=>'-1','errorMessage'=>'Failure');
        }
        return $response;            
    }
}