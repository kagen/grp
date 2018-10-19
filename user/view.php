<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('ユーザー詳細', 'administration');
$option['authority'] = array('member'=>'メンバー', 'editor'=>'編集者', 'manager'=>'マネージャ', 'administrator'=>'管理者');
?>
<h1>ユーザー詳細</h1>
<ul class="operate">
	<li><a href="index.php?group=<?=$hash['data']['user_group']?>">一覧に戻る</a></li>
	<li><a href="../member/view.php?id=<?=$hash['data']['id']?>">メンバー詳細へ</a></li>
<?php
if ($view->permitted($hash['parent'], 'add') && $view->permitted($hash['data'], 'edit')) {
	echo '<li><a href="edit.php?id='.$hash['data']['id'].'">編集</a></li>';
	echo '<li><a href="delete.php?id='.$hash['data']['id'].'">削除</a></li>';
}
$today = date('Y-m-d');
$retired = false;
if ($hash['data'][user_retired] != null && $hash['data'][user_retired] != '0000-00-00' && strtotime($hash['data'][user_retired]) < strtotime($today)) {
	$retired = true;
}
?>
</ul>
<table class="view" style="border-spacing:0;border-collapse:collapse;">
	<tr><th>ユーザーID</th><td><?=$hash['data']['userid']?>&nbsp;</td></tr>
	<tr><th>名前</th><td <?=($retired?'class="font-red"':'')?>><?=$hash['data']['realname'].($retired?'（退職）':'')?>&nbsp;</td></tr>
	<tr><th>メイングループ</th><td><?=$hash['data']['user_groupname']?>&nbsp;</td></tr>
	<tr><th>兼任グループ</th><td><?=implode("<br>",array_values($hash['subgroups']))?>&nbsp;</td></tr>
	<tr><th>権限</th><td><?=$option['authority'][$hash['data']['authority']]?>&nbsp;</td></tr>
	<tr><th>順序</th><td><?=$hash['data']['user_order']?>&nbsp;</td></tr>
</table>
<?php
$view->property($hash['data']);
$view->footing();
?>