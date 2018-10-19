<?php
require_once('../application/library/helper.php');
$helper = new Helper;
if(!isset($hash['id'])){
	$hash = $_POST;
}
?>
<form class="forum" id="<?='editnews_'.$hash['id']?>">
	<div style="margin: 10px;">
		<span>タイトル：&nbsp;<input type="text" name="news_title" id="news_title" style="width: 60%;" value="<?=$hash['news_title']?>"/></span>
		<input type="hidden" name="id" value="<?=$hash['id']?>"/>
		<?=$helper->checkbox('recorder_disp', 1, $hash['recorder_disp'], 'recorder_disp', '打刻機で表示')?>
		<?=$helper->checkbox('news_hide', 1, $hash['news_hide'], 'news_hide', '非表示')?>
		&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="News.cancel(<?=$hash['id']?>);">×</a>
	</div>
	<div class="forumcontent">
		<textarea name="news_body" id="news_body" style="width: 100%;" rows="20"/>
		</textarea>
	</div>

	<div class="forumproperty" style="margin-bottom: 10px;">
		<span>表示開始：&nbsp;<input class="datetimepicker" type="text" name="news_begin" id="news_begin" value="<?=$helper->nicedatetime($hash['news_begin'])?>"/></span>
		<span>表示終了：&nbsp;<input class="datetimepicker" type="text" name="news_end" id="news_end" value="<?=$helper->nicedatetime($hash['news_end'])?>"/></span>


		<button type="button" onclick="News.update(<?=$hash['id']?>);">更新</button>
		<button type="button" onclick="News.cancel(<?=$hash['id']?>);">キャンセル</button>
		<button type="button" onclick="News.remove(<?=$hash['id']?>);">削除</button>
	</div>
</form>
