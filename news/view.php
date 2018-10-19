<?php
require_once('../application/library/helper.php');
$helper = new Helper;
if(!isset($hash['id'])){
	$hash = $_POST;
}
?>
<div class="forum">
	<div class="topcaption"><span>[<?=sprintf('%03d', $hash['id'])?>]　<?=$hash['news_title']?></span><ul><li><span class="operator" id="<?=$hash['id']?>" onclick="News.edit(<?=$hash['id']?>)">編集</span></li></ul></div>
	<div class="forumtitle"><span><?=nl2br($hash['news_body'])?></span></div>
	<div class="forumproperty">
	<table style="font-size: 90%;"><tr>
	<td style="width: 25%;"><span>表示開始：<?=$helper->nicedatetime($hash['news_begin'])?></span></td>
	<td style="width: 25%;"><span>表示終了：<?=$helper->nicedatetime($hash['news_end'])?></span></td>
	<td style="width: 25%;"><span>打刻機表示：<?=($hash['recorder_disp']==1?"表示":"非表示")?></span></td>
	<td style="width: 25%;"><span>公開設定：<?=($hash['news_hide']==1?"非表示":"表示")?></span></td>
	</tr>
	<tr>
	<td><span>作成時刻：<?=$helper->nicedatetime($hash['created'])?></span></td>
	<td><span>作成者：<?=$hash['owner']?></span></td>
	<td><span>更新時刻：<?=$helper->nicedatetime($hash['updated'])?></span></td>
	<td><span>更新者：<?=$hash['editor']?></span></td>
	</tr>
	</table>
	</div>
</div>
