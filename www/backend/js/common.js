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

$(function(){
	$.ajaxSetup({dataType: 'json', dataFilter:function(ret, type){
		if (type == 'json') {
			ajax_ret = eval('(' + ret + ')');
			if (ajax_ret && ajax_ret.debug) {
				showDebugInfo(ajax_ret.debug);
				delete ajax_ret.debug;
				ret = json_encode(ajax_ret);
			}
		}
		return ret;
	}});
	
	//切换城市
	$('.city-menu a').click(function(){
		$.get(this.href, function(ret){
			if (ret.status == 0) {
				alert(ret.data);
			} else {
				location.reload();
			}
		});
		return false;
	});
	
	//日期选择
	if ($('.datepicker').datepicker) {
		$('.datepicker').datepicker({dateFormat:'yy-mm-dd'});
	}
	$('.laydatetime').click(function(){
		if (typeof laydate != 'function') {
			alert('请先引入laydate.js!');
			return false;
		}
		var format = 'YYYY-MM-DD hh:mm:ss';
		if ($(this).attr('format')) {
			format = $(this).attr('format');
		}
		laydate.skin('molv');
		laydate({istime: true, format: format});
	});
        
    $("#backtop").hide();
    $(window).scroll(function () {
      if ($(this).scrollTop() > 100) {
        $('#backtop').fadeIn();
      } else {
        $('#backtop').fadeOut();
      }
    });
    $('#backtop').click(function () {
      $('body,html').animate({
        scrollTop: 0
      }, 500);
    });
});