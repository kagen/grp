<?php
/*
 * kagen
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('時間外申請');
$week = array('日', '月', '火', '水', '木', '金', '土');
$calendar = new Calendar;
?>
<h1>時間外申請・承認</h1>
<ul class="operate">
<?php
	echo '<li><a href="index.php">タイムカードに戻る</a></li>';
?>
</ul>
<form id="overtime_approve" class="content" method="post" action="">
		<table class="timecard" style="border-spacing:0;border-collapse:collapse;width:80%;" border="0">
			<tr>
			<th>日付&nbsp;<?=$helper->selector("sel_date", $hash['sel_date'], $hash['otdate'], ' onChange="Timecard.overtimeapproveredirect();"'.($hash['sel_date']?"":" disabled"), "sel_date", true)?></th>
			<th>部署&nbsp;<?=$helper->selector("sel_user_groupname", $hash['sel_user_groupname'], $hash['otgroupid'], ' onChange="Timecard.overtimeapproveredirect();"'.($hash['sel_user_groupname']?"":" disabled"), "sel_user_groupname", true)?></th>
			<th>申請者&nbsp;<?=$helper->selector("sel_realname", $hash['sel_realname'], $hash['otuserid'], ' onChange="Timecard.overtimeapproveredirect();"'.($hash['sel_realname']?"":" disabled"), "sel_realname", true)?></th>
			<th>出社</th>
			<th>退社</th>
			<th>申請</th>
			<th><input type="submit" class="center-text" value="一括承認" <?=(is_array($hash['overtimelist']) && count($hash['overtimelist']) > 0?"":"disabled") ?>/></th><!-- 6 -->
			</tr>
	<?php
	if (is_array($hash['overtimelist']) && count($hash['overtimelist']) > 0) {
		foreach ($hash['overtimelist'] as $row) {
	?>
			<tr>
				<td><?=date("m月d日",strtotime($row['overtime_date']))?>(<?=$week[date("w",strtotime($row['overtime_date']))]?>)</td>
				<td><?=$row['user_groupname']?></td>
				<td><?=$row['realname']?></td>
				<td <?=($row['timecard_originalopen']!=$row['timecard_open']?'class="timecardupdated" title="'.($row['timecard_originalopen']?$row['timecard_originalopen']:"未打刻").'"':'')?>><?=$row['timecard_open']?></td>
				<td <?=($row['timecard_originalclose']!=$row['timecard_close']?'class="timecardupdated" title="'.($row['timecard_originalclose']?$row['timecard_originalclose']:"未打刻").'"':'')?>><?=$row['timecard_close']?></td>
				<td id="overtimereqs_<?=$row['id']?>"><?=date("H:i",strtotime($row['overtime_time_requested']))?></td>
				<td><input id="overtimeappv_inp<?=$row['id']?>" onblur="convertHankakuTime(this);" ondblclick="copyTime(this);" name="overtime_time_approved[<?=$row['id']?>]" style="width: 60px; height: 12px; ime-mode: inactive;" size="5" placeholder="00：00" class="otinput" /></td>
			</tr>
	<?php
		}
	} else {
		echo '<tr><td colspan="7">時間外承認待ちはありません。</td></tr>';
	}
	?>
		</table>
</form>
<br><br><br>
<h1>時間外制限超過コメント</h1>
<form id="overtime_approv_comment" class="content" method="post" action="">
		<table class="timecard" style="border-spacing:0;border-collapse:collapse;width:80%;" border="0">
			<tr>
			<th style="width:80px;">年月</th>
			<th style="width:80px;">申請者</th>
			<th style="width:30px;">申請合計</th>
			<th style="width:30px;">承認合計</th>
			<th style="width:230px;">申請者コメント</th>
			<th style="width:230px;">上司コメント</th>
			<th style="width:10px;">承認</th>
			</tr>
	<?php
	if (is_array($hash['overtime_comments']) && count($hash['overtime_comments']) > 0) {
		foreach ($hash['overtime_comments'] as $row) {
	?>
			<tr>
				<td><a href="index.php?year=<?=$row['timecard_year']?>&month=<?=$row['timecard_month']?>&member=<?=$row['owner']?>"><?=$row['timecard_year']?>年<?=$row['timecard_month']?>月</a></td>
				<td><?=$row['applicant']?></td>
				<td><?=$row['sum_overtime_requested']?></td>
				<td><?=$row['sum_overtime_approved']?></td>
				<td><?=$row['comment']?></td>
				<td><?=$row['timecard_overtime_supervisor_comment']?></td>
				<td><input type="checkbox" disabled="disabled" <?=($row['timecard_overtime_approved']?'checked="checked"':'') ?>/></td>
			</tr>
	<?php
		}
	} else {
		echo '<tr><td colspan="7">時間外超過コメントはありません。</td></tr>';
	}
	?>
		</table>
</form>

<script type="text/javascript">
function convertHankakuTime(elm) {
	App.convertFullCharaToHalf($(elm));
}
function copyTime(elm) {
	var id = elm.id.replace("overtimeappv_inp","");
	$(elm).val($("#overtimereqs_"+id).text());
}
$(document).ready(function() {
	$("#overtime_approve").validate();
	$(".otinput").each(function() {
		 $(this).rules("add", {overtime: true});
	});
});

</script>
<?php
$view->footing();
?>