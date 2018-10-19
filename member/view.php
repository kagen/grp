<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->heading('メンバー詳細');
?>
<h1>メンバー詳細</h1>
<ul class="operate">
	<li><a href="index.php?group=<?=$hash['data']['user_group']?>">一覧に戻る</a></li>
<?php if ($hash['data']['userid'] == $_SESSION['userid'] || $_SESSION['authority'] == 'administrator') { ?>
	<li><a href="edit.php?id=<?=$hash['data']['id']?>">個人設定</a></li>
	<li><a href="../timecard/index.php?member=<?=$hash['data']['userid']?>">タイムカード</a></li>
<?php } elseif ($_SESSION['authority'] == 'manager' || $hash['user_is_boss_of_target_user']) { ?>
	<li><a href="../timecard/index.php?member=<?=$hash['data']['userid']?>">タイムカード</a></li>
<?php } ?>
</ul>
<table class="view" style="border-spacing:0;border-collapse:collapse;">
	<tr><th>社員番号</th><td><?=$hash['data']['user_code']?>&nbsp;</td></tr>
	<tr><th>名前</th><td><?=$hash['data']['realname']?>&nbsp;</td></tr>
	<tr><th>かな</th><td><?=$hash['data']['user_ruby']?>&nbsp;</td></tr>
	<tr><th>メイングループ</th><td><?=$hash['data']['user_groupname']?>&nbsp;</td></tr>
	<tr><th>兼任グループ</th><td><?=implode("<br>",array_values($hash['data']['subgroups']))?>&nbsp;</td></tr>
	<!--<tr><th>郵便番号</th><td><?=$hash['data']['user_postcode']?>&nbsp;</td></tr>-->
	<!--<tr><th>住所</th><td><?=$hash['data']['user_address']?>&nbsp;</td></tr>-->
	<!--<tr><th>住所（かな）</th><td><?=$hash['data']['user_addressruby']?>&nbsp;</td></tr>-->
	<tr><th>電話番号</th><td><?=$hash['data']['user_phone']?>&nbsp;</td></tr>
	<tr><th>携帯電話</th><td><?=$hash['data']['user_mobile']?>&nbsp;</td></tr>
	<tr><th>メール</th><td><a href="mailto:<?=$hash['data']['user_email']?>"><?=$hash['data']['user_email']?></a>&nbsp;</td></tr>
	<!--<tr><th>QRコード</th><td><img src='https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=<?=$hash['data']['user_email']?>'/></td></tr>-->
	<!--<tr><th>スカイプID</th><td><?=$hash['data']['user_skype']?>&nbsp;</td></tr>-->
<?php if ($_SESSION['authority'] == 'administrator') { ?>
	<tr><th>生年月日</th><td><?=$hash['data']['user_birthday']?>&nbsp;</td></tr>
	<tr><th>入社日</th><td><?=$hash['data']['user_joindate']?>&nbsp;</td></tr>
	<tr><th>雇用形態</th><td><?=($hash['data']['user_hiretype']==1?'バイト':'正社員')?>&nbsp;</td></tr>
	<tr><th>就業時間</th><td><?=($hash['data']['user_openhour']?$helper->numpad2($hash['data']['user_openhour']):$helper->numpad2($hash['config']['timecard']['openhour'])).":".($hash['data']['user_openminute']?$helper->numpad2($hash['data']['user_openminute']):$helper->numpad2($hash['config']['timecard']['openminute']))." - ".($hash['data']['user_closehour']?$helper->numpad2($hash['data']['user_closehour']):$helper->numpad2($hash['config']['timecard']['closehour'])).":".($hash['data']['user_closeminute']?$helper->numpad2($hash['data']['user_closeminute']):$helper->numpad2($hash['config']['timecard']['closeminute']))?>&nbsp;</td></tr>
	<tr><th>メモ</th><td><?=$hash['data']['remark']?>&nbsp;</td></tr>
	<tr><th>退職日</th><td><?=$hash['data']['user_retired']?>&nbsp;</td></tr>
<?php } ?>
</table>
<?php
$view->footing();
?>