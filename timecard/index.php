<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('タイムカード');
$calendar = new Calendar;
if (count($hash['list']) <= 0) {
	$attribute = ' onclick="alert(\'出力するデータがありません。\');return false;"';
}
if (strlen($hash['owner']['realname']) > 0 && (isset($_GET['member']) || $hash['owner']['userid'] != $_SESSION['userid'])) {
	$caption = ' - '.$hash['owner']['realname'];
}
if (strlen($_GET['member']) > 0 && isset($_GET['member'])){
	echo '<input type="hidden" name="member" value="'.$_GET['member'].'" >';
}
?>
<h1>タイムカード<?=$caption?></h1>
<ul class="operate">
	<li><a href="csv.php<?=$view->positive(array('year'=>$_GET['year'], 'month'=>$_GET['month']))?>"<?=$attribute?>>CSV出力</a></li>
<?php
if (($hash['owner']['userid'] == $_SESSION['userid'] && !$hash['fix']) || $view->authorize('administrator', 'manager')) {
	echo '<li><a href="index.php'.$view->positive(array('year'=>$hash['year_disp'], 'month'=>$hash['month_disp'], 'member'=>$hash['owner']['userid'], 'recalculate'=>1)).'">再計算</a></li>';
} elseif ($hash['owner']['userid'] == $_SESSION['userid'] && $hash['fix']) {
	echo '<li>'.$hash['fix']['timecard_year'].'年'.$hash['fix']['timecard_month'].'月は締めきりました</li>';
}
if ($view->authorize('administrator', 'manager')) {
	echo '<li><a href="index.php'.$view->positive(array('year'=>$hash['year_disp'], 'month'=>$hash['month_disp'], 'member'=>$hash['owner']['userid'], 'fix'=>1)).'">'.($hash['fix']?'再開':'締め').'</a></li>';
}
if ($view->authorize('administrator', 'manager') || $hash['session_user_is_group_leader']) {
	if (isset($hash['overtime_approved_count'])) {
		echo '<li><a href="overtimeapprove.php">時間外承認待ち一覧（'.$hash['overtime_approved_count'].'件）'.(isset($hash['overtime_comment_approved_count'])?'　コメント待ち（'.$hash['overtime_comment_approved_count'].'件）':'').'</a></li>';
	} else {
		echo '<li><a href="overtimeapprove.php">時間外承認待ち一覧へ</a></li>';
	}
}

?>
</ul>
<form class="content" method="post" action="" id="timecard">
	<?=$view->error($hash['error'])?>
	<?php
	if (DISP_HAYADE) {
		$titles = array("日付","出社","退社","勤務","早出","残業","深夜","休出","休深","遅刻","早退","理由","備考","休暇");
	} else {
		$titles = array("日付","出社","退社","勤務","残業","深夜","休出","休深","遅刻","早退","理由","備考","休暇");
	}
	?>
	<table class="timecard" style="border-spacing:0;border-collapse:collapse;">
		<tr><td colspan="<?=($hash['owner']['user_overtime_flg'] == 1?16:14)?>" class="timecardcaption">
			<a href="index.php?year=<?=($hash['month_disp']==1?$hash['year_disp']-1:$hash['year_disp'])?>&amp;month=<?=($hash['month_disp']==1?12:$hash['month_disp']-1)?><?=($_GET['member']?"&amp;member=".$_GET['member']:"") ?>"><img class="schedulearrow" src="../images/arrowprevious.gif"></a>
			<select id="year_disp" name="year" onchange="Timecard.redirect(this)"><?=$helper->option(2000, 2020, $hash['year_disp'])?></select>年&nbsp;
			<select id="month_disp" name="month" onchange="Timecard.redirect(this)"><?=$helper->option(1, 12, $hash['month_disp'])?></select>月
			<a href="index.php?year=<?=($hash['month_disp']==12?$hash['year_disp']+1:$hash['year_disp'])?>&amp;month=<?=($hash['month_disp']==12?1:$hash['month_disp']+1)?><?=($_GET['member']?"&amp;member=".$_GET['member']:"") ?>"><img class="schedulearrow" src="../images/arrownext.gif"></a>
		</td></tr>
		<tr><th style="width:85px;"><?=array_shift($titles)?></th><!-- 1 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 2 -->
		<!--<th>外出</th>-->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 3 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 4 -->
		<?php if (DISP_HAYADE):?>
			<th style="width:40px;"><?=array_shift($titles)?></th><!-- 5 -->
		<?php endif;?>
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 6 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 7 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 8 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 9 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 10 -->
		<th style="width:40px;"><?=array_shift($titles)?></th><!-- 11 -->
		<th style="width:80px;"><?=array_shift($titles)?></th><!-- 12 -->
		<!--<th>外出時間</th>-->
		<th style="width:140px;"><?=array_shift($titles)?></th><!-- 13 -->
		<th style="width:90px;"><?=array_shift($titles)?></th><!-- 14 -->
		<?php if ($hash['owner']['user_overtime_flg'] == 1):?>
			<th style="width:10px;" class="noborder">&nbsp;</th>
			<th style="width:110px;">時間外申請<?=$view->explain('overtime')?></th><!-- 15 -->
		<?php endif;?>
		</tr>


<?php
$timestamp = $hash['timestamp_from'];
$timestamp_to = $hash['timestamp_to'];
$weekday = date('w', $timestamp);
$week = array('日', '月', '火', '水', '木', '金', '土');
$today = array('year'=>date('Y'), 'month'=>date('n'), 'day'=>date('j'));
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
		$data[$row['timecard_day']] = $row;
	}
}

$date_begin = new DateTime( date('Y-m-d',$timestamp) );
$date_end = new DateTime( date('Y-m-d',$timestamp_to) );
$date_end = $date_end->modify( '+1 day' );//DatePeriodが最終日を無視するバグを解決するため
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($date_begin, $interval, $date_end);

$i=1;

$hash['sum']['sum_timecard_work'] = 0;
$hash['sum']['sum_timecard_hayade'] = 0;
$hash['sum']['sum_timecard_overtime'] = 0;
$hash['sum']['sum_timecard_latenightovertime'] = 0;
$hash['sum']['sum_timecard_hd_work'] = 0;
$hash['sum']['sum_timecard_hd_latenightovertime'] = 0;
$hash['sum']['sum_timecard_latecome'] = 0;
$hash['sum']['sum_timecard_earlyleave'] = 0;
$hash['sum']['sum_overtime_requested'] = 0;
$hash['sum']['sum_overtime_approved'] = 0;


foreach ( $period as $dt ) {

	$class = $calendar->style($dt->format('Y'), $dt->format('n'), $dt->format('j'), $dt->format('w'));

	$day = $dt->format('j');
	$sql_dt = $dt->format('Y-m-d');
	$dispday = $dt->format('n月j日');
	$addVacation = '';
	$addOvertime = '';

	//時間外申請
	//未申請
	if (!isset($hash['overtime'][$sql_dt])) {
		$overtime_status = '<span id="'.$sql_dt.'_ot_stat"></span>';
		$addOvertimeInp = '<input id="'.$sql_dt.'_overtime_inp" onchange="postOvertime(this);" onblur="convertTime(this);" name="overtime_time_requested:'.$sql_dt.'" style="width: 40px; height: 12px; display: none; ime-mode: inactive;" size="5" placeholder="00：00" disabled="disabled" data-otstatus="'.$sql_dt.'_overtime_col" class="otinput" />';
		if ($hash['owner']['userid'] == $_SESSION['userid'] && !$hash['fix']) {
			$addOvertime = '<a id="'.$sql_dt.'_overtime" href="javascript:void(0);" onclick="inputOvertime(this);">＋</a>';
		} else {
			$addOvertime = '';
		}
	//申請済
	} else {
		//承認済み
		if ($hash['overtime'][$sql_dt]['overtime_time_approved']) {
			$addOvertime = '<span  data-otstatus="'.$sql_dt.'_overtime_col" ondblclick="delOvertime(this,'.$hash['overtime'][$sql_dt]['id'].')" id="'.$sql_dt.'_overtime"'.($hash['overtime'][$sql_dt]['overtime_time_approved'] != $hash['overtime'][$sql_dt]['overtime_time_requested']?' class="timecardupdated" title="'.$hash['overtime'][$sql_dt]['overtime_time_requested'].'"':'').'>'.$helper->nicetime($hash['overtime'][$sql_dt]['overtime_time_approved']).'</span><span id="'.$sql_dt.'_overtime_ori_reqs" style="display: none;">'.$hash['overtime'][$sql_dt]['overtime_time_requested'].'</span>';
			$overtime_status = '<span id="'.$sql_dt.'_ot_stat"><img class="inlineicon" title="'.$hash['overtime'][$sql_dt]['overtime_approved_at'].' by '.$hash['overtime'][$sql_dt]['overtime_approved_by'].'" src="../images/approved.png"></span>';
			$hash['sum']['sum_overtime_approved'] = $helper->addtime($hash['sum']['sum_overtime_approved'],$hash['overtime'][$sql_dt]['overtime_time_approved']);
		//承認未だ
		} else {
			//申請者本人
			if ($hash['owner']['userid'] == $_SESSION['userid'] && !$hash['fix']) {
				$addOvertime = '<a id="'.$sql_dt.'_overtime" href="javascript:void(0);" ondblclick="inputOvertime(this)">'.$helper->nicetime($hash['overtime'][$sql_dt]['overtime_time_requested'])."</a>";
			} else {
				$addOvertime = '<span>'.$helper->nicetime($hash['overtime'][$sql_dt]['overtime_time_requested']).'</span>';
			}
			$overtime_status = '<span id="'.$sql_dt.'_ot_stat"><img class="inlineicon" title="'.($hash['overtime'][$sql_dt]['updated']?$hash['overtime'][$sql_dt]['updated']:$hash['overtime'][$sql_dt]['created']).'" src="../images/request.png"></span>';
		}
		$addOvertimeInp = '<input id="'.$sql_dt.'_overtime_inp" onchange="postOvertime(this);" onblur="convertTime(this);" name="overtime_time_requested:'.$sql_dt.'" style="width: 40px; height: 12px; display: none; ime-mode: inactive;" size="5" placeholder="00：00" disabled="disabled" data-otstatus="'.$sql_dt.'_overtime_col" class="otinput" value="'.$helper->nicetime($hash['overtime'][$sql_dt]['overtime_time_requested']).'" />';
		$hash['sum']['sum_overtime_requested'] = $helper->addtime($hash['sum']['sum_overtime_requested'],$hash['overtime'][$sql_dt]['overtime_time_requested']);
	}
	$addOvertime .= $addOvertimeInp.$overtime_status;


	if ($hash['owner']['userid'] == $_SESSION['userid'] && (!$calendar->is_holidays($dt->format('Y-m-d')) || $data[$day]['timecard_workday_flg'] == 1) && !$hash['fix']) {
		if($data[$day]['timecard_vacation_approved']){
			$addVacation = '<a title="'.$data[$day]['timecard_vacation_comment'].($hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['allday']==1?" ":"（".date("H:i", strtotime($data[$day]['timecard_vacation_from']))." ～ ".date("H:i", strtotime($data[$day]['timecard_vacation_to']))."）").'" href="vacation.php?year='.$dt->format('Y').'&month='.$dt->format('n').'&day='.$dt->format('j').'">'.$hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['shortname'].'</a>'.'<img class="inlineicon" title="'.$data[$day]['timecard_vacation_approved'].' by '.$data[$day]['timecard_vacation_approved_by'].'" src="../images/approved.png">';
		}elseif($data[$day]['timecard_vacation_requested']){
			$addVacation = '<a title="'.$data[$day]['timecard_vacation_comment'].($hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['allday']==1?" ":"（".date("H:i", strtotime($data[$day]['timecard_vacation_from']))." ～ ".date("H:i", strtotime($data[$day]['timecard_vacation_to']))."）").'" href="vacation.php?year='.$dt->format('Y').'&month='.$dt->format('n').'&day='.$dt->format('j').'">'.$hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['shortname'].'</a>'.'<img class="inlineicon" title="'.$data[$day]['timecard_vacation_requested'].'" src="../images/request.png">';
		}else{
			$addVacation = '<a href="vacation.php?year='.$dt->format('Y').'&month='.$dt->format('n').'&day='.$dt->format('j').'">＋</a>';
		}
	} else {
		if($data[$day]['timecard_vacation_approved']){
			$addVacation = '<a title="'.$data[$day]['timecard_vacation_comment'].($hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['allday']==1?" ":"（".date("H:i", strtotime($data[$day]['timecard_vacation_from']))." ～ ".date("H:i", strtotime($data[$day]['timecard_vacation_to']))."）").'" href="javascript:popmsg(\'編集はできません、読み取り専用です\');">'.$hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['shortname'].'</a>'.'<img class="inlineicon" title="'.$data[$day]['timecard_vacation_approved'].' by '.$data[$day]['timecard_vacation_approved_by'].'" src="../images/approved.png">';
		}elseif($data[$day]['timecard_vacation_requested']){
			$addVacation = '<a title="'.$data[$day]['timecard_vacation_comment'].($hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['allday']==1?" ":"（".date("H:i", strtotime($data[$day]['timecard_vacation_from']))." ～ ".date("H:i", strtotime($data[$day]['timecard_vacation_to']))."）").'" href="javascript:popmsg(\'編集はできません、読み取り専用です\');">'.$hash['vacationinfo'][$data[$day]['timecard_vacation_type']]['shortname'].'</a>'.'<img class="inlineicon" title="'.$data[$day]['timecard_vacation_requested'].'" src="../images/request.png">';
		}
	}

?>
		<tr<?=$class?>>
		<td>
			<?php if($hash['owner']['userid'] == $_SESSION['userid'] && !$hash['fix']):?>
				<a href="edit.php?year=<?=$dt->format('Y')?>&month=<?=$dt->format('n')?>&day=<?=$dt->format('j')?>"><?=$dispday?>(<?=$week[$weekday]?>)</a>
			<?php elseif ($view->authorize('administrator')):?>
				<a href="edit.php?year=<?=$dt->format('Y')?>&month=<?=$dt->format('n')?>&day=<?=$dt->format('j')?>&member=<?=$_GET['member']?>"><?=$dispday?>(<?=$week[$weekday]?>)</a>
			<?php else:?>
				<?=$dispday?>(<?=$week[$weekday]?>)
			<?php endif;?>
		</td>
		<td <?=($data[$day]['timecard_originalopen']!=$data[$day]['timecard_open']?'class="timecardupdated" title="'.($data[$day]['timecard_originalopen']?$data[$day]['timecard_originalopen']:"未打刻").'"':'')?>><?=$data[$day]['timecard_open']?></td>
		<!--<td><?=$data[$day]['timecard_interval']?></td>-->
		<td <?=($data[$day]['timecard_originalclose']!=$data[$day]['timecard_close']?'class="timecardupdated" title="'.($data[$day]['timecard_originalclose']?$data[$day]['timecard_originalclose']:"未打刻").'"':'')?>><?=$data[$day]['timecard_close']?></td>
		<!--<td><?=$data[$day]['timecard_time']?></td>-->
		<!--<td><?=$data[$day]['timecard_timeinterval']?></td>-->
		<td><?=$helper->nicetime($data[$day]['timecard_work'])?></td>
		<?php if (DISP_HAYADE):?>
			<td><?=$helper->nicetime($data[$day]['timecard_hayade'])?></td>
		<?php endif;?>
		<td><?=$helper->nicetime($data[$day]['timecard_overtime'])?></td>
		<td><?=$helper->nicetime($data[$day]['timecard_latenightovertime'])?></td>
		<td><?=$helper->nicetime($data[$day]['timecard_hd_work'])?></td>
		<td><?=$helper->nicetime($data[$day]['timecard_hd_latenightovertime'])?></td>
		<td><?=$helper->nicetime($data[$day]['timecard_latecome'])?></td>
		<td><?=$helper->nicetime($data[$day]['timecard_earlyleave'])?></td>
		<td class="truncate"><?=($data[$day]['timecard_reason_open']?$data[$day]['timecard_reason_open']['reason_desc']:'').($data[$day]['timecard_reason_open'] && $data[$day]['timecard_reason_close']?'・':'').($data[$day]['timecard_reason_close']?$data[$day]['timecard_reason_close']['reason_desc']:'')?></td>
		<td class="truncate"><?=($data[$day]['timecard_comment']?$data[$day]['timecard_comment']:(isset($hash['holiday'][$sql_dt])?'<span class="font-red">'.$hash['holiday'][$sql_dt].'</span>':''))?></td>
		<td class="truncate"><?=$addVacation?></td>
		<?php if ($hash['owner']['user_overtime_flg'] == 1):?>
			<td class="noborder">&nbsp;</td>
			<td id="<?=$sql_dt?>_overtime_col"><?=$addOvertime?></td>
		<?php endif;?>
<?php
	$weekday = ($weekday + 1) % 7;
	$i++;
	$hash['sum']['sum_timecard_work'] = $helper->addtime($hash['sum']['sum_timecard_work'],$data[$day]['timecard_work']);
	$hash['sum']['sum_timecard_hayade'] = $helper->addtime($hash['sum']['sum_timecard_hayade'], $data[$day]['timecard_hayade']);
	$hash['sum']['sum_timecard_overtime'] = $helper->addtime($hash['sum']['sum_timecard_overtime'], $data[$day]['timecard_overtime']);
	$hash['sum']['sum_timecard_latenightovertime'] = $helper->addtime($hash['sum']['sum_timecard_latenightovertime'], $data[$day]['timecard_latenightovertime']);
	$hash['sum']['sum_timecard_hd_work'] = $helper->addtime($hash['sum']['sum_timecard_hd_work'],$data[$day]['timecard_hd_work']);
	$hash['sum']['sum_timecard_hd_latenightovertime'] = $helper->addtime($hash['sum']['sum_timecard_hd_latenightovertime'], $data[$day]['timecard_hd_latenightovertime']);
	$hash['sum']['sum_timecard_latecome'] = $helper->addtime($hash['sum']['sum_timecard_latecome'], $data[$day]['timecard_latecome']);
	$hash['sum']['sum_timecard_earlyleave'] = $helper->addtime($hash['sum']['sum_timecard_earlyleave'], $data[$day]['timecard_earlyleave']);
}
?>
		<tr><td colspan="3" class="timecardtotal">勤務時間合計</td>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_work'])?></td>
		<?php if (DISP_HAYADE):?>
			<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_hayade'])?></td>
		<?php endif;?>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_overtime'])?></td>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_latenightovertime'])?></td>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_hd_work'])?></td>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_hd_latenightovertime'])?></td>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_latecome'])?></td>
		<td class="timecardtotal"><?=$helper->dropsecond($hash['sum']['sum_timecard_earlyleave'])?></td>
		<td colspan="3" class="timecardtotal"></td>

		<?php if ($hash['owner']['user_overtime_flg'] == 1):?>
			<td class="noborder">&nbsp;</td>
			<td class="timecardtotal"><span id="sum_ot_appv">
				<?=$helper->dropsecond($hash['sum']['sum_overtime_approved'])?></span> / <span id="sum_ot_reqs"><?=$helper->dropsecond($hash['sum']['sum_overtime_requested'])?></span>
				<?php if ($helper->time2sec($hash['sum']['sum_overtime_approved']) > $helper->time2sec($hash['timecard_config']['overtimehourlimit']) || $helper->time2sec($hash['sum']['sum_overtime_requested']) > $helper->time2sec($hash['timecard_config']['overtimehourlimit'])) {?>
					<img id="overtimecommenticon" src="../images/comment-editable.png" onclick="Timecard.popovertimecomment();return false;" title="<?=$hash['overtime_comment']['comment']?>">
					<?php if (!$hash['overtime_comment']['comment']):?>
					<script type="text/javascript">
						popmsg("時間外合計が規定を超えた為、コメントをして、上司の承認をもらってください！");
					</script>
					<?php endif;?>
				<?php } else { ?>
					<img id="overtimecommenticon" src="../images/comment.png" onclick="Timecard.popovertimecomment();return false;" title="<?=$hash['overtime_comment']['comment']?>">
				<?php }?>
			</td>
		<?php endif;?>
		</tr>
	</table>
	<div class="property">
	</div>
</form>
<script type="text/javascript">
$('.truncate').truncate({
	width: 'auto',
	token: '&hellip;',
	side: 'right',
	addclass: false,
	addtitle: true
});

function inputOvertime(elm) {
	var ovdate = elm.id.substr(0,10);
	$(elm).hide();
	$("#"+ovdate+"_ot_stat").hide();
	$("#"+elm.id+"_inp").prop("disabled", false);
	$("#"+elm.id+"_inp").show();
	$("#timecard").validate();
	$("#"+elm.id+"_inp").rules('add', {overtime: true});
	$("#"+elm.id+"_inp").focus();
}

function cancelInputOvertime(elm) {
	var ovdate = elm.id.substr(0,10);
	$(elm).prop("disabled", true);
	$(elm).hide();
	$("#"+ovdate+"_overtime").show();
	$("#"+ovdate+"_ot_stat").show();
}

function convertTime(elm) {
	var ovdate = elm.id.substr(0,10);

	if (elm.value == $("#"+ovdate+"_overtime").text() || (elm.value == "" && $("#"+ovdate+"_overtime").text() == "＋")) {
		cancelInputOvertime(elm);
	}
}

function postOvertime(elm) {
	App.convertFullCharaToHalf($(elm));
	Timecard.sendOvertime($(elm));
}
function delOvertime(elm, id) {
	Timecard.delOvertime($(elm), id);
}

$('body').keydown(function(e) {
    if (e.keyCode==13) { //Enter keycode
        $('input:focus').change(); //The blur is to prevent change happen again
    }
});

</script>
<?php
$view->footing();
?>