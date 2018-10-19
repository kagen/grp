<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$calendar = new Calendar;
$view->heading('タイムカード編集');
$open = explode(':', $hash['data']['timecard_open']);
$close = explode(':', $hash['data']['timecard_close']);
$target_date = strtotime($_GET['year']."-".$_GET['month']."-".$_GET['day']);
$class = $calendar->fontstyle($_GET['year'], $_GET['month'], $_GET['day']);
$weekday = date('w', $target_date);
$week = array('日', '月', '火', '水', '木', '金', '土');

if (strlen($hash['owner']['realname']) > 0 && (isset($_GET['member']) || $hash['owner']['userid'] != $_SESSION['userid'])) {
	$caption = ' - '.$hash['owner']['realname'];
}

?>
<h1>タイムカード編集<?=$caption?></h1>
<ul class="operate">
	<li><a href="index.php?year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$_GET['day']?>&member=<?=$_GET['member']?>">一覧に戻る</a></li>
</ul>
<form class="content" method="post" action="">
	<?=$view->error($hash['error'])?>
	<table class="form" style="border-spacing:0;border-collapse:collapse;">
		<tr><th>日付</th>
		<td>
			<?=$_GET['year']."年".$_GET['month']."月".$_GET['day']."日"?>&nbsp;<span<?=$class?>>（<?=$week[$weekday]?>）</span>&nbsp;
			<?=(($hash['is_holiday'] || $hash['data']['timecard_workday_flg'] == 1) && $view->authorize('administrator')?'<input type=\'hidden\' value=0 name=\'timecard_workday_flg\'>'.$helper->checkbox('timecard_workday_flg', 1, $hash['data']['timecard_workday_flg'], 'timecard_workday_flg', '平日扱い'):'') ?>
		</td>
		</tr>

		<tr><th>出社</th>
		<td><select name="openhour"><?=$helper->option(0, 23, $open[0], true)?></select>時&nbsp;
			<select name="openminute"><?=$helper->option(0, 59, $open[1], true)?></select>分&nbsp;
			<?=$helper->selector('timecard_reason_open', $hash['reason'], $hash['data']['timecard_reason_open'],null,null,true)?>
		</td>
		</tr>
<!--
		<tr><th>外出</th><td>
<?php
$array = explode(' ', $hash['data']['timecard_interval']);
foreach ($array as $value) {
	list($intervalopen, $intervalclose) = explode('-', $value);
	list($openhour, $openminute) = explode(':', $intervalopen);
	list($closehour, $closeminute) = explode(':', $intervalclose);
?>
			<div><select name="intervalopenhour[]"><?=$helper->option(0, 23, $openhour)?></select>時&nbsp;
			<select name="intervalopenminute[]"><?=$helper->option(0, 59, $openminute)?></select>分&nbsp;-&nbsp;
			<select name="intervalclosehour[]"><?=$helper->option(0, 23, $closehour)?></select>時&nbsp;
			<select name="intervalcloseminute[]"><?=$helper->option(0, 59, $closeminute)?></select>分&nbsp;
			<span class="operator" onclick="Timecard.remove(this)">削除</span></div>
<?php
}
?>
		<span class="operator" onclick="Timecard.interval(this)">追加</span></td></tr>
-->
		<tr><th>退社</th><td>
			<select name="closehour"><?=$helper->option(0, 23, $close[0], true)?></select>時&nbsp;
			<select name="closeminute"><?=$helper->option(0, 59, $close[1], true)?></select>分&nbsp;
			<?=$helper->selector('timecard_reason_close', $hash['reason'], $hash['data']['timecard_reason_close'],null,null,true)?>
		</td></tr>
		<tr><th>備考</th><td><textarea name="timecard_comment" class="inputcomment" rows="5"><?=$hash['data']['timecard_comment']?></textarea></td></tr>
	</table>
	<div class="submit">
		<input type="submit" value="　確定　" />&nbsp;
		<input type="button" value="キャンセル" onclick="location.href='index.php?year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$_GET['day']?>&member=<?=$_GET['member']?>'" />
	</div>
</form>
<?php
$view->footing();
?>