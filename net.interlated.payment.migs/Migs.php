<?php

/**
 * @property string _mode
 * @package CRM
 * @author John Robens jrobens@interlated.com.au
 * $Id: Migs 42195 2013-08-03 11:51:58IST jrobens $
 */
//require_once 'MigsResponse.php';

class net_interlated_payment_migs extends CRM_Core_Payment {

  CONST CHARSET = 'iso-8859-1';
  CONST AUTH_APPROVED = 'Approved';
  CONST STATUS_CODE = 'Ok';
  CONST TIMEZONE = 'Australia/Sydney';

  protected $templateDir;
  protected $_mode = NULL;
  protected $_params = array();

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @param $paymentProcessor
   * @return \net_interlated_payment_migs
   */
  function __construct($mode, &$paymentProcessor) {
   //  Accessing static property net_interlated_payment_migs::$_mode as non static in net_interlated_payment_migs->__construct() (line 38 of/srv/web/interlated.net/web/sites/all/modules/custom/civicrm/extensions/net.interlated.payment.migs/Migs.php).
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('MIGS');

    $config = CRM_Core_Config::singleton();
    $this->_setParam('merchantID', $paymentProcessor['user_name']);
    $this->_setParam('accessCode', $paymentProcessor['password']);
    $this->templateDir = $config->extensionsDir . $this->_paymentProcessor['class_name'] . '/templates/';

    $this->_setParam('timestamp', time());
    srand(time());
    $this->_setParam('sequence', rand(1, 1000));
  }

  /**
   * Set a field to the specified value.  Value must be a scalar (int,
   * float, string, or boolean)
   *
   * @param string $field
   * @param mixed $value
   *
   * @return bool false if value is not a scalar, true if successful
   */
  function _setParam($field, $value) {
    if (!is_scalar($value)) {
      return FALSE;
    }
    else {
      $this->_params[$field] = $value;
    }
  }

  /**
   * Get the value of a field if set
   *
   * @param string $field the field
   *
   * @param bool $xmlSafe
   * @return mixed value of the field, or empty string if the field is
   * not set
   */
  function _getParam($field, $xmlSafe = FALSE) {
    $value = CRM_Utils_Array::value($field, $this->_params, '');
    if ($xmlSafe) {
      $value = str_replace(array('&', '"', "'", '<', '>'), '', $value);
    }
    return $value;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('Merchant ID is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Access Code is not set for this payment processor');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @param object $paymentProcessor
   * @return object
   * @static
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new self($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, array(), $errorMessage);
    }
    else {
      $e->push(9001, 0, array(), 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * Submit a payment using Advanced Integration Method
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doDirectPayment(&$params) {
    $response = NULL;
    if (CRM_Utils_Array::value('is_recur', $params) && $params['contributionRecurID']) {
      $response = $this->doRecurringPayment($params);
    }
    else {
      $response = $this->doInstantPayment($params);
    }

    return $response;
  }

  /**
   * Submit an Automated Recurring Billing subscription
   *
   * @public
   */
  private function doRecurringPayment(&$params) {

    /*
     * recurpayment function does not compile an array & then proces it 
     * so adding call to hook here & giving it a change to act on the params array
     * 
     */

    $newParams = $params;
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $newParams);
    foreach ($newParams as $field => $value) {
      $this->_setParam($field, $value);
    }

    $packetData = $this->buildRecurringRequest($params);
    $url = $this->_paymentProcessor['url_recur'];

    $response = self::migsSendRequest($packetData, $url);
    //$migsResponse = MigsResponse::singleton($response);

    // Check for response
    if ($response) {
      parse_str($response, $migs_response);

      if ($migs_response['vpc_TxnResponseCode'] != '0') {
        return self::error($migs_response['vpc_TxnResponseCode'], $this->migs_merchant_response_description($migs_response['vpc_TxnResponseCode']));
      }
    }
    else {
      return self::error(9001, 'There has been any error with your order. Please contact the website administrator for more information.');
    }

    if ($migsResponse->Code != self::STATUS_CODE && $migsResponse->FirstTransactionResult != self::AUTH_APPROVED) {
      return self::error(9001, $migsResponse->Error);
    }
    elseif (!empty($migsResponse->ContractKey) && !empty($migsResponse->FirstTransactionPNRef)) {
      // update recur processor_id with subscriptionId
      CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_getParam('contributionRecurID'), 'processor_id', $migsResponse->ContractKey);
      CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_getParam('contributionRecurID'), 'trxn_id', $migsResponse->FirstTransactionPNRef);

      $this->_setParam('subscriptionId', $migsResponse->ContractKey);
      $this->_setParam('trxn_id', $migsResponse->FirstTransactionPNRef); // Traxn Id 
    }
    else {
      return self::error(9001, 'There is some problem in recurring billing. Your recurring billing profile has been not created');
    }
    return $params;
  }

  /**
   *
   * @param array $params
   * @return type
   */
  private function doInstantPayment(&$params) {
    $packetData = $this->buildInstantRequest($params);
    $url = $this->_paymentProcessor['url_api'];
    $response = self::migsSendRequest($packetData, $url);

    // Check for response
    if ($response) {
      parse_str($response, $migs_response);

      if ($migs_response['vpc_TxnResponseCode'] != '0') {
        return self::error($migs_response['vpc_TxnResponseCode'], $this->migs_merchant_response_description($migs_response['vpc_TxnResponseCode']));
      }
    }
    else {
      return self::error(9001, 'There has been any error with your order. Please contact the website administrator for more information.');
    }

    $params['trxn_id'] = $migs_response['vpc_ReceiptNo'];
    return $params;
  }

  static function migsSendRequest($packet, $url) {
    $header = array(
      "MIME-Version: 1.0",
      "Content-type: application/x-www-form-urlencoded",
      "Contenttransfer-encoding: text"
    );
    $ch = curl_init();
    if (!$ch) {
      return self::error(9002, 'Could not initiate connection to payment gateway');
    }

    // set URL and other appropriate options 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    // uncomment for host with proxy server
    // curl_setopt ($ch, CURLOPT_PROXY, "http://proxyaddress:port"); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL'));
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $packet);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // send packet and receive response
    $result = curl_exec($ch);

    if (!$result) {
      return self::error(curl_errno($ch), curl_error($ch));
    }

    curl_close($ch);

    return $result;
  }

  /**
   * Convert dollars into cents
   * @staticvar type $divisors
   * @param type $amount
   * @param type $currency_code
   * @return type
   */
  private function currency_amount_to_decimal($amount, $currency_code) {
    static $divisors;

    // If the divisor for this currency hasn't been calculated yet...
    if (empty($divisors[$currency_code])) {
      // Load the currency and calculate its divisor as a power of 10.
      $currency = commerce_currency_load($currency_code);
      $divisors[$currency_code] = pow(10, $currency['decimals']);
    }

    return $amount / $divisors[$currency_code];
  }

  private function buildInstantRequest(&$params) {

    // build the HTTP request  
    $amount_no_points = $this->currency_amount_to_decimal($params['amount'], trim($params['amount'])) * 100;

    // Create the order info.
    $order_info = 'MIGS' . $this->_params['timestamp'] . $this->_params['sequence'];
    // Build the payment message.
    static $tx_ref = 1;

    $args = '';
    $args .= 'vpc_Version=1.0';
    $args .= '&vpc_Command=pay';
    $args .= '&vpc_MerchTxnRef=' . $order_info . '/' . $tx_ref;
    $args .= '&vpc_Merchant=' . $this->_paymentProcessor['user_name']; //Your Merchant ID which can be located in your MIGS merchant interface
    $args .= '&vpc_AccessCode=' . $this->_paymentProcessor['password']; //Your Access Code which can be located in your MIGS merchant interface
    $args .= '&vpc_OrderInfo=' . $order_info;
    $name_on_card = $params['billing_first_name'] . '%20' . $params['billing_middle_name'] . '%20' . $params['billing_last_name'];
    $args .= "&NameOnCard=" . $name_on_card;
    $args .= "&vpc_CardNum=" . $params['credit_card_number'];
    $args .= "&vpc_CardSecurityCode=" . $params['cvv2'];
    $args .= "&vpc_Amount=" . $amount_no_points;
    $args .= "&vpc_TxSourceSubType=SINGLE";
    if ($params['month'] < 10) {
      $mm = '0' . $params['month'];
    }
    else {
      $mm = $params['month'];
    }

    $yy = substr($params['year'], 2, strlen($params['year']));

    $mmyy = $yy . $mm;

    $args .= "&vpc_CardExp=" . $mmyy; //MMYY Format

    $state_id = $params['billing_state_province_id-5'];
    if (isset($state_id)) {
      $stateName = CRM_Core_PseudoConstant::stateProvinceAbbreviation($state_id);
    }

    $state = $stateName;

    $country_id = $params["billing_country_id-5"];
    if (isset($country_id)) {
      $countryName = CRM_Core_PseudoConstant::countryIsoCode($country_id);
    }
    $country = $countryName;

    $tpl = $this->templateDir . 'extraData.tpl';
    if (file_exists($tpl)) {

      $template = CRM_Core_Smarty::singleton();

      $template->assign('InvNum', $params['invoiceID']);
      $template->assign('Name', $nameOncard);
      $template->assign('Street', $params['billing_street_address-5']);
      $template->assign('City', $params['billing_city-5']);
      $template->assign('State', $state);
      $template->assign('Zip', $params['billing_postal_code-5']);
      $template->assign('Country', $country);
      if (isset($params['email-5'])) {
        $template->assign('Email', $params['email-5']);
      }

      $tplStr = $template->fetch($tpl);
      $args .= "&ExtData=" . $tplStr;
    }
    return $args;
  }

  private function buildRecurringRequest(&$params) {

    $firstPaymentDate = $this->_getParam('receive_date');
    if (!empty($firstPaymentDate)) {
      //allow for post dated payment if set in form
      $startDate = date_create($firstPaymentDate);
    }
    else {
      $startDate = date_create();
    }

    $startDate->setTimezone(new DateTimeZone(self::TIMEZONE));

    /*
     * BillingInterval : 
     * Indicates the day on which the billing interval will be applied.
      For a BillingPeriod of Week/Weekly or Biweekly, valid
      values are:
      Mon or 1
      Tue or 2
      Wed or 3
      Thu or 4
      Fri or 5
      Sat or 6
      Sun or 7
      For a BillingPeriod of Month/Monthly, valid values are:
      1 – 31 (the date of the month)
      Last (the last day of each month)
      For a BillingPeriod of Day/Daily, Year/Annually,
      Semiannually, Semimonth/Semimonthly, or Quarterly,
      set this parameter to 0. The system will calculate the
      BillingInterval using the StartDate in the contract.

     * 
     *  
     */

    /*
     * Because there is no way to send Installments on Migs so we will set
     * EndDate for Migs recurring Contract (profile) by calculating $billingPeriod,
     * number of $installments and for this we uncheck 
     * 'Support recurring intervals' form Contribution Page in account section 
     * 
     */

    $billingPeriod = $this->_getParam('frequency_unit');
    $instalments = $this->_getParam('installments') ? $this->_getParam('installments') : 1;
    //$params['frequency_interval']
    //$params['is_recur']

    if ($billingPeriod == 'day') {
      $billingPeriod = 'Day';
      $endDate = new DateTime('+' . $instalments . ' ' . $billingPeriod, new DateTimeZone(self::TIMEZONE));
      $billingInterval = 0;
    }
    elseif ($billingPeriod == 'week') {
      $billingPeriod = 'Week';
      $endDate = new DateTime('+' . $instalments . ' ' . $billingPeriod, new DateTimeZone(self::TIMEZONE));
      $billingInterval = $endDate->format('N'); //Numeric representation of the day of the week{1 (for Monday) through 7 (for Sunday)}
    }
    elseif ($billingPeriod == 'month') {
      $billingPeriod = 'Month';
      $endDate = new DateTime('+' . $instalments . ' ' . $billingPeriod, new DateTimeZone(self::TIMEZONE));
      $billingInterval = $endDate->format('j'); //Day of the month without leading zeros(1 to 31)
    }
    elseif ($billingPeriod == 'year') {
      $billingPeriod = 'Year';
      $endDate = new DateTime('+' . $instalments . ' ' . $billingPeriod, new DateTimeZone(self::TIMEZONE));
      $billingInterval = 0;
    }


    if ($this->_getParam('month') < 10) {
      $mm = '0' . $this->_getParam('month');
    }
    else {
      $mm = $this->_getParam('month');
    }

    $yy = substr($this->_getParam('year'), 2, strlen($this->_getParam('year')));

    $mmyy = $yy . $mm;

    $args = '';

    $args .= "&vpc_CardExp=" . $mmyy; //MMYY Format
    $args .= "&vpc_TxSourceSubType=RECURRING";

    $amount_no_points = $this->currency_amount_to_decimal($this->_getParam('amount'), trim($params['amount'])) * 100;

    $order_info = 'MIGS' . $this->_params['timestamp'] . $this->_params['sequence'];
    static $tx_ref = 1;
    $args .= '&vpc_Version=1.0';
    $args .= '&vpc_Command=pay';
    $args .= '&vpc_MerchTxnRef=' . $order_info . '/' . $tx_ref;
    $args .= '&vpc_AccessCode=' . $this->_paymentProcessor['password'];
    $args .= '&vpc_Merchant=' . $this->_paymentProcessor['user_name'];
    $args .= '&vpc_OrderInfo=' . $order_info;
    $args .= '&vpc_Amount=' . $amount_no_points;
    $args .= '&vpc_CardNum=' . $this->_getParam('credit_card_number');
    $args .= "&vpc_CardSecurityCode=" . $this->_getParam('cvv2');
    return $args;
  }

  /**
   * Map MIGs response code to string
   * @param type $response_code
   * @return type
   */
  private function migs_merchant_response_description($response_code) {
    $response_string = array(
      '0' => t('Transaction Successful'),
      '1' => t('Transaction could not be processed'),
      '2' => t('Bank Declined Transaction - contact issuing bank'),
      '3' => t('No Reply from Bank'),
      '4' => t('Expired Card'),
      '5' => t('Insufficient funds'),
      '6' => t('Error Communicating with Bank'),
      '7' => t('Payment Server System Error'),
      '8' => t('Transaction Type Not Supported'),
      '9' => t('Bank declined transaction (Do not contact Bank)'),
      'A' => t('Transaction Aborted'),
      'C' => t('Transaction Cancelled'),
      'D' => t('Deferred transaction has been received and is awaiting processing'),
      'F' => t('3D Secure Authentication failed'),
      'I' => t('Card Security Code verification failed'),
      'L' => t('Shopping Transaction Locked (Please try the transaction again later)'),
      'N' => t('Cardholder is not enrolled in Authentication scheme'),
      'P' => t('Transaction has been received by the Payment Adaptor and is being processed'),
      'R' => t('Transaction was not processed - Reached limit of retry attempts allowed'),
      'S' => t('Duplicate SessionID (OrderInfo)'),
      'T' => t('Address Verification Failed'),
      'U' => t('Card Security Code Failed'),
      'V' => t('Address Verification and Card Security Code Failed'),
    );
    if (isset($response_string[$response_code])) {
      $response = $response_string[$response_code];
    }
    else {
      $response = t('Unable to be determined');
    }
    return $response;
  }

}
