Support:
--------

Drupal version : 7.22.x
CiviCRM Version : 4.3.5

This extension is used for Donation section of CiviCRM.

Configuration Steps:
--------------------
1) Login into CiviCRM admin panel.
2) Go to Administers > System Settings > Manage Extensions
3) Enable MIGS (net.interlated.payment.migs) extension
4) Go to Administer > System Settings > Payment Processors
5) Click on 'Add Payment Processor' button. New payment processor page will open.
6) Choose 'Payment Processor Type' as 'MIGS (net.interlated.payment.migs)'
7) Enter Name(i.e MIGS) and Description in the specific fields.
8) Click on the corresponding checkboxes for making the processor active (enable) and for setting default.

9) Give Processor Details for Live Payments,
	Merchant ID= your merchant id
	Access Code = your access code
	Secure Hash = your secure hash
	Purchase Order prefix = eg TEST
	Payment Url = https://migs.mastercard.com.au/vpcdps
	
10) Give Processor Details for Test Payments
	Test Merchant ID= test merchant id
	Access Code = test access code
	Secure Hash = test secure hash
	Purchase Order prefix = eg TEST
	Payment Url = https://migs.mastercard.com.au/vpcdps

11) Click Save button.

12) Go to Administer > CiviPayment > Manage Payment Pages
13) Click on Configure > Payment Amounts
14) Check Payment Processor has been chosen
15) Click Save button 

16) For More: 
  DOC: https://merchantservices.tnspayments.com/commweb commweb / commweb55
- ADM: https://migs.mastercard.com.au/ma/CBA 
  Test Merchant ID: TESTINTPTYCOM01
  MasterCard 5123456789012346 05/17 Any 3 digits 
  Test: Visa 4987654321098769 05/17 Any 3 digits 
  Amex 345678901234564 05/17 Any 4 digits 
- CommWeb TEST a/c
  Test:   Merchant ID TESTINTPTYCOM01 access-code 801C8A4A secure-hash E37865BD36B12F55082924295CAB9FA0  
  https://migs.mastercard.com.au/vpcdps
	
https://www.interlated.net/civicrm/contribute/transact?reset=1&action=preview&id=1
Test: Visa 4987654321098769 05/17 Any 3 digits