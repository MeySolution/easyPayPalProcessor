<?php

# require the bootstrap file
require_once('../lib/meyBootstrap.php');

# Initialize the payment-class
$meyPayment = new MeyPayment(
    'clientId',
    'clientSecret'
);
# set the $_SESSION key to store the payment id. It will be needed for finalizing the payment!
$meyPayment->setSessionVar('meyPaymentId');

# if the payment was processed properly we can finalize it!
if(isset($_GET['paypalSuccess'])) {
	# this sends a last request to the paypal API Servers to transfer the money.
	$meyPayment->executePayment($_GET['PayerID']);
	die("Thanks for buying!");
}


/**
* PayPal uses redirect URLs for payment-transactions
* Success: The user will be send to this site if the payment
* Error: Something went wrong. (Most of the times the user aboreded the payment to get here)
*/

$meyPayment->setRedirectUrlSuccess('differentPrices.php?paypalSuccess=true');
$meyPayment->setRedirectUrlError('error.php');

/**
 * To add one or more items we just set its name and the price
 */
$meyPayment->addItem('The items name', 50.00);
$meyPayment->addItem('Item 2', 50.00);
$meyPayment->addItem('Item 3', 52.00);

# The class prepares the payment and transfers all data to PayPal, we just have to send the user there!
$paymentURL = $meyPayment->makePayment('Name of our product');
header("Location: $paymentURL");
exit;