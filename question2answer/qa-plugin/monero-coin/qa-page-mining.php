<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
?>

<h3>Account</h3>
<?php if ($user->success): ?>
<div class="">
  name: <?php echo $user->name ?>
</div>
<div class="">
  total: <?php echo $user->total ?> Hash
</div>
<div class="">
  withdrawn: <?php echo $user->withdrawn ?> Hash
</div>
<div class="">
  balance: <?php echo $user->balance ?> Hash
</div>
<?php endif ?>

<h3>Spend</h3>
<div class="">
  monero_spend: <?php echo $us ? $us['monero_spend'] : '0' ?> Hash
</div>
<div class="">
  last_spend_time: <?php echo $us ? $us['last_spend_time'] : '' ?>
</div>

<h3>Mine</h3>

<script src="https://authedmine.com/lib/simple-ui.min.js" async></script>
<div class="coinhive-miner" 
	style="width: 256px; height: 310px"
	data-autostart="true"
	data-key="<?php echo qa_opt("monero_coin_site_key") ?>"
	data-user="u<?php echo qa_get_logged_in_userid() ?>">
	<em>Loading...</em>
</div>
