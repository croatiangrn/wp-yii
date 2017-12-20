<?php
/**
 * @author: Marko Mikulic
 */

namespace App\payments;


use rnd\cart\Cart;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentOptions;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use rnd\payments\PayPalCreds;
use rnd\web\Session;

abstract class Checkout
{
	protected $currency = 'EUR';

	/**
	 * @return mixed
	 */
	abstract public function pay();

	private function payDefault() {
		$cart = new Cart();
		$payPalCreds = new PayPalCreds();
		$paypal = $payPalCreds->getCredentials();
		$payer  = new Payer();
		$payer->setPaymentMethod( 'paypal' );

		$products = $cart->getItems(Cart::ITEM_PRODUCT);
		$itemsList = [];
		$totalPrice = floatval(0);
		foreach ( $products as $product ) {
			$item = new Item();

			$formattedPrice = number_format(floatval($product->getPriceEur()), 2);


			$item->setName( $product->getLabel() )
			     ->setCurrency( $this->currency )
			     ->setQuantity( $product->getQuantity() )
			     ->setPrice( $formattedPrice );
			$totalPrice += (float) $product->getQuantity() * $formattedPrice;
			array_push( $itemsList, $item);
		}
		$totalPrice = (float) $totalPrice;

		$itemList = new ItemList();
		$itemList->setItems( $itemsList );

		$amount = new Amount();
		$amount->setCurrency( $this->currency )
		       ->setTotal( $totalPrice );

		$transaction = new Transaction();
		$transaction->setAmount( $amount )
		            ->setItemList( $itemList )
		            ->setInvoiceNumber( time() );


		$paymentPageUrl = get_permalink(get_field( 'process_payment_page', 'options')->ID);
		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl( add_query_arg(['success' => 'true'], $paymentPageUrl) )
		             ->setCancelUrl( add_query_arg(['success' => 'false'], $paymentPageUrl) );

		$payment = new Payment();
		$payment->setIntent( 'sale' )
		        ->setPayer( $payer )
		        ->setRedirectUrls( $redirectUrls )
		        ->setTransactions( [ $transaction ] );

		$paymentOptions = new PaymentOptions();
		$paymentOptions->setAllowedPaymentMethod('UNRESTRICTED');
		$session = new Session();

		try {
			$payment->create( $paypal );
		} catch ( \Exception $e ) {
			echo '<pre>';
			print_r($e);
			echo '</pre>';
			$session->setFlash( 'error', 'Something went wrong while making purchase!');
		}

		$approvalUrl = $payment->getApprovalLink();
		$cart->clear();
		header( "Location: {$approvalUrl}" );
	}
}