<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('ファイル共有');
$pagination = new Pagination(array('folder'=>$_GET['folder']));
if (strlen($hash['folder'][$_GET['folder']]) > 0) {
	$caption = ' - '.$hash['folder'][$_GET['folder']];
}
?>
<h1>ファイル共有<?=$caption?></h1>
<ul class="operate">
<?php
if ($view->permitted($hash['parent'], 'add')) {
	echo '<li><a href="add.php'.$view->positive(array('folder'=>$_GET['folder'])).'">ファイルアップロード</a></li>';
	echo '<li><a href="folderadd.php'.$view->positive(array('folder'=>$_GET['folder'])).'">フォルダ追加</a></li>';
}
?>
</ul>
<form method="post" class="searchform" action="<?=$_SERVER['SCRIPT_NAME']?><?=$view->positive(array('folder'=>$_GET['folder']))?>">
	<?=$helper->checkbox('csearch', 1, $_POST['csearch'], 'csearch', 'フォルダを横断検索');?>&nbsp;
	<input type="text" name="search" id="search" class="inputsearch" value="<?=$view->escape($_REQUEST['search'])?>" /><input type="submit" value="検索" />
</form>
<table class="content" style="border-spacing:0;border-collapse:collapse;"><tr><td class="contentfolder">
	<div class="folder">
		<div class="foldercaption">フォルダ</div>
		<ul class="folderlist">
<?php
foreach ($hash['folder_tree'] as $key => $value) {
	if (($value['id'] == $_GET['folder'] && $_GET['folder']) || ($value['id'] == 0 && (!$_GET['folder'] || $_GET['folder'] == 0))) {
		echo '<li id="current_folder" class="current storagefolderlist" data-storage_id="'.$value['id'].'" data-parent_id="'.$value['parent_id'].'" data-storage_type="'.$value['storage_type'].'">'.str_repeat("　", $value['deepth']).$value['storage_title'].'</li>';
	} else {
		echo sprintf('<li><a class="storagefolderlist" data-storage_id="'.$value['id'].'" data-parent_id="'.$value['parent_id'].'" data-storage_type="'.$value['storage_type'].'" href="index.php?folder=%s">%s</a></li>', $value['id'], str_repeat("　", $value['deepth']).$value['storage_title']);
	}
}
?>
		</ul>
	</div>
</td><td>
	<table class="list" style="border-spacing:0;border-collapse:collapse;">
		<tr><th><?=$pagination->sortby('storage_title', 'タイトル')?><?=$view->explain('storageoperate')?></th>
		<th><?=$pagination->sortby('storage_file', 'ファイル名')?></th>
		<th><?=$pagination->sortby('storage_size', 'サイズ')?></th>
		<th><?=$pagination->sortby('storage_name', '名前')?></th>
		<th style="width:140px;"><?=$pagination->sortby('storage_date', '日時')?></th>
		<th class="listlink">&nbsp;</th></tr>
<?php
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
		if ($row['storage_type'] == 'file') {
			$url = 'view.php?id='.$row['id'];
			$file = '<a href="download.php?id='.$row['id'].'&file='.urlencode($row['storage_file']).'">'.$row['storage_file'].'</a>';
			$property = $url;
		} else {
			$url = 'index.php?folder='.$row['id'];
			$property = 'folderview.php?id='.$row['id'];
		}
?>
		<tr><td><a class="storage<?=$row['storage_type']?>" href="<?=$url?>" data-storage_id="<?=$row['id']?>" data-parent_id="<?=$row['storage_folder']?>" data-storage_type="<?=$row['storage_type']?>"><?=$row['storage_title']?></a>&nbsp;</td>
		<td><?=$file?></a>&nbsp;</td>
		<td><?=$row['storage_size']?>&nbsp;</td>
		<td><?=$row['storage_name']?>&nbsp;</td>
		<td><?=date('Y/m/d H:i:s', strtotime($row['storage_date']))?>&nbsp;</td>
		<td><a href="<?=$property?>">詳細</a>&nbsp;</td></tr>
<?php
	}
} else {
	echo '<tr><td colspan="6" style="text-align: center;">該当する内容はありません</td></tr>';
}
?>
	</table>
	<?=$view->pagination($pagination, $hash['count']);?>
</td></tr></table>
<?php
$view->footing();
?>