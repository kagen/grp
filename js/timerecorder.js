function Timerecorder() {
}

Timerecorder.dakoku = function(email) {
	url = '/' + window.location.pathname.split('/')[1] + '/timecard/dakoku.php';
	var sndData = {
		dakoku_email : email
	};
	// タイムカードを打刻します
	// 戻り値：
	// 1 - 出社打刻成功しました
	// 2 - 退社打刻成功しました
	// 3 - テスト打刻成功
	// 901 - ユーザは登録されていない
	// 902 - 120秒以内の重複打刻
	// 903 - 120秒以上10分以内の重複打刻
	// 904 - 既にWEB出社打刻済
	// 905 - 既にWEB退社打刻済
	// 906 - 既に退社しました
	// 999 - 原因不明なエラー
	var successCallbackTC = function(request, status, e) {
		//alert(request);
		var obj = eval("(" + request + ")");
		var dDate = "";
		var dTime = "";
		var dJikoku = "";
		var msg = "";
		var vmsg = "";
		var aisatsu_taisya = "";
		var aisatsu_syussya = "";

		if (moment(obj["dakokuDate"],"YYYY-MM-DD").format("MM-DD") == "12-24") {
			aisatsu_taisya = "メリークリスマス!";
		} else if (moment(obj["dakokuDate"],"YYYY-MM-DD").format("MM-DD") == "12-25") {
			aisatsu_syussya = "メリークリスマス!";
			aisatsu_taisya = "メリークリスマス!";
		}


		if (obj["shigotoosame"] && moment(obj["dakokuDate"],"YYYY-MM-DD").format("YYYY-MM-DD") == obj["shigotoosame"]) {
			aisatsu_taisya = "よいお年をお迎えください!";
		}
		if (obj["shigotohajime"] && moment(obj["dakokuDate"],"YYYY-MM-DD").format("YYYY-MM-DD") == obj["shigotohajime"]) {
			aisatsu_syussya = "あけましておめでとうございます!";
		}

		switch (obj["dakokuStatus"]) {
			case 1:
				//playSound("1.wav");
				dDate = moment(obj["dakokuDate"] + " " + obj["dakokuTime"], "YYYY-MM-DD HH:mm").format("YYYY年MM月DD日");
				dTime = moment(obj["dakokuDate"] + " " + obj["dakokuTime"], "YYYY-MM-DD HH:mm").format("HH時mm分");
				dJikoku = moment(obj["dakokuJikoku"],"YYYY-MM-DD HH:mm:ss").format("YYYY年MM月DD日 HH時mm分ss秒");
				$("#result").html(dJikoku);

				if (aisatsu_syussya == "") {
					msg = obj["userName"] + " さん、お早うございます！" + "<br>" + dTime + "に出社打刻しました。";
					vmsg = (obj["user_ruby"]?obj["user_ruby"]:obj["userName"]) + "さん、お早うございます！" + dTime + "に出社打刻しました。";
				} else {
					msg = obj["userName"] + " さん、" + aisatsu_syussya + "<br>" + dTime + "に出社打刻しました。";
					vmsg = (obj["user_ruby"]?obj["user_ruby"]:obj["userName"]) + "さん、" + aisatsu_syussya + dTime + "に出社打刻しました。";
				}
				tts(vmsg);
				tempAlert(dDate + "<br>" + msg, 5000);
				break;
			case 2:
				//playSound("2.wav");
				dDate = moment(obj["dakokuDate"] + " " + obj["dakokuTime"], "YYYY-MM-DD HH:mm").format("YYYY年MM月DD日");
				dTime = moment(obj["dakokuDate"] + " " + obj["dakokuTime"], "YYYY-MM-DD HH:mm").format("HH時mm分");
				dJikoku = moment(obj["dakokuJikoku"],"YYYY-MM-DD HH:mm:ss").format("YYYY年MM月DD日 HH時mm分ss秒");
				$("#result").html(dJikoku);

				if (aisatsu_taisya == "") {
					msg = obj["userName"] + " さん、お疲れ様でした！" + "<br>" + dTime + "に退社打刻しました。";
					vmsg = (obj["user_ruby"]?obj["user_ruby"]:obj["userName"]) + "さん、お疲れ様でした。!" + dTime + "に退社打刻しました。";
				} else {
					msg = obj["userName"] + " さん、" + aisatsu_taisya + "<br>" + dTime + "に退社打刻しました。";
					vmsg = (obj["user_ruby"]?obj["user_ruby"]:obj["userName"]) + "さん、" + aisatsu_taisya + dTime + "に退社打刻しました。";
				}


				tts(vmsg);
				tempAlert(dDate + "<br>" + msg, 5000);
				break;
			case 3:
				dJikoku = moment(obj["dakokuJikoku"],"YYYY-MM-DD HH:mm:ss").format("YYYY年MM月DD日 HH時mm分ss秒");
				tts("QRコードのテスト読取りは成功しました。サーバ時間は" + dJikoku + "でした。");
				tempAlert("QRコードのテスト読取りは成功しました。",5000);
				break;
			case 901:
				//playSound("error.wav");
				popmsg("QRコードの読取が失敗したか、まだ登録されていないか、<br>もう一回カードを飾ってください。");
				break;
			case 902:
				//playSound("error.wav");
				popmsg("重複の打刻と思われます。");
			case 903:
				//playSound("error.wav");
				popmsg("出社の打刻からまだ10分も経っていないので、重複の打刻と思われます。");
				break;
			case 906:
				//playSound("error.wav");
				popmsg("既に退社しました、ご確認ください。");
				break;
			default:
				break;
		}

	};
	ajax_send(url, sndData, successCallbackTC);
}