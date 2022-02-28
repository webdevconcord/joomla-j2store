<?php
/**
 * ConcordPay API.
 *
 * @version     1.0.0
 * @description Service class ConcordPay API.
 * @package     ConcordPay_API
 * @author      ConcordPay <serhii.shylo@mustpay.tech>
 * @copyright   Copyright (c) 2021 https://concordpay.concord.ua
 * @license     GNU GPL v3.0 (https://opensource.org/licenses/GPL-3.0)
 */

// Protection against direct access.
defined('_JEXEC') or die('Restricted access');

/**
 * ConcordPayApi Class.
 *
 * @since 3.8.0
 */
class ConcordPayApi
{
	public const SIGNATURE_SEPARATOR = ';';
	public const ORDER_SEPARATOR = '#';
	public const TRANSACTION_STATUS_APPROVED = 'Approved';
	public const TRANSACTION_STATUS_DECLINED = 'Declined';
	public const PHONE_LENGTH_MIN = 10;
	public const PHONE_LENGTH_MAX = 11;
	public const ALLOWED_CURRENCIES = array('UAH');
	public const RESPONSE_TYPE_PAYMENT = 'payment';
	public const RESPONSE_TYPE_REVERSE = 'reverse';

	/**
	 * Array keys for generate response signature.
	 *
	 * @var string[]
	 *
	 * @since 3.8.0
	 */
	protected $keysForResponseSignature = array(
		'merchantAccount',
		'orderReference',
		'amount',
		'currency',
	);

	/**
	 * Array keys for generate request signature.
	 *
	 * @var string[]
	 *
	 * @since 3.8.0
	 */
	protected $keysForRequestSignature = array(
		'merchant_id',
		'order_id',
		'amount',
		'currency_iso',
		'description',
	);

	/**
	 * Allowed callback operation types.
	 *
	 * @var string[]
	 *
	 * @since 3.8.0
	 */
	protected $allowedOperationTypes = array(
		self::RESPONSE_TYPE_PAYMENT,
		self::RESPONSE_TYPE_REVERSE,
	);

	/**
	 * Allowed ConcordPay payment page languages.
	 *
	 * @var array|string[]
	 *
	 * @since 3.8.0
	 */
	protected $allowedPaymentPageLanguages = array(
		'ru' => 'ru',
		'uk' => 'uk',
		'en' => 'en',
	);

	/**
	 * ConcordPay API URL
	 *
	 * @var string
	 *
	 * @since 3.8.0
	 */
	private $url = 'https://pay.concord.ua/api/';

	/**
	 * ConcordPay secret key.
	 *
	 * @var string
	 *
	 * @since 3.8.0
	 */
	private $secretKey;

	/**
	 * ConcordPay_API constructor.
	 *
	 * @param   string $secretKey ConcordPay secret key.
	 *
	 * @since 3.8.0
	 */
	public function __construct($secretKey)
	{
		$this->secretKey = $secretKey;
	}

	/**
	 * Getter for request signature keys.
	 *
	 * @return string[]
	 *
	 * @since 3.8.0
	 */
	public function getKeysForRequestSignature(): array
	{
		return $this->keysForRequestSignature;
	}

	/**
	 * Getter for response signature keys.
	 *
	 * @return string[]
	 *
	 * @since 3.8.0
	 */
	public function getKeysForResponseSignature(): array
	{
		return $this->keysForResponseSignature;
	}

	/**
	 * Getter for allowed operation types.
	 *
	 * @return string[]
	 *
	 * @since 3.8.0
	 */
	public function getAllowedOperationTypes(): array
	{
		return $this->allowedOperationTypes;
	}

	/**
	 * Getter for allowed operation types.
	 *
	 * @return string[]
	 *
	 * @since 3.8.0
	 */
	public function getAllowedPaymentPageLanguages(): array
	{
		return $this->allowedPaymentPageLanguages;
	}

	/**
	 * Generate request signature.
	 *
	 * @param   array $options Request data.
	 *
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function getRequestSignature(array $options): string
	{
		return $this->getSignature($options, $this->keysForRequestSignature);
	}

	/**
	 * Generate signature for operation.
	 *
	 * @param   array $option Request or response data.
	 * @param   array $keys   Keys for signature.
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function getSignature($option, $keys): string
	{
		$hash = array();

		foreach ($keys as $dataKey)
		{
			if (!isset($option[$dataKey]))
			{
				continue;
			}

			if (is_array($option[$dataKey]))
			{
				foreach ($option[$dataKey] as $v)
				{
					$hash[] = $v;
				}
			}
			else
			{
				$hash [] = $option[$dataKey];
			}
		}

		$hash = implode(self::SIGNATURE_SEPARATOR, $hash);

		return hash_hmac('md5', $hash, $this->secretKey);
	}

	/**
	 * ConcordPay API URL.
	 *
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function getApiUrl(): string
	{
		return $this->url;
	}

	/**
	 * Generate ConcordPay payment form with hidden fields.
	 *
	 * @param   array $data Order data, prepared for payment.
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function generateForm($data): string
	{
		$form = PHP_EOL . "<form method='post' id='form_concordpay' action=$this->url accept-charset=utf-8>" . PHP_EOL;

		foreach ($data as $k => $v)
		{
			$form .= $this->printInput($k, $v);
		}

		$form .= "<input type='submit' style='display:none;'/>" . PHP_EOL;
		$form .= '</form>' . PHP_EOL;
		$form .= "<script type='text/javascript'>window.addEventListener('DOMContentLoaded', function () { document.querySelector('#form_concordpay').submit(); }) </script>";

		return $form;
	}

	/**
	 * Prints inputs in form.
	 *
	 * @param   string       $name Attribute name.
	 * @param   array|string $val  Attribute value.
	 *
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function printInput($name, $val): string
	{
		$str = '';

		if (!is_array($val))
		{
			return "<input type='hidden' name='" . $name . "' value='" . htmlspecialchars($val) . "'>" . PHP_EOL;
		}

		foreach ($val as $v)
		{
			$str .= $this->printInput($name . '[]', $v);
		}

		return $str;
	}

	/**
	 * Validate gateway response.
	 *
	 * @param   array $data     Response data.
	 * @param   array $settings Gateway settings.
	 *
	 * @return boolean
	 *
	 * @since 3.8.0
	 */
	public function isPaymentValid($data, $settings): bool
	{
		if ($settings['merchant_id'] !== $data['merchantAccount'])
		{
			return false;
		}

		if ($this->getResponseSignature($data) !== $data['merchantSignature'])
		{
			return false;
		}

		return true;
	}

	/**
	 * Generate response signature.
	 *
	 * @param   array $options Response data.
	 * @return string
	 *
	 * @since 3.8.0
	 */
	public function getResponseSignature($options): string
	{
		return $this->getSignature($options, $this->keysForResponseSignature);
	}
}
