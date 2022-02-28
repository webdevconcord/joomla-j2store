<?php

/**
 * PHP version 7.2.34
 *
 * @category  Class
 * @package   J2Store
 * @author    ConcordPay <serhii.shylo@mustpay.tech>
 * @copyright 2021 ConcordPay
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://concordpay.concord.ua
 * @since     3.8.0
 */

use Joomla\CMS\Object\CMSObject;

// Protection against direct access.
defined('_JEXEC') or die('Restricted access');
?><?php

require_once JPATH_ADMINISTRATOR . '/components/com_j2store/library/plugins/payment.php';
require_once JPATH_ROOT . '/plugins/j2store/payment_concordpay/ConcordPayApi.php';

/**
 * Class plgJ2StorePayment_concordpay
 *
 * @category Class
 * @package  J2Store
 * @author   ConcordPay <serhii.shylo@mustpay.tech>
 * @license  GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link     https://concordpay.concord.ua
 *
 * @since version 3.8.0
 *
 * @property stdClass $payment_params
 * @property stdClass $currency
 * @property string $url_itemid
 * @property array $vars
 */
class plgJ2StorePayment_concordpay extends J2StorePaymentPlugin
{
	public const CONCORDPAY_TRANSACTION_STATUS_PROCESSED = 'Processed';
	public const CONCORDPAY_TRANSACTION_STATUS_CONFIRMED = 'Confirmed';

	// From table '#_j2store_orderstatuses'.
	public const CONCORDPAY_ORDER_STATUS_CONFIRMED = 1;
	public const CONCORDPAY_ORDER_STATUS_PROCESSED = 2;
	public const CONCORDPAY_ORDER_STATUS_FAILED    = 3;
	public const CONCORDPAY_ORDER_STATUS_PENDING   = 4;
	public const CONCORDPAY_ORDER_STATUS_NEW       = 5;
	public const CONCORDPAY_ORDER_STATUS_CANCELLED = 6;

	/**
	 * @var ConcordPayApi
	 *
	 * @since version 3.8.0
	 */
	protected $concordpay;

	/**
	 * @var string $_element Should always correspond with the plugin's filename, forcing it to be unique.
	 *
	 * @since 3.8.0
	 */
	public $_element = 'payment_concordpay';

	/**
	 * Payment ConcordPay constructor.
	 *
	 * @param   JEventDispatcher $subject Dispatcher.
	 * @param   array            $config  Module config.
	 *
	 * @since 3.8.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('com_j2store', JPATH_ADMINISTRATOR);
		$this->loadLanguage('plg_j2store_payment_concordpay', JPATH_ADMINISTRATOR);
		$this->concordpay = new ConcordPayApi($this->params->get('secret_key', ''));
	}

	/**
	 * Prepares variables and renders the form for collecting payment info.
	 *
	 * @param   array $data form post data for pre-populating form.
	 *
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function _renderForm($data): string
	{
		$user = JFactory::getUser();
		$vars = new JObject;
		$vars->concordpay = $this->translate('NAME');

		return  $this->_getLayout('form', $vars);
	}

	/**
	 * Translate plugin language files.
	 *
	 * @param   string $key Predefined translation string.
	 *
	 * @return mixed
	 *
	 * @since 3.8.0
	 */
	protected function translate(string $key)
	{
		return JText::_('CONCORDPAY_' . strtoupper($key));
	}

	/**
	 * Processes the payment form and returns HTML to be displayed to the user
	 * generally with a success/failed message
	 *
	 * @param   array $data Form post data.
	 *
	 * @return string HTML to display
	 *
	 * @throws Exception
	 * @since 3.8.0
	 */
	public function _prePayment($data): string
	{
		$vars = new JObject;

		$vars->orderId            = $data['order_id'];
		$vars->orderpaymentId     = $data['orderpayment_id'];
		$vars->orderpaymentAmount = $data['orderpayment_amount'];
		$vars->orderpaymentType   = $this->_element;
		$vars->buttonText         = $this->params->get('button_text', 'J2STORE_PLACE_ORDER');
		$vars->merchantId         = $this->params->get('merchant_id', '');
		$vars->secretKey          = $this->params->get('secret_key', '');

		// Customer information
		$orderinfo = F0FTable::getInstance('Orderinfo', 'J2StoreTable')->getClone();
		$orderinfo->load(['order_id' => $data['order_id']]);

		$clientFirstName = $orderinfo->billing_first_name;
		$clientLastName  = $orderinfo->billing_last_name;
		$allBilling      = json_decode($orderinfo->all_billing);

		$email = $allBilling->email->value;
		$phone = $orderinfo->billing_phone_2;

		if (empty($phone))
		{
			$phone = !empty($orderinfo->billing_phone_1) ? $orderinfo->billing_phone_1 : '';
		}

		// Get current order.
		$order = $this->getOrder($data['order_id']);

		if ($vars->merchantId == '')
		{
			$msg = $this->translate('ERROR_CONFIG');
			$vars->error = $msg;
			$order->add_history($msg);
			$order->store();

			return $this->_getLayout('prepayment', $vars);
		}

		$amount   = round($vars->orderpaymentAmount, 0);
		$currency = $this->getCurrency($order);

		$description = $this->translate('ORDER_DESCRIPTION') . ' ' . htmlspecialchars($_SERVER['HTTP_HOST'])
			. " , $clientFirstName $clientLastName, $phone.";
		$returnUrl = JRoute::_(JURI::root() . $this->getReturnUrl());
		$callbackUrl = JRoute::_(JURI::root() . "index.php?option=com_j2store&view=checkout&paction=process")
			. '&orderpayment_type=' . $vars->orderpaymentType . '&task=confirmPayment';

		if (empty($amount))
		{
			$msg = $this->translate('ERROR_PRICE');
			$vars->error = $msg;
			$order->add_history($msg);
			$order->store();

			return $this->_getLayout('prepayment', $vars);
		}

		$data = [
			'operation'    => 'Purchase',
			'merchant_id'  => $this->params->get('merchant_id', ''),
			'amount'       => $amount,
			'order_id'     => $data['orderpayment_id'],
			'currency_iso' => $currency['currency_code'],
			'description'  => $description,
			'approve_url'  => $returnUrl . '&result=success',
			'decline_url'  => $returnUrl . '&result=fail',
			'cancel_url'   => $returnUrl . '&result=cancel',
			'callback_url' => $callbackUrl,
			// Statistics.
			'client_last_name'  => $clientLastName,
			'client_first_name' => $clientFirstName,
			'phone' => $phone,
			'email' => $email,
		];

		$data['signature'] = $this->concordpay->getRequestSignature($data);

		$vars->data = '';

		foreach ($data as $key => $value)
		{
			$vars->data .= $this->concordpay->printInput($key, $value);
		}

		$vars->apiUrl = $this->concordpay->getApiUrl();

		return $this->_getLayout('prepayment', $vars);
	}

	/**
	 * Processes the payment form and returns HTML to be displayed to the user
	 * generally with a success/failed message
	 *
	 * @param   array $data form post data
	 * @return string HTML to display
	 *
	 * @throws Exception
	 * @since 3.8.0
	 */
	function _postPayment($data)
	{
		$app = JFactory::getApplication();

		if (!$app)
		{
			exit($this->translate('ERROR_NO_APPLICATION_FOUND'));
		}

		$vars = new JObject;
		$paction = $app->input->getString('paction');
		$result = ($app->input->getString('result') !== null) ? strtolower($app->input->getString('result')) : '';

		switch ($paction)
		{
			case 'display':
				if ($result === 'success')
				{
					$vars->postpaymentMessage = $this->translate('PAYMENT_ORDER_CONFIRMED');
					$vars->postpaymentMessageColor = 'green';
				}
				elseif ($result === 'fail')
				{
					$vars->postpaymentMessage = $this->translate('PAYMENT_ORDER_DECLINED');
					$vars->postpaymentMessageColor = 'red';
				}
				elseif ($result === 'cancel')
				{
					$vars->postpaymentMessage = $this->translate('PAYMENT_ORDER_CANCELED');
					$vars->postpaymentMessageColor = 'orange';
				}
				break;
			case 'process':
				$this->callbackHandler();
				break;
		}

		return $this->returnResult($vars);
	}

	/**
	 * Render template.
	 *
	 * @param   CMSObject $vars Template variables.
	 * @return string
	 *
	 * @since 3.8.0
	 */
	protected function returnResult($vars): string
	{
		return $this->_getLayout('postpayment', $vars);
	}

	/**
	 * Payment gateway callback handler.
	 *
	 * @return string|void
	 * @throws Exception
	 *
	 * @since 3.8.0
	 */
	private function callbackHandler()
	{
		$app = JFactory::getApplication();

		if (!$app)
		{
			exit($this->translate('ERROR_NO_APPLICATION_FOUND'));
		}

		F0FTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2store/tables');
		$orderpayment = F0FTable::getInstance('Order', 'J2StoreTable')->getClone();
		$response = $app->input->json->getArray();

		$errorMessages = '';

		// Check merchant.
		$merchantId = $response['merchantAccount'] ?? '';

		if (empty($merchantId))
		{
			$errorMessages .= $this->translate('ERROR_MERCHANT') . PHP_EOL;
		}

		// Check amount.
		$amount = $response['amount'] ?? '';

		if (empty($amount))
		{
			$errorMessages .= $this->translate('ERROR_AMOUNT') . PHP_EOL;
		}

		// Check currency.
		$currency = $response['currency'] ?? '';

		if (empty($currency))
		{
			$errorMessages .= $this->translate('ERROR_CURRENCY') . PHP_EOL;
		}

		// Check operation type.
		$type = $response['type'] ?? '';

		if (empty($type) || !in_array($type, $this->concordpay->getAllowedOperationTypes(), true))
		{
			$errorMessages .= $this->translate('ERROR_OPERATION_TYPE') . PHP_EOL;
		}

		// Check order ID and transaction ID.
		$transactionId = $response['transactionId'] ?? '';
		$orderId       = $response['orderReference'] ?? '';

		if (empty($transactionId) || empty($orderId))
		{
			$errorMessages .= $this->translate('ERROR_ORDER_ID') . PHP_EOL;
		}

		// Check order existing.
		if (!$orderpayment->load($orderId))
		{
			$errorMessages .= $this->translate('ERROR_ORDER_NOT_FOUND') . PHP_EOL;
		}

		// Check signature.
		$signature = $this->concordpay->getResponseSignature($response);

		if ($response['merchantSignature'] !== $signature)
		{
			$errorMessages .= $this->translate('ERROR_SIGNATURE') . PHP_EOL;
		}

		// Re-payment check.
		if ($response['type'] !== ConcordPayApi::RESPONSE_TYPE_REVERSE
			&& ($orderpayment->get('transaction_status') === self::CONCORDPAY_TRANSACTION_STATUS_PROCESSED
			|| $orderpayment->get('transaction_status') === self::CONCORDPAY_TRANSACTION_STATUS_CONFIRMED)
		)
		{
			$errorMessages .= $this->translate('ERROR_ALREADY_COMPLETED') . PHP_EOL;
		}

		// Shows error.
		if (!empty($errorMessages))
		{
			exit($errorMessages);
		}

		// Update order status.
		if ($response['transactionStatus'] === ConcordPayApi::TRANSACTION_STATUS_APPROVED)
		{
			if ($response['type'] === ConcordPayApi::RESPONSE_TYPE_PAYMENT)
			{
				// Ordinary payment.
				$orderpayment->add_history($this->translate('PAYMENT_APPROVED') . $response['transactionId']);

				if ($orderpayment->store())
				{
					$orderpayment->payment_complete(
						$this->params->get('approved_status', self::CONCORDPAY_ORDER_STATUS_PROCESSED)
					);
					$orderpayment->empty_cart();

					exit('Payment OK');
				}
			}
			elseif ($response['type'] === ConcordPayApi::RESPONSE_TYPE_REVERSE)
			{
				// Refunded payment.
				$orderpayment->add_history($this->translate('PAYMENT_REFUNDED') . $response['transactionId']);
				$orderpayment->update_status(
					$this->params->get('refunded_status', self::CONCORDPAY_ORDER_STATUS_CANCELLED)
				);
				$orderpayment->restore_order_stock();
				$orderpayment->store();

				exit('Refunded OK');
			}
		}
		else
		{
			$orderpayment->add_history($this->translate('PAYMENT_DECLINED'));
			$orderpayment->update_status(
				$this->params->get('declined_status', self::CONCORDPAY_ORDER_STATUS_FAILED)
			);
			$orderpayment->reduce_order_stock();
			$orderpayment->store();

			exit('Operation failed');
		}
	}

	/**
	 * Get order object from  model.
	 *
	 * @param   string $orderId Order ID.
	 *
	 * @return F0FTable $order
	 *
	 * @since 3.8.0
	 */
	private function getOrder($orderId): F0FTable
	{
		F0FTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2store/tables');
		$order = F0FTable::getInstance('Order', 'J2StoreTable')->getClone();
		$order->load(array('order_id' => $orderId));

		return $order;
	}
}
