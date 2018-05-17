<?php

/**
 * @package CRM
 * @author John Robens jrobens@interlated.com.au
 * $Id: Migs 42195 2013-08-03 11:51:58IST jrobens $
 */
class MigsResponse {

    static private $_singleton = NULL;
    
    //Do Direct Pyament Properties
    public $InvNum = array();
    public $AuthCode, $GetAVSResult, $GetCVResult, $GetCommercialCard, $HostCode;
    public $ProcessedAsCreditCard, $Message, $RespMSG, $Result, $TokenNumber;
    public $PNRef = null;
    // Recurring Billing Properties
    public  $CcInfoKey, $Code, $ContractKey, $CustomerKey, $Error,
            $FirstTransactionPNRef,  $FirstTransactionResult;

    private function __construct($prams) {
        if (!empty($prams)) {
            foreach ($prams as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }

    static function singleton($prams) {
        if (self::$_singleton === null) {
            self::$_singleton = new self($prams);
        }
        return self::$_singleton;
    }

}

?>
