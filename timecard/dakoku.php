<?php
require_once ('../application/config.php');
if (DB_STORAGE == 'mysql') {
	require_once (DIR_LIBRARY . 'connection' . DB_STORAGE . '.php');
} else {
	require_once (DIR_LIBRARY . 'connection.php');
}
dakoku ();

// タイムカードを打刻します
// 戻り値：
// 1 - 出社打刻成功しました
// 2 - 退社打刻成功しました
// 3 - テストカードの打刻
// 901 - ユーザは登録されていない
// 902 - 120秒以内の重複打刻
// 903 - 120秒以上10分以内の重複打刻
// 904 - 既にWEB出社打刻済
// 905 - 既にWEB退社打刻済
// 906 - 既に退社しました
// 999 - 原因不明なエラー
function dakoku() {
	if (isset ( $_POST ['dakoku_email'] )) {
		$connection = new Connection ();

		$query = sprintf("SELECT config_value FROM %sconfig WHERE config_type = 'timecard' AND config_key = 'dakokutest'", DB_PREFIX);
		$conf = $connection->fetchOne ( $query );
		$dakokuJikoku = time ();

		if (count ( $conf ) > 0 && $conf ['config_value'] == $_POST ['dakoku_email']) {
			$status = 3;
		} else {
			$status = 999;
			$query = sprintf ( "SELECT id,userid,password,realname,user_ruby,user_group,authority FROM %suser WHERE user_email = '%s'", DB_PREFIX, $connection->quote ( $_POST ['dakoku_email'] ) );
			$user = $connection->fetchOne ( $query );
			if (count ( $user ) > 0) {
				$query = sprintf ( "SELECT * FROM %stimecard WHERE owner = '%s' AND timecard_date = '%s'", DB_PREFIX, $user ['userid'], date ( "Y-m-d" ) );
				$record = $connection->fetchOne ( $query );
				if (count ( $record ) > 0) {
				    if (!$record ['timecard_originalopen'] && !$record ['timecard_open']) {
						// 出社打刻
						$time = date ( 'G:i' );
						$timecard ['timecard_originalopen'] = $time;
						$timecard ['timecard_open'] = $time;
						$timecard ['timecard_date'] = date ( 'Y-m-d' );
						$query = sprintf ( "UPDATE %stimecard SET timecard_originalopen = '" . $timecard ['timecard_originalopen'] . "', timecard_open = '" . $timecard ['timecard_open'] . "' WHERE owner = '%s' AND timecard_date = '%s'", DB_PREFIX, $user ['userid'], date ( "Y-m-d" ) );
						$connection->query ( $query );
						$status = 1; // 出社打刻成功しました
					} elseif (($dakokuJikoku - strtotime ( $record ['timecard_originalopen'] ) < 120) && $record ['timecard_originalopen'] !== null) { //2分以内の打刻は重複と見なします
						$status = 902;
					} elseif (($dakokuJikoku - strtotime ( $record ['timecard_originalopen'] ) < 600) && $record ['timecard_originalopen'] !== null) { //10分以内の打刻も重複と見なします
						$status = 903;
					} elseif (($record ['timecard_originalopen'] || $record ['timecard_open']) && !$record ['timecard_close'] && !$record ['timecard_originalclose']) {
						// 退社打刻
						$time = date ( 'G:i' );
						$timecard ['timecard_originalclose'] = $time;
						$timecard ['timecard_close'] = $time;
						$timecard ['timecard_date'] = $record ['timecard_date'];
						$array = sum ( $record ['timecard_open'], $timecard ['timecard_close'] );
						$timecard ['timecard_time'] = $array ['timecard_time'];
						$timecard ['timecard_timeinterval'] = $array ['timecard_timeinterval'];
						$query = sprintf ( "UPDATE %stimecard SET timecard_originalclose = '" . $timecard ['timecard_originalclose'] . "', timecard_close = '" . $timecard ['timecard_close'] . "', timecard_time = '" . $timecard ['timecard_time'] . "', timecard_timeinterval = '" . $timecard ['timecard_timeinterval'] . "'  WHERE owner = '%s' AND timecard_date = '%s'", DB_PREFIX, $user ['userid'], date ( "Y-m-d" ) );
						$connection->query ( $query );
						$status = 2; // 退社打刻成功しました
					} elseif (($record ['timecard_originalopen'] || $record ['timecard_open']) && $record ['timecard_close'] && $record ['timecard_originalclose']) {
						$status = 906;
					} elseif (($record ['timecard_originalopen'] || $record ['timecard_open']) && $record ['timecard_close'] && ! $record ['timecard_originalclose']) {
						$status = 905;
					} else {
						$status = 999;
					}
				} else {
					// 出社打刻
					$time = date ( 'G:i' );
					$timecard ['timecard_year'] = date ( 'Y' );
					$timecard ['timecard_month'] = date ( 'n' );
					$timecard ['timecard_day'] = date ( 'j' );
					$timecard ['timecard_date'] = date ( 'Y-m-d' );
					$timecard ['timecard_originalopen'] = $time;
					$timecard ['timecard_open'] = $time;
					$timecard ['owner'] = $user ['userid'];
					$timecard ['created'] = date ( 'Y-m-d H:i:s' );
					$keys = array_keys ( $timecard );
					$values = array_values ( $timecard );

					$query = sprintf ( "INSERT INTO %stimecard (" . implode ( ",", $keys ) . ") VALUES ('" . implode ( "','", $values ) . "')", DB_PREFIX );
					$connection->query ( $query );
					$status = 1; // 出社打刻成功しました
				}
			} else {
				$status = 901; // ユーザは登録されていない
			}
			$shigotoosame = null;
			$shigotohajime = null;
			// クリスマス後から年末仕事納めを取得する
			if (isset($timecard['timecard_date']) && strtotime($timecard['timecard_date']) > strtotime(date('Y',strtotime($timecard ['timecard_date']))."-12-25")) {
				$shigotoosame = getShigotoosame(date('Y',strtotime($timecard ['timecard_date'])),$user['userid']);
			}
			// 新年10日まで仕事はじめを取得する
			if (isset($timecard['timecard_date']) && strtotime($timecard['timecard_date']) < strtotime(date('Y',strtotime($timecard ['timecard_date']))."-01-10")) {
				$shigotohajime = getShigotohajime(date('Y',strtotime($timecard ['timecard_date'])),$user['userid']);
			}

		}
	}
	$connection->close ();
	switch ($status) {
		case 1 :
		case 2 :
		case 902 :
		case 903 :
		case 904 :
		case 905 :
			echo '{"dakokuStatus": ' . $status . ', "userName": "' . $user ["realname"] . '", "user_ruby": "' . $user ["user_ruby"] . '", "dakokuDate": "' . $timecard ['timecard_date'] . '", "dakokuTime": "' . $time . '", "dakokuJikoku": "' . date ( 'Y-m-d H:i:s', $dakokuJikoku ) . '", "shigotoosame": '.(is_null($shigotoosame)?"null":'"'.$shigotoosame->format('Y-m-d').'"'). ', "shigotohajime": '.(is_null($shigotohajime)?'null':'"'.$shigotohajime->format('Y-m-d').'"'). ' }';
			break;
		case 3 :
		case 901 :
		case 906 :
			echo '{"dakokuStatus": ' . $status . ', "dakokuJikoku": "' . date ( 'Y-m-d H:i:s', $dakokuJikoku ) . '" }';
			break;
	}
}
function minute($time) {
	$array = explode ( ':', $time );
	return intval ( $array [0] ) * 60 + intval ( $array [1] );
}
function sum($open, $close) {
	$open = minute ( $open );
	$close = minute ( $close );
	$sum = $close - $open;
	if ($sum < 0) {
		$sum = 0;
	}
	$result ['timecard_time'] = sprintf ( '%d:%02d', (($sum - ($sum % 60)) / 60), ($sum % 60) );
	$result ['timecard_timeinterval'] = sprintf ( '%d:%02d', 0, 0 );
	return $result;
}
function getShigotoosame($year, $userid) {
	if (!$year) {
		$year = date('Y');
	}
	$begin = new DateTime($year.'-01-01');
	$end = new DateTime(($year+1).'-01-01');
	$interval = DateInterval::createFromDateString('1 day');
	$period = new DatePeriod($begin, $interval, $end);

	foreach ($period as $dt) {
		$days[] = $dt;
	}

	$days = array_reverse($days);
	$connection = new Connection ();

	if ($userid) {
		$query = sprintf("SELECT DISTINCT timecard_date FROM grp_timecard INNER JOIN grp_vacation ON grp_vacation.id = grp_timecard.timecard_vacation_type WHERE owner = '%s' AND YEAR(timecard_date) = '%d' AND grp_vacation.allday = 1", $userid, $year);
		$uservacations = $connection->query($query);

		foreach ($uservacations as $vac) {
			$vacations[$vac['timecard_date']] = $vac['timecard_date'];
		}
	}
	foreach ($days as $dt) {
		$query = sprintf("SELECT COUNT(*) AS cnt FROM %sholiday WHERE holiday_date = '%s'", DB_PREFIX, $dt->format("Y-m-d"));
		$tmp = $connection->fetchOne($query);

		if (!($tmp['cnt'] > 0 || isset($vacations[$dt]) || $dt->format('w') == 0 || $dt->format('w') == 6)) {
			return $dt;
		}
	}
	$connection->close ();
}

function getShigotohajime($year, $userid) {
	if (!$year) {
		$year = date('Y');
	}
	$begin = new DateTime($year.'-01-01');
	$end = new DateTime(($year+1).'-01-01');
	$interval = DateInterval::createFromDateString('1 day');
	$period = new DatePeriod($begin, $interval, $end);

	foreach ($period as $dt) {
		$days[] = $dt;
	}

	$connection = new Connection ();

	if ($userid) {
		$query = sprintf("SELECT DISTINCT timecard_date FROM grp_timecard INNER JOIN grp_vacation ON grp_vacation.id = grp_timecard.timecard_vacation_type WHERE owner = '%s' AND YEAR(timecard_date) = '%d' AND grp_vacation.allday = 1", $userid, $year);
		$uservacations = $connection->query($query);

		foreach ($uservacations as $vac) {
			$vacations[$vac['timecard_date']] = $vac['timecard_date'];
		}
	}
	foreach ($days as $dt) {
		$query = sprintf("SELECT COUNT(*) AS cnt FROM %sholiday WHERE holiday_date = '%s'", DB_PREFIX, $dt->format("Y-m-d"));
		$tmp = $connection->fetchOne($query);

		if (!($tmp['cnt'] > 0 || isset($vacations[$dt]) || $dt->format('w') == 0 || $dt->format('w') == 6)) {
			return $dt;
		}
	}
	$connection->close ();
}