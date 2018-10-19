<?php
/*
 * kagen.yu. All Rights Reserved.
 * 文字コード UTF-8
 */
require_once('../application/json.php');
?>
<form id="from_overtimecomment" name="overtimecomment" onsubmit="return false">
<?php
	if (strlen($_GET['member']) > 0 && isset($_GET['member'])){
		echo '<input type="hidden" name="member" value="'.$_GET['member'].'" >';
	}
	if ($hash['overtime_comment']['id']){
		echo '<input type="hidden" name="overtime_comment_id" value="'.$hash['overtime_comment']['id'].'" >';
	}
?>
	<span>申請者<?=($hash['overtime_comment']['applicant']?"（".$hash['overtime_comment']['applicant']."）":"") ?>からのコメント</span>
	<?php if ($hash['target_user'] == $_SESSION['userid'] && !$hash['fix'] && $hash['overtime_exceeded']): ?>
		<textarea name="comment" rows="4" style="width:100%; margin:5px 0"><?=$hash['overtime_comment']['comment']?></textarea>
		<div class="layerlistsubmit"><input id="btn-overtimecomment" type="button" value="　送信　" onclick="Timecard.postOvertimeComment(1)" /></div>
	<?php else: ?>
		<textarea name="comment" rows="4" style="width:100%; margin:5px 0" disabled="disabled"><?=$hash['overtime_comment']['comment']?></textarea>
	<?php endif; ?>
	<br><br>

	<?php
		// 上司のコメントがまだ書いてない且つ上司である、または上司コメントは自分が書いたなら編集は可能、勿論〆切までだ
		if (((!$hash['overtime_comment']['timecard_overtime_supervisor_userid'] && isset($hash['user_is_boss_of_target_user']) && $hash['user_is_boss_of_target_user'] == true) ||
			  $hash['overtime_comment']['timecard_overtime_supervisor_userid'] == $_SESSION['userid']) && !$hash['fix'] && $hash['overtime_comment']['id'] && $hash['overtime_exceeded']): ?>
		<span>上司からのコメント</span><span style="float: right;"><input type="checkbox" id="chksyounin" name="syounin" value=1 <?=($hash['overtime_comment']['timecard_overtime_approved']?'checked="checked"':'') ?>/><label for="syounin">承認</label><span class="necessary">(必須)</span></span>
		<textarea name="timecard_overtime_supervisor_comment" rows="4" style="width:100%; margin:5px 0"><?=$hash['overtime_comment']['timecard_overtime_supervisor_comment']?></textarea>
		<div class="layerlistsubmit"><input id="btn-overtimecomment" type="button" value="　送信　" onclick="Timecard.postOvertimeComment(0)" /></div>
	<?php else: ?>
		<span>上司<?=($hash['overtime_comment']['authorizer']?"（".$hash['overtime_comment']['authorizer']."）":"") ?>からのコメント</span><span style="float: right;"><input type="checkbox" name="syounin" value=1 <?=($hash['overtime_comment']['timecard_overtime_approved']?'checked="checked"':'') ?> disabled="disabled"/><label for="syounin">承認</label></span>
		<textarea name="timecard_overtime_supervisor_comment" rows="4" style="width:100%; margin:5px 0" disabled="disabled"><?=$hash['overtime_comment']['timecard_overtime_supervisor_comment']?></textarea>
	<?php endif; ?>
</form>
