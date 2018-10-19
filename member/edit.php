<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
$view->script('addressbook.js', 'postcode.js');
$view->heading('個人設定');
?>
<h1>個人設定</h1>
<ul class="operate">
	<li><a href="index.php">一覧に戻る</a></li>
</ul>
<form class="content" method="post" action="">
	<?=$view->error($hash['error'])?>
	<table class="form" style="border-spacing:0;border-collapse:collapse;">
		<tr><th>名前<span class="necessary">(必須)</span></th><td><input type="text" name="realname" class="inputvalue" value="<?=$hash['data']['realname']?>" /></td></tr>
		<tr><th>かな</th><td><input type="text" name="user_ruby" class="inputvalue" value="<?=$hash['data']['user_ruby']?>" /></td></tr>
		<!--
		<tr><th>郵便番号</th><td>
			<input type="text" name="user_postcode" id="postcode" class="inputalpha" value="<?=$hash['data']['user_postcode']?>" />&nbsp;
			<input type="button" value="検索" onclick="Postcode.feed(this)" />
		</td></tr>
		<tr><th>住所</th><td>
			<input type="text" name="user_address" id="address" class="inputtitle" value="<?=$hash['data']['user_address']?>" />&nbsp;
			<input type="button" value="検索" onclick="Postcode.feed(this, 'address')" />
		</td></tr>
		<tr><th>住所（かな）</th><td><input type="text" name="user_addressruby" id="addressruby" class="inputtitle" value="<?=$hash['data']['user_addressruby']?>" /></td></tr>
		-->
		<tr><th>電話番号</th><td><input type="text" name="user_phone" class="inputalpha" value="<?=$hash['data']['user_phone']?>" /></td></tr>
		<tr><th>携帯電話</th><td><input type="text" name="user_mobile" class="inputalpha" value="<?=$hash['data']['user_mobile']?>" /></td></tr>
	<?php
		if ($_SESSION['authority'] == 'administrator') {
			$inputattr = '';
			$readonly = '';
		}else{
			$inputattr = 'style="display:none;"';
			$readonly = 'READONLY';
		}
	?>
		<tr><th>メール<span class="necessary">(必須)</span></th><td><input type="text" name="user_email" class="inputvalue" value="<?=$hash['data']['user_email']?>" <?=$readonly?>/></td></tr>
		<!--<tr><th>スカイプID</th><td><input type="text" name="user_skype" class="inputalpha" value="<?=$hash['data']['user_skype']?>" /></td></tr>-->
		<tr <?=$inputattr?>><th>社員番号</th><td><input type="text" name="user_code" class="inputalpha" value="<?=$hash['data']['user_code']?>" /></td></tr>
		<tr <?=$inputattr?>><th>生年月日<span class="necessary">(必須)</span></th><td><input type="text" id="dp_user_birthday" name="user_birthday" class="inputalpha" value="<?=$hash['data']['user_birthday']?>" /></td></tr>
		<tr <?=$inputattr?>><th>入社日<span class="necessary">(必須)</span></th><td><input type="text" id="dp_user_joindate" name="user_joindate" class="inputalpha" value="<?=$hash['data']['user_joindate']?>" /></td></tr>
		<tr <?=$inputattr?>><th>雇用形態<span class="necessary">(必須)</span></th><td><?=$helper->selector('user_hiretype', $GLOBALS['hiretype'], $hash['data']['user_hiretype'])?></td></tr>
		<tr <?=$inputattr?>><th>就業時間<span class="necessary">(必須)</span></th><td><input type="text" name="user_opentime" id="timepicker_open" style="width: 70px;" value="<?=$hash['data']['user_opentime']?>" /> ～ <input type="text" name="user_closetime" id="timepicker_close" style="width: 70px;" value="<?=$hash['data']['user_closetime']?>" /></td></tr>
		<tr <?=$inputattr?>><th>時間外申請<span class="necessary">(必須)</span></th><td><?=$helper->radio('user_overtime_flg', 1, $hash['data']['user_overtime_flg'], 'overtime1', 'あり')?>&nbsp;<?=$helper->radio('user_overtime_flg', 0, $hash['data']['user_overtime_flg'], 'overtime0', 'なし')?></td></tr>
		<tr <?=$inputattr?>><th>メモ</th><td><input type="text" name="remark" class="inputvalue" value="<?=$hash['data']['remark']?>" /></td></tr>
		<tr <?=$inputattr?>><th>退職日</span></th><td><input type="text" id="dp_user_retired" name="user_retired" class="inputalpha" value="<?=$hash['data']['user_retired']?>" /></td></tr>
	</table>
	<h2>パスワードの変更</h2>
	<table class="form" style="border-spacing:0;border-collapse:collapse;">
	<?php
		if ($_SESSION['authority'] == 'administrator' && $_SESSION['userid'] != $hash['data']['userid']) {
	?>
		<tr><th>リセットパスワード<?=$view->explain('userpassword')?></th><td><input type="password" name="newpassword" class="inputvalue" /></td></tr>
		<tr><th>リセットパスワード（確認）</th><td><input type="password" name="confirmpassword" class="inputvalue" /></td></tr>
	<?php } else { ?>
		<tr><th>現在のパスワード</th><td><input type="password" name="password" class="inputvalue" /></td></tr>
		<tr><th>新しいパスワード<?=$view->explain('userpassword')?></th><td><input type="password" name="newpassword" class="inputvalue" /></td></tr>
		<tr><th>新しいパスワード（確認）</th><td><input type="password" name="confirmpassword" class="inputvalue" /></td></tr>
	<?php } ?>
	</table>
	<div class="submit">
		<input type="submit" value="　確定　" />&nbsp;
		<input type="button" value="キャンセル" onclick="location.href='index.php'" />
	</div>
	<input type="hidden" name="id" value="<?=$hash['data']['id']?>" />
	<input type="hidden" name="userid" value="<?=$hash['data']['userid']?>" />
</form>
<?php
$view->footing();
?>
<script type="text/javascript">
$('#dp_user_birthday').datetimepicker({
	yearOffset:-30,
	lang:'ja',
	timepicker:false,
	format:'Y-m-d',
});
$('#dp_user_joindate').datetimepicker({
	lang:'ja',
	timepicker:false,
	format:'Y-m-d',
});
$('#dp_user_retired').datetimepicker({
	lang:'ja',
	timepicker:false,
	format:'Y-m-d',
});
$('#timepicker_open').datetimepicker({
	datepicker:false,
	format:'H:i',
	minTime:'9:00',
	maxTime:'18:00',
	step:30

});
$('#timepicker_close').datetimepicker({
	datepicker:false,
	format:'H:i',
	minTime:'9:30',
	maxTime:'18:30',
	step:30

});
</script>