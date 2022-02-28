<?php
/**
 * PHP version 7.2.34
 *
 * @category  Template
 * @package   J2Store
 * @author    ConcordPay <serhii.shylo@mustpay.tech>
 * @copyright 2021 ConcordPay
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://concordpay.concord.ua
 * @since     3.8.0
 */

defined('_JEXEC') or die('Restricted access'); ?>

<?php /** @var \Joomla\CMS\Object\CMSObject $vars */ ?>
<style>
  .concordpay-message {
	  margin-bottom: 20px
  }
  .concordpay-message img {
	  display: inline-block;
	  vertical-align: middle;
	  width: 70px;
	  margin-right: 10px
  }
</style>
<div class="note concordpay-message">
	<img src="/plugins/j2store/payment_concordpay/payment_concordpay/concordpay.svg" alt="ConcordPay">
  <strong style="color: <?php echo JText::_($vars->postpaymentMessageColor); ?>">
	  <?php echo JText::_($vars->postpaymentMessage); ?>
  </strong>
</div>
