<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('メンバー情報');
$pagination = new Pagination(array('group'=>$_GET['group']));
if ($_GET['group'] == 'all') {
	$caption = ' - すべて表示';
} elseif (strlen($hash['group'][$_GET['group']]) > 0) {
	$caption = ' - '.$hash['group'][$_GET['group']];
}
?>
<h1>メンバー情報<?=$caption?></h1>
<ul class="operate">
	<li><a href="edit.php">個人設定</a></li>
	<li><a href="csv.php<?=$view->positive(array('group'=>$_GET['group']))?>">CSV出力</a></li>
</ul>
<?=$view->searchform(array('group'=>$_GET['group']))?>
<table class="content" style="border-spacing:0;border-collapse:collapse;"><tr><td class="contentfolder">
	<div class="folder" style="width: 230px;">
		<div class="foldercaption">グループ</div>
		<ul class="folderlist">
		<li<?=$current[0]?>><a href="index.php?group=all">すべて表示</a></li>
<?php
$current[intval($_GET['group'])] = ' class="current"';
if (is_array($hash['group']) && count($hash['group']) > 0) {
	foreach ($hash['group'] as $key => $value) {
		echo '<li'.$current[$key].'><a href="index.php?group='.$key.'">'.$value.'</a></li>';
	}
}
?>
		</ul>
	</div>
</td><td>
	<table class="list" style="border-spacing:0;border-collapse:collapse;">
		<tr>
		<th><?=$pagination->sortby('user_code', '社員番号')?></th>
		<th>出社</th>
		<th><?=$pagination->sortby('realname', '名前')?></th>
		<th><?=$pagination->sortby('user_groupname', 'グループ')?></th>
		<th>リーダー</th>
		<th><?=$pagination->sortby('user_email', 'メール')?></th>
		<!--<th><?=$pagination->sortby('user_skype', 'スカイプID')?></th>-->
		<th><?=$pagination->sortby('user_mobile', '携帯電話')?></th></tr>
<?php
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
?>
		<tr>
		<td><?=$row['user_code']?>&nbsp;</td>
		<td class="font-red"><?=($row['is_in_office']?"●":"")?>&nbsp;</td>
		<td><a href="view.php?id=<?=$row['id']?>"><?=$row['realname']?></a>&nbsp;</td>
		<td><?=$row['user_groupname']?>&nbsp;</td>
		<td class="font-green"><?=($row['is_group_leader']?"★":"")?>&nbsp;</td>
		<td><a href="mailto:<?=$row['user_email']?>"><?=$row['user_email']?></a>&nbsp;</td>
		<!--<td><?=$row['user_skype']?>&nbsp;</td>-->
		<td><?=$row['user_mobile']?>&nbsp;</td></tr>
<?php
	}
}
?>
	</table>
	<?=$view->pagination($pagination, $hash['count'])?>
</td></tr></table>
<?php
$view->footing();
?>