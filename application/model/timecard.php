<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class Timecard extends ApplicationModel {

	function Timecard() {

		$this->table = DB_PREFIX.'timecard';
		$this->schema = array(
		'timecard_year'=>array(),
		'timecard_month'=>array(),
		'timecard_day'=>array(),
		'timecard_date'=>array(),
		'timecard_open'=>array(),
		'timecard_close'=>array(),
		'timecard_interval'=>array(),
		'timecard_originalopen'=>array(),
		'timecard_originalclose'=>array(),
		'timecard_originalinterval'=>array(),
		'timecard_time'=>array(),
		'timecard_timeinterval'=>array(),
		'timecard_comment'=>array(),
		'timecard_work'=>array(),
		'timecard_hayade'=>array(),
		'timecard_overtime'=>array(),
		'timecard_latenightovertime'=>array(),
		'timecard_hd_work'=>array(),
		'timecard_hd_overtime'=>array(),
		'timecard_hd_latenightovertime'=>array(),
		'timecard_latecome'=>array(),
		'timecard_earlyleave'=>array(),
		'timecard_reason_open'=>array(),
		'timecard_reason_close'=>array(),
		'timecard_admin_comment'=>array(),
		'timecard_vacation_from'=>array(),
		'timecard_vacation_to'=>array(),
		'timecard_vacation_substitute_id'=>array(),
		'timecard_vacation_type'=>array(),
		'timecard_vacation_comment'=>array(),
		'timecard_vacation_requested'=>array(),
		'timecard_vacation_approved'=>array(),
		'timecard_vacation_supervisor_comment'=>array(),
		'timecard_vacation_supervisor_userid'=>array(),
		'timecard_workday_flg'=>array()
		);
	}

	function index() {

		if ($_GET['member'] !== $_SESSION['userid']) {
			$hash['user_is_boss_of_target_user'] = $this->isMySupervisorByUserid($_GET['member'], $_SESSION['userid']);
		}

		if ($hash['user_is_boss_of_target_user']) {
			$member = $this->findOwner($_GET['member'],true);
		} else {
			$member = $this->findOwner($_GET['member']);
		}
		//$this->add(); #もともとあって、意味分からない
		$field = implode(',', $this->schematize());

		$hash = $this->getTimecardRange($_GET['year'], $_GET['month'], $_GET['day']);

		$config = new Config($this->handler);
		$hash['timecard_config'] = $config->configure('timecard');

		$hash['owner'] = $member['owner'];
		$hash['user'] = $member['user'];

		$calendar = new Calendar;
		$hash['holiday'] = $calendar->holidaysdesc();

		$_GET['year'] = $hash['get_year'];
		$_GET['month'] = $hash['get_month'];
		$_GET['day'] = $hash['get_day'];
		$hash['vacationinfo'] = $this->getVacationInfo();

		if ($hash['owner']['userid'] == $_SESSION['userid'] && ($hash['owner']['is_group_leader'] || $_SESSION['authority'] == 'administrator')) {
			$hash['overtime_approved_count'] = $this->overtimeapprovecount($hash['owner']['userid']);
			$hash['overtime_comment_approved_count'] = $this->overtimecommentapprovecount($hash['owner']['userid']);
		}

		$query = sprintf("SELECT %s FROM %s WHERE (timecard_date >= '%s') AND (timecard_date <= '%s') AND (owner = '%s') ORDER BY timecard_date", $field, $this->table, $hash['date_from'], $hash['date_to'], $this->quote($hash['owner']['userid']));
		$hash['list'] = $this->fetchAll($query);

		if ($_GET['fix'] == 1 && $_SERVER['REQUEST_METHOD'] != 'POST') {
			$hash['fix'] = $this->fix($hash['year_disp'], $hash['month_disp'], $hash['owner']['userid'], $hash['date_from'], $hash['date_to']);
		}
		$hash['fix'] = $this->get_fix_status($hash['year_disp'], $hash['month_disp'], $hash['owner']['userid']);

		if ($_GET['recalculate'] == 1 && $_SERVER['REQUEST_METHOD'] != 'POST') {
			$hash['list'] = $this->recalculate($hash);
			$hash['list'] = $this->fetchAll($query);
		}

		foreach ($hash['list'] as $key => $row) {
			if ($row['timecard_reason_open']) {
				$hash['list'][$key]['timecard_reason_open'] = $this->getReason($row['timecard_reason_open']);
			}
			if ($row['timecard_reason_close']) {
				$hash['list'][$key]['timecard_reason_close'] = $this->getReason($row['timecard_reason_close']);
			}
			if ($row['timecard_vacation_supervisor_userid']) {
				$hash['list'][$key]['timecard_vacation_approved_by'] = $this->getUserNameByUserId($row['timecard_vacation_supervisor_userid']);
			}
		}

		//時間外申請
		$otdata = $this->getOvertime($hash['date_from'], $hash['date_to'], $hash['owner']['userid']);
		foreach ($otdata as $ot) {
			$hash['overtime'][$ot['overtime_date']] = $ot;
			$hash['overtime'][$ot['overtime_date']]['overtime_approved_by'] = $this->getUserNameByUserId($ot['overtime_supervisor_userid']);
		}

		//時間外申請コメント
		$query = "SELECT * FROM grp_overtime_comment WHERE timecard_year = '".$hash['year_disp']."' AND timecard_month = '".$hash['month_disp']."' AND owner = '".$hash['owner']['userid']."';";
		$hash['overtime_comment'] = $this->fetchOne($query);

		$hash['session_user_is_group_leader'] = $this->isGroupLeader($_SESSION['userid']);
		return $hash;

	}

	function fix($year, $month, $userid, $fixec_from, $fixed_to) {
		$sql = sprintf("SELECT COUNT(*) AS cnt FROM ".DB_PREFIX."timecard_fixed WHERE timecard_year = %d AND timecard_month = %d AND owner = '%s'", $year, $month, $userid);
		$data = $this->fetchOne($sql);
		if ($data['cnt'] > 0) {
			$sql = sprintf("DELETE FROM ".DB_PREFIX."timecard_fixed WHERE timecard_year = %d AND timecard_month = %d AND owner = '%s'", $year, $month, $userid);
			$this->response = $this->query($sql);
		} else {
			$sql = sprintf("INSERT INTO ".DB_PREFIX."timecard_fixed (timecard_year, timecard_month, owner, fixed_by, fixed_at, fixed_from, fixed_to) VALUE (%d, %d, '%s', '%s', CURRENT_TIMESTAMP, '%s', '%s')", $year, $month, $userid, $_SESSION['userid'], $fixec_from, $fixed_to);
			$this->response = $this->query($sql);
		}
	}

	function get_fix_status($year, $month, $userid) {
		$sql = sprintf("SELECT * FROM ".DB_PREFIX."timecard_fixed WHERE timecard_year = %d AND timecard_month = %d AND owner = '%s'", $year, $month, $userid);
		$data = $this->fetchOne($sql);
		return $data;
	}

	function add() {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = $this->findRecord();
			$time = date('G:i');
			if (isset($_POST['timecard_open']) && !$data && !$data['timecard_open']) {
				$this->post['timecard_year'] = date('Y');
				$this->post['timecard_month'] = date('n');
				$this->post['timecard_day'] = date('j');
				$this->post['timecard_date'] = date('Y-m-d');
				$this->post['timecard_originalopen'] = $time;
				$this->post['timecard_open'] = $time;
				$this->insertPost();
			} elseif (isset($_POST['timecard_open']) && !$data['timecard_open']) {
				$this->post['timecard_year'] = date('Y');
				$this->post['timecard_month'] = date('n');
				$this->post['timecard_day'] = date('j');
				$this->post['timecard_date'] = date('Y-m-d');
				$this->post['timecard_originalopen'] = $time;
				$this->post['timecard_open'] = $time;
				$this->record($this->post);
			} elseif ($data['timecard_open'] && !$data['timecard_close']) {
				if (isset($_POST['timecard_interval'])) {
					if ($data['timecard_interval']) {
						if (preg_match('/.*-[0-9]+:[0-9]+$/', $data['timecard_interval'])) {
							$time = ' '.$time;
						} elseif (preg_match('/.*[0-9]+:[0-9]+$/', $data['timecard_interval'])) {
							$time = '-'.$time;
						}
					}
					$this->post['timecard_originalinterval'] = trim($data['timecard_originalinterval'].$time);
					$this->post['timecard_interval'] = $data['timecard_interval'].$time;
				} elseif (isset($_POST['timecard_close'])) {
					$this->post['timecard_originalclose'] = $time;
					$this->post['timecard_close'] = $time;
					$this->post += $this->sum($data['timecard_open'], $this->post['timecard_close'], $data['timecard_interval']);
				}
				$this->record($this->post);
			}
		}

	}

	function edit() {
		$member = $this->findOwner($_GET['member']);
		$hash = $this->getTimecardRange($_GET['year'], $_GET['month'], $_GET['day']);
		$hash['owner'] = $member['owner'];
		$hash['user'] = $member['user'];
		$hash['fix'] = $this->get_fix_status($hash['year_disp'], $hash['month_disp'], $hash['owner']['userid']);
		if ($hash['fix']) {
			$this->authorize('administrator');
		}
		$hash['data'] = $this->findRecord($_GET['year'], $_GET['month'], $_GET['day'], $_GET['member']);
		$hash['reason'] = $this->listReason();

		$calendar = new Calendar;
		$hash['is_holiday'] = $calendar->is_holidays($hash['get_year']."-".$hash['get_month']."-".$hash['get_day']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->validator('timecard_comment', '内容', array('length:10000', 'line:100'));
			$this->post['timecard_open'] = $this->validatetime($_POST['openhour'], $_POST['openminute']);
			$this->post['timecard_close'] = $this->validatetime($_POST['closehour'], $_POST['closeminute']);
			$this->post['timecard_reason_open'] = $_POST['timecard_reason_open'];
			$this->post['timecard_reason_close'] = $_POST['timecard_reason_close'];
			if (isset($_POST['timecard_workday_flg'])) {
				$this->post['timecard_workday_flg'] = $_POST['timecard_workday_flg'];
			}
			$array = array();
			if (is_array($_POST['intervalopenhour']) && count($_POST['intervalopenhour']) > 0) {
				for ($i = 0; $i < count($_POST['intervalopenhour']); $i++) {
					$open = $this->validatetime($_POST['intervalopenhour'][$i], $_POST['intervalopenminute'][$i]);
					$close = $this->validatetime($_POST['intervalclosehour'][$i], $_POST['intervalcloseminute'][$i]);
					if (strlen($open) > 0 && strlen($close) > 0) {
						$array[] = $open.'-'.$close;
					}
				}
			}
			$this->post['timecard_interval'] = implode(' ', $array);
			if (strlen($this->post['timecard_close']) > 0) {
				$array = $this->sum($this->post['timecard_open'], $this->post['timecard_close'], $this->post['timecard_interval']);
				$this->post['timecard_time'] = $array['timecard_time'];
				$this->post['timecard_timeinterval'] = $array['timecard_timeinterval'];
			} else {
				$this->post['timecard_time'] = '';
				$this->post['timecard_timeinterval'] = '';
			}
			$this->post['editor'] = $_SESSION['userid'];
			$this->post['updated'] = date('Y-m-d H:i:s');

			if (!$this->post['timecard_open'] && $hash['data']['timecard_originalopen']) {
				$this->post['timecard_open'] = $hash['data']['timecard_originalopen'];
			}

			if (!$this->post['timecard_close'] && $hash['data']['timecard_originalclose']) {
				$this->post['timecard_close'] = $hash['data']['timecard_originalclose'];
			}

			if ($this->post['timecard_open'] && $this->post['timecard_close']) {
				$input_open = strtotime($this->post['timecard_open']);
				$input_close = strtotime($this->post['timecard_close']);
				if ($input_open > $input_close) {
					$this->error[] = '出社時間と退社時間が矛盾してます。ご確認ください。';
				}
			}

			if (!$this->post['timecard_open'] && !$this->post['timecard_close'] && !$hash['data']['timecard_originalopen'] && !$hash['data']['timecard_originalclose'] && !$hash['data']['timecard_vacation_type']) {

				$query = sprintf("DELETE FROM %s WHERE id=%d",$this->table, $hash['data']['id']);
				$this->response = $this->query($query);

			} elseif (is_array($hash['data']) && $hash['data']['id'] > 0 && count($this->error) <= 0) {
				$this->record($this->post, $_GET['year'], $_GET['month'], $_GET['day'], $_GET['member']);
			} else {
				if (!$this->post['timecard_open']) {
					$this->error[] = '出社時間を入力してください。';
				}

				$this->schema += array('editor'=>'', 'updated'=>'');
				$this->post['timecard_year'] = $_GET['year'];
				$this->post['timecard_month'] = $_GET['month'];
				$this->post['timecard_day'] = $_GET['day'];
				$this->post['timecard_date'] = date('Y-m-d', mktime(0, 0, 0, $_GET['month'], $_GET['day'], $_GET['year']));
				if (isset($_GET['member']) && strlen($_GET['member']) > 0) {
					$this->post['owner'] = $_GET['member'];
				}
				$this->insertPost();
			}

			$this->redirect('index.php?year='.$_GET['year'].'&month='.$_GET['month'].'&day='.$_GET['day'].'&member='.$_GET['member']);
			$hash['data'] = $this->post;
		}
		return $hash;

	}

	function record($data, $year = null, $month = null, $day = null, $userid = null) {

		if (is_array($data) && count($data) > 0) {
			if (isset($year) && isset($month) && isset($day)) {
				$date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
			} else {
				$date = date('Y-m-d');
			}
			foreach ($data as $key => $value) {
				$array[] = ($value === null? $key." = NULL":$key." = '".$this->quote($value)."'");
			}
			if (!$userid) {
				$userid = $_SESSION['userid'];
			}
			$query = sprintf("UPDATE %s SET %s WHERE (timecard_date = '%s') AND (owner = '%s')", $this->table, implode(",", $array), $date, $this->quote($userid));
			$this->response = $this->query($query);
			return $this->response;
		}

	}

	function recordfixedovertime($data, $otdate, $userid = null) {

		if (is_array($data) && count($data) > 0) {
			if (!$userid) {
				$userid = $_SESSION['userid'];
			}
			$query = sprintf("UPDATE grp_overtime SET
					overtime_fixed_normal_overtime = '%s', overtime_fixed_latenightovertime = '%s',
					overtime_fixed_hd_worktime = '%s', overtime_fixed_hd_latenightovertime = '%s'
					WHERE (overtime_date = '%s') AND (owner = '%s')", $data['overtime_fixed_normal_overtime'], $data['overtime_fixed_latenightovertime'], $data['overtime_fixed_hd_worktime'], $data['overtime_fixed_hd_latenightovertime'], $otdate, $userid);
			$this->response = $this->query($query);
			return $this->response;
		}

	}

	function sum($open, $close, $interval = '') {

		$open = $this->minute($open);
		$close = $this->minute($close);
		$config = new Config($this->handler);
		$status = $config->configure('timecard');
		$status['open'] = intval($status['openhour']) * 60 + intval($status['openminute']);
		if ($open < $status['open']) {
			$open = $status['open'];
		}
		$status['close'] = intval($status['closehour']) * 60 + intval($status['closeminute']);
		if ($status['close'] > 0 && $close > $status['close']) {
			$close = $status['close'];
		}
		if ($status['timeround'] == 1) {
			$open = ceil($open / 10) * 10;
			$close = floor($close / 10) * 10;
		}
		$status['lunchopen'] = intval($status['lunchopenhour']) * 60 + intval($status['lunchopenminute']);
		$status['lunchclose'] = intval($status['lunchclosehour']) * 60 + intval($status['lunchcloseminute']);
		if ($status['intervalround'] == 1) {
			$status['lunchopen'] = floor($status['lunchopen'] / 10) * 10;
			$status['lunchclose'] = ceil($status['lunchclose'] / 10) * 10;
		}
		$intervalsum = 0;
		if (strlen($interval) > 0) {
			$array = explode(' ', $interval);
			if (is_array($array) && count($array) > 0) {
				foreach ($array as $key => $value) {
					list($intervalopen, $intervalclose) = explode('-', $value);
					$intervalopen = $this->minute($intervalopen);
					$intervalclose = $this->minute($intervalclose);
					if ($status['intervalround'] == 1) {
						$intervalopen = floor($intervalopen / 10) * 10;
						$intervalclose = ceil($intervalclose / 10) * 10;
					}
					if ($intervalclose <= $status['lunchopen'] || $intervalopen >= $status['lunchclose']) {
						if ($intervalopen < $intervalclose) {
							$intervalsum += $intervalclose - $intervalopen;
						}
					} else {
						if ($intervalopen < $status['lunchopen']) {
							$status['lunchopen'] = $intervalopen;
						}
						if ($intervalclose > $status['lunchclose']) {
							$status['lunchclose'] = $intervalclose;
						}
					}
				}
			}
		}
		if ($status['lunchopen'] < $status['lunchclose']) {
			$intervalsum += $status['lunchclose'] - $status['lunchopen'];
		}
		$sum = $close - $open - $intervalsum;
		if ($sum < 0) {
			$sum = 0;
		}
		$result['timecard_time'] = sprintf('%d:%02d', (($sum - ($sum % 60)) / 60), ($sum % 60));
		$result['timecard_timeinterval'] = sprintf('%d:%02d', (($intervalsum - ($intervalsum % 60)) / 60), ($intervalsum % 60));
		return $result;

	}

	function minute($time) {

		$array = explode(':', $time);
		return intval($array[0]) * 60 + intval($array[1]);

	}

	function findRecord($year = null, $month = null, $day = null, $userid = null) {

		if (isset($year) && isset($month) && isset($day)) {
			$date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
		} else {
			$date = date('Y-m-d');
		}
		$field = implode(',', $this->schematize());
		if (!$userid) {
			$userid = $_SESSION['userid'];
		}
		$query = sprintf("SELECT %s FROM %s WHERE (timecard_date = '%s') AND (owner = '%s')", $field, $this->table, $date, $this->quote($userid));
		return $this->fetchOne($query);

	}

	function validatetime($hour, $minute) {

		if (strlen($hour) > 0 && strlen($minute) > 0 && $hour >= 0 && $hour < 24 && $minute >= 0 && $minute < 60) {
			return sprintf('%d:%02d', intval($hour), intval($minute));
		}

	}

	function recalculate($data) {

		if ($data['fix']) {
			$this->authorize('administrator');
		}

		if (is_array($data['list']) && count($data['list']) > 0) {
			$this->response = true;
			$calendar = new Calendar;

			foreach ($data['list'] as $key => $row) {
				if (strlen($row['timecard_close']) > 0) {
					$array = $this->calWorkingHours($row['timecard_date'], $row['timecard_open'], $row['timecard_close'], $data['owner']['userid'], $row['timecard_workday_flg']);
					$this->record($array, $row['timecard_year'], $row['timecard_month'], $row['timecard_day'], $row['owner']);
				}
			}
			if ($this->response) {
				$this->error[] = 'タイムカードを再計算結果を保存しました。';
			} else {
				$this->died('タイムカードの再計算に失敗しました。');
			}
		}
		return $data;

	}

	function config() {

		$this->authorize('administrator');
		$config = new Config($this->handler);
		$hash['data'] = $config->edit('timecard');
		$this->error = $config->error;
		return $hash;

	}

	function csv() {

		$field = implode(',', $this->schematize());
		$query = sprintf("SELECT %s FROM %s WHERE (timecard_year = %d) AND (timecard_month = %d) AND (owner = '%s') ORDER BY timecard_date", $field, $this->table, $_GET['year'], $_GET['month'], $this->quote($_SESSION['userid']));
		$list = $this->fetchAll($query);
		if (is_array($list) && count($list) > 0) {
			$csv = $_GET['year'].'年'.$_GET['month'].'月'."\n";
			$csv .= '"日付","出社","外出","退社","勤務時間","外出時間","備考"'."\n";
			$timestamp = mktime(0, 0, 0, $_GET['month'], 1, $_GET['year']);
			$lastday = date('t', $timestamp);
			$weekday = date('w', $timestamp);
			$week = array('日', '月', '火', '水', '木', '金', '土');
			foreach ($list as $row) {
				$data[$row['timecard_day']] = $row;
			}
			$sum = 0;
			for ($i = 1; $i <= $lastday; $i++) {
				if (strlen($data[$i]['timecard_time']) > 0) {
					$array = explode(':', $data[$i]['timecard_time']);
					$sum += intval($array[0]) * 60 + intval($array[1]);
				}
				$csv .= '"'.$i.'('.$week[$weekday].')","';
				$csv .= $data[$i]['timecard_open'].'","';
				$csv .= $data[$i]['timecard_interval'].'","';
				$csv .= $data[$i]['timecard_close'].'","';
				$csv .= $data[$i]['timecard_time'].'","';
				$csv .= $data[$i]['timecard_timeinterval'].'","';
				$csv .= $data[$i]['timecard_comment'].'"'."\n";
				$weekday = ($weekday + 1) % 7;
			}
			$csv .= '"勤務時間合計","'.sprintf('%d:%02d', (($sum - ($sum % 60)) / 60), ($sum % 60)).'"'."\n";
			header('Content-Disposition: attachment; filename=timecard'.date('Ymd').'.csv');
			header('Content-Type: application/octet-stream; name=timecard'.date('Ymd').'.csv');
			echo mb_convert_encoding($csv, 'SJIS', 'UTF-8');
			exit();
		} else {
			$this->died('データが見つかりません。');
		}

	}

	function group() {


		$hash = $this->getTimecardRange($_GET['year'], $_GET['month'], $_GET['day']);
		$_GET['year'] = $hash['get_year'];
		$_GET['month'] = $hash['get_month'];
		$_GET['day'] = $hash['get_day'];

		$this->authorize('administrator', 'manager');

		if ($_GET['group'] != 'all' && $_GET['group'] <= 0) {
			$_GET['group'] = $_SESSION['group'];
		}
		if ($_GET['group'] == 'all') {
			$sql = "SELECT userid, realname FROM ".DB_PREFIX."user WHERE (DATE_ADD(user_retired, INTERVAL 2 MONTH) > CURRENT_TIMESTAMP OR user_retired IS NULL OR user_retired = '0000-00-00') ORDER BY user_code,id";
		} else {
			$sql = "SELECT DISTINCT grp_user.userid, grp_user.realname FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE (grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).")) AND (DATE_ADD(grp_user.user_retired, INTERVAL 2 MONTH) > CURRENT_TIMESTAMP OR grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00') ORDER BY grp_user.user_code,grp_user.id";
		}
		$data = $this->fetchAll($sql);
		$hash['user'] = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$hash['user'][$row['userid']] = $row['realname'];
			}
			$user = implode("','", array_keys($hash['user']));
			$field = implode(',', $this->schematize());
			$query = sprintf("SELECT %s FROM %s WHERE (timecard_date >= '%s') AND (timecard_date <= '%s') AND (owner IN ('%s')) ORDER BY timecard_date", $field, $this->table,  $hash['date_from'], $hash['date_to'], $user);
			$hash['list'] = $this->fetchAll($query);
			$query = sprintf("SELECT owner FROM %s WHERE timecard_year = '%s' AND timecard_month = '%s' AND owner IN ('%s')", DB_PREFIX."timecard_fixed",  $hash['year_disp'], $hash['month_disp'], $user);
			$hash['fixed'] = $this->fetchAll($query);
		}
		$hash['group'] = $this->findGroup();

		return $hash;

	}


	function excel() {
		$week = array('日', '月', '火', '水', '木', '金', '土');

		$hash = $this->getTimecardRange($_GET['year'], $_GET['month'], $_GET['day']);
		$_GET['year'] = $hash['get_year'];
		$_GET['month'] = $hash['get_month'];
		$_GET['day'] = $hash['get_day'];

		$this->authorize('administrator');

		if ($_GET['group'] != 'all' && $_GET['group'] <= 0) {
			$_GET['group'] = $_SESSION['group'];
		}
		if ($_GET['group'] == 'all') {
			$sql = "SELECT * FROM grp_user WHERE (DATE_ADD(user_retired, INTERVAL 2 MONTH) > CURRENT_TIMESTAMP OR user_retired IS NULL OR user_retired = '0000-00-00') ORDER BY user_code,id";
		} else {
			$sql = "SELECT grp_user.* FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE (DATE_ADD(grp_user.user_retired, INTERVAL 2 MONTH) > CURRENT_TIMESTAMP OR grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00') AND (grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).")) ORDER BY grp_user.user_code,grp_user.id";
		}
		$hash['user'] = $this->fetchAll($sql);
		$hash['data'] = array();

		$calendar = new Calendar;
		$helper = new Helper;
		$config = new Config($this->handler);
		$hash['config'] = $config->configure('timecard');

		#標準出社時間
		$stdworkstart = strtotime($hash['config']['openhour'].":".$hash['config']['openminute']);
		$stdworkend = strtotime($hash['config']['closehour'].":".$hash['config']['closeminute']);

		#ユーザの出社時間特別に設定されてなければ、標準出社時間を使う
		if($hash['user']['user_openhour'] && $hash['user']['user_openminute']){
			$userworkstart = strtotime($hash['user']['user_openhour'].":".$hash['user']['user_openminute']);
		}else{
			$userworkstart = $stdworkstart;
		}
		#ユーザの退社時間特別に設定されてなければ、標準退社時間を使う
		if($hash['user']['user_closehour'] && $hash['user']['user_closeminute']){
			$userworkend = strtotime($hash['user']['user_closehour'].":".$hash['user']['user_closeminute']);
		}else{
			$userworkend = $stdworkend;
		}

		#標準休憩時間
		$stdlunchstart = strtotime($hash['config']['lunchopenhour'].":".$hash['config']['lunchopenminute']);
		$stdlunchend = strtotime($hash['config']['lunchclosehour'].":".$hash['config']['lunchcloseminute']);

		$userworktime = gmdate('H:i:s',$userworkend - $userworkstart - ($stdlunchend - $stdlunchstart));

		if (is_array($hash['user']) && count($hash['user']) > 0) {

			$hash['data']['サマリー'] = array();

			foreach ($hash['user'] as $row) { //社員ループ
				$query = sprintf("SELECT ".
									"grp_timecard.timecard_date AS '月日', ".
									"grp_timecard.timecard_open AS '出勤', ".
									"grp_timecard.timecard_close AS '退勤', ".
									"grp_timecard.timecard_work AS '勤務時間', ".
									"grp_timecard.timecard_overtime AS '残業', ".
									"grp_timecard.timecard_latenightovertime AS '深夜', ".
									"grp_timecard.timecard_hd_work AS '休日出勤', ".
									"grp_timecard.timecard_hd_latenightovertime AS '休日深夜', ".
									"grp_timecard.timecard_latecome AS '遅刻', ".
									"grp_timecard.timecard_earlyleave AS '早退', ".
									"SEC_TO_TIME(TIME_TO_SEC(grp_timecard.timecard_latecome) + TIME_TO_SEC(grp_timecard.timecard_earlyleave)) AS '遅刻早退', ".
									"CONCAT(IFNULL(latecome.reason_desc,''), IF(latecome.id IS NULL OR earlyleave.id IS NULL,'',' / '), IFNULL(earlyleave.reason_desc,'')) AS '事由', ".
									"grp_timecard.timecard_comment AS '備考', ".
									"grp_vacation.vacation_name AS '休暇タイプ', ".
									"grp_timecard.timecard_vacation_from AS '時間給開始', ".
									"grp_timecard.timecard_vacation_to AS '時間給終了', ".
									"SEC_TO_TIME(TIME_TO_SEC(grp_timecard.timecard_vacation_to) - TIME_TO_SEC(grp_timecard.timecard_vacation_from)) AS '有給時間', ".
									"grp_timecard.timecard_vacation_comment AS '休暇コメント', ".
									"supervisor.realname AS '休暇承認者', ".
									"grp_timecard.timecard_vacation_approved AS '休暇承認時刻', ".
									"grp_timecard.timecard_workday_flg AS '平日扱いフラグ', ".
									"grp_overtime.overtime_fixed_normal_overtime AS '普通残業(支給)', ".
									"grp_overtime.overtime_fixed_latenightovertime AS '深夜残業(支給)', ".
									"grp_overtime.overtime_fixed_hd_worktime AS '休日勤務(支給)', ".
									"grp_overtime.overtime_fixed_hd_latenightovertime AS '休日深夜(支給)' ".
								"FROM grp_timecard ".
								"LEFT JOIN grp_reason latecome ON latecome.id = grp_timecard.timecard_reason_open ".
								"LEFT JOIN grp_reason earlyleave ON earlyleave.id = grp_timecard.timecard_reason_close ".
								"LEFT JOIN grp_vacation ON grp_vacation.id = grp_timecard.timecard_vacation_type ".
								"LEFT JOIN grp_user supervisor ON supervisor.userid = grp_timecard.timecard_vacation_supervisor_userid ".
								"LEFT JOIN grp_overtime ON grp_overtime.overtime_date = grp_timecard.timecard_date AND grp_overtime.owner = grp_timecard.owner ".
								"WHERE ".
									"grp_timecard.timecard_date >= '%s' AND grp_timecard.timecard_date <= '%s' ".
									"AND grp_timecard.owner = '%s' ORDER BY grp_timecard.timecard_date", $hash['date_from'], $hash['date_to'], $row['userid']);
				if (isset($hash['data'][$row['realname']])) {
					$sheetname = $row['realname'].'('.$row['userid'].')';
				} else {
					$sheetname = $row['realname'];
				}
				$timecard_data = $this->fetchAll($query);
				$timecard = array();
				foreach ($timecard_data as $datarow) {
					if ($datarow['時間給開始'] && $datarow['時間給終了']) {
						$vstarttime = strtotime($datarow['時間給開始']);
						$vendtime = strtotime($datarow['時間給終了']);
						if ($vstarttime <= $stdlunchstart && $vendtime >= $stdlunchend) {
							$datarow['有給時間'] = $helper->addtime($datarow['有給時間'],"-".gmdate("H:i:s", $stdlunchend - $stdlunchstart));
						}
					}
					$timecard[$datarow['月日']] = $datarow;
				}

				$result = array();
				$idx = 0;

				$sumresult = array(
						'所定就労日数' => 0,
						'平日出勤日数' => 0,
						'休日出勤日数' => 0,
						'欠勤・その他休日数' => 0,
						'振替休日日数' => 0,
						'振替代休時間' => '00:00',
						'特別休暇日数' => 0,
						'有給取得日数' => 0,
						'時間有給合計' => '00:00',
						'所定労働時間' => '00:00'
				);

				$hidden_sumresult = array(
					'普通残業(支給)' => '00:00',
					'深夜残業(支給)' => '00:00',
					'休日勤務(支給)' => '00:00',
					'休日深夜(支給)' => '00:00'
				);



				// ServerのPHPは5.3.3ですので、DatePeriodオブジェクトは一回再利用できないみたいので、ループの中に入れました
				$date_begin = new DateTime($hash['date_from']);
				$date_end = new DateTime($hash['date_to']);
				$date_end = $date_end->modify( '+1 day' );//DatePeriodが最終日を無視するバグを解決するため
				$interval = DateInterval::createFromDateString('1 day');
				$period = new DatePeriod($date_begin, $interval, $date_end);


				foreach ( $period as $dt ) { //タイムカード日付ループ

					//所定就労日数は「平日扱い」フラグと関係ないとします
					if(!$calendar->is_holidays($dt->format('Y-m-d'))){
						$sumresult['所定就労日数']++;
						$sumresult['所定労働時間'] = $helper->addtime($sumresult['所定労働時間'], $userworktime);
					}

					$result[$idx]['月日'] = $dt->format('Y-m-d').'（'.$week[$dt->format('w')].'）';
					if (isset($timecard[$dt->format('Y-m-d')])) {
						$result[$idx]['出勤'] = $timecard[$dt->format('Y-m-d')]['出勤'];
						$result[$idx]['退勤'] = $timecard[$dt->format('Y-m-d')]['退勤'];
						$result[$idx]['勤務時間'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['勤務時間']);
						$result[$idx]['残業'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['残業']);
						$result[$idx]['深夜'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['深夜']);
						$result[$idx]['休日出勤'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['休日出勤']);
						$result[$idx]['休日深夜'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['休日深夜']);
						$result[$idx]['遅刻'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['遅刻']);
						$result[$idx]['早退'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['早退']);
						$result[$idx]['事由'] = $timecard[$dt->format('Y-m-d')]['事由'];
						$result[$idx]['備考'] = $timecard[$dt->format('Y-m-d')]['備考'].($timecard[$dt->format('Y-m-d')]['平日扱いフラグ'] == 1?'（振休取得・平日扱い）':'');
						if ($timecard[$dt->format('Y-m-d')]['休暇タイプ']) {
							$result[$idx]['休暇'] = $timecard[$dt->format('Y-m-d')]['休暇タイプ'].
							($timecard[$dt->format('Y-m-d')]['休暇承認時刻']?'[承認済→'.$timecard[$dt->format('Y-m-d')]['休暇承認者'].'] ':'[未承認] ').
							($timecard[$dt->format('Y-m-d')]['時間給開始'] && $timecard[$dt->format('Y-m-d')]['時間給終了']?'（'.$helper->dropsecond($timecard[$dt->format('Y-m-d')]['時間給開始']).' ～ '.$helper->dropsecond($timecard[$dt->format('Y-m-d')]['時間給終了']).'）':'').
							($timecard[$dt->format('Y-m-d')]['休暇コメント']?$timecard[$dt->format('Y-m-d')]['休暇コメント']:'');
						} else {
							$result[$idx]['休暇'] = null;
						}

						//平日出勤日数・休日出勤日数は「平日扱い」フラグに左右されません
						if (!$calendar->is_holidays($dt->format('Y-m-d')) && $timecard[$dt->format('Y-m-d')]['勤務時間']) {
							$sumresult['平日出勤日数']++;
						} elseif ($calendar->is_holidays($dt->format('Y-m-d')) && $timecard[$dt->format('Y-m-d')]['勤務時間']) {
							$sumresult['休日出勤日数']++;
						}

						switch ($timecard[$dt->format('Y-m-d')]['休暇タイプ']) {
							case '欠勤・その他休':
								$sumresult['欠勤・その他休日数']++;
								break;
							case '有給休暇':
								$sumresult['有給取得日数']++;
								break;
							case '振替休日':
								$sumresult['振替休日日数']++;
								break;
							case '特別休暇':
								$sumresult['特別休暇日数']++;
								break;
							case '時間有給':
								$sumresult['時間有給合計'] = $helper->addtime($sumresult['時間有給合計'], $timecard[$dt->format('Y-m-d')]['有給時間']);
								break;
							case '時間振休':
								$sumresult['振替代休時間'] = $helper->addtime($sumresult['振替代休時間'], $timecard[$dt->format('Y-m-d')]['有給時間']);
								break;
						}
						$result[$idx]['普通残業(支給)'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['普通残業(支給)']);
						$result[$idx]['深夜残業(支給)'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['深夜残業(支給)']);
						$result[$idx]['休日勤務(支給)'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['休日勤務(支給)']);
						$result[$idx]['休日深夜(支給)'] = $helper->dropsecond($timecard[$dt->format('Y-m-d')]['休日深夜(支給)']);

					} else {
						$result[$idx]['出勤'] = null;
						$result[$idx]['退勤'] = null;
						$result[$idx]['勤務時間'] = null;
						$result[$idx]['残業'] = null;
						$result[$idx]['深夜'] = null;
						$result[$idx]['休日出勤'] = null;
						$result[$idx]['休日深夜'] = null;
						$result[$idx]['遅刻'] = null;
						$result[$idx]['早退'] = null;
						$result[$idx]['事由'] = null;
						$result[$idx]['備考'] = null;
						$result[$idx]['休暇'] = null;
						$result[$idx]['普通残業(支給)'] = null;
						$result[$idx]['深夜残業(支給)'] = null;
						$result[$idx]['休日勤務(支給)'] = null;
						$result[$idx]['休日深夜(支給)'] = null;

					}
					$idx++;
					$hidden_sumresult['普通残業(支給)'] = $helper->addtime($hidden_sumresult['普通残業(支給)'], $timecard[$dt->format('Y-m-d')]['普通残業(支給)']);
					$hidden_sumresult['深夜残業(支給)'] = $helper->addtime($hidden_sumresult['深夜残業(支給)'], $timecard[$dt->format('Y-m-d')]['深夜残業(支給)']);
					$hidden_sumresult['休日勤務(支給)'] = $helper->addtime($hidden_sumresult['休日勤務(支給)'], $timecard[$dt->format('Y-m-d')]['休日勤務(支給)']);
					$hidden_sumresult['休日深夜(支給)'] = $helper->addtime($hidden_sumresult['休日深夜(支給)'], $timecard[$dt->format('Y-m-d')]['休日深夜(支給)']);
				}
				$sumresult['所定労働時間'] = $helper->dropsecond($sumresult['所定労働時間']);
				$sumresult['振替代休時間'] = $helper->dropsecond($sumresult['振替代休時間']);
				$sumresult['時間有給合計'] = $helper->dropsecond($sumresult['時間有給合計']);

				$hash['data'][$sheetname]['A1'] = $row['realname'].'（社員番号：'.$row['user_code'].'）';
				$hash['data'][$sheetname]['D1'] = $hash['year_disp'].'年'.$hash['month_disp'].'月分（'.$hash['date_from'].' ～ '.$hash['date_to'].'）';
				$hash['data'][$sheetname]['A2'] = $result; //Range("A2")から表を書き出す

				$query = "SELECT ".
						"'合計' AS '月日', ".
						"NULL AS '出勤', ".
						"NULL AS '退勤', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_work))), '%H:%i') AS '勤務時間', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_overtime))), '%H:%i') AS '残業', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_latenightovertime))), '%H:%i') AS '深夜', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_hd_work))), '%H:%i') AS '休日出勤', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_hd_latenightovertime))), '%H:%i') AS '休日深夜', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_latecome))), '%H:%i') AS '遅刻', ".
						"TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_timecard.timecard_earlyleave))), '%H:%i') AS '早退', ".
						"NULL AS '事由', ".
						"NULL AS '備考', ".
						"NULL AS '休暇' ".
						"FROM grp_timecard ".
						"WHERE ".
						"grp_timecard.timecard_date >= '".$hash['date_from']."' AND grp_timecard.timecard_date <= '".$hash['date_to']."' ".
						"AND grp_timecard.owner = '".$row['userid']."'";
				$user_indvi_sum = $this->fetchOne($query);
				$user_indvi_sum['普通残業(支給)'] = $helper->dropsecond($hidden_sumresult['普通残業(支給)']);
				$user_indvi_sum['深夜残業(支給)'] = $helper->dropsecond($hidden_sumresult['深夜残業(支給)']);
				$user_indvi_sum['休日勤務(支給)'] = $helper->dropsecond($hidden_sumresult['休日勤務(支給)']);
				$user_indvi_sum['休日深夜(支給)'] = $helper->dropsecond($hidden_sumresult['休日深夜(支給)']);


				$hash['data'][$sheetname]['A2'][] = $user_indvi_sum;
				$hash['data'][$sheetname]['A'.($idx + 6)][] = $sumresult; //タイムカード表の下に
				$colwidth[$sheetname] = array(
						'A' => 18,
						'B' => 10,
						'C' => 10,
						'D' => 10,
						'E' => 10,
						'F' => 10,
						'G' => 10,
						'H' => 10,
						'I' => 10,
						'J' => 10,
						'K' => 20,
						'L' => 30,
						'M' => 40
				);

				$usersummary = array();
				$usersummary['社員番号'] = $row['user_code'];
				$usersummary['氏名'] = $row['realname'];
				$usersummary['所定就労日数'] = $sumresult['所定就労日数'];
				$usersummary['平日出勤日数'] = $sumresult['平日出勤日数'];
				$usersummary['休日出勤日数'] = $sumresult['休日出勤日数'];
				$usersummary['欠勤・その他休日数'] = $sumresult['欠勤・その他休日数'];
				$usersummary['振替休日日数'] = $sumresult['振替休日日数'];
				$usersummary['振替代休時間'] = $helper->dropsecond($sumresult['振替代休時間']);
				$usersummary['特別休暇日数'] = $sumresult['特別休暇日数'];
				$usersummary['有給取得日数'] = $sumresult['有給取得日数'];
				$usersummary['時間有給合計'] = $helper->dropsecond($sumresult['時間有給合計']);
				$usersummary['所定労働時間'] = $helper->dropsecond($sumresult['所定労働時間']);
				$usersummary['勤務時間'] = $helper->dropsecond($user_indvi_sum['勤務時間']);
				$usersummary['遅刻早退時間'] = $helper->dropsecond($helper->addtime($user_indvi_sum['遅刻'], $user_indvi_sum['早退']));
				$usersummary['育児時短時間'] = null;
				$usersummary['普通残業(GW)'] = $helper->dropsecond($user_indvi_sum['残業']);
				$usersummary['深夜残業(GW)'] = $helper->dropsecond($user_indvi_sum['深夜']);
				$usersummary['休日勤務(GW)'] = $helper->dropsecond($user_indvi_sum['休日出勤']);
				$usersummary['休日深夜(GW)'] = $helper->dropsecond($user_indvi_sum['休日深夜']);
				$usersummary['普通残業(支給)'] = $helper->dropsecond($hidden_sumresult['普通残業(支給)']);
				$usersummary['深夜残業(支給)'] = $helper->dropsecond($hidden_sumresult['深夜残業(支給)']);
				$usersummary['休日勤務(支給)'] = $helper->dropsecond($hidden_sumresult['休日勤務(支給)']);
				$usersummary['休日深夜(支給)'] = $helper->dropsecond($hidden_sumresult['休日深夜(支給)']);
				$usersummary['有給残日数'] = null;
				$usersummary['有給残時間'] = null;
				$hash['data']['サマリー']['A1'] = '勤怠一覧';
				$hash['data']['サマリー']['A2'] = $hash['year_disp'].'年'.$hash['month_disp'].'月分（'.$hash['date_from'].' ～ '.$hash['date_to'].'）';
				$hash['data']['サマリー']['A3'][]= $usersummary;
			}
			$colwidth['サマリー'] = array(
					'A' => 10,
					'B' => 15,
					'C' => 15,
					'D' => 15,
					'E' => 15,
					'F' => 15,
					'G' => 15,
					'H' => 15,
					'I' => 15,
					'J' => 15,
					'K' => 15,
					'L' => 15,
					'M' => 15,
					'N' => 15,
					'O' => 15,
					'P' => 15,
					'Q' => 15,
					'R' => 15,
					'S' => 15,
					'T' => 17,
					'U' => 17,
					'V' => 17,
					'W' => 17,
					'X' => 15,
					'Y' => 15
			);

		}
		require_once(DIR_LIBRARY.'Excel/excelmaker.php');
		$excelmaker = new ExcelMaker;
		$hash['group'] = $excelmaker->download($hash['year_disp'].'年'.$hash['month_disp'].'月タイムカード.xlsx', $hash, true, $colwidth, false, true);
	}

	function findOwner($owner, $skipauth = false) {

		if (strlen($owner) > 0 && $owner !== $_SESSION['userid']) {
			if (!$skipauth) {
				$this->authorize('administrator', 'manager');
			}
			$result = $this->fetchOne("SELECT userid, realname, user_group, user_overtime_flg FROM ".DB_PREFIX."user WHERE userid = '".$this->quote($owner)."'");
			if (count($result) <= 0) {
				$this->died('選択されたユーザーは存在しません。');
			}
		} else {
			$result['userid'] = $_SESSION['userid'];
			$result['realname'] = $_SESSION['realname'];
			$result['user_group'] = $_SESSION['group'];
			$result['user_overtime_flg'] = $_SESSION['user_overtime_flg'];
		}
		$result['is_group_leader'] = $this->isGroupLeader($result['userid']);
		$query = "SELECT DISTINCT grp_user.userid, grp_user.realname FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE (grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($result['user_group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($result['user_group'])).")) ORDER BY grp_user.user_order,grp_user.id";
		$data = $this->fetchAll($query);
		$user = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$user[$row['userid']] = $row['realname'];
			}
		}
		return array('owner'=>$result, 'user'=>$user);

	}

	function calWorkingHours($date, $open, $close, $userid, $workday_flg) {

		$calendar = new Calendar;
		$config = new Config($this->handler);
		$hash['config'] = $config->configure('timecard');

		$hash['user'] = $this->findUserByUserId($userid);

		#標準出社時間
		$stdworkstart = strtotime($hash['config']['openhour'].":".$hash['config']['openminute']);
		$stdworkend = strtotime($hash['config']['closehour'].":".$hash['config']['closeminute']);

		#ユーザの出社時間特別に設定されてなければ、標準出社時間を使う
		if($hash['user']['user_openhour'] && $hash['user']['user_openminute']){
			$userworkstart = strtotime($hash['user']['user_openhour'].":".$hash['user']['user_openminute']);
		}else{
			$userworkstart = $stdworkstart;
		}
		$userstdworkstart = $userworkstart;//時間休を考慮しない始業時間

		#ユーザの退社時間特別に設定されてなければ、標準退社時間を使う
		if($hash['user']['user_closehour'] && $hash['user']['user_closeminute']){
			$userworkend = strtotime($hash['user']['user_closehour'].":".$hash['user']['user_closeminute']);
		}else{
			$userworkend = $stdworkend;
		}
		$userstdworkend = $userworkend;//時間休を考慮しない終業時間

		#標準休憩時間
		$stdlunchstart = strtotime($hash['config']['lunchopenhour'].":".$hash['config']['lunchopenminute']);
		$stdlunchend = strtotime($hash['config']['lunchclosehour'].":".$hash['config']['lunchcloseminute']);

		$workstart = strtotime($open);

		#当日有給の有無をチェック
		$sql = "SELECT vc.vacation_name, vc.allday, vc.paid, tc.timecard_vacation_from, tc.timecard_vacation_to, tc.timecard_vacation_substitute_id, tc.timecard_vacation_type, tc.timecard_vacation_comment, tc.timecard_vacation_requested, tc.timecard_vacation_approved, tc.timecard_vacation_supervisor_comment, tc.timecard_vacation_supervisor_userid FROM ".DB_PREFIX."timecard tc INNER JOIN ".DB_PREFIX."vacation vc ON vc.id = tc.timecard_vacation_type WHERE tc.timecard_date = '".$date."' AND tc.owner = '".$userid."' AND tc.timecard_vacation_approved IS NOT NULL";
		$hash['vacation'] = $this->fetchOne($sql);

		$checklateearly = true;
		if (is_array($hash['vacation']) && count($hash['vacation']) > 0) {
			if (isset($hash['vacation']['timecard_vacation_from']) && isset($hash['vacation']['timecard_vacation_to'])) {
				$vacationstart = strtotime($hash['vacation']['timecard_vacation_from']);
				$vacationend = strtotime($hash['vacation']['timecard_vacation_to']);


				#休暇開始時間が始業時間より早い、休暇終了時間が休み開始時間より早い
				if($vacationstart <= $userworkstart && $vacationend < $stdlunchstart){ //9:00-10:00
					$userworkstart = $vacationend;
				#休暇開始時間が始業時間より早い、休暇終了時間が休み開始時間より遅い、休暇終了時間が休み終了時間より早い
				}elseif($vacationstart <= $userworkstart && $vacationend >= $stdlunchstart && $vacationend <= $stdlunchend){ //9:00-12:00
					$userworkstart = $stdlunchend;
				#休暇開始時間が始業時間より早い、休暇終了時間が休み終了時間より遅い、終業時間より早い
				}elseif($vacationstart <= $userworkstart && $vacationend > $stdlunchend){ //9:00-14:00
					$userworkstart = $vacationend;
				}

				#休暇終了時間が終業時間より遅い、休暇開始時間が休み終了時間より遅い
				if($vacationend >= $userworkend && $vacationstart > $stdlunchend){ //16:00-18:00
					$userworkend = $vacationstart;
				#休暇終了時間が終業時間より遅い、休暇開始時間が休み開始時間より遅い、休暇開始時間が休み終了時間より早い
				}elseif($vacationend >= $userworkend && $vacationstart >= $stdlunchstart && $vacationstart <= $stdlunchend){ //13:00-18:00
					$userworkend = $stdlunchstart;
				#休暇終了時間が終業時間より遅い、休暇開始時間が休み開始時間より早い、始業時間より遅い
				}elseif($vacationend >= $userworkend && $vacationstart < $stdlunchstart){ //10:00-18:00
					$userworkend = $vacationstart;
				}

				#休暇開始時間が始業時間より早い、休暇終了時間が終業時間より遅い
				if ($vacationstart <= $userworkstart && $vacationend >= $userworkend) {
					$latecome = 0;
					$earlyleave = 0;
					$checklateearly = false;
				}
			} elseif ($hash['vacation']['allday'] == 1) {
				$latecome = 0;
				$earlyleave = 0;
				$checklateearly = false;
			}

		}

		#終業時間はそのまま
		$workend = strtotime($close);

		#とりあえず、標準休憩時間を使います
		$lunchstart = $stdlunchstart;
		$lunchend = $stdlunchend;

		if($checklateearly){
			#遅刻を計算

			#当日電車遅延などしゃない（syanai）の理由があるかをチェック
			$sql = "SELECT COUNT(*) AS cnt FROM ".DB_PREFIX."timecard tc INNER JOIN ".DB_PREFIX."reason r ON r.id = tc.timecard_reason_open WHERE tc.timecard_date = '".$date."' AND tc.owner = '".$userid."' AND r.syanai = 1";
			$data = $this->fetchOne($sql);

			#祝日休日の場合、ゆるく見ているので、遅刻早退は計算しない。例え平日と振替でも…いいのか…
			if ($workstart > $userworkstart && $data['cnt'] <= 0 && (!$calendar->is_holidays($date) || $workday_flg == 1)){
				if ($userworkstart >= $lunchend) {
					$latecome = $workstart - $userworkstart;
				} elseif ($workstart > $lunchend) {
					$latecome = $workstart - $userworkstart - ($lunchend - $lunchstart);
				} elseif ($workstart > $lunchstart) {
					$latecome = $lunchstart - $userworkstart;
				} else {
					$latecome = $workstart - $userworkstart;
				}
			}else{
				$latecome = 0;
			}

			#もし正社員なら、9:00前の出社でも9:00としてカウント
			#早出も勤務時間にカウントされるので、コメントアウトします。201805改修
			/*
			if($hash['user']['user_hiretype'] == 0 && $workstart < $userworkstart){
				$workstart = $userworkstart;
			}
			*/

			#早退時間を計算、休日出勤は計算しない
			if ($workend < $userworkend && (!$calendar->is_holidays($date) || $workday_flg == 1)){

				$earlyleave = $userworkend - $workend;
				if ($workend < $lunchstart) {
					$earlyleave = $earlyleave - ($lunchend - $lunchstart);
				} elseif ($workend < $lunchend) {
					$earlyleave = $earlyleave - ($lunchend - $workend);
				}
			}else{
				$earlyleave = 0;
			}
		}


		#もし出社時間が退社時間よりも遅い場合、処理しない（TODO深夜残業で翌日退社打刻はとりあえず対応しない）
		if($workstart > $workend){
			return "";
		}
		#同様休憩時間も矛盾があれば処理をやめます。
		if($lunchstart > $lunchend){
			return "";
		}

		#勤務時間を計算
		$worktime = $workend - $workstart;
		$lunchtime = $lunchend - $lunchstart;

		//中抜き時間給あり、且つお昼休み時間を包含する場合
		if (isset($vacationstart) && isset($vacationend) && $workstart <= $vacationstart && $workend >= $vacationend && $vacationstart <= $lunchstart && $vacationend >= $lunchend) {
			$worktime = $worktime - ($vacationend - $vacationstart); //v:10:00-14:00,w:9:00-18:00
		} elseif (isset($vacationstart) && isset($vacationend) && $workstart <= $vacationstart && $workend >= $vacationend && $vacationstart <= $lunchstart && $vacationend <= $lunchstart) {
			$worktime = $worktime - ($vacationend - $vacationstart) - ($lunchend - $lunchstart); //v:10:00-11:00,w:9:00-18:00
		} elseif (isset($vacationstart) && isset($vacationend) && $workstart <= $vacationstart && $workend >= $vacationend && $vacationstart >= $lunchend && $vacationend >= $lunchend) {
			$worktime = $worktime - ($vacationend - $vacationstart) - ($lunchend - $lunchstart); //v:14:00-17:00,w:9:00-18:00
		} elseif (isset($vacationstart) && isset($vacationend) && $workstart > $vacationstart && $workstart < $vacationend) {
			$worktime = $worktime - ($vacationend - $workstart); //v:9:00-10:00,w:9:30-18:00
		} elseif ($workstart <= $lunchstart && $workend >= $lunchend) {
			$worktime = $worktime - ($lunchend - $lunchstart); //とにかく始業時刻と就業時刻に昼休みを挟む場合
		} elseif ($workstart <= $lunchstart && $workend > $lunchstart && $workend <= $lunchend) {
			$worktime = $worktime - ($workend - $lunchstart); //終業時刻が昼休み中だったら
		}

		$hd_worktime = 0;
		if($calendar->is_holidays($date) && $workday_flg == 0){
			$hd_worktime = $worktime;
		}

		#深夜残業開始時間を取得
		$latenightovertimestart = strtotime($hash['config']['latenightovertimestart']);

		#深夜残業を計算
		$latenightovertime = 0;
		$hd_latenightovertime = 0;
		if($calendar->is_holidays($date) && $workday_flg == 0){
			if ($workend > $latenightovertimestart){ //休日出勤の深夜残業
				$hd_latenightovertime = $workend - $latenightovertimestart;
			}
		}else{
			if ($workend > $latenightovertimestart && $workend > $userstdworkend){ //平日（または平日扱い）終業時間が深夜残業開始時間より遅い、且つ、ユーザの通常終業時間よりも遅い
				$latenightovertime = $workend - $latenightovertimestart;
			}
		}

		#普通残業を計算
		$overtime = 0;
		$hd_overtime = 0;
		if($calendar->is_holidays($date) && $workday_flg == 0){
			//休日出勤の深夜残業なしの普通残業
			if ($workend > $userworkend && $workend <= $latenightovertimestart){
				$hd_overtime = $workend - $userworkend;
			}elseif ($workend > $userworkend && $workend > $latenightovertimestart){ //休日出勤の深夜残業ありの普通残業
				$hd_overtime = $latenightovertimestart - $userworkend;
			}
		}else{
			//平日出勤の深夜残業なしの普通残業
			if ($workend > $userworkend && $workend <= $latenightovertimestart && $workend > $userstdworkend){ //終業時間が深夜残業開始時間より早い、且つ、ユーザの通常終業時間（時間休を考慮セず）より遅い、ユーザの終業時間（時間休を考慮する）よりも遅い
				$overtime = $workend - $userstdworkend; //ここではユーザの通常終業時間（時間休を考慮セず）を引く
			}elseif ($workend > $userworkend && $workend > $latenightovertimestart && $workend > $userstdworkend){
				$overtime = $latenightovertimestart - $userworkend;
			}
		}

		#残業の申請と承認
		$query = "SELECT * FROM grp_overtime WHERE owner = '".$userid."' AND overtime_date = '".$date."' AND overtime_time_approved IS NOT NULL";
		$overtimeapproveddata = $this->fetchOne($query);
		if ($overtimeapproveddata) {
			$helper = new Helper;

			$overtimeapproved = strtotime($overtimeapproveddata['overtime_time_approved']);

			if($calendar->is_holidays($date) && $workday_flg == 0){
				//休日出勤の場合、普通残業ではなく、休日勤務だから、出社時間＋申請する時間外＝退社時間
				$userfixedotworkend = strtotime($helper->addtime(date('H:i',$userstdworkstart), date('H:i',$overtimeapproved)));
			} else {
				//平日の場合は、標準退社時間＋時間外申請＝退社時間
				$userfixedotworkend = strtotime($helper->addtime(date('H:i',$userstdworkend), date('H:i',$overtimeapproved)));
			}

			$fixed_latenightovertime = 0;
			$fixed_hd_latenightovertime = 0;
			$fixed_overtime = 0;
			$fixed_hd_worktime = '00:00';
			if($calendar->is_holidays($date) && $workday_flg == 0){
				if ($userfixedotworkend > $latenightovertimestart){ //承認済みの休日出勤の深夜残業
					$fixed_hd_latenightovertime = $userfixedotworkend - $latenightovertimestart;
				}
				//承認済みの休日出勤
				$fixed_hd_worktime = date('H:i', $overtimeapproved);

			}else{
				if ($userfixedotworkend > $latenightovertimestart && $userfixedotworkend > $userstdworkend){ //承認済みの平日（または平日扱い）終業時間が深夜残業開始時間より遅い、且つ、ユーザの通常終業時間よりも遅い
					$fixed_latenightovertime = $userfixedotworkend - $latenightovertimestart;
				}
				//承認済みの平日出勤の深夜残業なしの普通残業
				if ($userfixedotworkend > $userstdworkend && $userfixedotworkend <= $latenightovertimestart){ //承認済みの終業時間が深夜残業開始時間より早い、且つ、ユーザの通常終業時間（時間休を考慮セず）より遅い、ユーザの終業時間（時間休を考慮する）よりも遅い
					$fixed_overtime = $userfixedotworkend - $userstdworkend; //承認済みのここではユーザの通常終業時間（時間休を考慮セず）を引く
				}elseif ($userfixedotworkend > $userstdworkend && $userfixedotworkend > $latenightovertimestart){
					$fixed_overtime = $latenightovertimestart - $userstdworkend;
				}
			}
			$fixovertime['overtime_fixed_latenightovertime'] = gmdate('H:i', $fixed_latenightovertime);
			$fixovertime['overtime_fixed_normal_overtime'] = gmdate('H:i', $fixed_overtime);
			$fixovertime['overtime_fixed_hd_latenightovertime'] = gmdate('H:i', $fixed_hd_latenightovertime);
			$fixovertime['overtime_fixed_hd_worktime'] = $fixed_hd_worktime;
			$this->recordfixedovertime($fixovertime, $date, $userid);
		}

		//正社員であれば早出の計算
		if($hash['user']['user_hiretype'] == 0 && $workstart < $userworkstart){
			if (isset($vacationstart) && isset($vacationend)){
				if($workstart >= $vacationstart && $workstart <= $vacationend){ //出社時間が時間有給の範囲内なら、早出は計算しない
					$hayade = 0;
				}elseif($workstart < $vacationstart){ //時間給なのに、時間給始まる前に出勤した場合は、早出扱い
					$hayade = $vacationstart - $workstart;
				}
			}else{//時間有休がなければ、普通に勤務開始時間から出勤時間を引く
				$hayade = $userworkstart - $workstart;
			}
		}

		$result['timecard_work'] = gmdate('H:i', $worktime);
		$result['timecard_latenightovertime'] = gmdate('H:i', $latenightovertime);
		$result['timecard_overtime'] = gmdate('H:i', $overtime);
		$result['timecard_hd_work'] = gmdate('H:i', $hd_worktime);
		$result['timecard_hd_latenightovertime'] = gmdate('H:i', $hd_latenightovertime);
		$result['timecard_hd_overtime'] = gmdate('H:i', $hd_overtime);
		$result['timecard_latecome'] = gmdate('H:i', $latecome);
		$result['timecard_earlyleave'] = gmdate('H:i', $earlyleave);
		$result['timecard_hayade'] = gmdate('H:i', $hayade);
		return $result;
	}

	function vacation() {
		$hash = $this->getTimecardRange($_GET['year'], $_GET['month'], $_GET['day']);
		$member = $this->findOwner($_GET['member']);
		$hash['owner'] = $member['owner'];
		$hash['user'] = $member['user'];

		$hash['fix'] = $this->get_fix_status($hash['year_disp'], $hash['month_disp'], $hash['owner']['userid']);
		if ($hash['fix']) {
			$this->died('既に締めきった期間に対して、休暇の申請・修正はできません');
		}

		$hash['data'] = $this->findRecord($_GET['year'], $_GET['month'], $_GET['day'], $_GET['member']);
		$hash['vacation'] = $this->listVacation();
		$hash['vacationinfo'] = $this->getVacationInfo();

		$config = new Config($this->handler);

		$user = $this->findUserByUserId($_SESSION['userid']);

		$hash['config'] = $config->configure('timecard');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			#削除処理
			if($_POST['delete_vacation_id'] && is_numeric($_POST['delete_vacation_id'])){
				$query = sprintf("UPDATE %s SET timecard_vacation_from = NULL, timecard_vacation_to = NULL, timecard_vacation_substitute_id = NULL, timecard_vacation_type = NULL, timecard_vacation_comment = NULL, timecard_vacation_requested = NULL, timecard_vacation_approved = NULL WHERE (id = %d) AND (owner = '%s')", $this->table, intval($_POST['delete_vacation_id']), $this->quote($_SESSION['userid']));
				$this->response = $this->query($query);
				$this->redirect('index.php?year='.$_GET['year'].'&month='.$_GET['month'].'&day='.$_GET['day']);
				$hash['data'] = $this->post;
				return $hash;
			}

			$this->validator('timecard_vacation_comment', '内容', array('length:10000', 'line:100'));

			#標準出社時間
			$stdworkstart = strtotime($hash['config']['openhour'].":".$hash['config']['openminute']);
			$stdworkend = strtotime($hash['config']['closehour'].":".$hash['config']['closeminute']);

			#ユーザの出社時間特別に設定されてなければ、標準出社時間を使う
			if($hash['user']['user_openhour'] && $hash['user']['user_openminute']){
				$userworkstart = strtotime($hash['user']['user_openhour'].":".$hash['user']['user_openminute']);
			}else{
				$userworkstart = $stdworkstart;
			}
			#ユーザの退社時間特別に設定されてなければ、標準退社時間を使う
			if($hash['user']['user_closehour'] && $hash['user']['user_closeminute']){
					$userworkend = strtotime($hash['user']['user_closehour'].":".$hash['user']['user_closeminute']);
			}else{
			$userworkend = $stdworkend;
			}


			$this->post['timecard_vacation_type'] = $_POST['timecard_vacation_type'];
			if($hash['vacationinfo'][$this->post['timecard_vacation_type']]['allday'] == 1){
				$this->post['timecard_vacation_from'] = date('H:i', $userworkstart);
				$this->post['timecard_vacation_to'] = date('H:i', $userworkend);
			} else {
				$this->post['timecard_vacation_from'] = $_POST['timecard_vacation_from'];
				$this->post['timecard_vacation_to'] = $_POST['timecard_vacation_to'];
			}

			if(!$this->post['timecard_vacation_to'] && $this->post['timecard_vacation_from']){
				$this->post['timecard_vacation_to'] = date('H:i', $userworkend);
			}

			if($this->post['timecard_vacation_to'] && !$this->post['timecard_vacation_from']){
				$this->post['timecard_vacation_from'] = date('H:i', $userworkstart);
			}

			if($this->post['timecard_vacation_to'] && $this->post['timecard_vacation_from']){
				if(strtotime($this->post['timecard_vacation_to']) - strtotime($this->post['timecard_vacation_from']) < 60 * 60 ){
					$this->error[] = '休みの最小単位は30分です、休み時間を確認ください。';
				}
			}

			$array = array();
			$this->post['timecard_vacation_requested'] = date('Y-m-d H:i:s'); //申請時刻
			$this->post['timecard_vacation_approved'] = null; //承認時刻をクリア

			//$this->post['updated'] = date('Y-m-d H:i:s');
			if (is_array($hash['data']) && $hash['data']['id'] > 0 && count($this->error) <= 0) {
				$this->record($this->post, $_GET['year'], $_GET['month'], $_GET['day'], $_GET['member']);
			} else {
				$this->post['timecard_year'] = $_GET['year'];
				$this->post['timecard_month'] = $_GET['month'];
				$this->post['timecard_day'] = $_GET['day'];
				$this->post['timecard_date'] = date('Y-m-d', mktime(0, 0, 0, $_GET['month'], $_GET['day'], $_GET['year']));

				$this->insertPost();
			}

			$this->redirect('index.php?year='.$_GET['year'].'&month='.$_GET['month'].'&day='.$_GET['day']);
			$hash['data'] = $this->post;
		}
		return $hash;
	}


	function getTimecardRange($year, $month, $day) {
		if(!isset($year) && !isset($month) && !isset($day)){
			$year = date('Y');
			$month = date('n');
			$day = date('j');
		}

		$config = new Config($this->handler);
		$hash['config'] = $config->configure('timecard');

		if(isset($year) && isset($month) && !isset($day)){
			if ($hash['config']['closeday']) {
				$day = $hash['config']['closeday'];
			}else{
				$day = 1;
			}
		}

		if(!checkdate($month, $day, $year)){
			$year = date('Y');
			$month = date('n');
			$day = date('j');
		}

		$theDay = mktime(0, 0, 0, $month, $day, $year);
		if ($hash['config']['closeday']) {
			if($hash['config']['closeday'] >= $day){
				$timestamp_from = mktime(0, 0, 0, $month, $hash['config']['closeday'], $year);
				$timestamp_from = strtotime("-1 month +1 day",$timestamp_from);
				$timestamp_to = mktime(0, 0, 0, $month, $hash['config']['closeday'], $year);
			}else{
				$timestamp_from = mktime(0, 0, 0, $month, $hash['config']['closeday'], $year);
				$timestamp_from = strtotime("+1 day",$timestamp_from);
				$timestamp_to = mktime(0, 0, 0, $month, $hash['config']['closeday'], $year);
				$timestamp_to = strtotime("+1 month",$timestamp_to);
			}
			$hash['year_disp'] = date('Y',$timestamp_to);
			$hash['month_disp'] = date('n',$timestamp_to);
			$date_from = date('Y-m-d',$timestamp_from);
			$date_to = date('Y-m-d',$timestamp_to);
		}else{
			$hash['year_disp'] = $year;
			$hash['month_disp'] = $month;
			$timestamp_from = mktime(0, 0, 0, $month, 1, $year);
			$timestamp_to = strtotime("+1 month -1 day",$timestamp_from);
			$date_from = date('Y-m-d',$timestamp_from);
			$date_to = date('Y-m-d',$timestamp_to);
		}
		$hash['timestamp_from'] = $timestamp_from;
		$hash['timestamp_to'] = $timestamp_to;
		$hash['date_from'] = $date_from;
		$hash['date_to'] = $date_to;
		$hash['get_year'] = $year;
		$hash['get_month'] = $month;
		$hash['get_day'] = $day;
		return $hash;
	}

	function getTimecardRangeFromYearMonth($year, $month) {
		$config = new Config($this->handler);
		$hash['config'] = $config->configure('timecard');
		if ($hash['config']['closeday']) {
			$day = $hash['config']['closeday'];
		} else {
			$day = 1;
		}
		$timestamp_from = mktime(0, 0, 0, $month, $day, $year);
		$timestamp_from = strtotime("-1 month +1 day",$timestamp_from);
		$timestamp_to = mktime(0, 0, 0, $month, $day, $year);
		$date_from = date('Y-m-d',$timestamp_from);
		$date_to = date('Y-m-d',$timestamp_to);
		$hash['date_from'] = $date_from;
		$hash['date_to'] = $date_to;
		return $hash;
	}

	function getOvertime($datefrom, $dateto, $userid) {
		$query = sprintf("SELECT * FROM grp_overtime WHERE overtime_date >= '%s' AND overtime_date <= '%s' AND owner = '%s' ORDER BY overtime_date", $datefrom, $dateto, $userid);
		return $this->fetchAll($query);
	}

	function overtime() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST["del_id"])) {
				$query = "DELETE FROM grp_overtime WHERE id = ".$_POST["del_id"];
				$this->response = $this->query($query);
				$otdate = $_POST["current_date"];
				$hash['overtime_date'] = $otdate;
				$hash['result'] = "deleted";
			} elseif (isset($_POST["overtime_time_requested:".$_POST["current_date"]])) {
				$val = $_POST["overtime_time_requested:".$_POST["current_date"]];
				$otdate = $_POST["current_date"];
				$hash['overtime_date'] = $otdate;
				if (empty($val)) {
					$query = "DELETE FROM grp_overtime WHERE overtime_date = '".$otdate."' AND owner = '".$_SESSION['userid']."';";
					$hash['result'] = "deleted";
				} else {
					$query = "INSERT INTO grp_overtime (overtime_date, overtime_time_requested, owner, created) VALUES ('".$otdate."', '".$val."', '".$_SESSION['userid']."', CURRENT_TIMESTAMP) ".
								"ON DUPLICATE KEY UPDATE overtime_time_requested = '".$val."', overtime_time_approved = NULL, overtime_approved_at = NULL, overtime_supervisor_userid = NULL, overtime_fixed_normal_overtime = NULL, overtime_fixed_latenightovertime = NULL, ".
								"overtime_fixed_hd_worktime = NULL, overtime_fixed_hd_latenightovertime = NULL, editor = '".$_SESSION['userid']."', updated = CURRENT_TIMESTAMP;";
					$hash['result'] = "requested";
					$hash['overtime_requested'] = $val;
					$hash['overtime_updated'] = date('Y-m-d H:i:s');
				}
				$this->response = $this->query($query);
			}
			return $hash;
		}
	}


	function overtimeapprove() {
		$user = new User();
		$userid = $_SESSION['userid'];
		$ids = $user->getSubMembsByLeaderId($userid);
		$where = "";
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			foreach ($_POST['overtime_time_approved'] as $id => $val) {
				if ($val) {
					$query = sprintf("UPDATE grp_overtime SET overtime_time_approved = '%s', overtime_approved_at = CURRENT_TIMESTAMP, overtime_supervisor_userid = '%s', editor = '%s', updated = CURRENT_TIMESTAMP WHERE id = %d;",$val,$userid,$userid,$id);
					$this->response = $this->query($query);
				}
			}
		} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {

			if ($_GET['otdate']) {
				$otdate = $_GET['otdate'];
				$where .= sprintf(" AND grp_overtime.overtime_date = '%s'",$otdate);
			}

			if ($_GET['otgroupid']) {
				$otgroupid = $_GET['otgroupid'];
				$where .= " AND (grp_user.user_group IN (".$this->getChildrenIdsByParentId($otgroupid).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId($otgroupid)."))";
			}

			if ($_GET['otuserid']) {
				$otuserid = $_GET['otuserid'];
				$where .= sprintf(" AND grp_overtime.owner = '%s'",$otuserid);
			}

		}

		$query = "SELECT DISTINCT
				grp_overtime.id, grp_user.realname, grp_user.userid, grp_user.user_groupname, grp_user.user_group, grp_overtime.overtime_date,
				grp_timecard.timecard_open, grp_timecard.timecard_originalopen, grp_timecard.timecard_close, grp_timecard.timecard_originalclose,
				grp_overtime.overtime_time_requested FROM grp_overtime
				INNER JOIN grp_user ON grp_user.userid = grp_overtime.owner
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard ON grp_timecard.timecard_date = grp_overtime.overtime_date AND grp_timecard.owner = grp_overtime.owner
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.fixed_from <= grp_overtime.overtime_date AND grp_timecard_fixed.fixed_to >= grp_overtime.overtime_date
				WHERE ".($_SESSION['authority'] == 'administrator'?"":"grp_overtime.owner IN ('".implode("','", $ids)."') AND ")."grp_overtime.overtime_time_approved IS NULL AND grp_timecard_fixed.fixed_at IS NULL".$where.
				" ORDER BY grp_overtime.overtime_date ASC, grp_user.user_group, grp_user.user_code";
		$hash['overtimelist'] = $this->fetchAll($query);

		$query = "SELECT DISTINCT
				grp_overtime.id, grp_user.realname, grp_user.userid, grp_user.user_groupname, grp_user.user_group, grp_overtime.overtime_date,
				grp_timecard.timecard_open, grp_timecard.timecard_originalopen, grp_timecard.timecard_close, grp_timecard.timecard_originalclose,
				grp_overtime.overtime_time_requested FROM grp_overtime
				INNER JOIN grp_user ON grp_user.userid = grp_overtime.owner
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard ON grp_timecard.timecard_date = grp_overtime.overtime_date AND grp_timecard.owner = grp_overtime.owner
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.fixed_from <= grp_overtime.overtime_date AND grp_timecard_fixed.fixed_to >= grp_overtime.overtime_date
				WHERE ".($_SESSION['authority'] == 'administrator'?"":"grp_overtime.owner IN ('".implode("','", $ids)."') AND ")."grp_overtime.overtime_time_approved IS NULL AND grp_timecard_fixed.fixed_at IS NULL".
						" ORDER BY grp_overtime.overtime_date ASC, grp_user.user_group, grp_user.user_code";
		$temp_data = $this->fetchAll($query);


		$query = "SELECT DISTINCT grp_overtime_comment.*,applicant.realname AS applicant, authorizer.realname AS authorizer,
				TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_overtime.overtime_time_requested))), '%H:%i') AS sum_overtime_requested,
				TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(grp_overtime.overtime_time_approved))), '%H:%i') AS sum_overtime_approved
				FROM grp_overtime_comment
				LEFT JOIN grp_user applicant ON applicant.userid = grp_overtime_comment.owner
				LEFT JOIN grp_user authorizer ON authorizer.userid = grp_overtime_comment.timecard_overtime_supervisor_userid
				LEFT JOIN grp_overtime ON grp_overtime.overtime_date >= grp_overtime_comment.date_from AND grp_overtime.overtime_date <= grp_overtime_comment.date_to AND grp_overtime.owner = grp_overtime_comment.owner
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.timecard_year = grp_overtime_comment.timecard_year AND grp_timecard_fixed.timecard_month = grp_overtime_comment.timecard_month AND grp_timecard_fixed.owner = grp_overtime_comment.owner
				WHERE ".($_SESSION['authority'] == 'administrator'?"grp_timecard_fixed.fixed_at IS NULL AND ":"grp_overtime_comment.owner IN ('".implode("','", $ids)."') AND grp_timecard_fixed.fixed_at IS NULL AND ")."(timecard_overtime_supervisor_userid IS NULL OR (timecard_overtime_supervisor_userid = '".$_SESSION['userid']."'))
				GROUP BY grp_overtime_comment.owner, grp_overtime_comment.timecard_year, grp_overtime_comment.timecard_month";
		$utility = new Utility();
		$hash['overtime_comments'] = $this->fetchAll($query);


		$hash['sel_realname'] = $utility->assembarray($temp_data, 'userid', 'realname');
		$hash['sel_user_groupname'] = $utility->assembarray($temp_data, 'user_group', 'user_groupname');
		$hash['sel_date'] = $utility->assembarray($temp_data, 'overtime_date', 'overtime_date');
		$hash['otdate'] = $otdate;
		$hash['otuserid'] = $otuserid;
		$hash['otgroupid'] = $otgroupid;

		return $hash;
	}

	function overtimeapprovecount($userid) {
		$user = new User();
		$ids = $user->getSubMembsByLeaderId($userid);
		$query = "SELECT count(*) AS cnt FROM grp_overtime
				INNER JOIN grp_user ON grp_user.userid = grp_overtime.owner
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.fixed_from <= grp_overtime.overtime_date AND grp_timecard_fixed.fixed_to >= grp_overtime.overtime_date
				WHERE ".($_SESSION['authority'] == 'administrator'?"":"grp_overtime.owner IN ('".implode("','", $ids)."') AND ")."grp_overtime.overtime_time_approved IS NULL AND grp_timecard_fixed.fixed_at IS NULL";
		$count = $this->fetchOne($query);
		return $count['cnt'];
	}

	function overtimecommentapprovecount($userid) {
		$user = new User();
		$ids = $user->getSubMembsByLeaderId($userid);
		$query = "SELECT count(*) AS cnt FROM grp_overtime_comment
				INNER JOIN grp_user ON grp_user.userid = grp_overtime_comment.owner
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.timecard_year = grp_overtime_comment.timecard_year AND grp_timecard_fixed.timecard_month = grp_overtime_comment.timecard_month
				WHERE ".($_SESSION['authority'] == 'administrator'?"":"grp_overtime_comment.owner IN ('".implode("','", $ids)."') AND ")."grp_overtime_comment.timecard_overtime_supervisor_userid IS NULL AND grp_timecard_fixed.fixed_at IS NULL";
		$count = $this->fetchOne($query);
		return $count['cnt'];
	}

	function overtimecomment() {
		if (isset($_GET["member"])) {
			$targetowner = $_GET["member"];
		} else {
			$targetowner = $_SESSION['userid'];
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			//上司からのコメント
			if (isset($_POST["member"]) && $_POST["member"] != $_SESSION['userid'] && isset($_POST["overtime_comment_id"])) {
				$owner = $_POST["member"];
				$supervisor_comment = $_POST['timecard_overtime_supervisor_comment'];
				$approved = (isset($_POST['syounin']) && $_POST['syounin'] == 1?true:false);
				$query = "UPDATE grp_overtime_comment SET timecard_overtime_supervisor_comment = '".$_POST['timecard_overtime_supervisor_comment']."', timecard_overtime_supervisor_userid = '".$_SESSION['userid']."', timecard_overtime_approved = ".($approved?"CURRENT_TIMESTAMP":"NULL")." WHERE id = ".$_POST["overtime_comment_id"];
			} else {
				if (isset($_POST["member"])) {
					$owner = $_POST["member"];
				} else {
					$owner = $_SESSION['userid'];
				}

				$from_to = $this->getTimecardRangeFromYearMonth($_POST["timecard_year"], $_POST["timecard_month"]);

				$query = "INSERT INTO grp_overtime_comment (timecard_year, timecard_month, date_from, date_to, comment, owner, created) VALUES ('".$_POST["timecard_year"]."', '".$_POST["timecard_month"]."', '".$from_to["date_from"]."', '".$from_to["date_to"]."', '".$_POST['comment']."', '".$owner."', CURRENT_TIMESTAMP) ".
						"ON DUPLICATE KEY UPDATE comment = '".$_POST['comment']."', updated = CURRENT_TIMESTAMP;";
			}
			$this->response = $this->query($query);
			$query = "SELECT grp_overtime_comment.*,applicant.realname AS applicant, authorizer.realname AS authorizer
					FROM grp_overtime_comment
					LEFT JOIN grp_user applicant ON applicant.userid = grp_overtime_comment.owner
					LEFT JOIN grp_user authorizer ON authorizer.userid = grp_overtime_comment.timecard_overtime_supervisor_userid
					WHERE grp_overtime_comment.timecard_year = '".$_POST["timecard_year"]."' AND grp_overtime_comment.timecard_month = '".$_POST["timecard_month"]."' AND grp_overtime_comment.owner = '".$owner."';";
		} else {
			$query = "SELECT grp_overtime_comment.*,applicant.realname AS applicant, authorizer.realname AS authorizer
					FROM grp_overtime_comment
					LEFT JOIN grp_user applicant ON applicant.userid = grp_overtime_comment.owner
					LEFT JOIN grp_user authorizer ON authorizer.userid = grp_overtime_comment.timecard_overtime_supervisor_userid
					WHERE grp_overtime_comment.timecard_year = '".$_GET["timecard_year"]."' AND grp_overtime_comment.timecard_month = '".$_GET["timecard_month"]."' AND grp_overtime_comment.owner = '".$targetowner."';";

			$from_to = $this->getTimecardRangeFromYearMonth($_GET["timecard_year"], $_GET["timecard_month"]);
		}
		$hash['overtime_comment'] = $this->fetchOne($query);

		$query = "SELECT
					TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(overtime_time_requested))), '%H:%i') AS sum_overtime_requested,
					TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(overtime_time_approved))), '%H:%i') AS sum_overtime_approved
					FROM grp_overtime
					WHERE overtime_date >= '".$from_to["date_from"]."' AND overtime_date <= '".$from_to["date_to"]."' AND grp_overtime.owner = '".($_SERVER['REQUEST_METHOD'] == 'POST'?$owner:$targetowner)."';";
		$hash['overtime'] = $this->fetchOne($query);


		$config = new Config($this->handler);
		$helper = new Helper;
		$hash['timecard_config'] = $config->configure('timecard');
		if ($helper->time2sec($hash['overtime']['sum_overtime_approved']) > $helper->time2sec($hash['timecard_config']['overtimehourlimit']) ||
			$helper->time2sec($hash['overtime']['sum_overtime_requested']) > $helper->time2sec($hash['timecard_config']['overtimehourlimit'])) {
			$hash['overtime_exceeded'] = true;
		} else {
			$hash['overtime_exceeded'] = false;
		}

		$hash['fix'] = $this->get_fix_status($_GET["timecard_year"], $_GET["timecard_month"], $targetowner);
		if ($targetowner !== $_SESSION['userid']) {
			$hash['user_is_boss_of_target_user'] = $this->isMySupervisorByUserid($targetowner, $_SESSION['userid']);
		}
		$hash['target_user'] = $targetowner;
		return $hash;
	}


}

?>