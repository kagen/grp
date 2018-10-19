<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('タイムカード設定');
$array = array('0'=>'00', '10'=>'10', '20'=>'20', '30'=>'30', '40'=>'40', '50'=>'50');
?>
<h1>タイムカード設定</h1>
<span class="necessary">（※注意：変数名も値も英数字のみが許されます）</span>
<ul class="operate">
	<li><a href="group.php">一覧に戻る</a></li>
</ul>
<form class="content" method="post" id="timecardconfig" onsubmit="return validateForm()" action="">
	<?=$view->error($hash['error'])?>
	<table class="form" style="border-spacing:0;border-collapse:collapse;">
		<tr><th>標準出社時刻<?=$view->explain('timecardopen')?></th><td>
			<select id="workopentime" name="timecard[openhour]"><?=$helper->option(0, 23, $hash['data']['openhour'])?></select>時&nbsp;
			<?=$helper->selector('timecard[openminute]', $array, $hash['data']['openminute'], '', 'workopentimeminute')?>分&nbsp;
		</td></tr>
		<tr><th>標準退社時刻<?=$view->explain('timecardclose')?></th><td>
			<select id="workclosetime" name="timecard[closehour]"><?=$helper->option(0, 23, $hash['data']['closehour'])?></select>時&nbsp;
			<?=$helper->selector('timecard[closeminute]', $array, $hash['data']['closeminute'], '', 'workclosetimeminute')?>分&nbsp;
		</td></tr>
		<tr><th>出退社計算単位<?=$view->explain('timecardround')?></th><td>
			<?=$helper->radio('timecard[timeround]', 0, $hash['data']['timeround'], 'timeround0', '1分単位')?>
			<?=$helper->radio('timecard[timeround]', 1, $hash['data']['timeround'], 'timeround1', '10分単位')?>
		</td></tr>
		<tr><th>固定外出時刻<?=$view->explain('timecardlunch')?></th><td>
			<select id="lunchopentime" name="timecard[lunchopenhour]"><?=$helper->option(0, 23, $hash['data']['lunchopenhour'])?></select>時&nbsp;
			<?=$helper->selector('timecard[lunchopenminute]', $array, $hash['data']['lunchopenminute'], '', 'lunchopentimeminute')?>分&nbsp;
			-&nbsp;
			<select id="lunchclosetime" name="timecard[lunchclosehour]"><?=$helper->option(0, 23, $hash['data']['lunchclosehour'])?></select>時&nbsp;
			<?=$helper->selector('timecard[lunchcloseminute]', $array, $hash['data']['lunchcloseminute'], '', 'lunchclosetimeminute')?>分&nbsp;
		</td></tr>
		<tr><th>外出時間計算単位<?=$view->explain('timecardlunchround')?></th><td>
			<?=$helper->radio('timecard[intervalround]', 0, $hash['data']['intervalround'], 'intervalround0', '1分単位')?>
			<?=$helper->radio('timecard[intervalround]', 1, $hash['data']['intervalround'], 'intervalround1', '10分単位')?>
		</td></tr>
		<tr><th>テスト打刻<?=$view->explain('dakokutest')?></th><td>
			<input type="text" name="timecard[dakokutest]" class="inputvalue" value="<?=$hash['data']['dakokutest']?>" />
		</td></tr>
		<tr><th>タイムカード締め日<?=$view->explain('timecardcloseday')?></th><td>
			<select name="timecard[closeday]"><?=$helper->option(1, 27, $hash['data']['closeday'],true)?></select>日
		</td></tr>
		<tr><th>深夜残業<?=$view->explain('timecardlatenightovertime')?></th><td>
			<input type="text" id="overtimeopentime" name="timecard[latenightovertimestart]" id="timepicker_open" style="width: 70px;" value="<?=$hash['data']['latenightovertimestart']?>" /> ～
			<input type="text" id="overtimeclosetime" name="timecard[latenightovertimeend]" id="timepicker_close" style="width: 70px;" value="<?=$hash['data']['latenightovertimeend']?>" />
			&nbsp;（<input type="hidden" name="timecard[latenightovertimesendnextday]" value=0 /><input id="timecardlatenightovertimesendnextday" type="checkbox" name="timecard[latenightovertimesendnextday]" value=1 <?=($hash['data']['latenightovertimesendnextday']==1?"checked":"")?> />翌日）
		</td></tr>
		<tr><th>月残業時間リミット<?=$view->explain('overtimehourlimit')?></th><td>
			<input type="text" id="overtimehourlimit" name="timecard[overtimehourlimit]" style="width: 70px;" value="<?=$hash['data']['overtimehourlimit']?>" />時間
		</td></tr>

	</table>
	<div class="submit">
		<input type="submit" value="　設定　" />&nbsp;
		<input type="button" value="キャンセル" onclick="location.href='index.php'" />
	</div>
</form>
<?php
$view->footing();
?>
<script type="text/javascript">
$('#timepicker_open').datetimepicker({
	datepicker:false,
	format:'H:i',
	allowTimes:['20:00','21:00','22:00','23:00']
});
$('#timepicker_close').datetimepicker({
	datepicker:false,
	format:'H:i',
	allowTimes:['22:00','21:00','22:00','23:00','0:00','1:00','2:00','3:00','4:00','5:00','6:00']
});
function validateForm() {
	var startTime;
	var endTime;
	startTime = new Date('2014/09/25 ' + $('#workopentime').val() + ':' + $('#workopentimeminute').val());
	endTime = new Date('2014/09/25 ' + $('#workclosetime').val() + ':' + $('#workclosetimeminute').val());
	if (startTime.getTime() > endTime.getTime()) {
        alert("標準出社時刻は標準退社時刻より早くなければなりません。");
        return false;
    }
	startTime = new Date('2014/09/25 ' + $('#lunchopentime').val() + ':' + $('#lunchopentimeminute').val());
	endTime = new Date('2014/09/25 ' + $('#lunchclosetime').val() + ':' + $('#lunchclosetimeminute').val());
	if (startTime.getTime() > endTime.getTime()) {
        alert("固定外出時刻の設定が矛盾しています。");
        return false;
    }

	startTime = new Date('2014/09/25 ' + $('#overtimeopentime').val());
	endTime = new Date('2014/09/25 ' + $('#overtimeclosetime').val());
	if (startTime.getTime() > endTime.getTime() && !$("#timecardlatenightovertimesendnextday:checked").val()) {
        alert("深夜残業時刻の設定が矛盾しています。");
        return false;
    }

	if(!$('#overtimehourlimit').val().match(/^\d+:\d{2}$/)){
        alert("月残業時間リミットを正しく入力してください。フォーマット例「20:00」（20時間）。");
        return false;
	}
}


</script>