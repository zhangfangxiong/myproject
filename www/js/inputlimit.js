$(function(){
	$('input[inputlimit]').keypress(function(event){
		var keycode = event.keyCode || event.charCode;
		var keychar = String.fromCharCode(keycode);
		// alert(keycode + '|' + keychar + '|' + type);
		var okkey = [8, 37, 38, 39, 40];
		if (okkey.indexOf(keycode) != -1) {
			return true;
		}

		var tmp = $(this).attr('inputlimit').split(':');
		var type = tmp[0];
		var length = tmp[1] ? tmp[1] : 999999;
		if ($(this).val().length >= length) {
			event.returnValue = false;
		    event.preventDefault();
		    return false;
		}
		
		var rules = {
			'int' : new RegExp('[0-9\-]'),
			'float' : new RegExp('[0-9\-\.]'),
			'uint' : new RegExp('[0-9]'),
			'ufloat' : new RegExp('[0-9\.]'),
			'char' : new RegExp('[0-9a-z]', 'i'),
			'char1' : new RegExp('[0-9a-z_]', 'i'),
			'char2' : new RegExp('[0-9a-z_\-]', 'i'),
			'enchar' : new RegExp('[a-z]', 'i'),
			'enchar1' : new RegExp('[a-z_]', 'i'),
			'enchar2' : new RegExp('[a-z_\-]', 'i')
		};
		
		if (rules[type]) {
			var re = rules[type];
		} else {
			var re = RegExp(type);
		}
		
		if (! re.test(keychar)) {
			event.returnValue = false;
		    event.preventDefault();
		    return false;
		} else {
			return true;
		}
	});
});