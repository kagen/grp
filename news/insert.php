<?php
require_once('../application/json.php');
?>
<div id="news_<?=$hash['id']?>" class="content">
<div id="newsview_<?=$hash['id']?>">
</div>
<div id="newsedit_<?=$hash['id']?>">
</div>
<script type="text/javascript">
$("#news_<?=$hash['id']?>").find("#newsview_<?=$hash['id']?>").load('view.php',<?=json_encode($hash);?>);
$("#news_<?=$hash['id']?>").find("#newsedit_<?=$hash['id']?>").load('edit.php',<?=json_encode($hash);?>,
function(){
	$("#news_<?=$hash['id']?>").find("#newsedit_<?=$hash['id']?>").hide();
	$(".datetimepicker").datetimepicker({
		lang:"ja",
		format:'Y-m-d H:i'
	});
	$("#newsedit_<?=$hash['id']?> textarea").val(<?=json_encode($hash['news_body']);?>);
	$("#newsform").fadeToggle(250);
});
</script>
</div>
