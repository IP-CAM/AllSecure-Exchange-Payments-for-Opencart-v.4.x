<?php

// Text
$_['text_title']                                                                                        = 'Credit Card';
$_['text_legend']											= 'Enter below additional information';
$_['text_basket']                                                                                       = 'Basket';
$_['text_checkout']											= 'Checkout';
$_['text_success']											= 'Success';
$_['text_shipping']											= 'Shipping';
$_['text_transaction_id']										= 'Transaction ID';
$_['text_transaction_details']										= 'Transaction Details';
$_['text_transaction_codes']										= 'Transaction Codes';
$_['text_card_type']                                                                                    = 'Card Type';
$_['text_payment_type']                                                                                 = 'Payment Type';
$_['text_transaction_time']										= 'Transaction Time';
$_['text_payment_currency']                                                                             = 'Currency';
$_['text_payment_amountpaid']                                                                           = 'Amount Paid';

$_['text_credit_card_information']										= 'Credit Card Information';
$_['text_card_holder']										= 'Card holder';
$_['text_card_number']										= 'Card Number';
$_['text_expiration_date']										= 'Expiration Date';
$_['text_cvv']										= 'CVV';

$_['button_pay']											= 'Pay now';

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

$_['entry_optional'] = '';

$_['error_no_order_data']              = 'No order data received';
$_['error_payment_failed']             = 'Payment is failed. ';
$_['error_invalid_transaction_token']  = 'Invalid transaction token.';
$_['error_unknown']  = 'Unknown error';
$_['error_callback_validation_failed']  = 'Callback validation failed.';
$_['error_merchant_transaction_validation_failed']  = 'Merchant transaction id validation failed.';

$_['error_required_field'] = 'This is a required field.';
$_['error_invalid_holder_name'] = 'Please enter a valid card holder name in this field.';
$_['error_invalid_card_number'] = 'Please enter a valid number in this field.';
$_['error_card_not_supported'] = 'This card type is not supported.';
$_['error_incorrect_card_expire_date'] = 'Incorrect credit card expiration date.';
$_['error_invalid_cvv'] = 'Please enter a valid number in this field.';

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
