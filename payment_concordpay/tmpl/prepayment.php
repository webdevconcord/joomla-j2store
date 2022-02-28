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

defined('_JEXEC') or die('Restricted access');
?>
<?php /** @var \Joomla\CMS\Object\CMSObject $vars */ ?>
<form action="<?php echo @$vars->apiUrl; ?>" method="post" name="adminForm" enctype="multipart/form-data">
  <p>
	  <img src="/plugins/j2store/payment_concordpay/payment_concordpay/concordpay.svg"
		  style="display: inline-block;vertical-align: middle;width: 70px;" alt="ConcordPay">
		<?php echo JText::_("CONCORDPAY_NAME_ON_CHECKOUT"); ?>
  </p>
  <br/>
	<?php if (!empty(@$vars->error)) : ?>
	  <div class="warning alert alert-danger">
		  <?php echo @$vars->error ?>
	  </div>
	<?php else : ?>
    <?php echo $vars->data; ?>
	  <input type="submit" class="j2store_cart_button button btn btn-primary"
			 value="<?php echo JText::_($vars->buttonText); ?>"/>
	<?php endif; ?>
</form>
