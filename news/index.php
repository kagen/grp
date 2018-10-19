<?php
/*
 * kagen.yu
  文字コード UTF-8
*/
require_once('../application/loader.php');
$view->heading('お知らせ');
$view->script('news.js');
?>
<h1>お知らせ</h1>
<?=$view->error($hash['error'])?>
<ul class="operate">
	<li><a href="javascript:void(0)" onclick="toggleForm()">お知らせを追加</a></li>
</ul>
<div class="content" style="width:720px;">
	<?php
	require_once('add.php');
	?>

	<div id="newslist">
	<?php
	if (is_array($hash['list']) && count($hash['list']) > 0) {
		foreach ($hash['list'] as $row) {
	?>
			<div id="news_<?=$row['id']?>" class="content">
			<div id="newsview_<?=$row['id']?>"></div>
			<div id="newsedit_<?=$row['id']?>"></div>
			</div>
			<script type="text/javascript">
			$("#news_<?=$row['id']?>").find("#newsview_<?=$row['id']?>").load('view.php',<?=json_encode($row);?>);
			$("#news_<?=$row['id']?>").find("#newsedit_<?=$row['id']?>").load('edit.php',<?=json_encode($row);?>,
			function(){
				$("#news_<?=$row['id']?>").find("#newsedit_<?=$row['id']?>").hide();
				$(".datetimepicker").datetimepicker({
					lang:"ja",
					format:'Y-m-d H:i'
				});
				$("#newsedit_<?=$row['id']?> textarea").val(<?=json_encode($row['news_body']);?>);
			});
			</script>
	<?php
		}
	}
	?>
	</div>
</div>
<?php
$view->footing();
?>
<script type="text/javascript">
$("#newsform").hide();
$(".datetimepicker").datetimepicker({
	lang:"ja",
	format:'Y-m-d H:i'
});
function toggleForm(){
	$("#newsform").fadeToggle(250);
}
</script>
