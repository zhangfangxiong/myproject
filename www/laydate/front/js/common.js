function showDebugInfo(aDebug) {	
	if (console && console.debug && aDebug) {
		console.groupCollapsed(aDebug.shift());
		while(aDebug.length) {
			try {
				var msg = aDebug.shift();
				if (console[msg[0]]) {
					console[msg[0]](msg[1]);
				} else {
					console.debug(msg);
				}
			} catch (e) {
				console.log(msg);
				console.log(e);
			}
		}
		console.groupEnd();
	}
}

function showErrorInfo(form, data) {
	if ($.type(data) == 'string') {
		alert(data);
		if(data.url != undefined & data.url){
			window.location.href = data.url;
		}
	} else {
		$(form).find('label.error').hide();
		for (var key in data) {
			$(form).find('label.' + key).html(data[key]).show();
		}
	}
}

$(function(){
	var ajax_reqnum = 0;
	var ajax_loading = null;
	var ajax_requrl = '';
	$.ajaxSetup({
		dataType: 'json', 
		beforeSend: function(xhr) {
			if (ajax_reqnum > 0 && ajax_requrl == this.url) {
				return false;
			}
			
			ajax_requrl = this.url;
			ajax_reqnum++;
			if ($('#loading').length == 0) {
				$('body').append('<div id="loading"></div>');
				$('#loading').hide();
			}
			
			if (this.type == 'POST') {
				if (ajax_loading) {
					clearTimeout(ajax_loading);
				}
				ajax_loading = setTimeout(function(){
					$('#loading').show();
				}, 300);
			}
			
			return true;
		},
		complete: function(xhr, ts) {
			ajax_reqnum--;
			if (ajax_loading) {
				clearTimeout(ajax_loading);
			}
			$('#loading').hide();
		},
		dataFilter: function(ret, type) {
		if (type == 'json') {
			try {
				ajax_ret = $.parseJSON(ret);
				if (ajax_ret && ajax_ret.debug) {
					showDebugInfo(ajax_ret.debug);
					delete ajax_ret.debug;
					ret = $.toJSON(ajax_ret);
				}
			} catch (e) {
				ret = $.toJSON({'data': '数据请求异常', 'status':false});
			}
		}
		return ret;
	}});
});