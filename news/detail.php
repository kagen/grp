<?php
require_once('../application/json.php');
?>

<table class="fittable" style="border-spacing:0;border-collapse:collapse;">
	<tr><th style="height: 40px; vertical-align: middle;" colspan="4"><?=$hash['news_title']?></th></tr>
	<tr><td colspan="4" class="messagecontent" style="height:300px;">
		<?=nl2br($hash['news_body'])?>
	</td></tr>
	<tr><th style="width:20%;">作成者</th><td style="width:30%;"><?=$hash['owner']?></td><th style="width:20%;">作成時刻</th><td style="width:30%;"><?=$helper->nicedatetime($hash['created'])?></td></tr>
	<tr><th style="width:20%;">更新者</th><td style="width:30%;"><?=$hash['editor']?></td><th style="width:20%;">更新時刻</th><td style="width:30%;"><?=$helper->nicedatetime($hash['updated'])?></td></tr>
</table>
