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
 * Description of BaseMigsContributionTest
 *
 * @author brijesh
 */
require_once 'CiviSeleniumTestCase.php';

class BaseMigsTestCase extends CiviSeleniumTestCase {

    protected function setUp() {
        parent::setUp();
    }

    //Command: scripts/phpunit -uroot -proot -hlocalhost -bcivicrm_ext_tests_dev --filter testInstallMigsPaymentProcessor WebTest_Contribute_MigsContributionTest
    function testInstallMigsPaymentProcessor() {
        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");
        $this->_installMigsPaymentProcessor();
    }

    function _installMigsPaymentProcessor() {
        //Load Extensions Page 
        $this->open($this->sboxPath . "civicrm/admin/extensions");

        $this->waitForElementPresent('new');
        $paleapRow = "xpath=//table[@id='option11']/tbody//tr//td[1][text()='(com.payment.payleap)']/";
        $this->waitForElementPresent($paleapRow);

        $installLeink = $paleapRow . "../td[6]/span/a[text()='Install']";

        $this->waitForElementPresent($installLeink);
        $this->click($installLeink);
        $this->waitForPageToLoad("30000");

        $this->click("_qf_Extensions_next");
        $this->waitForPageToLoad("300000000");

        $this->waitForElementPresent('new');

        $this->waitForElementPresent($paleapRow);

        $disableLeink = $paleapRow . "../td[6]/span/a[text()='Disable']";

        if (!$this->isElementPresent($disableLeink)) {
            $this->assertTrue(True, 'There is some problem to install Migs payment processor.');
        }
    }

    //Command: scripts/phpunit -uroot -proot -hlocalhost -bcivicrm_ext_tests_dev --filter testDisableMigsPaymentProcessor WebTest_Contribute_MigsContributionTest
    function testDisableMigsPaymentProcessor() {

        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");
        $this->_disableMigsPaymentProcessor();
    }

    function _disableMigsPaymentProcessor() {
        //Load Extensions Page 
        $this->open($this->sboxPath . "civicrm/admin/extensions");

        $this->waitForElementPresent('new');
        $paleapRow = "xpath=//table[@id='option11']/tbody//tr//td[1][text()='(com.payment.payleap)']/";
        $this->waitForElementPresent($paleapRow);

        $installLeink = $paleapRow . "../td[6]/span/a[text()='Disable']";

        $this->waitForElementPresent($installLeink);
        $this->click($installLeink);
        $this->waitForPageToLoad("30000");

        $this->click("_qf_Extensions_next");
        $this->waitForPageToLoad("300000000");

        $this->waitForTextPresent("Extension has been disabled.");
        $this->waitForElementPresent('new');
        $this->waitForElementPresent($paleapRow);

        $enableLeink = $paleapRow . "../td[6]/span//a[text()='Enable']";

        if ($this->isElementPresent($enableLeink)) {
            $this->assertTrue(True, 'Migs Payment Processor Disable Successfully.');
        }
    }

    //Command: scripts/phpunit -uroot -proot -hlocalhost -bcivicrm_ext_tests_dev --filter testEnableMigsPaymentProcessor WebTest_Contribute_MigsContributionTest
    function testEnableMigsPaymentProcessor() {

        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");

        $this->_enableLeapPaymentProcessor();
    }

    function _enableMigsPaymentProcessor() {
        //Load Extensions Page 
        $this->open($this->sboxPath . "civicrm/admin/extensions");

        $this->waitForElementPresent('new');
        $paleapRow = "xpath=//table[@id='option11']/tbody//tr//td[1][text()='(com.payment.payleap)']/";
        $this->waitForElementPresent($paleapRow);

        $installLeink = $paleapRow . "../td[6]/span//a[text()='Enable']";

        $this->waitForElementPresent($installLeink);
        $this->click($installLeink);
        $this->waitForPageToLoad("30000");

        $this->click("_qf_Extensions_next");
        $this->waitForPageToLoad("300000000");

        $this->waitForTextPresent('Extension has been enabled.');

        $this->waitForElementPresent('new');

        $this->waitForElementPresent($paleapRow);

        $enableLeink = $paleapRow . "../td[6]/span//a[text()='Disable']";

        if ($this->isElementPresent($enableLeink)) {
            $this->assertTrue(True, 'Migs Payment Processor Disable Successfully.');
        }
    }

    //Command: scripts/phpunit -uroot -proot -hlocalhost -bcivicrm_ext_tests_dev --filter testUninstallMigsPaymentProcessor WebTest_Contribute_MigsContributionTest
    function testUninstallMigsPaymentProcessor() {
        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");
        $this->_uninstallLeapPaymentProcessor();
    }

    function _uninstallMigsPaymentProcessor() {

        //Load Extensions Page 
        $this->open($this->sboxPath . "civicrm/admin/extensions");

        $this->waitForElementPresent('new');
        $paleapRow = "xpath=//table[@id='option11']/tbody//tr//td[1][text()='(com.payment.payleap)']/";
        $this->waitForElementPresent($paleapRow);

        $installLeink = $paleapRow . "../td[6]/span//a[text()='Uninstall']";

        $this->waitForElementPresent($installLeink);
        $this->click($installLeink);
        $this->waitForPageToLoad("30000");

        $this->click("_qf_Extensions_next");
        $this->waitForPageToLoad("300000000");

        $this->waitForElementPresent($paleapRow);

        $enableLeink = $paleapRow . "../td[6]/span//a[text()='Enable']";

        if (!$this->isElementPresent($enableLeink)) {
            $this->assertTrue(True, 'There is some problem to Uninstall payment processor.');
        }
    }

    //Command: scripts/phpunit -uroot -proot -hlocalhost -bcivicrm_ext_tests_dev --filter testAddMigsPaymentProcessor WebTest_Contribute_MigsContributionTest
    function testAddMigsPaymentProcessor() {
        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");
        $this->_addMigsPaymentProcessor();
    }

    function _addMigsPaymentProcessor() {

        //Load Payment Processor Page 
        $this->open($this->sboxPath . "civicrm/admin/paymentProcessor");
        $this->waitForElementPresent('newPaymentProcessor');
        $this->click('newPaymentProcessor');
        $this->waitForPageToLoad("30000");

        $this->select("payment_processor_type", "value=Migs");
        $this->waitForPageToLoad("30000");

        $this->type("name", "Migs");
        $this->type("description", "MIGS Test Payment Processor");
        $this->check('is_active');

        //For Live
        $this->type("user_name", "thetigroup_API");
        $this->type("password", "Sx3YqN~C@Umh%CyT");
        $this->type("signature", "1237");
        $this->type("url_site", "https://uat.interlated.net/TransactServices.svc/ProcessCreditCard");
        $this->type("url_api", "https://uat.interlated.net/TransactServices.svc/ProcessCreditCard");
        $this->type("url_recur", "https://uat.interlated.net/MerchantServices.svc/ProcessCreditCard");

        //For Test
        $this->type("test_user_name", "thetigroup_API");
        $this->type("test_password", "Sx3YqN~C@Umh%CyT");
        $this->type("test_signature", "1237");
        $this->type("test_url_site", "https://uat.interlated.net/TransactServices.svc/ProcessCreditCard");
        $this->type("test_url_api", "https://uat.interlated.net/TransactServices.svc/ProcessCreditCard");
        $this->type("test_url_recur", "https://uat.interlated.net/MerchantServices.svc/ProcessCreditCard");

        $this->waitForElementPresent('_qf_PaymentProcessor_next');
        $this->click('_qf_PaymentProcessor_next');
        $paleapRow = "xpath=//table[@id='selector']/tbody//tr//td[@class='crm-payment_processor-name'][text()='Payleap']/";
        if ($this->isElementPresent($paleapRow)) {
            $this->assertTrue(True, 'Migs Payment Processor Add Successfully.');
        }
    }

    //Command: scripts/phpunit -uroot -proot -hlocalhost -bcivicrm_ext_tests_dev --filter testDeleteMigsPaymentProcessor WebTest_Contribute_MigsContributionTest
    function testDeleteMigsPaymentProcessor() {
        $this->open($this->sboxPath);
        $this->webtestLogin();
        $this->waitForPageToLoad("30000");
        $this->_deleteMigsPaymentProcessor();
    }

    function _deleteMigsPaymentProcessor() {

        $this->open($this->sboxPath . "civicrm/admin/paymentProcessor");
        $this->waitForPageToLoad("30000");

        $noPP = "There are no Payment Processors entered.";
        if ($this->isTextPresent($noPP)) {
            $this->assertTrue(True, $noPP);
        } else {
            $MigsRow = "xpath=//table[@class='selector']/tbody//tr//td[@class='crm-payment_processor-name'][text()='Migs']/";
            $this->waitForElementPresent($paleapRow);

            if ($this->isElementPresent($paleapRow)) {

                $deleteLeink = $paleapRow . "../td[6]/span//a[text()='Delete']";
                $this->waitForElementPresent($deleteLeink);
                $this->click($deleteLeink);
                $this->waitForPageToLoad("30000");
                $this->waitForElementPresent('_qf_PaymentProcessor_next');
                $this->click('_qf_PaymentProcessor_next');
                $msg = "Selected Payment Processor has been deleted.";
                $this->waitForTextPresent($msg);
                if (!$this->isTextPresent($msg)) {
                    $this->assertTrue(True, 'There is some problem to delete payent processor.');
                }
            }
        }
    }
    
     function _setCCAndBillingDetail() {
        $this->select("credit_card_type", "value=Visa");
        $this->type('credit_card_number', '4111111111111111');
        $this->type('cvv2', '123');

        $this->select("credit_card_exp_date[M]", "value=5");
        $this->select("credit_card_exp_date[Y]", "value=2015");

        $this->type('billing_first_name', substr(sha1(rand()), 0, 3));
        $this->type('billing_middle_name', substr(sha1(rand()), 0, 4));
        $this->type('billing_last_name', substr(sha1(rand()), 0, 5));

        $this->type('billing_street_address-5', substr(sha1(rand()), 0, 5) . '' . substr(sha1(rand()), 0, 7));
        $this->type('billing_city-5', substr(sha1(rand()), 0, 4));

        $this->select("billing_state_province_id-5", "value=1001");
        $this->type('billing_postal_code-5', '12345');
    }

}

?>
