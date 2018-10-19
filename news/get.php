<?php
require_once('../application/jsonnoauth.php');
?>
<?php
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
?>
<marquee behavior="scroll" scrollamount="3" direction="left" width="300" style="width: 300px;"><p><?=($row['news_title'])?></p></marquee>
<?php
	}
}
?>
