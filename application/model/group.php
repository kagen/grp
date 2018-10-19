<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class Group extends ApplicationModel {

	function Group() {

		$this->authorize('administrator');
		$this->schema = array(
		'group_name'=>array('グループ名', 'notnull', 'length:100','distinct'),
		'group_leader'=>array(),
		'group_order'=>array('順序', 'numeric', 'length:10', 'except'=>array('search')),
		'parent_id'=>array('notnull','except'=>array('search')),
		'add_level'=>array('except'=>array('search')),
		'add_group'=>array('except'=>array('search')),
		'add_user'=>array('except'=>array('search')),
		'edit_level'=>array('except'=>array('search')),
		'edit_group'=>array('except'=>array('search')),
		'edit_user'=>array('except'=>array('search')));

	}

	function index() {

		$hash['list'] = $this->getGroupListWithDepth();
		foreach ($hash['list'] as $idx => $group) {
			$hash['list'][$idx]['group_leader_data'] = $this->getGroupLeaderById($group['id']);
		}
		$hash['count'] = count($hash['list']);
		return $hash;

	}

	function view() {

		$hash['data'] = $this->findView();
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function add() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST['group_name'])) {
				$_POST['group_name'] = $this->trim_emspace($_POST['group_name']);
				$_POST['parent_id'] = $this->getParentIdByGroupName($_POST['group_name']);
				if ($_POST['parent_id'] == 0 && strpos(str_replace(" ", "　", $_POST['group_name']),"　") !== false){
					$this->error[] = '指定した親グループを見つかりませんでした<br>スペースを削除する、または親グループのグループ名を正しくしてください。';
				}
			}
		}
		$hash['data'] = $this->permitInsert();
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function edit() {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->validateSchema('update');
			$this->post['group_name'] = $this->trim_emspace($this->post['group_name']);
			$this->permitValidate();
			$this->post['parent_id'] = $this->getParentIdByGroupName($this->post['group_name']);
			if ($this->post['parent_id'] == 0 && strpos(str_replace(" ", "　", $this->post['group_name']),"　") !== false){
				$this->error[] = '指定した親グループを見つかりませんでした<br>スペースを削除する、または親グループのグループ名を正しくしてください。';
			}
			$this->updatePost();

			if ($this->response) {
				$query = sprintf("UPDATE %s SET user_groupname = '%s' WHERE user_group = %s", DB_PREFIX.'user', $this->quote($this->post['group_name']), intval($_POST['id']));
				$this->response = $this->query($query);
			}
			$this->redirect();
			$hash['data'] = $this->post;
		} else {
			$hash['data'] = $this->findView();
		}
		$hash += $this->findUser($hash['data']);
		$hash['group_user'] = $this->findGroupUser(intval($_GET['id']));
		return $hash;

	}

	function delete() {

		$this->checkGroupuser($_REQUEST['id']);
		$this->checkGroupChildren($_REQUEST['id']);
		$hash['data'] = $this->permitFind('edit');
		$this->deletePost();
		$this->redirect();
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function checkGroupuser($id) {

		$count = $this->fetchCount(DB_PREFIX.'user', "WHERE user_group = '".intval($id)."'", 'id');
		$countsub = $this->fetchCount('grp_user_group', "WHERE group_id = '".intval($id)."'", 'id');
		if ($count + $countsub > 0) {
			$this->error[] = 'このグループに所属しているユーザーが存在します。<br />ユーザーのグループを変更してください。';
		}

	}

	function checkGroupChildren($id) {

		$count = $this->fetchCount(DB_PREFIX.'group', "WHERE parent_id = '".intval($id)."'", 'id');
		if ($count > 0) {
			$this->error[] = 'このグループに子グループが存在します。<br />まず子グループを削除してください。';
		}

	}

	function getGroupLeaderById($group_id) {
		$query = "SELECT ".DB_PREFIX."user.* FROM ".DB_PREFIX."group"." LEFT JOIN ".DB_PREFIX."user ON ".DB_PREFIX."user.userid = ".DB_PREFIX."group.group_leader"." WHERE ".DB_PREFIX."group.id = ".$group_id;
		$data = $this->fetchOne($query);
		return $data;
	}

	function getParentIdByGroupName($group_name) {
		$groups_slice = explode("　", $group_name);
		array_pop($groups_slice);
		$group_parent = join("　",$groups_slice);
		$query = "SELECT id FROM grp_group WHERE group_name = '".$group_parent."' LIMIT 1";
		$data = $this->fetchOne($query);
		return ($data['id']?$data['id']:0);
	}

	function findGroupUser($groupid, $include_retired = false) {

		$query = "SELECT grp_user.* FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE 1".($groupid?" AND (grp_user.user_group = ".$groupid." OR grp_user_group.group_id = ".$groupid.")":"").(!$include_retired?" AND (grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00' OR grp_user.user_retired > CURRENT_TIMESTAMP)":"");
		$data = $this->fetchAll($query);
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $key => $row) {
				$groupuser[$row['userid']] = $row['realname'];
			}
		}
		return $groupuser;

	}

	function getGroupListWithDepth($parent_id = 0) {
		static $idx = 0, $groups, $depth = 0;
		$depth ++;
		$query = "SELECT * FROM ".DB_PREFIX."group WHERE parent_id = ".$parent_id." ORDER BY group_order";
		$data = $this->fetchAll($query);
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $key => $row) {
				$groups[$idx] = $row;
				$groups_slice = explode("　", $groups[$idx]['group_name']);
				$groups[$idx]['short_name'] = $groups_slice[count($groups_slice) - 1];
				$groups[$idx]['depth'] = $depth;
				$current_depth = $depth;
				$current_idxh = $idx;
				$idx ++;
				if (count($this->getGroupListWithDepth($row['id'])) > 0) {
					$groups[$current_idxh]['is_parent'] = true;
				} else {
					$depth = $current_depth;
					$groups[$current_idxh]['is_parent'] = false;
				}
			}
			$depth --;
			return $groups;
		} else {
			return array();
		}
	}

	function feed() {
		$query = "SELECT id, group_name FROM ".$this->table." ORDER BY group_order,id";
		$hash['list'] = $this->fetchAll($query);
		if (isset($_POST["selectedGrpId"])) {
			$hash['selected'] = $_POST["selectedGrpId"];
		}
		if (isset($_POST["mainGrpId"])) {
			$hash['mainGrpId'] = $_POST["mainGrpId"];
		}

		return $hash;

	}

}

?>