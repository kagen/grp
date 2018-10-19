function tts(speech){
	var audio = new Audio();
	audio.src ="http://api.voicerss.org?src=" + speech + "&key=28a82a6d08a8445cafc0a156f3e4ed4e&hl=ja-jp&f=44khz_16bit_stereo&c=OGG";
	audio.play();
}

function popmsg(msg,type){
	if (type===undefined){
		type='notice';
	}
	switch (type){
		case 'success':
			jSuccess(msg);
			break;
		case 'notice':
			jNotify(msg);
			break;
		case 'error':
			jError(msg);
			break;
	}
}