/**
 * 非同期通信
 */


function ajax_send(url,objData, successCallback, method, datatype){
	if(!method){
		method = "POST"
	}

	if(!datatype){
		datatype = "json"
	}

	$.ajax({
    url: url,
    type: method,
    data: objData,
    data_type: datatype,
    timeout: 10000,
    async: false,
    error: function(request, status, e){
    	tempAlert("通信に不具合が発生しました、少し時間をおいて再度試してください。", 5000);
    },
    success: successCallback
  });
}

/*
 * formname：formのid属性、ごめんnameじゃない
 * url：送る先URL
 * contaner：結果を表示する要素
 * responsepend：結果を最後に追加するか、一番上に挿入するか、それとも差し替えるのかを決める
 * loadmsg：normal（アイコン＋文字）icon（アイコンのみ）small-icon（小さいアイコンinline）その他（なし）
 * callbackfunc：コールバック
 * 追加するPOSTデータ
 *
 */
function ajax_submit(formname, url, contaner, responsepend, loadmsg, callbackfunc, extrdata, skipvalidate){
	var fromObj = $('#'+formname);
	if(!skipvalidate){
		skipvalidate = false
	}
	if (fromObj.valid() || skipvalidate) {
		var data = {};
		fromObj.find(':input').each(function(){
			if(this.name !== "" && !this.disabled){
				switch (this.type) {
					case 'checkbox':
						if(this.checked){
							data[this.name] = 1;
						}else{
							data[this.name] = 0;
						}
						break;
					default:
						data[this.name] = (this.value?this.value:null);
						break;
				}
			}
		});

		if (loadmsg === undefined) {
			loadmsg = 'normal';
	    }

		if (extrdata !== undefined) {
			Object.keys(extrdata).forEach(function (key) {
				data[key] = extrdata[key];
			});
		}

		switch (loadmsg) {
			case 'normal':
				var strContaner = '<div class="layercontent" id="ajaxloging"><img src="../images/indicator.gif" style="vertical-align:middle;" />&nbsp;データを読み込んでいます。しばらくお待ちください。</div>';
				break;
			case 'icon':
				var strContaner = '<div class="layercontent" id="ajaxloging"><img src="../images/indicator.gif" style="vertical-align:middle;" /></div>';
				break;
			case 'small-icon':
				var strContaner = '<div class="layercontent" id="ajaxloging" style="display:inline;"><img src="../images/small-indicator.gif" style="vertical-align:middle;" /></div>';
				break;
			default:
				var strContaner = '<div class="layercontent" id="ajaxloging"></div>';
				break;
		}

		var object = $('#'+contaner);
		object.prepend(strContaner);
		object.ajaxError(function(){
			object.html('<div class="error">データファイルへのアクセスに失敗しました。</div>');
		});

		switch (responsepend) {
			case 'append':
				var callbackfuncall = function(response){object.find('#ajaxloging').remove();fromObj.trigger("reset");object.append(response);if(callbackfunc !== undefined){callbackfunc(response);}};
				break;
			case 'prepend':
				var callbackfuncall = function(response){object.find('#ajaxloging').remove();fromObj.trigger("reset");object.prepend(response);if(callbackfunc !== undefined){callbackfunc(response);}};
				break;
			default:
				var callbackfuncall = function(response){object.find('#ajaxloging').remove();fromObj.trigger("reset");object.html(response);if(callbackfunc !== undefined){callbackfunc(response);}};
				break;
		}

		ajax_send(url, data, callbackfuncall);
	}
}