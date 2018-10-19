/*
 * kagen.yu
 * 文字コード UTF-8
 */

function News() {}

$(document).ready(function() {
	$("#newsform").validate({
	    rules: {
	    	news_title: {required: true, maxlength: 50},
	    	news_body: {required: true, maxlength: 10000},
	    	news_end: {greaterThan: "#news_begin"}
	    },
	    messages: {
	    	news_title: "タイトルは必須です。長さ制限は50文字です。",
	    	news_body: "成文は必須です。長さ制限は10000文字です。",
	    	news_end: "表示開始と表示終了を正しく設定してください。"
	    },
	    tooltip_options: {
	    	'_all_': { placement: 'right' }
	    }
	});
});


News.insert = function () {
	ajax_submit('newsform','/' + window.location.pathname.split('/')[1] + '/news/insert.php','newslist','prepend');
}

News.edit = function (id) {
	$("#news_"+id).find("#newsview_"+id).hide();
	$("#news_"+id).find("#newsedit_"+id).show();
}

News.cancel = function(id){
	$("#news_"+id).find("#newsview_"+id).show();
	$("#news_"+id).find("#newsedit_"+id).hide();
}

News.update = function (id) {
	ajax_submit('editnews_'+id,'/' + window.location.pathname.split('/')[1] + '/news/update.php','news_'+id);
}

News.remove = function (id) {
	if (confirm('お知らせを削除します\nよろしいですか？')) {
		ajax_send('/' + window.location.pathname.split('/')[1] + '/news/remove.php', {"id":id}, function(){$("#news_"+id).remove();});
	}
}