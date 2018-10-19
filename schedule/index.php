<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('スケジュール');
$calendar = new Calendar;
$data = $calendar->prepare($hash['list'], $_GET['year'], $_GET['month'], 1, $_GET['year'], $_GET['month'], date('t', mktime(0, 0, 0, $_GET['month'], 1, $_GET['year'])));
$timestamp = mktime(0, 0, 0, $_GET['month'], 1, $_GET['year']);
$previous = mktime(0, 0, 0, $_GET['month']-1, 1, $_GET['year']);
$next = mktime(0, 0, 0, $_GET['month']+1, 1, $_GET['year']);
if (strlen($hash['owner']['realname']) > 0 && (isset($_GET['member']) || $hash['owner']['userid'] != $_SESSION['userid'])) {
	$caption = ' - '.$hash['owner']['realname'];
}
?>
<div class="contentcontrol">
	<h1>スケジュール<?=$caption?></h1>
	<table style="border-spacing:0;border-collapse:collapse;"><tr>
		<td><a class="current" href="index.php">カレンダー</a></td>
		<td><a href="groupweek.php<?=$calendar->parameter($_GET['year'], $_GET['month'], $_GET['day'], array('group'=>'', 'member'=>'', 'facility'=>''))?>">グループ</a></td>
		<td><a href="facilityweek.php<?=$calendar->parameter($_GET['year'], $_GET['month'], $_GET['day'], array('group'=>'', 'member'=>'', 'facility'=>''))?>">施設</a></td>
	</tr></table>
	<div class="clearer"></div>
</div>
<div class="scheduleheaderright">
<?=$calendar->selector('groupweek', $hash['owner']['groupuser'], $hash['group'], $hash['owner']['userid'])?>
</div>

<table class="schedule" style="border-spacing:0;border-collapse:collapse;">
<tr><th class="sunday">日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th class="saturday">土</th></tr>
<?php
$lastday = date('t', $timestamp);
for ($i = 0; $i <= 5; $i++) {
	echo '<tr>';
	for ($j = 0; $j <= 6; $j++) {
		$day = $i * 7 + $j - date('w', $timestamp) + 1;
		if ($day < 1 || $day > $lastday) {
			$schedule = '&nbsp;';
		} else {
			$sql_dt = date('Y-m-d',mktime(0,0,0, $_GET['month'], $day, $_GET['year']));
			$schedule = sprintf('<a href="view.php%s">%s</a>', $calendar->parameter($_GET['year'], $_GET['month'], $day), $day);
			$schedule .= sprintf('&nbsp;<a href="add.php?year=%s&month=%s&day=%s&member=%s&caller=%s">＋</a>',$_GET['year'],$_GET['month'],$day,(isset($_GET['member'])?$GET_['member']:$_SESSION['userid']),"index.php");
			$schedule .= (isset($hash['holiday'][$sql_dt])?'<span class="font-red">'.$hash['holiday'][$sql_dt].'</span>':'');
			if (is_array($data[$day]) && count($data[$day]) > 0) {
				foreach ($data[$day] as $row) {
					$parameter = $calendar->parameter($_GET['year'], $_GET['month'], $day, array('id'=>$row['id']));
					$schedule .= sprintf('<br /><a class="truncate" style="padding:2px;background:%s;color:%s;" href="view.php%s"%s>%s%s</a>', $row['schedule_event_bgcolor'], $row['schedule_event_fontcolor'], $parameter, $calendar->share($row), $row['schedule_starttime'], ($row['schedule_event_disp']?"（":"").$row['schedule_event_disp'].($row['schedule_event_disp']?"）":"").$row['schedule_title']);
				}
			}
		}
		echo '<td'.$calendar->style($_GET['year'], $_GET['month'], $day, $j, $lastday).'>'.$schedule.'</td>';
	}
	echo '</tr>';
	if ($day >= $lastday) {
		break;
	}
}
?>
</table>
<div class="schedulenavigation"><a href="index.php<?=$calendar->parameter(date('Y', $previous), date('n', $previous))?>">前の月</a><span class="separator">|</span>
<a href="index.php<?=$calendar->parameter()?>">今月</a><span class="separator">|</span>
<a href="index.php<?=$calendar->parameter(date('Y', $next), date('n', $next))?>">次の月</a></div>

<div id='calendar'></div>

<script type="text/javascript">
$('.truncate').truncate({
	width: 'auto',
	token: '&hellip;',
	side: 'right',
	addclass: false,
	addtitle: true
});

$(document).ready(function() {
	$('#calendar').fullCalendar({
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay,listMonth'
		},
		theme: false,
		buttonIcons: false, // show the prev/next text
		defaultDate: '<?=date("Y-m-d")?>',
		navLinks: true, // can click day/week names to navigate views
		editable: true,
		/*
		dayRender: function (date, cell) {
			console.log(date._d);
			if ($.inArray(date, holidays) > 0) {
				cell.css("background-color", "red");
			}
		},
		*/
		eventLimit: true, // allow "more" link when too many events
		eventSources: [
		{
			url: '/' + window.location.pathname.split('/')[1] + '/schedule/json.php',
			type: 'GET',
			color: 'yellow',
			textColor: 'black'
		},
		{
			url: '/' + window.location.pathname.split('/')[1] + '/schedule/jsonholidays.php',
			type: 'GET',
			color: 'red',
			textColor: 'black'
		}
		],
		height: 750
	});
});
</script>
<?php
$view->footing();
?>