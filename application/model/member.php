<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class Member extends ApplicationModel {

	function Member() {

		$this->table = DB_PREFIX.'user';
		$this->schema = array(
		'userid'=>array('except'=>array('search', 'update')),
		'user_group'=>array('except'=>array('search', 'update')),
		'user_groupname'=>array('except'=>array('update')),
		'realname'=>array('名前', 'notnull', 'length:100'),
		'user_ruby'=>array('かな', 'length:100'),
		'user_postcode'=>array('郵便番号', 'postcode', 'length:8'),
		'user_address'=>array('住所', 'length:1000'),
		'user_addressruby'=>array('住所(かな)', 'length:1000'),
		'user_phone'=>array('電話番号', 'phone', 'length:20'),
		'user_mobile'=>array('携帯電話', 'phone', 'length:20'),
		'user_email'=>array('メール', 'notnull:email', 'length:1000'),
		'user_skype'=>array('スカイプID', 'userid', 'length:1000'),
		'user_code'=>array('社員番号', 'userid', 'length:1000'),
		'user_birthday'=>array('生年月日', 'date'),
		'user_joindate'=>array('入社日', 'date'),
		'user_hiretype'=>array('雇用形態', 'notnull'),
		'user_retired'=>array('退職日', 'date'),
		'user_openhour'=>array(),
		'user_openminute'=>array(),
		'user_closehour'=>array(),
		'user_closeminute'=>array(),
		'user_overtime_flg'=>array(),
		'remark'=>array()
		);

	}

	function validate() {
		// 管理者が自分以外のユーザのパスワードをリセット
		if ($_SESSION['authority'] == 'administrator' && $_SESSION['userid'] != $_POST['userid'] && (strlen($_POST['newpassword']) > 0 || strlen($_POST['confirmpassword']) > 0)) {
			$this->validator('newpassword', '新しいパスワード', array('alphaNumeric', 'length:4:32'));
			$this->validator('confirmpassword', '新しいパスワード(確認)', array('alphaNumeric', 'length:4:32'));
			$_POST['newpassword'] = trim($_POST['newpassword']);
			$_POST['confirmpassword'] = trim($_POST['confirmpassword']);
			if ($_POST['newpassword'] != $_POST['confirmpassword']) {
				$this->error[] = '新しいパスワードと確認用パスワードが違います。';
			} else {
				$this->schema['password']['except'] = array('search');
				$this->post['password'] = md5($_POST['newpassword']);
			}
		// 自分のパスワードを変更
		} elseif (strlen($_POST['password']) > 0 || strlen($_POST['newpassword']) > 0 || strlen($_POST['confirmpassword']) > 0) {
			$this->validator('password', 'パスワード', array('alphaNumeric', 'length:4:32'));
			$this->validator('newpassword', '新しいパスワード', array('alphaNumeric', 'length:4:32'));
			$this->validator('confirmpassword', '新しいパスワード(確認)', array('alphaNumeric', 'length:4:32'));
			$_POST['password'] = trim($_POST['password']);
			$_POST['newpassword'] = trim($_POST['newpassword']);
			$_POST['confirmpassword'] = trim($_POST['confirmpassword']);
			if ($_POST['newpassword'] != $_POST['confirmpassword']) {
				$this->error[] = '新しいパスワードと確認用パスワードが違います。';
			} else {
				$data = $this->fetchOne("SELECT password FROM ".$this->table." WHERE userid = '".$this->quote($_SESSION['userid'])."'");
				if (is_array($data) && count($data) > 0) {
					if ($data['password'] === md5($_POST['password'])) {
						$this->schema['password']['except'] = array('search');
						$this->post['password'] = md5($_POST['newpassword']);
					} else {
						$this->error[] = '現在のパスワードが違います。';
					}
				} else {
					$this->error[] = 'パスワード確認時にエラーが発生しました。';
				}
			}
		}
		if (strlen($_POST['user_email']) > 0) {
			$data = $this->fetchOne("SELECT user_email, userid, id FROM ".$this->table." WHERE user_email = '".$this->quote($_POST['user_email'])."'");
			if (is_array($data) && count($data) > 0 && $_POST['id'] != $data['id']) {
				$this->error[] = 'メールが既に存在しています。';
			}
		}

		if (strtotime($_POST['user_opentime']) > strtotime($_POST['user_closetime'])) {
			$this->error[] = '始業時間と終業時間を正しく入力してください。';
		}else {
			$this->post['user_openhour'] = date("H",strtotime($_POST['user_opentime']));
			$this->post['user_openminute'] = date("i",strtotime($_POST['user_opentime']));
			$this->post['user_closehour'] = date("H",strtotime($_POST['user_closetime']));
			$this->post['user_closeminute'] = date("i",strtotime($_POST['user_closetime']));
		}
	}

	function index() {

		$join = "";
		if (!isset($_GET['group'])) {
			$_GET['group'] = 'all';
		} else if ($_GET['group'] != 'all') {
			if ($_GET['group'] <= 0) {
				$_GET['group'] = $_SESSION['group'];
			}
			$this->join[] = "LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid";
			$this->where[] = "(grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_GET['group']))."))";
		}

		$this->where[] = "(grp_user.user_retired > CURRENT_TIMESTAMP OR grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00')";

		$hash = $this->findLimit('user_code', 0, null, null, null, true);
		$hash['group'] = $this->findGroup(true);
		if (is_array($hash['list']) && count($hash['list']) > 0) {
			for ($i = 0; $i < count($hash['list']); $i++) {
				$hash['list'][$i]['is_in_office'] = $this->getInOfficeStatus($hash['list'][$i]['userid']);
				$hash['list'][$i]['is_group_leader'] = $this->isGroupLeader($hash['list'][$i]['userid'], $_GET['group']);
			}
		}

		return $hash;

	}

	function view() {

		$hash['data'] = $this->findView();
		$config = new Config($this->handler);
		$hash['config']['timecard'] = $config->configure('timecard');
		require_once(DIR_MODEL.'user.php');
		$user = new User;
		$hash['data'] += $user->getSubGroups($hash['data']['userid']);
		if ($hash['data']['userid'] !== $_SESSION['userid']) {
			$hash['user_is_boss_of_target_user'] = $this->isMySupervisorByUserid($hash['data']['userid'], $_SESSION['userid']);
		}

		return $hash;

	}

	function edit() {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->validateSchema('update');
			$this->validate();
			$this->post['userid'] = $_POST['userid'];
			$this->post['editor'] = $_SESSION['userid'];
			$this->post['updated'] = date('Y-m-d H:i:s');
			if (count($this->error) <= 0) {
				$field = $this->schematize('update');
				foreach ($field as $key) {
					if (isset($this->post[$key])) {
						$array[] = $key." = '".$this->quote($this->post[$key])."'";
					}
				}
				$query = sprintf("UPDATE %s SET %s WHERE id = '%d'", $this->table, implode(",", $array), $_POST['id']);
				$this->response = $this->query($query);
			}
			$this->redirect();
			$hash['data'] = $this->post;
		} else {
			$field = implode(',', $this->schematize());
			if (isset($_GET['id']) && $_SESSION['authority'] == 'administrator') {
				$sql = "SELECT ".$field." FROM ".$this->table." WHERE id = ".$this->quote($_GET['id']);
			} else {
				$sql = "SELECT ".$field." FROM ".$this->table." WHERE userid = '".$this->quote($_SESSION['userid'])."'";
			}
			$hash['data'] = $this->fetchOne($sql);
			$hash['data']['user_opentime'] = date("H:i",strtotime($hash['data']['user_openhour'].":".$hash['data']['user_openminute']));
			$hash['data']['user_closetime'] = date("H:i",strtotime($hash['data']['user_closehour'].":".$hash['data']['user_closeminute']));
		}
		return $hash;

	}

	function csv() {

		$field = "grp_user.user_code, grp_user.realname, grp_user.user_email";
		if ($_GET['group'] == "all" || $_GET['group'] == null) {
			$group = "TRUE";
		} else {
			$group = "(grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_GET['group']))."))";
		}
		$query = sprintf("SELECT DISTINCT %s FROM %s LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE ". $group ." AND (grp_user.user_retired > CURRENT_TIMESTAMP OR grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00') ORDER BY grp_user.user_code", $field, $this->table);
		$list = $this->fetchAll($query);
		if (is_array($list) && count($list) > 0) {
			for ($i = 1; $i <= count($list); $i++) {
				$csv .= '"'.$list[$i]['user_code'].'","';
				$csv .= $list[$i]['realname'].'","';
				$csv .= $list[$i]['user_email'].'"'."\n";
			}
			header('Content-Disposition: attachment; filename=members'.date('Ymd').'.csv');
			header('Content-Type: application/octet-stream; name=members'.date('Ymd').'.csv');
			echo mb_convert_encoding($csv, 'SJIS', 'UTF-8');
			exit();
		} else {
			$this->died('データが見つかりません。');
		}
	}

}

?>