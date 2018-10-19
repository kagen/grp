<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class ApplicationModel extends Model {

	function authorize() {

		$this->connect();
		$authorized = false;
		$argument = func_get_args();
		if (is_array($argument) && count($argument) > 0) {
			$data = $this->fetchOne("SELECT authority FROM ".DB_PREFIX."user WHERE userid = '".$this->quote($_SESSION['userid'])."'");
			foreach ($argument as $value) {
				if (strlen($value) > 0 && $value === $data['authority']) {
					$authorized = true;
				}
			}
		}
		if ($authorized !== true) {
			$this->died('権限がありません。');
		}
		return $authorized;

	}

	function permitList($sort = 'id', $desc = 1) {

		$this->where[] = $this->permitWhere('',true);
		return $this->findLimit($sort, $desc);

	}

	function simpleList($sort = 'id', $desc = 1, $join = null, $field = null) {

		return $this->findLimit($sort, $desc, null, $join, $field);
	}

	function permitWhere($level = '', $findWhatCanAccess = false) {

		$usergrpids = explode(",",$this->getUserGroups($_SESSION['group_id'],$_SESSION['userid']));

		// 誰か見れるを探すではなく、何か見れるかを探すので、親グループを取得
		if ($findWhatCanAccess) {
			$mygroups = array();
			foreach ($usergrpids as $gid) {
				$mygroups = array_merge($mygroups,$this->getParentIdsByGroupId($gid));
				foreach ($mygroups as $pgid) {
					$grpwhere .= ($grpwhere == ""?"":" OR ")."public_group LIKE '%%[".$pgid."]%%'";
					$grpaddwhere .= ($grpaddwhere == ""?"":" OR ")."add_group LIKE '%%[".$pgid."]%%'";
				}
			}

		} else {
			foreach ($usergrpids as $usergroupid) {
				$grpwhere .= ($grpwhere == ""?"":" OR ")."public_group LIKE '%%[".$usergroupid."]%%'";
				$grpaddwhere .= ($grpaddwhere == ""?"":" OR ")."add_group LIKE '%%[".$usergroupid."]%%'";
			}
		}
		$grpwhere = "(".$grpwhere.")";
		$grpaddwhere = "(".$grpaddwhere.")";

		$query = "(public_level = 0 OR owner = '%s' OR ";
		$query .= "(public_level = 2 AND (".$grpwhere." OR public_user LIKE '%%[%s]%%')))";
		$where = sprintf($query, $this->quote($_SESSION['userid']), $this->quote($_SESSION['userid']));
		if ($level == 'add') {
			$query = "AND (add_level = 0 OR owner = '%s' OR ";
			$query .= "(add_level = 2 AND (".$grpaddwhere." OR add_user LIKE '%%[%s]%%')))";
			$where .= sprintf($query, $this->quote($_SESSION['userid']), $this->quote($_SESSION['userid']));
		}
		return $where;

	}

	function permitFind($level = 'public', $id = 0) {

		if ($id <= 0) {
			$id = $_REQUEST['id'];
		}
		if ($id > 0) {
			$field = implode(',', $this->schematize());
			$data = $this->fetchOne("SELECT ".$field." FROM ".$this->table." WHERE id = ".intval($id));
			if ($this->permitted($data, 'public')) {
				if ($level == 'edit' && !$this->permitted($data, 'edit')) {
					$this->died('編集する権限がありません。');
				} else {
					return $data;
				}
			} else {
				$this->died('閲覧する権限がありません。');
			}
		}

	}

	function permitted($data, $level = 'public') {
		$utility = new Utility();
		$permission = false;
		if ($data[$level.'_level'] == 0) {
			$permission = true;
		} elseif (strlen($data['owner']) > 0 && $data['owner'] == $_SESSION['userid']) {
			$permission = true;
		} elseif ($data[$level.'_level'] == 2 && stristr($data[$level.'_user'], '['.$_SESSION['userid'].']')) {
			$permission = true;
		} elseif ($data[$level.'_level'] == 2 && !is_null($data[$level.'_group'])) {
			$strgids = $data[$level.'_group'];
			$strgids = ltrim($strgids, "[");
			$strgids = rtrim($strgids, "]");
			$gids = explode("][", $strgids);
			$subgrpids = $this->getChildrenIdsByParentIdsArray($gids);
			$usergrpids = explode(",",$this->getUserGroups($_SESSION['group_id'],$_SESSION['userid']));
			if (is_array($usergrpids)) {
				if ($utility->array_intersect_flatten($subgrpids, $usergrpids)) {
					$permission = true;
				}
			} else {
				if (in_array($usergrpids, $subgrpids)) {
					$permission = true;
				}
			}
		}
		return $permission;

	}

	function permitInsert($redirect = 'index.php') {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->validateSchema('insert');
			$this->permitValidate();
			if (method_exists($this, 'validate')) {
				$this->validate();
			}
			$this->insertPost();
			$this->redirect($redirect);
		}
		return $this->post;

	}

	function permitUpdate($redirect = 'index.php') {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->validateSchema('update');
			$this->permitValidate();
			if (method_exists($this, 'validate')) {
				$this->validate();
			}
			$this->updatePost();
			$this->redirect($redirect);
			return $this->post;
		}

	}

	function permitValidate() {

		$array = array('public'=>'公開', 'edit'=>'編集を許可', 'add'=>'書き込みを許可');
		foreach ($array as $key => $value) {
			if (isset($_POST[$key.'_level'])) {
				$this->post[$key.'_level'] = intval($_POST[$key.'_level']);
				if ($_POST[$key.'_level'] == 2) {
					if (count($_POST[$key]['group']) <= 0 && count($_POST[$key]['user']) <= 0) {
						$this->error[] = $value.'するグループ・ユーザーを選択してください。';
					} else {
						$this->post[$key.'_group'] = $this->permitParse($_POST[$key]['group']);
						$this->post[$key.'_user'] = $this->permitParse($_POST[$key]['user']);
					}
				} else {
					$this->post[$key.'_group'] = '';
					$this->post[$key.'_user'] = '';
				}
			}
		}

	}

	function permitParse($array) {

		if (is_array($array) && count($array) > 0) {
			$array = array_unique(array_keys($array));
			$string = '['.implode('][', $array).']';
			if (!preg_match('/^[-_a-zA-Z0-9\.\[\]]*$/', $string)) {
				$this->error[] = '権限の設定が無効です。';
			}
		}
		return $string;

	}

	function permitOwner($id = 0) {

		if ($id <= 0) {
			$id = $_REQUEST['id'];
		}
		if ($id > 0) {
			$field = implode(',', $this->schematize());
			$data = $this->fetchOne("SELECT ".$field." FROM ".$this->table." WHERE (id = ".intval($id).") AND (owner = '".$this->quote($_SESSION['userid'])."')");
			return $data;
		}

	}

	function findGroup($shortName = false) {
		if ($shortName) {
			$short_group_name_with_indent = "CONCAT(REPEAT('　',CHAR_LENGTH(g.group_name)-CHAR_LENGTH(REPLACE(g.group_name,'　',''))),REPLACE(g.group_name,IFNULL(pg.group_name,''),'')) AS group_name";
			$join = "LEFT JOIN ".DB_PREFIX."group pg ON pg.id = g.parent_id ";
			$query = "SELECT g.id,".$short_group_name_with_indent." FROM ".DB_PREFIX."group g ".$join." ORDER BY g.group_name,g.group_order,g.id";
		} else {
			$query = "SELECT id,group_name FROM ".DB_PREFIX."group ORDER BY group_name,group_order,id";
		}

		$data = $this->fetchAll($query);
		$array = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$array[$row['id']] = $row['group_name'];
			}
		}
		return $array;

	}

	function findUser() {

		$group = $this->findGroup();
		$argument = func_get_args();
		$array = array();
		foreach ($argument as $row) {
			if (isset($row['owner']) && strlen($row['owner']) > 0) {
				$array[] = $this->quote($row['owner']);
			}
			if (isset($row['editor']) && strlen($row['editor']) > 0) {
				$array[] = $this->quote($row['editor']);
			}
			if (is_array($row) && count($row) > 0) {
				foreach ($row as $key => $value) {
					if (strlen($value) > 0 && stristr($key, '_user') && stristr($value, '[')) {
						$string .= $value;
					}
				}
			}
		}
		if (strlen($string) > 0) {
			$data = explode(',', str_replace(array('][', '[', ']'), array(',', '', ''), $string));
			$data = array_unique($data);
			if (is_array($data) && count($data) > 0) {
				foreach ($data as $value) {
					if (strlen($value) > 0) {
						$array[] = $this->quote($value);
					}
				}
			}
		}
		$array = array_unique($array);
		$user = array();
		if (is_array($array) && count($array) > 0) {
			$query = "SELECT userid, realname FROM ".DB_PREFIX."user WHERE userid IN ('".implode("','", $array)."') ORDER BY user_order,id";
			$data = $this->fetchAll($query);
			if (is_array($data) && count($data) > 0) {
				foreach ($data as $row) {
					$user[$row['userid']] = $row['realname'];
				}
			}
		}
		return array('group'=>$group, 'user'=>$user);

	}

	function permitCategory($type, $id = 0, $level = 'public') {

		$query = sprintf("SELECT folder_id,folder_caption FROM %sfolder WHERE (folder_type = '%s') AND %s ORDER BY folder_order,folder_name", DB_PREFIX, $type, $this->permitWhere($level));
		$data = $this->fetchAll($query);
		$result['folder'] = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result['folder'][$row['folder_id']] = $row['folder_caption'];
			}
		}
		if ($id > 0) {
			$query = sprintf("SELECT * FROM %sfolder WHERE (folder_type = '%s') AND (folder_id = '%s')", DB_PREFIX, $type, intval($id));
			$data = $this->fetchOne($query);
			if ($this->permitted($data, 'public')) {
				if ($level == 'add' && !$this->permitted($data, 'add')) {
					$this->died('このカテゴリへの書き込み権限がありません。');
				} else {
					$result['category'] = $data;
				}
			} else {
				$this->died('閲覧する権限がありません。');
			}
		}
		return $result;

	}

	function folderWhere($folder, $default = '0') {

		if (strlen($_GET['folder']) > 0) {
			$id = $_GET['folder'];
		} else {
			$id = $default;
		}
		if ($id === 'all') {
			if (is_array($folder) && count($folder) > 0) {
				$array = array_keys($folder);
			}
			$array[] = '0';
			return "(folder_id IN (".implode(",", $array)."))";
		} else {
			return "(folder_id = ".intval($id).")";
		}

	}

	function findFolder($type) {

		$query = sprintf("SELECT folder_id,folder_caption FROM %sfolder WHERE (folder_type = '%s') AND (owner = '%s') ORDER BY folder_order,folder_name", DB_PREFIX, $type, $_SESSION['userid']);
		$data = $this->fetchAll($query);
		$result = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result[$row['folder_id']] = $row['folder_caption'];
			}
		}
		return $result;

	}

	function parameter($array) {

		if (is_array($array) && count($array) > 0) {
			foreach ($array as $key => $value) {
				if ($value > 0) {
					$result[] = $key.'='.intval($value);
				}
			}
		}
		if (is_array($result) && count($result) > 0) {
			return '?'.implode('&', $result);
		}

	}

	function listReason() {

		$query = sprintf("SELECT * FROM %sreason WHERE 1 ORDER BY id", DB_PREFIX);
		$data = $this->fetchAll($query);
		$result = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result[$row['id']] = $row['reason_desc'];
			}
		}
		return $result;

	}

	function getReason($id) {

		$query = sprintf("SELECT * FROM %sreason WHERE id = ".intval($id), DB_PREFIX);
		$data = $this->fetchOne($query);
		return $data;

	}

	function listVacation() {

		$query = sprintf("SELECT * FROM %svacation WHERE 1 ORDER BY id", DB_PREFIX);
		$data = $this->fetchAll($query);
		$result = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result[$row['id']] = $row['vacation_name'];
			}
		}
		return $result;

	}

	function getVacationInfo() {

		$query = sprintf("SELECT * FROM %svacation WHERE 1 ORDER BY id", DB_PREFIX);
		$data = $this->fetchAll($query);
		$result = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result[$row['id']]['vacation_name'] = $row['vacation_name'];
				$result[$row['id']]['shortname'] = $row['shortname'];
				$result[$row['id']]['allday'] = $row['allday'];
				$result[$row['id']]['paid'] = $row['paid'];
				$result[$row['id']]['vacation_comment'] = $row['vacation_comment'];
			}
		}
		return $result;

	}

	function getVacation($id) {

		$query = sprintf("SELECT * FROM %svacation WHERE id = ".intval($id), DB_PREFIX);
		$data = $this->fetchOne($query);
		return $data;

	}

	function findUserByUserId($userid) {

		if (!isset($userid)) {
			$id = $_SESSION['userid'];
		}
		$query = sprintf("SELECT * FROM %suser WHERE userid = '%s'", DB_PREFIX, $userid);
		return $this->fetchOne($query);

	}

	function getUserNameByUserId($userid) {

		if (!isset($userid)) {
			$userid = $_SESSION['userid'];
		}
		$query = sprintf("SELECT realname FROM %suser WHERE userid = '%s'", DB_PREFIX, $userid);
		$user = $this->fetchOne($query);
		return $user['realname'];

	}

	function getGroupNameByGroupId($groupid) {

		if (!isset($groupid)) {
			$groupid = $_SESSION['group'];
		}
		$query = sprintf("SELECT group_name FROM %sgroup WHERE id = '%s'", DB_PREFIX, $groupid);
		$group = $this->fetchOne($query);
		return $group['group_name'];

	}

	function getUserGroups($groupids, $userid = null) {
		$grpids = array();
		if (is_array($groupids) && count($groupids) > 0){
			$grpids = $groupids;
		} elseif (!isset($groupids)) {
			$grpids = array($_SESSION['group']);
		} else {
			$grpids = array($groupids);
		}

		if ($userid) {
			$query = "SELECT group_id FROM grp_user_group WHERE userid = '".$userid."'";
			$data = $this->fetchAll($query);
			$utility = new Utility();
			if (is_array($data) && count($data) > 0) {
				$grpids = array_merge($grpids, $utility->flatarray($data, 'group_id'));
			}
		}
		$result = array();
		foreach ($grpids as $id) {
			$result = array_merge($result, explode(',',$this->getChildrenIdsByParentId($id)));
		}
		return implode(',', $result);
	}

	//getChildrenIdsByParentIdの戻り値がコンマで繋いた文字列のバージョン
	function getChildrenIdsByParentId($groupid) {

		if (!isset($groupid)) {
			$groupid = $_SESSION['group'];
		}
		$query = "SELECT id FROM ".DB_PREFIX."group WHERE parent_id = ".$groupid;
		$data = $this->fetchAll($query);
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $key => $row) {
				$sub_groupids .= ($sub_groupids == ""?"":",").$this->getChildrenIdsByParentId($row[id]);
			}
			$groupids .= $sub_groupids.($sub_groupids == ""?"":",").$groupid;
			return $groupids;
		} else {
			return $groupid;
		}

	}

	function getParentIdsByGroupId($groupid, $includethisgroup = true) {

		if (!isset($groupid)) {
			$groupid = $_SESSION['group'];
		}
		if ($includethisgroup) {
			$parentids[] = $groupid;
		}

		while ($groupid != 0){
			$query = "SELECT parent_id FROM grp_group WHERE id = ".$groupid;
			$data = $this->fetchOne($query);

			if ($data['parent_id'] != 0) {
				$parentids[] = $data['parent_id'];
			}
			$groupid = $data['parent_id'];
		}
		return $parentids;
	}

	function isMySupervisorByUserid($userid, $bossid) {
		$query = "SELECT user_group FROM grp_user WHERE userid = '".$userid."'";
		$data = $this->fetchOne($query);
		$parentid = $data['user_group'];
		$mygroups = $this->getParentIdsByGroupId($parentid);

		//兼任
		$query = "SELECT group_id FROM grp_user_group WHERE userid = '".$userid."'";
		$data = $this->fetchAll($query);
		foreach ($data as $gid) {
			$mygroups = array_merge($mygroups,$this->getParentIdsByGroupId($gid['group_id']));
		}

		foreach ($mygroups as $gid) {
			$query = "SELECT * FROM grp_group WHERE group_leader = '".$bossid."' AND group_leader <> '".$userid."' AND id = ".$gid;
			$data = $this->fetchOne($query);
			if (is_array($data) && count($data) > 0) {
				return true;
			}
		}
		return false;
	}

	//getChildrenIdsByParentIdの戻り値が配列のバージョン
	function getChildrenIdsByParentIdArray($groupid) {
		if (!isset($groupid)) {
			$groupid = $_SESSION['group'];
		}
		$query = "SELECT id FROM ".DB_PREFIX."group WHERE parent_id = ".$groupid;
		$data = $this->fetchAll($query);
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $key => $row) {
				$sub_groupids[] = $this->getChildrenIdsByParentIdArray($row[id]);
			}
			$sub_groupids[] = $groupid;
			return $sub_groupids;
		} else {
			return $groupid;
		}
	}

	//getChildrenIdsByParentIdArrayの複数バージョン
	function getChildrenIdsByParentIdsArray($groupids) {
		if (isset($groupids) && is_array($groupids)) {
			$all_groupids = array();
			foreach ($groupids as $groupid) {
				$resultgrpids = $this->getChildrenIdsByParentIdArray($groupid);
				$all_groupids = array_merge($all_groupids, (array)$resultgrpids);
			}
		}
		return $all_groupids;
	}

	function getUserListByGroupId($groupid, $exceptRetired = false) {

		if (!isset($groupid)) {
			$groupid = $_SESSION['group'];
		}
		$query = "SELECT DISTINCT grp_user.* FROM grp_user LEFT JOIN grp_user_group ON grp_user_group.userid = grp_user.userid WHERE (user_group IN (".$this->getChildrenIdsByParentId($groupid).") OR grp_user_group.group_id IN (".$this->getChildrenIdsByParentId(intval($_GET['group']))."))".($exceptRetired?" AND (grp_user.user_retired > CURRENT_TIMESTAMP OR grp_user.user_retired IS NULL OR grp_user.user_retired = '0000-00-00')":"");
		return $this->fetchAll($query);
	}

	function getInOfficeStatus($userid) {

		if (!isset($userid)) {
			$groupid = $_SESSION['userid'];
		}
		$query = "SELECT timecard_open FROM ".DB_PREFIX."timecard WHERE timecard_date = CURDATE() AND (timecard_open IS NOT NULL AND STR_TO_DATE(timecard_open,'%H:%i') <= TIME(CURRENT_TIMESTAMP)) AND (timecard_close IS NULL OR STR_TO_DATE(timecard_close,'%H:%i') >= TIME(CURRENT_TIMESTAMP)) AND owner = '".$userid."'";
		$data = $this->fetchOne($query);

		return $data['timecard_open'];
	}

	function isGroupLeader($userid, $group_id = "all") {

		if (!isset($userid)) {
			$groupid = $_SESSION['userid'];
		}
		if ($group_id == "all") {
			$query = "SELECT COUNT(*) AS cnt FROM ".DB_PREFIX."group WHERE group_leader = '".$userid."'";
		} else {
			$query = "SELECT COUNT(*) AS cnt FROM ".DB_PREFIX."group WHERE group_leader = '".$userid."' AND id = '".$group_id."'";
		}

		$data = $this->fetchOne($query);

		return $data['cnt']>0;
	}

	function sendMailNotifyUploaded() {
		$sendto = array();
		$sendToNames = array();
		$sendToNamesUnic = array(); //重複防止
		$array = array('public'=>'公開', 'edit'=>'編集を許可', 'add'=>'書き込みを許可');
		$names = array();
		foreach ($array as $action => $actionname) {
			if (isset($_POST[$action]['group']) && count($_POST[$action]['group']) > 0) {
				foreach ($_POST[$action]['group'] as $groupid => $groupname) {
					$names[$action]['group'][] = $groupname;
					$groupusers = $this->getUserListByGroupId($groupid, true);
					if (is_array($groupusers) && count($groupusers) > 0) {
						foreach ($groupusers as $user) {
							if (!isset($sendto[$user['user_email']][$action])) {
								$sendto[$user['user_email']][$action] = true;
							}
							if (!isset($sendToNamesUnic[$user['user_email']])) {
								$sendToNames[] = array($user['user_email'],$user['realname']);
								$sendToNamesUnic[$user['user_email']] = $user['realname'];
							}
						}
					}
				}
			}
			if (isset($_POST[$action]['user']) && count($_POST[$action]['user']) > 0) {
				foreach ($_POST[$action]['user'] as $userid => $username) {
					$userdata = $this->findUserByUserId($userid);
					$names[$action]['user'][] = $userdata['realname'];
					if (!isset($sendto[$userdata['user_email']][$action])) {
						$sendto[$userdata['user_email']][$action] = true;
					}
					if (!isset($sendToNamesUnic[$userdata['user_email']])) {
						$sendToNames[] = array($userdata['user_email'],$userdata['realname']);
						$sendToNamesUnic[$userdata['user_email']] = $userdata['realname'];
					}
				}
			}
		}

		foreach ($array as $action => $actionname) {
			if (isset($_POST[$action.'_level'])) {
				switch ($_POST[$action.'_level']) {
					case 0:
						$permitsetting[$action] = $actionname."：すべて許可";
						break;
					case 1:
						$permitsetting[$action] = $actionname."：許可しない";
						break;
					case 2:
						if (is_array($names[$action]['group']) && count($names[$action]['group'])>0) {
							$permitstring[$action]['group'] = $actionname.'グループ：';
							$permitstring[$action]['group'] .= implode('、', $names[$action]['group']);
						} else {
							$permitstring[$action]['group'] = $actionname.'グループ：なし';
						}
						if (is_array($names[$action]['user']) && count($names[$action]['user'])>0) {
							$permitstring[$action]['user'] = $actionname.'ユーザ：';
							$permitstring[$action]['user'] .= implode('、', $names[$action]['user']);
						} else {
							$permitstring[$action]['user'] = $actionname.'ユーザ：なし';
						}
						$permitsetting[$action] = $permitstring[$action]['group']."\n".$permitstring[$action]['user'];
						break;
				}
			}
		}
		$permitsettingstring = implode("\n", $permitsetting);
		if (isset($_FILES['uploadfile']['name'][0]) && strlen($_FILES['uploadfile']['name'][0]) > 0) {
			//日本語のファイル名だと、Outlookなどからうまくダウンロードできないらしいので、全部ファイル詳細ページに遷移するように、とりあえず
			//$download_url = "http://".$_SERVER[HTTP_HOST].substr($_SERVER['REQUEST_URI'],0,strrpos($_SERVER['REQUEST_URI'], "/"))."/download.php?id=".$_POST['id']."&file=".urlencode($_FILES['uploadfile']['name'][0]);
			$download_url = "http://".$_SERVER[HTTP_HOST].substr($_SERVER['REQUEST_URI'],0,strrpos($_SERVER['REQUEST_URI'], "/"))."/view.php?id=".$this->mysql_insert_id;
		} else {
			$download_url = "http://".$_SERVER[HTTP_HOST].substr($_SERVER['REQUEST_URI'],0,strrpos($_SERVER['REQUEST_URI'], "/"))."/view.php?id=".$this->mysql_insert_id;
		}

		$body = sprintf($GLOBALS['file_uploaded_notification_body'],'関係各位',$_SESSION['realname'],$_POST['storage_title'],$download_url,$permitsettingstring,$_POST['storage_comment'],$_FILES['uploadfile']['name'][0]);

		if (is_array($sendToNames) && count($sendToNames) > 0) {
			$mail = new Qdmail();
			$mail->easyText($sendToNames,$_SESSION['realname']."さんは"."「".$_POST['storage_title']."」をアップロードしました",$body,array('noreply@abenj.net','ABENグループウェアシステム配信'));
		}
	}

	function insertAccess($table,$userid,$id=0) {
		if (!$userid) {
			$userid = $_SESSION['userid'];
		}
		if (!$table) {
			$table = $this->table;
		}
		if ($id <= 0) {
			$id = $_REQUEST['id'];
		}
		if ($table && $userid && $id) {
			$query = sprintf("INSERT INTO grp_access (access_table,access_id,access_userid,access_at) VALUE ('%s','%s','%s','%s')",$table,$id,$userid,date('Y-m-d H:i:s'));
			$this->response = $this->query($query);
		}
	}
	function getAccessCount($table,$id=0) {
		if (!$table) {
			$table = $this->table;
		}
		if ($id <= 0) {
			$id = $_REQUEST['id'];
		}
		if ($table && $id) {
			$query = sprintf("SELECT COUNT(*) AS cnt FROM grp_access WHERE access_table = '%s' AND access_id = '%s'",$table,$id);
			$data = $this->fetchOne($query);
			return $data['cnt'];
		}
	}

	function listEvent() {
		$utility = new Utility();
		$query = sprintf("SELECT * FROM %sevent WHERE 1 ORDER BY id", DB_PREFIX);
		$data = $this->fetchAll($query);
		$result = array();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $row) {
				$result[$row['id']] = array($row['event_name'],$row['bg_color'],$utility->color_inverse($row['bg_color']));
			}
		}
		return $result;

	}

}

?>