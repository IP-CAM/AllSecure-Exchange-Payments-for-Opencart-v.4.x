<?php
// Heading
$_['heading_title'] = 'AllSecure Exchange';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified AllSecure Exchange account details!';
$_['text_edit']          = 'Edit AllSecure Exchange';
$_['text_live']			 = 'Live Mode';
$_['text_sandbox']		 = 'Test Mode';
$_['text_allsecureexchange']		 = '<a target="_BLANK" href="https://www.allsecure.eu/">AllSecure</a>';
$_['text_paymentjs']			 = 'Payment.js Javascript Integration';
$_['text_fullredirect']		 = 'Full-Page Redirect';
$_['text_debit']		 = 'Debit';
$_['text_preauthorize']		 = 'Preauthorize';
$_['text_capture']		 = 'Capture';
$_['text_void']		 = 'Void';
$_['text_refund']		 = 'Refund';
$_['text_VISA']		 = 'VISA';
$_['text_MASTERCARD']	 = 'MASTERCARD';
$_['text_MAESTRO']	 = 'MAESTRO';
$_['text_AMEX']		 = 'AMEX';
$_['text_DINERS']	 = 'DINERS';
$_['text_JCB']		 = 'JCB';
$_['text_DINA']		 = 'DINA';
$_['text_DISCOVER']	 = 'DISCOVER';
$_['text_transaction_id']										= 'Transaction ID';
$_['text_transaction_details']										= 'Transaction Details';
$_['text_transaction_codes']										= 'Transaction Codes';
$_['text_card_type']                                                                                    = 'Card Type';
$_['text_payment_type']                                                                                 = 'Payment Type';
$_['text_payment_status']                                                                                 = 'Payment Status';
$_['text_transaction_time']										= 'Transaction Time';

// Entry
$_['entry_title']		 = 'Payment Method Title';
$_['entry_title_placeholder']		 = 'Payment Method Title';
$_['entry_mode']		 = 'Operation Mode';
$_['entry_logging']		 = 'Logging';
$_['entry_order_status'] = 'Order Status';
$_['entry_geo_zone']	 = 'Geo Zone';
$_['entry_status']		 = 'Status';
$_['entry_sort_order']	 = 'Sort Order';
$_['entry_checkout_mode']	 = 'Checkout Mode';
$_['entry_api_user']	     = 'API User';
$_['entry_api_password']     = 'API Password';
$_['entry_api_key']	     = 'API Key';
$_['entry_api_secret']       = 'Shared Secret';
$_['entry_integrator_key']   = 'Integration Key';
$_['entry_transaction_type']   = 'Transaction Type';
$_['entry_card_supported']   = 'Accepted Cards';
$_['entry_transaction_email']   = 'Enable transaction details in the confirmation email';
$_['entry_transaction_confirmation_page']   = 'Enable transaction details in the confirmation page';

// Error
$_['error_permission']	 = 'Warning: You do not have permission to modify payment AllSecure Exchange!';
$_['error_api_user']	 = 'API User Required!';
$_['error_api_password'] = 'API Password Required!';
$_['error_api_key']	 = 'API key Required!';
$_['error_api_secret']	 = 'Shared Secret Required!';
$_['error_integrator_key'] = 'Integration Key Required!';
$_['error_card_supported'] = 'At least one card selection is Required!';

$_['desc_about'] = 'You want to securely and quickly accept payments 
on your online shop or mobile app by using one of the leading gateway technologies? <br/>
Looking for a PCI compliant payment solution that includes proven fraud prevention mechanism? 
Wanting to seamlessly integrate the most common national and international payment methods? <br/>
We will support you on selecting suitable packages and help you to make the right decisions
 in accepting and managing your payment transactions anytime, anywhere.';

$_['desc_title']		 = 'Method name shown on checkout page, if empty default will be used: Credit Card';

$_['desc_checkout_mode'] = '<strong>Payment.js Javascript Integration</strong><br/>
With the payment.js integration you can securely accept card payments and 
integrate card number and CVV collection directly into your shop website 
without the need for redirecting to a separate payment form. 
The payment.js library renders 2 separate iFrames for card number and CVV/CVC in your checkout page. 
This reduces your PCI-DSS scope to as low as it can get (PCI-DSS SAQ-A).<br/>
<strong>Full-Page Redirect</strong><br/>
Customer are redirected to the Payment Service Provider (PSP) page. 
Here, the customer fills in his or her payment details, and after paying, 
is redirected back to your website to complete the checkout process.';

$_['desc_api_user']		 = 'Please enter your Exchange API User. This is needed in order to take the payment.';
$_['desc_api_password']		 = 'Please enter your Exchange API Password. This is needed in order to take the payment.';
$_['desc_api_key']		 = 'Please enter your Exchange API Key. This is needed in order to take the payment.';
$_['desc_api_secret']		 = 'Please enter your Exchange API Shared Secret. This is needed in order to take the payment.';
$_['desc_integrator_key']	 = 'Public Integration Key required only if payment.js integration required.';
$_['desc_transaction_type']	 = '<strong>Debit: </strong>Debits the end customer with the given amount.<br/>
                                    <strong>Preauthorize: </strong>Reserves the payment amount on the customer payment instrument. 
                                    Preauthorization must be captured afterwards to conclude the transaction.';
$_['desc_card_supported']	 = 'Select the cards which you would like to accept.';
$_['desc_transaction_email']	 = 'When enabled, plugin will add transaction details in the order confirmation email.';
$_['desc_transaction_confirmation_page']	 = 'When enabled, plugin will add transaction details in the order confirmation page.';

$_['status_pending_debt'] = 'Allsecure Exchange payment request is created successfully and but payment debt status received as pending.';
$_['status_pending_preauthorize'] = 'Allsecure Exchange payment request is created successfully and but payment preauthorize status received as pending.';
$_['status_finished_debt'] = 'Allsecure Exchange payment is successfully debited. ';
$_['status_finished_preauthorize'] = 'Allsecure payment is successfully reserved for manual capture. ';
$_['status_failed'] = 'Your payment is failed with Allsecure Exchange Payment System.';
$_['status_cancel'] = 'Your order is cancelled.';
$_['status_error'] = 'Error from gateway.';
$_['status_webhook_wait'] = 'Order is just created and waiting for the payment confirmation. Order status will be updated in the webhook.';
$_['status_finished_capture'] = 'Allsecure Exchange payment is successfully captured. ';
$_['status_finished_void'] = 'Allsecure Exchange payment is successfully voided. ';
$_['status_finished_refund'] = 'Allsecure Exchange payment is successfully refunded. ';

$_['error_no_order_data']              = 'No order data received';
$_['error_payment_failed']             = 'Payment is failed. ';
$_['error_invalid_transaction_token']  = 'Invalid transaction token.';
$_['error_unknown']  = 'Unknown error';
$_['error_callback_validation_failed']  = 'Callback validation failed.';
$_['error_merchant_transaction_validation_failed']  = 'Merchant transaction id validation failed.';

$_['error_1000'] = 'CONFIG ERROR. Some fundamental error in your request';
$_['error_1001'] = 'CONFIG ERROR. The upstream system responded with an unknown response';
$_['error_1002'] = 'CONFIG ERROR. Request data are malformed or missing';
$_['error_1003'] = 'CONFIG ERROR. Transaction could not be processed';
$_['error_1004'] = 'CONFIG ERROR. The request signature you provided was wrong';
$_['error_1005'] = 'CONFIG ERROR. The XML you provided was malformed or invalid';
$_['error_1006'] = 'CONFIG ERROR. Preconditions failed, e.g. capture on a failed authorize';
$_['error_1007'] = 'CONFIG ERROR. Something is wrong your configuration, please contact your integration engineer';
$_['error_1008'] = 'CONFIG ERROR. Unexpected system error';
$_['error_9999'] = 'CONFIG ERROR. We received an error which is not (yet) mapped to a better error code';
$_['error_2001'] = 'Account closed. The customer cancelled permission for his payment instrument externally';
$_['error_2002'] = 'User cancelled. Transaction was cancelled by customer';
$_['error_2003'] = 'Transaction declined. Please try again later or change the card';
$_['error_2004'] = 'Quota regulation. Card limit reached';
$_['error_2005'] = 'Transaction expired. Customer took to long to submit his payment info';
$_['error_2006'] = 'Insufficient funds. Card limit reached';
$_['error_2007'] = 'Incorrect payment info. Double check and try again';
$_['error_2008'] = 'Invalid card. Try with some other card';
$_['error_2009'] = 'Expired card. Try with some other card';
$_['error_2010'] = 'Invalid card. Call your bank immediately';
$_['error_2011'] = 'Unsupported card. Try with some other card';
$_['error_2012'] = 'Transaction cancelled';
$_['error_2013'] = 'Transaction declined. Please try again later or call your bank';
$_['error_2014'] = 'Transaction declined. Please try again later or call your bank';
$_['error_2015'] = 'Transaction declined. Please try again later or call your bank';
$_['error_2016'] = 'Transaction declined. Please try again later or call your bank';
$_['error_2017'] = 'Invalid IBAN. Double check and try again';
$_['error_2018'] = 'Invalid BIC. Double check and try again';
$_['error_2019'] = 'Customer data invalid. Double check and try again';
$_['error_2020'] = 'CVV required. Double check and try again';
$_['error_2021'] = '3D-Secure Verification failed. Please call your bank or try with a non 3-D Secure card';
$_['error_3001'] = 'COMMUNICATION PROBLEM. Timeout. Try again after a short pause';
$_['error_3002'] = 'COMMUNICATION PROBLEM. Transaction not allowed';
$_['error_3003'] = 'COMMUNICATION PROBLEM. System temporary unavailable. Try again after a short pause';
$_['error_3004'] = 'Duplicate transaction ID';
$_['error_3005'] = 'COMMUNICATION PROBLEM. Try again after a short pause';
$_['error_7001'] = 'Schedule request is invalid';
$_['error_7002'] = 'Schedule request failed';
$_['error_7005'] = 'Schedule action is not valid';
$_['error_7010'] = 'RegistrationId is required';
$_['error_7020'] = 'RegistrationId is not valid';
$_['error_7030'] = 'The registrationId must point to a "register", "debit+register" or "preuth+register"';
$_['error_7035'] = 'Initial transaction is not a "register", "debit+register" or "preuth+register"';
$_['error_7036'] = 'The period between the initial and second transaction must be greater than 24 hours';
$_['error_7040'] = 'The scheduleId is not valid or does not match to the connector';
$_['error_7050'] = 'The startDateTime is invalid or older than 24 hours';
$_['error_7060'] = 'The continueDateTime is invalid or older than 24 hours';
$_['error_7070'] = 'The status of the schedule is not valid for the requested operation';

//More texts
$_['tab_general_settings'] = 'General Settings';
$_['tab_installment_settings'] = 'Installment Settings';
$_['txt_enable_installment_payments'] = 'Enable Installment Payments';
$_['txt_enter_installment_bin_info'] = 'Enter Installment Eligible BIN Information';
$_['txt_installment_bin'] = 'BIN';
$_['button_remove'] = 'Remove';
$_['txt_allowed_installments'] = 'Allowed Installments';
$_['txt_tip_comma_separated'] = 'Enter comma separated eg: 3,6,9,12';
$_['button_addnew'] = 'Add New';
$_['txt_allowed_max'] = 'Allowed to add maximum';