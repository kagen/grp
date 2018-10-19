<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('グループ', 'administration');
$pagination = new Pagination;
?>
<h1>グループ</h1>
<ul class="operate">
	<li><a href="../user/">ユーザー管理に戻る</a></li>
	<li><a href="add.php">グループ追加</a></li>
</ul>
<?=$view->searchform()?>
<table class="list" style="border-spacing:0;border-collapse:collapse;" style="width:500px;" id="table_group_list">
	<tr>
	<!--
	<th><?=$pagination->sortby('group_name', 'グループ名')?></th>
	<th><?=$pagination->sortby('realname', 'リーダー名')?></th>
	<th><?=$pagination->sortby('group_order', '順序')?></th>
	-->
	<th>グループ名</th>
	<th>リーダー名</th>
	<th>順序</th>
	<th class="listlink">&nbsp;</th></tr>
<?php
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
?>
	<tr data-tt-id="<?=$row['id']?>"<?=($row['parent_id']>0?' data-tt-parent-id="'.$row['parent_id'].'"':"")?>>
	<td><a href="view.php?id=<?=$row['id']?>"><?=$row['short_name']?></a>&nbsp;</td>
	<td><?=$row['group_leader_data']['realname']?>&nbsp;</td>
	<td><?=$row['group_order']?>&nbsp;</td>
	<td><a href="edit.php?id=<?=$row['id']?>">編集</a></td></tr>
<?php
	}
}
?>
</table>
<script type="text/javascript">
$("#table_group_list").treetable({ expandable: true });
</script>
<?php
//$view->pagination($pagination, $hash['count']);
$view->footing();
?>