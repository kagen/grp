﻿// QRCODE reader Copyright 2011 Lazar Laszlo
// http://www.webqr.com

var gCtx = null;
var gCanvas = null;
var c = 0;
var stype = 0;
var gUM = false;
var webkit = false;
var moz = false;
var v = null;

var imghtml = '<div id="qrfile"><canvas id="out-canvas" width="320" height="240"></canvas>'
		+ '<div id="imghelp">drag and drop a QRCode here'
		+ '<br>or select a file'
		+ '<input type="file" onchange="handleFiles(this.files)"/>'
		+ '</div>'
		+ '</div>';

var vidhtml = '<video id="v" autoplay></video>';

function dragenter(e) {
	e.stopPropagation();
	e.preventDefault();
}

function dragover(e) {
	e.stopPropagation();
	e.preventDefault();
}
function drop(e) {
	e.stopPropagation();
	e.preventDefault();

	var dt = e.dataTransfer;
	var files = dt.files;
	if (files.length > 0) {
		handleFiles(files);
	} else if (dt.getData('URL')) {
		qrcode.decode(dt.getData('URL'));
	}
}

function handleFiles(f) {
	var o = [];

	for (var i = 0; i < f.length; i++) {
		var reader = new FileReader();
		reader.onload = (function(theFile) {
			return function(e) {
				gCtx.clearRect(0, 0, gCanvas.width, gCanvas.height);

				qrcode.decode(e.target.result);
			};
		})(f[i]);
		reader.readAsDataURL(f[i]);
	}
}

function initCanvas(w, h) {
	gCanvas = document.getElementById("qr-canvas");
	gCanvas.style.width = w + "px";
	gCanvas.style.height = h + "px";
	gCanvas.width = w;
	gCanvas.height = h;
	gCtx = gCanvas.getContext("2d");
	gCtx.clearRect(0, 0, w, h);
}

function captureToCanvas() {
	if (stype != 1)
		return;
	if (gUM) {
		try {
			gCtx.drawImage(v, 0, 0);
			try {
				qrcode.decode();
			} catch (e) {
				console.log(e);
				setTimeout(captureToCanvas, 500);
			}
			;
		} catch (e) {
			console.log(e);
			setTimeout(captureToCanvas, 500);
		}
		;
	}
}

function htmlEntities(str) {
	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(
			/>/g, '&gt;').replace(/"/g, '&quot;');
}

function read(a) {
	var html = "";
	if (a.indexOf("http://") === 0 || a.indexOf("https://") === 0)
		html += "<a target='_blank' href='" + a + "'>" + a + "</a>";
	html += htmlEntities(a);
	document.getElementById("result").innerHTML = jQuery.trim(html);
	Timerecorder.dakoku(html);
	loadQrReader();
}

function tempAlert(msg, duration) {
	var el = document.createElement("div");
	el.id = "infopop";
	el.setAttribute("style",
			"position:absolute;top:40%;left:20%;background-color:white;");
	el.innerHTML = msg;
	setTimeout(function() {
		el.parentNode.removeChild(el);
	}, duration);
	document.body.appendChild(el);
}

function playSound(soundfile) {
	var audioElement = document.createElement('audio');
	audioElement.setAttribute('src', 'sounds/' + soundfile);
	audioElement.setAttribute('autoplay', 'autoplay');
	audioElement.load()
	audioElement.play();
}

function isCanvasSupported() {
	var elem = document.createElement('canvas');
	return !!(elem.getContext && elem.getContext('2d'));
}
function success(stream) {
	if (webkit)
		v.src = window.webkitURL.createObjectURL(stream);
	else if (moz) {
		v.mozSrcObject = stream;
		v.play();
	} else
		v.src = stream;
	gUM = true;
	setTimeout(captureToCanvas, 1000);
}

function error(error) {
	gUM = false;
	return;
}

function loadQrReader() {
	if (isCanvasSupported() && window.File && window.FileReader) {
		initCanvas(800, 600);
		qrcode.callback = read;
		document.getElementById("mainbody").style.display = "inline";
		setwebcam();
	}
}

function setwebcam() {
	document.getElementById("result").innerHTML = "- 社員証のQRコードをカメラにかざしてください -";
	if (stype == 1) {
		setTimeout(captureToCanvas, 500);
		return;
	}
	var n = navigator;
	document.getElementById("outdiv").innerHTML = vidhtml;
	v = document.getElementById("v");

	if (n.getUserMedia)
		n.getUserMedia({
			video : true,
			audio : false
		}, success, error);
	else if (n.webkitGetUserMedia) {
		webkit = true;
		n.webkitGetUserMedia({
			video : true,
			audio : false
		}, success, error);
	} else if (n.mozGetUserMedia) {
		moz = true;
		n.mozGetUserMedia({
			video : true,
			audio : false
		}, success, error);
	}


	stype = 1;
	setTimeout(captureToCanvas, 500);
}
function setimg() {
	document.getElementById("result").innerHTML = "";
	if (stype == 2)
		return;
	document.getElementById("outdiv").innerHTML = imghtml;
	var qrfile = document.getElementById("qrfile");
	qrfile.addEventListener("dragenter", dragenter, false);
	qrfile.addEventListener("dragover", dragover, false);
	qrfile.addEventListener("drop", drop, false);
	stype = 2;
}