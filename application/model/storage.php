<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

class Storage extends ApplicationModel {

	function Storage() {

		$this->schema = array(
		'storage_folder'=>array('fix'=>intval($_GET['folder']), 'except'=>array('search', 'update')),
		'storage_type'=>array('fix'=>'file'),
		'storage_title'=>array('タイトル', 'notnull', 'length:1000'),
		'storage_name'=>array('fix'=>$_SESSION['realname']),
		'storage_comment'=>array('内容', 'length:10000', 'line:100'),
		'storage_date'=>array('fix'=>date('Y-m-d H:i:s'), 'except'=>array('search', 'update')),
		'storage_file'=>array('except'=>array('update')),
		'storage_size'=>array('except'=>array('search', 'update')),
		'add_level'=>array('except'=>array('search')),
		'add_group'=>array('except'=>array('search')),
		'add_user'=>array(),
		'public_level'=>array('except'=>array('search')),
		'public_group'=>array('except'=>array('search')),
		'public_user'=>array('except'=>array('search')),
		'edit_level'=>array('except'=>array('search')),
		'edit_group'=>array('except'=>array('search')),
		'edit_user'=>array('except'=>array('search')));

	}

	function index() {

		$hash = $this->getFolders($_GET['folder']);
		return $hash;

	}

	function view() {

		$hash['data'] = $this->permitFind();
		if ($hash['data']['storage_folder'] > 0) {
			$hash['folder'] = $this->permitFind('public', $hash['data']['storage_folder']);
		}
		$this->insertAccess();
		$hash['data']['access_count'] = $this->getAccessCount();
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function add() {

		$hash['folder'] = $this->permitFolder($_GET['folder']);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (strlen($_FILES['uploadfile']['name'][0]) <= 0 && strlen($_POST['uploadedfile'][0]) <= 0) {
				$this->error[] = 'アップロードするファイルを選択してください。';
			}
			$this->validateSchema('insert');
			$this->permitValidate();
			$prefix = $_SESSION['userid'].'_'.strtotime($this->post['storage_date']);
			$this->post['storage_file'] = $this->uploadfile('storage', $prefix);
			$this->post['storage_size'] = $this->uploadfilesize($prefix.'_'.$this->post['storage_file'], 'storage');
			$this->insertPost();

			$this->sendMailNotifyUploaded();

			$this->redirect('index.php'.$this->parameter(array('folder'=>$_GET['folder'])));
			$hash['data'] = $this->post;
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function edit() {

		$hash['data'] = $this->permitFind('edit');
		$this->type($hash['data'], 'file');
		$hash['folder'] = $this->permitFolder($hash['data']['storage_folder']);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (strlen($_FILES['uploadfile']['name'][0]) > 0 || strlen($_POST['uploadedfile'][0]) > 0) {
				$this->schema['storage_file']['except'] = array();
				$this->schema['storage_size']['except'] = array();
			}
			$this->validateSchema('update');
			$this->permitValidate();
			if (strlen($_FILES['uploadfile']['name'][0]) > 0 || strlen($_POST['uploadedfile'][0]) > 0) {
				$prefix = $hash['data']['owner'].'_'.strtotime($hash['data']['storage_date']);
				$this->post['storage_file'] = $this->uploadfile('storage', $prefix, $hash['data']['storage_file']);
				$this->post['storage_size'] = $this->uploadfilesize($prefix.'_'.$this->post['storage_file'], 'storage');
			}
			$this->updatePost();

			$this->redirect('index.php'.$this->parameter(array('folder'=>$hash['data']['storage_folder'])));
			$this->post['storage_date'] = $hash['data']['storage_date'];
			$this->post['storage_file'] = $hash['data']['storage_file'];
			$hash['data'] = $this->post;
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function delete() {

		$hash['data'] = $this->permitFind('edit');
		$this->type($hash['data'], 'file');
		$hash['folder'] = $this->permitFolder($hash['data']['storage_folder']);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->deletePost();
			if ($this->response && count($this->error) <= 0) {
				$this->uploadfile('storage', $hash['data']['owner'].'_'.strtotime($hash['data']['storage_date']), $hash['data']['storage_file']);
				$this->redirect('index.php'.$this->parameter(array('folder'=>$hash['data']['storage_folder'])));
			}
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function folderview() {

		return $this->view();

	}

	function folderadd() {

		$hash['folder'] = $this->permitFolder($_GET['folder']);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->schema['storage_type']['fix'] = 'folder';
			$this->schema['storage_title'][0] = 'フォルダ名';
			$this->validateSchema('insert');
			$this->permitValidate();
			$this->insertPost();
			$this->redirect('index.php'.$this->parameter(array('folder'=>$_GET['folder'])));
			$hash['data'] = $this->post;
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function folderedit() {

		$hash['data'] = $this->permitFind('edit');
		$this->type($hash['data'], 'folder');
		$hash['folder'] = $this->permitFolder($hash['data']['storage_folder']);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->schema['storage_type']['fix'] = 'folder';
			$this->schema['storage_title'][0] = 'フォルダ名';
			$hash['data'] = $this->permitUpdate('index.php'.$this->parameter(array('folder'=>$hash['data']['storage_folder'])));
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function folderdelete() {

		$hash['data'] = $this->permitFind('edit');
		$this->type($hash['data'], 'folder');
		$hash['folder'] = $this->permitFolder($hash['data']['storage_folder']);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$query = "SELECT ".implode(',', $this->schematize())." FROM ".$this->table." WHERE storage_folder = ".intval($_POST['id']);
			$data = $this->fetchAll($query);
			if (is_array($data) && count($data) > 0) {
				foreach ($data as $row) {
					if ($row['storage_type'] == 'folder') {
						$this->error[] = 'サブフォルダが存在するフォルダは削除できません。<br />サブフォルダを削除してください。';
						break;
					} elseif (!$this->permitted($row, 'public') || !$this->permitted($row, 'edit')) {
						$this->error[] = '編集権限のないファイルが存在します。<br />フォルダを削除できませんでした。';
						break;
					}
				}
			}
			$this->deletePost();
			if ($this->response && count($this->error) <= 0) {
				$query = "DELETE FROM ".$this->table." WHERE storage_folder = ".intval($_POST['id']);
				$this->response = $this->query($query);
				if ($this->response && is_array($data) && count($data) > 0) {
					foreach ($data as $row) {
						$this->removefile('storage', $row['owner'].'_'.strtotime($row['storage_date']), $row['storage_file']);
					}
				}
				$this->redirect('index.php'.$this->parameter(array('folder'=>$hash['data']['storage_folder'])));
			}
		}
		$hash += $this->findUser($hash['data']);
		return $hash;

	}

	function permitFolder($id) {

		if ($id > 0) {
			$data = $this->permitFind('public', $id);
			if ($this->permitted($data, 'add')) {
				return $data;
			} else {
				$this->died('このフォルダへの書き込み権限がありません。');
			}
		}

	}

	function type($data, $type) {

		if ($type == 'file' && $data['storage_type'] == 'folder') {
			header('Location:folder'.basename($_SERVER['SCRIPT_NAME']).'?id='.$data['id']);
			exit();
		} elseif ($type == 'folder' && $data['storage_type'] == 'file') {
			header('Location:'.str_replace('folder', '', basename($_SERVER['SCRIPT_NAME'])).'?id='.$data['id']);
			exit();
		}

	}

	function download() {

		$data = $this->permitFind();
		if ($data['storage_folder'] > 0) {
			$hash['folder'] = $this->permitFind('public', $data['storage_folder']);
		}
		if (stristr($data['storage_file'], $_REQUEST['file'])) {
			$this->attachment('storage', $data['owner'].'_'.strtotime($data['storage_date']), $_REQUEST['file'], 'attachment');
		} else {
			$this->died('ファイルが見つかりません。');
		}

	}

	function getFolders($folderid) {

		$folderid = intval($folderid);
		$hash = array();

		if (isset($_POST['csearch']) && $_POST['csearch'] == 1) {
			$folderid = 0;
		}

		if ($folderid > 0) {
			$hash['parent'] = $this->permitFind('public', $folderid);
		}

		if ((is_array($hash['parent']) && count($hash['parent']) > 0) || $folderid == 0) {

			$usergrpids = explode(",",$this->getUserGroups($_SESSION['group_id'],$_SESSION['userid']));
			foreach ($usergrpids as $usergroupid) {
				$grpwhere .= ($grpwhere == ""?"":" OR ")."parent.public_group LIKE '%%[".$usergroupid."]%%'";
				$grpstwhere .= ($grpstwhere == ""?"":" OR ")."grp_storage.public_group LIKE '%%[".$usergroupid."]%%'";
			}
			$grpwhere = "(".$grpwhere.")";
			$grpstwhere = "(".$grpstwhere.")";

			if (isset($_POST['csearch']) && $_POST['csearch'] == 1 && isset($_REQUEST['search']) && strlen($_REQUEST['search']) > 0) {
				$query = "SELECT grp_storage.* ".
						"FROM grp_storage ".
						"LEFT JOIN grp_storage parent ON parent.id = grp_storage.storage_folder ".
						"WHERE ".
							"(parent.public_level = 0 OR parent.owner = '".$_SESSION['userid']."' OR (parent.public_level = 2 AND (".$grpwhere." OR parent.public_user LIKE '%[".$_SESSION['userid']."]%'))) AND ".
							"(grp_storage.public_level = 0 OR grp_storage.owner = '".$_SESSION['userid']."' OR (grp_storage.public_level = 2 AND (".$grpstwhere." OR grp_storage.public_user LIKE '%[".$_SESSION['userid']."]%'))) AND ".
							"((grp_storage.storage_type LIKE '%".$_REQUEST['search']."%') OR (grp_storage.storage_title LIKE '%".$_REQUEST['search']."%') OR (grp_storage.storage_name LIKE '%".$_REQUEST['search']."%') OR (grp_storage.storage_comment LIKE '%".$_REQUEST['search']."%') OR (grp_storage.storage_file LIKE '%".$_REQUEST['search']."%')) ".
						"ORDER BY grp_storage.storage_type DESC, grp_storage.storage_date DESC ";
				$hash['list'] = $this->fetchAll($query);
				$hash['count'] = count($hash['list']);
			} else {
				$this->where[] = "(storage_folder = '".intval($folderid)."')";
				$hash += $this->permitList('storage_type DESC, storage_date', 1);
			}

			if ($folderid == 0) {
				$parentid = 0;
				$deepth = 0;
			} else {
				$parentid = $hash['parent']['storage_folder'];
				$deepth = 1;
				$hash['folder_tree'][] = array(
					'id' => $hash['parent']['id'],
					'storage_title' => $hash['parent']['storage_title'],
					'parent_id' => $hash['parent']['storage_folder'],
					'storage_type' => $hash['parent']['storage_type']
				);

				while ($parentid > 0) {
					$deepth ++;
					$query = "SELECT * FROM grp_storage WHERE id = ".$parentid;
					$parentfolder = $this->fetchOne($query);
					$parentid = $parentfolder['storage_folder'];
					$hash['folder_tree'][] = array(
						'id' => $parentfolder['id'],
						'storage_title' => $parentfolder['storage_title'],
						'parent_id' => $parentfolder['storage_folder'],
						'storage_type' => $parentfolder['storage_type']
					);
				}
			}

			if ($parentid == 0) {
				$hash['folder_tree'][] = array(
						'id' => 0,
						'storage_title' => 'ルート',
						'parent_id' => -1,
						'storage_type' => 'folder'
				);
			}

			for ($i = 0; $i <= count($hash['folder_tree']) - 1; $i++) {
				$hash['folder_tree'][$i]['deepth'] = $deepth;
				$deepth --;
			}

			$hash['folder_tree'] = array_reverse($hash['folder_tree']);
			return $hash;
		}
	}

	function rename() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST["storage_id"]) && isset($_POST["parent_id"]) && isset($_POST["newname"])) { //TODO edit_levelを評価しないと
				if ($this->canModify($_POST["storage_id"])) {
					$query = sprintf("SELECT COUNT(*) AS cnt FROM grp_storage WHERE storage_folder = %d AND storage_title = '%s' AND storage_type = '%s'", $_POST["parent_id"], $_POST["newname"], $_POST["storage_type"]);
					$data = $this->fetchOne($query);
					if ($data['cnt'] > 0) {
						//エラー既に同名のフォルダー（ファイル）が存在している、重複は不可
						$hash['result'] = "existed";
						$hash['result_msg'] = "フォルダー名（またはファイル名）は既に存在しています、別の名前にしてください。";
					} else {
						$query = sprintf("UPDATE grp_storage SET storage_title = '%s', editor = '%s', updated = CURRENT_TIMESTAMP WHERE id = %d", $_POST["newname"], $_SESSION['userid'], $_POST["storage_id"]);
						$this->response = $this->query($query);
						$hash['result'] = "renamed";
						$hash['result_msg'] = "更新しました";
						$hash['newname'] = $_POST["newname"];
					}
				} else {
					//権限がありません、作成者に確認してください
					$hash['result'] = "notallowed";
					$hash['result_msg'] = "権限がありません、作成者に確認してください。";
				}
			}
		}
		echo json_encode($hash);
		return $hash;
	}

	function move() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST["storage_id"]) && isset($_POST["to_id"])) {
				if ($this->canModify($_POST["storage_id"],$_POST["to_id"])){
					$query = sprintf("SELECT COUNT(grp_storage.id) AS cnt FROM grp_storage INNER JOIN grp_storage taishou ON taishou.id = %d WHERE grp_storage.storage_folder = %d AND grp_storage.storage_title = taishou.storage_title AND grp_storage.storage_type = taishou.storage_type", $_POST["storage_id"], $_POST["to_id"]);
					$data = $this->fetchOne($query);
					if ($data['cnt'] > 0) {
						//エラー既に同名のフォルダー（ファイル）が存在している、重複は不可
						$hash['result'] = "existed";
						$hash['result_msg'] = "移動先に同じフォルダー名（またはファイル名）は既に存在しています。";
					} else {
						$query = sprintf("UPDATE grp_storage SET storage_folder = %d, editor = '%s', updated = CURRENT_TIMESTAMP WHERE id = %d", $_POST["to_id"], $_SESSION['userid'], $_POST["storage_id"]);
						$this->response = $this->query($query);
						$hash['result'] = "moved";
						$hash['result_msg'] = "移動しました。";
					}
				} else {
					//権限がありません、作成者に確認してください
					$hash['result'] = "notallowed";
					$hash['result_msg'] = "権限がありません、作成者に確認してください。";
				}
			}
		}
		echo json_encode($hash);
		return $hash;
	}

	function canModify($taishou_id, $to_id = null) {
		$query = "SELECT * FROM grp_storage WHERE id = ".$taishou_id;
		$data = $this->fetchOne($query);
		$from_id = $data['storage_folder'];
		$can_edit_taishou = $this->permitted($data, 'edit');

		if (!is_null($from_id) && !is_null($to_id)) {
			$query = "SELECT * FROM grp_storage WHERE id = ".$from_id;
			$data = $this->fetchOne($query);
			$can_edit_from = $this->permitted($data, 'edit');

			$query = "SELECT * FROM grp_storage WHERE id = ".$to_id;
			$data = $this->fetchOne($query);
			$can_add_to = $this->permitted($data, 'add');

			return $can_edit_taishou && $can_edit_from && $can_add_to;
		}
		return $can_edit_taishou;
	}
}

?>