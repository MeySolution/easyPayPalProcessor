<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Address;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\FundingInstrument;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Item;
use PayPal\Api\Details;
use PayPal\Api\ItemList;

/**
 * With this class you are able to process payments via PayPal.
 * It allows you to add as much items as you want with some simple functions.
 * @author Christopher Meyering <info@mey-solution.de>
 */
class meyPayment {
	private $credentialClientId;
	private $credentialClientSecret;
	private $context;
	private $redirectUrlError;
	private $redirectUrlSuccess;
	private $sessionVar;
	private $items;
	private $total = 0.00;
	private $shipping = 0.00;

	/**
	 * Getter for credentialClientId
	 *
	 * @return string the client Id
	 */
	public function getCredentialClientId() {
	    return $this->credentialClientId;
	}
	
	/**
	 * Setter for credentialClientId
	 *
	 * @param String $newcredentialClientId CredentialClientId
	 */
	public function setCredentialClientId($credentialClientId) {
	    $this->credentialClientId = $credentialClientId;
	
	    return $this;
	}

	/**
	 * Getter for credentialClientSecret
	 *
	 * @return string the paypal secret
	 */
	public function getCredentialClientSecret() {
	    return $this->credentialClientSecret;
	}
	
	/**
	 * Setter for credentialClientSecret
	 *
	 * @param String $newcredentialClientSecret CredentialClientSecret
	 */
	public function setCredentialClientSecret($credentialClientSecret) {
	    $this->credentialClientSecret = $credentialClientSecret;
	
	    return $this;
	}


	/**
	 * Getter for redirectUrlError
	 *
	 * @return string the url the user will be returned on cancelation
	 */
	public function getRedirectUrlError() {
	    return $this->redirectUrlError;
	}
	
	/**
	 * Setter for redirectUrlError
	 *
	 * @param String $newredirectUrlError RedirectUrlError
	 */
	public function setRedirectUrlError($redirectUrlError) {
	    $this->redirectUrlError = $redirectUrlError;
	
	    return $this;
	}


	/**
	 * Getter for redirectUrlSuccess
	 *
	 * @return string the url the user will be returned on success
	 */
	public function getRedirectUrlSuccess() {
	    return $this->redirectUrlSuccess;
	}
	
	/**
	 * Setter for redirectUrlSuccess
	 *
	 * @param String $newredirectUrlSuccess RedirectUrlSuccess
	 */
	public function setRedirectUrlSuccess($redirectUrlSuccess) {
	    $this->redirectUrlSuccess = $redirectUrlSuccess;
	
	    return $this;
	}


	/**
	 * Getter for sessionVar
	 *
	 * @return string the name of the session variable we need to use
	 */
	public function getSessionVar() {
	    return $this->sessionVar;
	}
	
	/**
	 * Setter for sessionVar
	 *
	 * @param String $newsessionVar SessionVar
	 */
	public function setSessionVar($sessionVar) {
	    $this->sessionVar = $sessionVar;
	
	    return $this;
	}


	/**
	 * Getter for shipping
	 *
	 * @return float shipping cost
	 */
	public function getShipping() {
	    return number_format($this->shipping, 2);
	}
	
	/**
	 * Setter for shipping
	 *
	 * @param Float $newshipping Shipping
	 */
	public function setShipping($shipping) {
	    $this->shipping = $shipping;
	    $this->total += (float)$shipping;
	
	    return $this;
	}

	/**
	 * Adds an item to the stack
	 * 
	 * @param string $description the name of the item
	 * @param float $price the price
	 */
	public function addItem($description, $price) {
		$price = number_format($price, 2);
		$i = new Item();
		$i->setQuantity(1);
		$i->setName($description);
		$i->setPrice($price);
		$i->setCurrency('EUR');

		$this->items[] = $i;
		$this->total += (float)$price;
	}

	/**
	 * Constructor
	 * @param string $clientId the ClientId of your paypal account
	 * @param String $clientSecret thie ClientSecret of your paypal Account
	 */
	public function __construct($clientId, $clientSecret) {
		$this->setCredentialClientId($clientId);
		$this->setCredentialClientSecret($clientSecret);

		$this->prepareAPIContext($clientId, $clientSecret);
	}

	/**
	 * Prepares the paypal Rest API Context#
	 * 
	 * @param string $id the paypal client ID
	 * @param string $secret the paypal client secret
	 */
	private function prepareAPIContext($id, $secret) {
		$tokenObj = new OAuthTokenCredential($id, $secret);		
		$apiContext = new ApiContext($tokenObj);

		$this->context = $apiContext;
	} 

	/**
	 * Sends the payment to paypal
	 * 
	 * @param string $description the description text paypal will show to the user
	 * @return string the payment url
	 */
	public function makePayment($description) {
		# create the payer
		$payer = new Payer();
		$payer->setPayment_method("paypal");

		# set the amount
		$amount = new Amount();
		$amount->setCurrency("EUR");		

		# details we display to the user
		$amountDetails = new Details();
		$amountDetails->setTax(0.00);
		$amountDetails->setShipping($this->getShipping());
		$amountDetails->setSubtotal(number_format(($this->total - $this->shipping), 2));		

		$amount->setDetails($amountDetails);

		# build the item list
		$itemList = new ItemList();
		$itemList->setItems($this->items);

		$amount->setTotal($this->total);



		# prepare an transaction
		$transaction = new Transaction();
		$transaction->setAmount($amount);
		$transaction->setDescription($description);
		$transaction->setItem_list($itemList);

		# setup the redirect URLs
		$redirect = new RedirectUrls();
		$redirect->setReturn_url($this->redirectUrlSuccess);
		$redirect->setCancel_url($this->redirectUrlError);

		# build the payment together
		$payment = new Payment();
		$payment->setIntent('sale');
		$payment->setPayer($payer);
		$payment->setRedirect_urls($redirect);
		$payment->setTransactions(array($transaction));

		try {
			$payment->create($this->context);
		} catch (\PPConnectionException $ex) {
			echo "Exception: " . $ex->getMessage() . PHP_EOL;
			var_dump($ex->getData());	
			exit(1);
		}

		foreach($payment->getLinks() as $link) {
			if($link->getRel() == 'approval_url') {
				$redirectUrl = $link->getHref();
			}
		}

		# safe the payment id to verify and proceed the payment
		$_SESSION[$this->sessionVar] = $payment->getId();

		return $redirectUrl;
	}

	/**
	 * Executes the Payment (At this point the funds will be transfered from payer to merchant!)
	 * @param int $payerId the Id of the payer
	 */
	public function executePayment($payerId) {
		$paymentId = $_SESSION[$this->sessionVar];
		$payment = Payment::get($paymentId, $this->context);
		
		$execution = new PaymentExecution();
		$execution->setPayer_id($payerId);

		$payment->execute($execution, $this->context);
	}
}