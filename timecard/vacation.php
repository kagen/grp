<?php
/*
 * kagen.yu
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$calendar = new Calendar;
$view->heading('休暇編集');
$target_date = strtotime($_GET['year']."-".$_GET['month']."-".$_GET['day']);
$class = $calendar->fontstyle($_GET['year'], $_GET['month'], $_GET['day']);
$weekday = date('w', $target_date);
$week = array('日', '月', '火', '水', '木', '金', '土');

?>
<h1>休暇編集</h1>
<ul class="operate">
	<li><a href="index.php?year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$_GET['day']?>">一覧に戻る</a></li>
</ul>
<form class="content" method="post" action="">
	<?=$view->error($hash['error'])?>
	<table class="form" style="border-spacing:0;border-collapse:collapse;">
		<tr><th>日付</th>
		<td>
			<?=$_GET['year']."年".$_GET['month']."月".$_GET['day']."日"?>&nbsp;<span<?=$class?>>（<?=$week[$weekday]?>）</span>
		</td>
		</tr>

		<tr><th>休暇のタイプ<span class="necessary">(必須)</span></th>
		<td>
			<?=$helper->selector('timecard_vacation_type', $hash['vacation'], $hash['data']['timecard_vacation_type']," onchange='vacationtypechange()'")?>&nbsp;&nbsp;&nbsp;<?=($hash['data']['timecard_vacation_approved']?'<span class="font-green">承認済み</span>&nbsp;'.$hash['data']['timecard_vacation_approved']:($hash['data']['timecard_vacation_requested']?'<span class="font-red">申請中</span>&nbsp;'.$hash['data']['timecard_vacation_requested']:''))?>
		</td>
		</tr>
		<tr><th>時間</th><td>
			<input type="text" name="timecard_vacation_from" id="timepicker_open" style="width: 70px;" value="<?=date("H:i", strtotime($hash['data']['timecard_vacation_from']))?>" /> ～ <input type="text" name="timecard_vacation_to" id="timepicker_close" style="width: 70px;" value="<?=date("H:i", strtotime($hash['data']['timecard_vacation_to']))?>" />
		</td></tr>
		<tr><th>備考</th><td><textarea name="timecard_vacation_comment" class="inputcomment" rows="5"><?=$hash['data']['timecard_vacation_comment']?></textarea></td></tr>
	</table>
	<div class="submit">
		<input type="submit" value="　確定　" />&nbsp;
		<input type="button" value="キャンセル" onclick="location.href='index.php?year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$_GET['day']?>'" />
	</div>
</form>
<?php if ($hash['data']['timecard_vacation_type']) { ?>
<form class="content right" method="post" action="">
	<input type="hidden" name="delete_vacation_id" value="<?=$hash['data']['id']?>" />
	<input type="submit" value="　削除　" />&nbsp;
</form>
<?php } ?>
<?php
$view->footing();
?>
<script type="text/javascript">
var vacationTypes = $.parseJSON('<?=json_encode($hash['vacationinfo']);?>');

$('#timepicker_open').datetimepicker({
	datepicker:false,
	format:'H:i',
	minTime:'9:00',
	maxTime:'18:00',
	step:60

});
$('#timepicker_close').datetimepicker({
	datepicker:false,
	format:'H:i',
	minTime:'10:00',
	maxTime:'19:00',
	step:60

});
vacationtypechange();
function vacationtypechange() {
	if (vacationTypes[$("#timecard_vacation_type").val()]["allday"] == 1) {
		$("#timepicker_open").attr("disabled", "disabled");
		$("#timepicker_open").val("");
		$("#timepicker_open").animate({'backgroundColor' : '#F0F0F0'});
		$("#timepicker_close").attr("disabled", "disabled");
		$("#timepicker_close").val("");
		$("#timepicker_close").animate({'backgroundColor' : '#F0F0F0'});
	} else {
		$("#timepicker_open").removeAttr("disabled");
		$("#timepicker_open").css("background-color", "")
		$("#timepicker_close").removeAttr("disabled");
		$("#timepicker_close").css("background-color", "")
	}
	if (vacationTypes[$("#timecard_vacation_type").val()]["vacation_comment"]) {
		popmsg(vacationTypes[$("#timecard_vacation_type").val()]["vacation_comment"]);
	}
}
</script>