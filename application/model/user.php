<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class User extends ApplicationModel {

	function User() {

		if (basename($_SERVER['SCRIPT_NAME']) != 'feed.php') {
			$this->authorize('administrator', 'manager', 'member');
		}
		$this->schema = array(
		'userid'=>array('ユーザーID', 'notnull', 'userid', 'length:100', 'distinct'),
		'password'=>array('パスワード', 'alphaNumeric', 'length:4:32', 'except'=>array('search', 'update')),
		'password_default'=>array('except'=>array('search', 'update')),
		'realname'=>array('名前', 'notnull', 'length:100'),
		'user_group'=>array('グループ', 'numeric', 'length:100', 'except'=>array('search')),
		'user_groupname'=>array(),
		'user_retired'=>array(),
		'authority'=>array('権限', 'notnull', 'alpha', 'length:100', 'except'=>array('search')),
		'user_order'=>array('順序', 'numeric', 'length:10', 'except'=>array('search')),
		'edit_level'=>array('except'=>array('search')),
		'edit_group'=>array('except'=>array('search')),
		'edit_user'=>array('except'=>array('search')));

	}

	function validate() {

		if (isset($this->post['password'])) {
			$this->post['password'] = md5(trim($this->post['password']));
			$this->post['password_default'] = $this->post['password'];
		} elseif ($_POST['resetpassword'] == 1) {
			$data = $this->fetchOne("SELECT password_default FROM ".$this->table." WHERE id = ".intval($_POST['id']));
			if (isset($data['password_default']) && strlen($data['password_default']) > 0) {
				$this->schema['password']['except'] = array('search');
				$this->post['password'] = $data['password_default'];
				$this->post['resetpassword'] = 1;
			} else {
				$this->error[] = '初期登録時のパスワードを取得できませんでした。<br />初期登録時のパスワードが設定されていない場合はパスワードを戻すことはできません。';
			}
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
				$data = $this->fetchOne("SELECT password FROM ".$this->table." WHERE id = ".intval($_POST['id']));
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

	}

	function index() {

		if ($_GET['group'] != 'all') {
			if ($_GET['group'] <= 0) {
				$_GET['group'] = $_SESSION['group'];
			}
			$this->where[] = "(grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($_GET['group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_GET['group']))."))";
		}
		$this->join[] = "LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid";
		$hash = $this->findLimit('grp_user.user_code', 0, null, null, null, true);
		$hash += $this->permitGroup($_GET['group'], 'public', true);
		return $hash;

	}

	function view() {

		$hash['data'] = $this->findView();
		$hash += $this->permitGroup($hash['data']['user_group'], 'public');
		$hash += $this->findUser($hash['data']);
		$hash += $this->getSubGroups($hash['data']['userid']);
		return $hash;

	}

	function add() {

		$hash = $this->permitGroup($_POST['user_group'], 'add');
		if ($_POST['user_group'] > 0) {
			$this->post['user_groupname'] = $hash['parent']['group_name'];
		}
		$hash['data'] = $this->permitInsert();
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function edit() {

		$hash['data'] = $this->permitFind('edit');
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$hash += $this->permitGroup($_POST['user_group'], 'add');
			if ($_POST['user_group'] > 0) {
				$this->post['user_groupname'] = $hash['parent']['group_name'];
			}
			if ($hash['data']['user_group'] != $_POST['user_group'] ) {
				$query = "UPDATE ".DB_PREFIX."group SET group_leader = '' WHERE id = ".$hash['data']['user_group']." AND group_leader = '".$hash['data']['userid']."'";
				$this->query($query);
			}


			$query = "DELETE FROM grp_user_group WHERE userid = '".$hash['data']['userid']."'";
			$this->query($query);

			if (is_array($_POST['subgroups']) && count($_POST['subgroups']) > 0) {
				foreach ($_POST['subgroups'] as $subgrpid) {
					$query = "INSERT INTO grp_user_group (group_id, userid) VALUES (".$subgrpid.", '".$hash['data']['userid']."')";
					$this->query($query);
				}
			}

			$hash['data'] = $this->permitUpdate();
		} else {
			$hash += $this->permitGroup($hash['data']['user_group'], 'add');
			$hash += $this->getSubGroups($hash['data']['userid']);
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function delete() {

		$this->checkUser();
		$hash['data'] = $this->permitFind('edit');
		$hash += $this->permitGroup($hash['data']['user_group'], 'add');
		$this->deletePost();
		$this->redirect();
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function checkUser() {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = $this->fetchOne("SELECT id FROM ".$this->table." WHERE userid = '".$this->quote($_SESSION['userid'])."'");
			if (is_array($data) && count($data) > 0) {
				if ((isset($_POST['id']) && $_POST['id'] == $data['id']) || (is_array($_POST['checkedid']) && in_array($data['id'], $_POST['checkedid']))) {
					$this->error[] = 'ログインしているユーザーは削除できません。';
				}
			}
		}

	}

	function permitGroup($id, $level = 'public', $shortName = false) {

		if ($level == 'add') {
			$where = "WHERE (add_level = 0 OR owner = '%s' OR ";
			$where .= "(add_level = 2 AND (add_group LIKE '%%[%s]%%' OR add_user LIKE '%%[%s]%%')))";
			$where = sprintf($where, $this->quote($_SESSION['userid']), $this->quote($_SESSION['group']), $this->quote($_SESSION['userid']));
		}
		if ($shortName) {
			$short_group_name_with_indent = "CONCAT(REPEAT('　',CHAR_LENGTH(g.group_name)-CHAR_LENGTH(REPLACE(g.group_name,'　',''))),REPLACE(g.group_name,IFNULL(pg.group_name,''),'')) AS group_name";
			$join = "LEFT JOIN ".DB_PREFIX."group pg ON pg.id = g.parent_id ";
			$query = "SELECT g.id,".$short_group_name_with_indent." FROM ".DB_PREFIX."group g ".$join.$where." ORDER BY g.group_name,g.group_order,g.id";
		} else {
			$query = "SELECT id,group_name FROM ".DB_PREFIX."group ".$where." ORDER BY group_name,group_order,id";
		}
		$data = $this->fetchAll($query);
		$result['folder'] = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result['folder'][$row['id']] = $row['group_name'];
			}
		}
		if ($id > 0) {
			$data = $this->fetchOne("SELECT * FROM ".DB_PREFIX."group WHERE id = ".intval($id));
			if ($level == 'add' && !$this->permitted($data, 'add')) {
				$this->died('このグループへの書き込み権限がありません。');
			} else {
				$result['parent'] = $data;
			}
		}
		return $result;

	}

	function feed() {

		if ($_REQUEST['type'] == 1 && $_REQUEST['group'] <= 0) {
			$_REQUEST['group'] = $_SESSION['group'];
		}
		if ($_REQUEST['group'] > 0) {
			$query = "SELECT DISTINCT grp_user.userid, grp_user.realname FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE (grp_user.user_group IN (".$this->getChildrenIdsByParentId(intval($_REQUEST['group'])).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_REQUEST['group'])).")) AND (grp_user.user_retired > CURRENT_TIMESTAMP OR grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00') ORDER BY grp_user.user_order,grp_user.id";
			$hash['list'] = $this->fetchAll($query);
		}
		$hash['group'] = $this->findGroup();
		return $hash;

	}

	function getSubGroups($userid) {
		if (!$userid) {
			$userid = $_SESSION['userid'];
		}

		$query = "SELECT group_id,group_name FROM grp_user_group INNER JOIN grp_group ON grp_group.id = grp_user_group.group_id WHERE userid = '".$userid."' ORDER BY group_order";
		$data = $this->fetchAll($query);
		$subgrps['subgroups'] = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $grp) {
				$subgrps['subgroups'][$grp['group_id']] = $grp['group_name'];
			}
		}
		return $subgrps;
	}

	function getSubMembsByLeaderId($userid) {
		if (!$userid) {
			$userid = $_SESSION['userid'];
		}

		$query = "SELECT id FROM grp_group WHERE group_leader = '".$userid."'";
		$data = $this->fetchAll($query);

		if (is_array($data) && count($data) > 0) {
			foreach ($data as $grp) {
				$subgrps[] = $this->getChildrenIdsByParentIdArray($grp['id']);
			}
		}
		$grps = $this->array_values_recursive($subgrps);
		$query = "SELECT DISTINCT grp_user.userid FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE grp_user.user_group IN (".implode($grps, ",").") OR grp_user_group.group_id IN  (".implode($grps, ",").")";
		$data = $this->fetchAll($query);
		$userids = $this->array_values_recursive($data);
		return $userids;
	}
}

?>