<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class General extends ApplicationModel {

	function index() {
		$sessionuserid = $this->quote($_SESSION['userid']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST['timecard_open']) || isset($_POST['timecard_close']) || isset($_POST['timecard_interval'])) {
				require_once(DIR_MODEL.'timecard.php');
				$timecard = new Timecard;
				$timecard->handler = $this->handler;
				$timecard->add();
			} elseif (isset($_POST['folder']) && $_POST['folder'] == 'complete') {
				require_once(DIR_MODEL.'todo.php');
				$todo = new Todo;
				$todo->handler = $this->handler;
				$todo->move();
			}
			if (isset($_POST['approvvacationids']) && count($_POST['approvvacationids']) > 0) {
				$ids = join($_POST['approvvacationids'], ",");
				$query = "UPDATE ".DB_PREFIX."timecard SET timecard_vacation_approved = CURRENT_TIMESTAMP, timecard_vacation_supervisor_userid = '".$sessionuserid."' WHERE id IN (".$ids.")";
				$this->response = $this->query($query);
			}
		}

		$hash['year'] = date('Y');
		$hash['month'] = date('n');
		$hash['day'] = date('j');
		$hash['weekday'] = date('w');
		$monthly = mktime(0, 0, 0, $hash['month'] - 1, $hash['day'], $hash['year']);
		$hash['begin'] = mktime(0, 0, 0, $hash['month'], $hash['day'] - $hash['weekday'], $hash['year']);
		$hash['end'] = mktime(23, 59, 59, $hash['month'], $hash['day'] + 6 - $hash['weekday'], $hash['year']);
		$string = "((schedule_type = 0 AND (schedule_date >= '%s' AND schedule_date <= '%s')) OR ";
		$string .= "(schedule_type = 1 AND (schedule_begin <= '%s' AND schedule_end >= '%s')))";
		$where[] = sprintf($string, date('Y-m-d', $hash['begin']), date('Y-m-d', $hash['end']), date('Y-m-d', $hash['end']), date('Y-m-d', $hash['begin']));
		$string = "((schedule_level = 0) OR (schedule_level = 1 AND owner = '%s') OR ";
		$string .= "(schedule_level = 2 AND (schedule_group LIKE '%%[%s]%%' OR schedule_user LIKE '%%[%s]%%')))";
		$where[] = sprintf($string, $sessionuserid, $this->quote($_SESSION['group']), $sessionuserid);
		$where[] = $this->permitWhere();

		#スケジュール
		$field = "*";
		$query = sprintf("SELECT %s FROM %sschedule WHERE %s ORDER BY schedule_allday,schedule_time,schedule_endtime", $field, DB_PREFIX, implode(" AND ", $where));
		$hash['schedule'] = $this->fetchAll($query);

		#タイムカード
		$field = "*";
		$query = sprintf("SELECT %s FROM %stimecard WHERE (timecard_date = '%s') AND (owner = '%s')", $field, DB_PREFIX, date('Y-m-d'), $sessionuserid);
		$hash['timecard'] = $this->fetchOne($query);

		#ＴＯＤＯリスト
		$field = "*";
		$query = sprintf("SELECT %s FROM %stodo WHERE (owner = '%s') AND (todo_complete = 0) ORDER BY todo_noterm, todo_term", $field, DB_PREFIX, $sessionuserid);
		$hash['todo'] = $this->fetchLimit($query, 0, 5);

		#お知らせ
		$field = "grp_news.*, owner.realname AS owner, editor.realname AS editor, (SELECT MAX(access_at) FROM grp_access WHERE access_table = 'grp_news' AND access_id = grp_news.id AND access_userid = '".$sessionuserid."') AS access_at";
		$query = sprintf("SELECT DISTINCT %s FROM %snews INNER JOIN grp_user owner ON owner.userid = grp_news.owner LEFT JOIN grp_user editor ON editor.userid = grp_news.editor WHERE (news_hide = 0) AND ((news_begin IS NULL OR news_begin <= CURRENT_TIMESTAMP) AND (news_end IS NULL OR news_end >= CURRENT_TIMESTAMP)) ORDER BY updated, created", $field, DB_PREFIX);
		$hash['news'] = $this->fetchAll($query);

		#メッセジー
		$field = "*";
		$query = sprintf("SELECT %s FROM %smessage WHERE (owner = '%s') AND (folder_id = 0) AND (message_type = 'received') ORDER BY message_date DESC", $field, DB_PREFIX, $sessionuserid);
		$hash['message'] = $this->fetchLimit($query, 0, 5);
		$where = array();
		$category = $this->permitCategory('forum');
		$where[] = "(forum_lastupdate > '".date('Y-m-d H:i:s', $monthly)."')";
		$where[] = $this->folderWhere($category['folder'], 'all');
		$where[] = "(forum_parent = 0)";
		$where[] = $this->permitWhere('',true);
		$where[] = sprintf("(grp_access.id IS NULL OR grp_access.id = (SELECT MAX(id) FROM grp_access WHERE grp_access.access_table = 'grp_forum' AND grp_access.access_id = grp_forum.id AND grp_access.access_userid = '%s'))",$sessionuserid);

		#掲示板
		$field = "grp_forum.*, grp_access.access_at";
		$query = sprintf("SELECT %s FROM %sforum LEFT JOIN grp_access ON grp_access.access_table = 'grp_forum' AND grp_access.access_id = grp_forum.id AND grp_access.access_userid = '%s' WHERE %s ORDER BY forum_lastupdate DESC", $field, DB_PREFIX, $sessionuserid, implode(" AND ", $where));
		$hash['forum'] = $this->fetchLimit($query, 0, 5);
		$where = array();
		$category = $this->permitCategory('bookmark');
		$where[] = $this->folderWhere($category['folder']);
		$where[] = $this->permitWhere();

		#ブックマーク
		$field = "*";
		$query = sprintf("SELECT %s FROM %sbookmark WHERE %s ORDER BY bookmark_order, bookmark_date DESC", $field, DB_PREFIX, implode(" AND ", $where));
		$hash['bookmark'] = $this->fetchLimit($query, 0, 5);
		$where = array();
		$category = $this->permitCategory('project');
		$where[] = "(project_end >= '".date('Y-m-d')."')";
		$where[] = $this->folderWhere($category['folder'], 'all');
		$where[] = "(project_parent = '0')";
		$where[] = $this->permitWhere();

		#プロジェクト
		$field = "*";
		$query = sprintf("SELECT %s FROM %sproject WHERE %s ORDER BY project_begin", $field, DB_PREFIX, implode(" AND ", $where));
		$hash['project'] = $this->fetchLimit($query, 0, 5);
		$hash['group'] = $this->findGroup();

		#休暇申請承認待ち
		$field = "*";
		$query = sprintf("SELECT %s FROM %sgroup WHERE group_leader = '%s'", $field, DB_PREFIX, $sessionuserid);
		$cntGroupLeader = $this->fetchAll($query);
		if ((is_array($cntGroupLeader) && count($cntGroupLeader) > 0) || $_SESSION['authority'] == 'administrator'){
			if (is_array($cntGroupLeader) && count($cntGroupLeader) > 0) {
				foreach ($cntGroupLeader as $grp) {
					$group_ids = $group_ids.($group_ids == ""?"":",").$this->getUserGroups(intval($grp['id']),$sessionuserid);
				}
				$hash['leader'] = true;
			}

			if ($_SESSION['authority'] == 'administrator'){
				$hash['admin'] = true;
			}

			$field = "grp_timecard.timecard_date AS timecard_date,".
					($hash['leader']?"CASE WHEN grp_group.id IN (".$group_ids.") THEN 1 ELSE 0 END AS vacation_from_my_group,":"0 AS vacation_from_my_group,").
					"grp_timecard.timecard_vacation_comment AS timecard_vacation_comment,
					grp_vacation.vacation_name AS vacation_type,
					grp_vacation.allday AS allday,
					grp_timecard.timecard_vacation_from AS timecard_vacation_from,
					grp_timecard.timecard_vacation_to AS timecard_vacation_to,
					grp_user.realname AS realname, grp_timecard.id AS timecard_id";


			$query = "SELECT DISTINCT ".$field." FROM ".DB_PREFIX."timecard
				INNER JOIN grp_user ON grp_user.userid = grp_timecard.owner
				INNER JOIN grp_vacation ON grp_vacation.id = grp_timecard.timecard_vacation_type
				INNER JOIN grp_group ON grp_group.id = grp_user.user_group
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.fixed_from <= grp_timecard.timecard_date AND grp_timecard_fixed.fixed_to >= grp_timecard.timecard_date
				WHERE grp_timecard.timecard_vacation_requested IS NOT NULL
					AND grp_timecard.timecard_vacation_approved IS NULL AND grp_timecard_fixed.fixed_at IS NULL ".
					($hash['admin']?"":"AND grp_timecard.owner <> '".$sessionuserid."' AND (grp_group.id IN (".$group_ids.") OR grp_user_group.group_id IN (".$group_ids."))").
					" ORDER BY grp_timecard.timecard_date ASC";

			$hash['waitingapprov'] = $this->fetchAll($query);

			#時間外申請件数
			$user = new User();
			$bukaids = $user->getSubMembsByLeaderId($userid);
			$query = "SELECT count(*) AS cnt FROM grp_overtime
				INNER JOIN grp_user ON grp_user.userid = grp_overtime.owner
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.fixed_from <= grp_overtime.overtime_date AND grp_timecard_fixed.fixed_to >= grp_overtime.overtime_date
				WHERE ".($_SESSION['authority'] == 'administrator'?"":"grp_overtime.owner IN ('".implode("','", $bukaids)."') AND ")."grp_overtime.overtime_time_approved IS NULL AND grp_timecard_fixed.fixed_at IS NULL";
			$otcount = $this->fetchOne($query);
			$hash['overtime_approved_count'] = $otcount['cnt'];

			#時間外超過コメント申請件数
			$query = "SELECT count(*) AS cnt FROM grp_overtime_comment
				INNER JOIN grp_user ON grp_user.userid = grp_overtime_comment.owner
				LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid
				LEFT JOIN grp_timecard_fixed ON grp_timecard_fixed.owner = grp_user.userid AND grp_timecard_fixed.timecard_year = grp_overtime_comment.timecard_year AND grp_timecard_fixed.timecard_month = grp_overtime_comment.timecard_month
				WHERE ".($_SESSION['authority'] == 'administrator'?"":"grp_overtime_comment.owner IN ('".implode("','", $bukaids)."') AND ")."grp_overtime_comment.timecard_overtime_supervisor_userid IS NULL AND grp_timecard_fixed.fixed_at IS NULL";
			$otcmtcount = $this->fetchOne($query);
			$hash['overtime_comment_approved_count'] = $otcmtcount['cnt'];


		} else {
			$hash['leader'] = false;
			$hash['admin'] = false;
		}

		return $hash;

	}

	function administration() {

		$this->authorize('administrator', 'manager');
		if (file_exists('setup.php')) {
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				if (is_writable('setup.php')) {
					if (@unlink('setup.php') == false) {
						$this->error[] = 'セットアップファイルの削除に失敗しました。';
					}
				} else {
					$this->error[] = 'セットアップファイルに書き込み権限がありません。<br />削除に失敗しました。';
				}
			} else {
				$this->error[] = 'セットアップファイル(setup.php)が存在します。<br />削除してください。';
			}
		}

	}

}

?>