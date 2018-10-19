<form class="forum" id="newsform">
	<div style="margin: 10px;">
		<span>タイトル：&nbsp;<input type="text" name="news_title" id="news_title" style="width: 60%;"/></span>
		<?=$helper->checkbox('recorder_disp', 1, 0, 'recorder_disp', '打刻機で表示')?>
		<input type="hidden" name="news_hide" id="news_hide" value="0"/>
		&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="$('#newsform').fadeToggle(250);">×</a>
	</div>
	<div class="forumcontent">
		<div><textarea name="news_body" id="news_body" style="width: 100%;" rows="20"/></textarea></div>
	</div>

	<div class="forumproperty" style="margin-bottom: 10px;">
		<span>表示開始：&nbsp;<input class="datetimepicker" type="text" name="news_begin" id="news_begin"/></span>
		<span>表示終了：&nbsp;<input class="datetimepicker" type="text" name="news_end" id="news_end"/></span>


		<button type="button" onclick="News.insert();">新規登録</button>
		<button type="button" onclick="$('#newsform').fadeToggle(250);">キャンセル</button>
	</div>
</form>
