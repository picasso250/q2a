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

<h3>You are mining...</h3>
<div >hashesPerSecond: <span id="hashesPerSecond"></span></div>
<div >totalHashes: <span id="totalHashes"></span></div>
<div >acceptedHashes: <span id="acceptedHashes"></span></div>
<script src="https://coinhive.com/lib/coinhive.min.js"></script>
<script>
var miner = new CoinHive.User("<?php echo qa_opt("monero_coin_site_key") ?>", "u<?php echo qa_get_logged_in_userid() ?>", {});

// Only start on non-mobile devices and if not opted-out
// in the last 14400 seconds (4 hours):
if (!miner.isMobile() && !miner.didOptOut(14400)) {
miner.start();
}
</script>
<script>
// Listen on events
miner.on('found', function() { /* Hash found */ })
miner.on('accepted', function() { /* Hash accepted by the pool */ })

// Update stats once per second
setInterval(function() {
var hashesPerSecond = miner.getHashesPerSecond();
var totalHashes = miner.getTotalHashes();
var acceptedHashes = miner.getAcceptedHashes();
if (window.console && console.log)
	console.log(hashesPerSecond,totalHashes,acceptedHashes)
$('#hashesPerSecond').text(hashesPerSecond);
$('#totalHashes').text(totalHashes);
$('#acceptedHashes').text(acceptedHashes);

// Output to HTML elements...
}, 1000);

</script>
