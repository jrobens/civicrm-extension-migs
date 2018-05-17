<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.2                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2012                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License along with this program; if not, contact CiviCRM LLC       |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 * Description of MigsEventRegistration
 *
 * @author brijesh
 */
require_once 'CiviTest/BaseMigsTestCase.php';

class WebTest_Event_MigsEventRegistrationTest extends BaseMigsTestCase {

    function testAddEventAndDoPayment() {
        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");
        $this->_addEvent();
    }

    function _addEvent() {
        $this->open($this->sboxPath . "civicrm/event/add?reset=1&action=add");
        $this->waitForPageToLoad("30000");
        $this->waitForElementPresent("_qf_EventInfo_upload");

        $this->selectAndWait('template_id', 'value=6');
        $this->select('event_type_id', 'value=3');
        $this->select('default_role_id', 'value=2');
        $this->select('participant_listing_id', 'value=1');
        $title = substr(sha1(rand()), 0, 6);
        $this->type('title', substr(sha1(rand()), 0, 6));
        $this->type('summary', "This is test event $title summary.");

        $this->click('_qf_EventInfo_upload');
        $this->waitForPageToLoad("30000");
        $this->waitForElementPresent("_qf_Location_upload");

        $elements = $this->parseURL();
        $eventId = $elements['queryString']['id'];

        $table = "xpath=//table[@class='form-layout-compressed']/tbody";
        $locationRadio = $table . "//tr[@id='optionType']//td[3]/strong/input[@type='radio']";
        $this->waitForElementPresent($locationRadio);
        $this->click($locationRadio);

        $this->waitForElementPresent('_qf_Location_upload');
        $this->click('_qf_Location_upload');
        $this->waitForPageToLoad('300000');

        $feesTab = "xpath=//li[@id='tab_fee']/a[text()='Fees']";
        $this->click($feesTab);

        $this->waitForElementPresent('CIVICRM_QFID_1_2');
        $this->click('CIVICRM_QFID_1_2');

        $paymentProcessorTable = "xpath=//table[@id='paymentProcessor']/tbody";

        $paymentProcessorChk = $paymentProcessorTable . "//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='Migs']/../input[@type='checkbox']";

        if (!$this->isElementPresent($paymentProcessorChk)) {
            $this->_addMigsPaymentProcessor();
            if (!empty($eventId)) {
                $this->open($this->sboxPath . " civicrm/event/manage/settings?reset=1&action=update&id=$eventId");
                $this->waitForPageToLoad('300000');
            }
        }

        $this->waitForElementPresent($paymentProcessorChk);

        $this->check($paymentProcessorChk);

        $this->select('contribution_type_id', 'value=1');

        $this->type('label_1', 'First Class');
        $this->type('value_1', '100');

        $this->type('label_2', 'Second Class');
        $this->type('value_2', '50');

        $this->type('label_3', 'Third Class');
        $this->type('value_3', '25');

        $this->click('_qf_Fee_upload');
        $this->waitForPageToLoad('300000');

        $registrationTab = "xpath=//li[@id='tab_registration']/a[text()='Online Registration']";
        $this->click($registrationTab);
        $this->waitForElementPresent('is_online_registration');

        $this->check('is_online_registration');
        $this->uncheck('is_multiple_registrations');

        $this->click('_qf_Registration_upload_done');
        $this->waitForPageToLoad('30000');

        //Logout
        $this->open($this->sboxPath . "civicrm/logout?reset=1");
        $this->_doOfflineEventPayment($eventId);
    }

    function testDoOfflineEventPayment() {
        //Logout
        $this->open($this->sboxPath . "civicrm/logout?reset=1");
        $this->waitForPageToLoad('30000');
        $eventId = 8;
        $this->_doOfflineEventPayment($eventId);
    }

    function _doOfflineEventPayment($eventId) {
        $this->open($this->sboxPath . "civicrm/event/register?reset=1&id=$eventId");
        $this->waitForPageToLoad('30000');

        $this->waitForElementPresent('_qf_Register_upload');
        $priceRd = "xpath=//div//span[@class='price-set-option-content']/input[@type='radio']";

        $this->click($priceRd);
        if ($this->isElementPresent('email-Primary')) {
            $email = substr(sha1(rand()), 0, 6) . '@webaccess.com';
            $this->type('email-Primary', $email);
        }

        $this->_setCCAndBillingDetail();
        $this->click('_qf_Register_upload');
        $this->waitForPageToLoad('30000');

        $alreadyRegistered = 'Oops. It looks like you are already registered for this event.';
        if ($this->isTextPresent($alreadyRegistered)) {
            $this->assertTrue(TRUE, $alreadyRegistered);
        } else {
            //On Confirm
            $this->waitForElementPresent('_qf_Confirm_next');
            $this->click('_qf_Confirm_next');
            $msg = "Your registration has been processed successfully. Please print this page for your records.";
            $this->waitForTextPresent($msg);

            if (!$this->isTextPresent($msg)) {
                $this->assertTrue(FALSE, 'There is some problem in payment process. Your registration has not been approved.');
            } else {
                $this->assertTrue(TRUE);
            }
        }
    }

}

?>
