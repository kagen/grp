/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

function Timecard() {}

Timecard.redirect = function (object, group) {

	var element = object.parentNode.getElementsByTagName('select');
	var year = element[0].options[element[0].selectedIndex].value;
	var month = element[1].options[element[1].selectedIndex].value;
	if (group == 'group') {
		var group = element[2].options[element[2].selectedIndex].value;
		location.href = 'group.php?year=' + year + '&month=' + month + '&group=' + group;
	} else {
		if (document.getElementsByName('member') && document.getElementsByName('member').length > 0){
			location.href = 'index.php?force=true&year=' + year + '&month=' + month + '&member=' + document.getElementsByName('member')[0].value;
		}else{
			location.href = 'index.php?force=true&year=' + year + '&month=' + month;
		}
	}

}

Timecard.interval = function (object) {

	if (object.parentNode) {
		var parent = object.parentNode;
		var element = document.createElement('div');
		element.innerHTML = '<select name="intervalopenhour[]"><option value="">&nbsp;</option>' + Timecard.option(0, 23) + '</select>時&nbsp;\n';
		element.innerHTML += '<select name="intervalopenminute[]"><option value="">&nbsp;</option>' + Timecard.option(0, 59) + '</select>分&nbsp;-&nbsp;\n';
		element.innerHTML += '<select name="intervalclosehour[]"><option value="">&nbsp;</option>' + Timecard.option(0, 23) + '</select>時&nbsp;\n';
		element.innerHTML += '<select name="intervalcloseminute[]"><option value="">&nbsp;</option>' + Timecard.option(0, 59) + '</select>分&nbsp;\n';
		element.innerHTML += '<span class="operator" onclick="Timecard.remove(this)">削除</span>';
		parent.insertBefore(element, object);
	}

}

Timecard.option = function (begin, end) {

	var option = '';
	for (var i = begin; i <= end; i++) {
		option += '<option value="' + i + '"%s>' + i + '</option>';
	}
	return option;

}

Timecard.remove = function (object) {

	if (object.parentNode) {
		var element = object.parentNode;
		var parent = element.parentNode;
		parent.removeChild(element);
	}

}

Timecard.sendOvertime = function (elm) {
	var callbackfunc = function (response) {Timecard.sumOvertime();};
	ajax_submit('timecard','/' + window.location.pathname.split('/')[1] + '/timecard/overtime.php',elm.data('otstatus'), null, 'small-icon', callbackfunc, {'current_date': elm.data('otstatus').substr(0,10)});
}

Timecard.delOvertime = function (elm, del_id) {
	if (confirm('承認済みの時間外申請を削除しますが\nよろしいですか？')) {
		var callbackfunc = function (response) {Timecard.sumOvertime();};
		ajax_submit('timecard','/' + window.location.pathname.split('/')[1] + '/timecard/overtime.php',elm.data('otstatus'), null, 'small-icon', callbackfunc, {'del_id': del_id, 'current_date': elm.data('otstatus').substr(0,10)});
	}
}

Timecard.sumOvertime = function () {
	var sumot = '0:00';
	$("span[id$='_overtime']").each(function(){
		sumot = App.sumtime(sumot,this.textContent)
	});
	$("#sum_ot_appv").text(sumot);

	sumot = '0:00';
	$("a[id$='_overtime']").each(function(){
		if (sumot,this.textContent != "＋") {
			sumot = App.sumtime(sumot,this.textContent);
		}
	});
	$("span[id$='_overtime_ori_reqs']").each(function(){
		sumot = App.sumtime(sumot,this.textContent)
	});
	$("#sum_ot_reqs").text(sumot);
}

Timecard.overtimeapproveredirect = function () {
	var userid = $("#sel_realname").val();
	var otdate = $("#sel_date").val();
	var groupid = $("#sel_user_groupname").val();
	location.href = 'overtimeapprove.php?otuserid=' + userid + '&otdate=' + otdate + '&otgroupid=' + groupid;
}

Timecard.popovertimecomment = function () {
	if (document.getElementsByName('member') && document.getElementsByName('member').length > 0){
		App.loader('../timecard/overtimecomment.php?timecard_year='+$("#year_disp").val()+'&timecard_month='+$("#month_disp").val()+'&member='+document.getElementsByName('member')[0].value, null, null, "コメント");
	} else {
		App.loader('../timecard/overtimecomment.php?timecard_year='+$("#year_disp").val()+'&timecard_month='+$("#month_disp").val(), null, null, "コメント");
	}
}

Timecard.postOvertimeComment = function (isApplication) {
	if ($("#chksyounin").prop('checked') || isApplication) {
		var callbackfunc = function (response) {$("#overtimecommenticon").attr('title', $('textarea[name=comment]').val());$("#modal-overlay").remove();};
		ajax_submit('from_overtimecomment','/' + window.location.pathname.split('/')[1] + '/timecard/overtimecomment.php','popuplayercontent', null, 'small-icon', callbackfunc, {'timecard_year': $("#year_disp").val(), 'timecard_month': $("#month_disp").val()}, true);
	} else {
		popmsg("承認のチェックしていません、ご確認ください。");
	}
}
