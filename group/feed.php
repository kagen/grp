<?php
/*
 * kagen.yu. All Rights Reserved.
 * 文字コード UTF-8
 */
require_once('../application/json.php');
?>
<form class="layerlist" name="grouplist" onsubmit="return false">
	<ul style="width: 98%; height: 420px; overflow: auto">
<?php
if (is_array($hash['list']) && count($hash['list']) > 0) {
	foreach ($hash['list'] as $row) {
?>
		<li><input type="checkbox" name="<?=$row['group_name']?>" value="<?=$row['id']?>" <?=(in_array($row['id'], $hash['selected'])?'checked="checked"':"") ?> <?=($row['id'] == $hash['mainGrpId']?'disabled="disabled"':"") ?>/>
		<span <?=($row['id'] == $hash['mainGrpId']?'':'class="operator"') ?>><?=$row['group_name']?></span></li>
<?php
	}
} else {
	echo '<li>グループ情報はありません。</li>';
}
?>
	</ul>
	<div class="layerlistsubmit"><input type="button" value="　選択　" onclick="App.sendSelectedGroups()" /></div>
</form>