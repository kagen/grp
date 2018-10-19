/*
 * kagen
 * 文字コード UTF-8
 */

function Storage() {}

$(document).ready(function() {
//	エレメントクラス

//	左のフォルダールート
//	storagefolderlist　　　　　右クリック名前変更(×)　ドラッグ(×)　ドロップ(○)　右クリックメニュー禁止(×)
//
//	左のフォルダー
//	storagefolderlist　　　　　右クリック名前変更(○)　ドラッグ(×)　ドロップ(○)　右クリックメニュー禁止(○)
//
//	左のフォルダー選択中
//	current storagefolderlist　右クリック名前変更(○)　ドラッグ(×)　ドロップ(×)　右クリックメニュー禁止(○)
//
//	右のフォルダー
//	storagefolder　　　　　　　右クリック名前変更(○)　ドラッグ(○)　ドロップ(○)　右クリックメニュー禁止(○)
//
//	右のファイル
//	storagefile　　　　　　　　右クリック名前変更(○)　ドラッグ(○)　ドロップ(×)　右クリックメニュー禁止(○)

	Storage.turnOnDraggable();
	$("a[class^='storagefolder'],li[class*=' storagefolder'],a[class^='storagefile']").oncontextmenu = function() {return false;};//「storagefolder」から始まると「 storagefolder」を含めるclassが対象に
	$("a[class^='storagefolder'],li[class*=' storagefolder'],a[class^='storagefile']").mousedown(function(e){
		if( e.button == 2 && $(this).data("parent_id") >= 0) { //ルートは変更させない（できない）
			var newname = prompt("名前変更", $.trim(this.textContent));
			if (newname != null && newname != $.trim(this.textContent)){ //新しい名前は空文字でも本来と一緒でもないこと
				spaceholder = this.textContent.replace($.trim(this.textContent),'')
				Storage.rename(this, newname, spaceholder);
			}
			return false;
		}
		return true;
	});
});

Storage.rename = function (elm, newname, spaceholder) {
	var callbackfunc = function (response) {
		res = $.parseJSON(response);
		if (res.result == "renamed") {
			elm.textContent = spaceholder + res.newname;
		}
		if (res.result_msg) {
			popmsg(res.result_msg);
		}
	};
	ajax_send('/' + window.location.pathname.split('/')[1] + '/storage/rename.php',{'storage_id': $(elm).data("storage_id"), 'parent_id': $(elm).data("parent_id"), 'storage_type': $(elm).data("storage_type"), 'newname': newname}, callbackfunc);
}

Storage.turnOnDraggable = function() {
	$("a[class='storagefolder'],a[class='storagefile']").draggable({
		revert: true
	});
	$("a[class^='storagefolder']").droppable({
		drop: function( event, ui ) {
			var elm = ui.draggable[0];
			var cur_folder = $("#current_folder")[0];
			var from_id = $(elm).data("parent_id");
			var storage_id = $(elm).data("storage_id");
			var to_id = $(this).data("storage_id");

			var from_row = $(elm).parent().parent();

			var from_desc = "「"+$.trim(cur_folder.textContent)+"」配下の「"+elm.textContent+"」"+($(elm).data("storage_type") == "folder"?"及び配下の内容":"");
			var to_desc = "「"+$.trim(this.textContent)+"」";

			if (confirm(from_desc+"を"+to_desc+"に移動ますか？")) {
				var callbackfunc = function (response) {
					res = $.parseJSON(response);
					if (res.result == "moved") {
						from_row.fadeOut("slow");
					}
					if (res.result_msg) {
						popmsg(res.result_msg);
					}

				};

				ajax_send('/' + window.location.pathname.split('/')[1] + '/storage/move.php',{'storage_id': storage_id, 'to_id': to_id}, callbackfunc);
			}
		}
	});
}
