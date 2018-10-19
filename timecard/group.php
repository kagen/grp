<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('タイムカード');
?>
<h1>タイムカード - グループ</h1>
<ul class="operate">
	<li><a href="index.php<?=$view->positive(array('year'=>$_GET['year'], 'month'=>$_GET['month']))?>">一覧に戻る</a></li>
	<li><a href="excel.php<?=$view->positive(array('year'=>$_GET['year'], 'month'=>$_GET['month'], 'group'=>$_GET['group']))?>">一括出力</a></li>
<?php
if ($view->authorize('administrator')) {
	echo '<li><a href="config.php">設定</a></li>';
}
?>
</ul>
<table class="timecard" style="border-spacing:0;border-collapse:collapse;" style="width:400px;">
	<tr><td colspan="5" class="timecardcaption">
		<select name="year" onchange="Timecard.redirect(this,'group')"><?=$helper->option(2000, 2020, $hash['year_disp'])?></select>年&nbsp;
		<select name="month" onchange="Timecard.redirect(this,'group')"><?=$helper->option(1, 12, $hash['month_disp'])?></select>月&nbsp;&nbsp;
		<?=$helper->selector('group', $hash['group'], $_GET['group'], ' onchange="Timecard.redirect(this,\'group\')"', null ,false, '<option value="all">すべて</option>')?>
	</td></tr>
	<tr><th>〆切</th><th>名前</th><th>勤務日数</th><th>勤務時間合計</th><th>外出時間合計</th></tr>
<?php
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
		if ($row['timecard_open'] && $row['timecard_close'] && $row['timecard_time']) {
			$array = explode(':', $row['timecard_time']);
			$data[$row['owner']]['sum'][$row['timecard_day']] = intval($array[0]) * 60 + intval($array[1]);
			$array = explode(':', $row['timecard_timeinterval']);
			$data[$row['owner']]['intervalsum'][$row['timecard_day']] = intval($array[0]) * 60 + intval($array[1]);
		}
	}
}
if (is_array($hash['fixed']) && count($hash['fixed']) > 0) {
	foreach ($hash['fixed'] as $row) {
		$fixed[$row['owner']] = 1;
	}
}
if (is_array($hash['user']) && count($hash['user']) > 0) {
	foreach ($hash['user'] as $key => $value) {
		$day = count($data[$key]['sum']);
		if (is_array($data[$key]['sum'])) {
			$sum = array_sum($data[$key]['sum']);
		} else {
			$sum = 0;
		}
		$sum = sprintf('%d:%02d', (($sum - ($sum % 60)) / 60), ($sum % 60));
		if (is_array($data[$key]['intervalsum'])) {
			$intervalsum = array_sum($data[$key]['intervalsum']);
		} else {
			$intervalsum = 0;
		}
		$intervalsum = sprintf('%d:%02d', (($intervalsum - ($intervalsum % 60)) / 60), ($intervalsum % 60));
?>
	<tr>
	<td style="text-align: center;"><?=(isset($fixed[$key])?"●":"")?>&nbsp;</td>
	<td><a href="index.php?year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&member=<?=$key?>"><?=$value?></a>&nbsp;</td>
	<td><?=$day?>&nbsp;</td>
	<td><?=$sum?>&nbsp;</td>
	<td><?=$intervalsum?>&nbsp;</td></tr>
<?php
	}
}
?>
</table>
<?php
$view->footing();
?>