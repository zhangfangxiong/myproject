;
$(function() {
	// 标签导航
	if ($("dl.tab_nav dd a.tab_light").length == 0) {
		$("div.tab_box > div").hide();
		$("dl.tab_nav dd a:first").addClass("tab_light");
		$("div.tab_box > div:first").show();
	} else {
		if ($("div.tab_box > div").length == 1) {
			$("div.tab_box > div:first").show();
		} else {
			$("div.tab_box > div").hide();
			var which = 0;
			$("dl.tab_nav dd a").each(function(){
				if ($(this).hasClass('tab_light')) {
					return false;
				}
				which++;
			});
			$("dl.tab_nav dd a:eq("+which+")").addClass("tab_light");
			$("div.tab_box > div:eq("+which+")").show();
		}
	}
	
	$('dl.tab_nav dd a').click(function() {
		$(this).addClass('tab_light').siblings().removeClass('tab_light');
		$("div.tab_box > div").hide().eq($('dl.tab_nav dd a').index(this)).fadeIn();
	});

	$('.minitip').miniTip();
});