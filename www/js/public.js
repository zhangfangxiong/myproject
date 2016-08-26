/**
 * @Abstract    4008000000公共脚本文件
 * @Datatime    13-10-12
 * @Author      Jayuh [ex-xieyijun001@pingan.com.cn]
 * @Version     1.0
 */

/* 导航 */
$('.main_menu_item').hover(function() {
	$(this).addClass('main_menu_item_select').find('.main_menu_sublist').show();
},function() {
	$(this).removeClass('main_menu_item_select').find('.main_menu_sublist').hide();
});

/* 展开主导航的侧边导航 */
if(!$('#nav_home').length && !$('#header_b').length){
	$('.nav_title').data('timer', null).bind({
		mouseover: function() {
			var $title = $(this);
			clearTimeout($title.data("timer"));
			$title.siblings('.header_sidenav').slideDown(200, function() {
				$(this).css('overflow', 'visible');
			}).bind({
				mouseover: function() {
					clearTimeout($title.data("timer"));
				},
				mouseout: function() {
					var $this = $(this);
					clearTimeout($title.data("timer"));
					$title.data("timer", setTimeout(function() {
						$this.hide();
					}, 200));
				}
			});
		},
		mouseout: function() {
			var $title = $(this);
			clearTimeout($title.data("timer"));
			$title.data("timer", setTimeout(function() {
				$title.siblings('.header_sidenav').hide();
			}, 200));
		}
	});
}

/* 侧导航菜单 */
(function(){
var startX = null, startY = null, endX = null, endY = null, able = true, isTimer = false, hoverTimer = null,
	fn = function (obj1, obj2) {
		obj1.addClass('header_sidenav_item_curr').siblings().removeClass('header_sidenav_item_curr');
		var minus = ( obj1.offset().top + obj2.height() ) - ( $(window).height() + $(window).scrollTop() ) + 20;
		if (minus > 0) {
			obj2.animate({'margin-top':- minus},200);
		} else {
			obj2.animate({'margin-top':'0'},200);
		}
		if ((!-[1,]) && !('XMLHttpRequest' in window)) {
			setTimeout(function() {
				$('.header_sidenav_close', obj2).css('zoom','1');
			}, 0);
		}
	};
//	$('.header_sidenav_shadow').css({'width':});
	$('.header_sidenav_item').bind({
		'mouseenter': function(e) {
			if(isTimer){ return }
			isTimer = true;
			startX = e.pageX;
			startY = e.pageY;
			var $this = $(this), $subCont = $this.find('.header_sidenav_cnt');
			if ($subCont.length && able) {
				fn($this, $subCont);
				isTimer = false;
			} else {
				hoverTimer = setTimeout(function(){
					fn($this, $subCont);
					isTimer = false;
				},500);
			}
		},
		'mouseleave': function(e) {
			clearTimeout(hoverTimer);
			isTimer = false;
			endX = e.pageX;
			endY = e.pageY;
			if (Math.abs(endX - startX)/Math.abs(endY - startY) < 0.3) {
				able = true;
			} else {
				able = false;
			}
		}
	});
	$('.header_sidenav_close').bind('click', function() {
		$(this).parents('.header_sidenav_item').removeClass('header_sidenav_item_curr');
		if(!$('#header_b').length){
			$('.header_sidenav').hide();
		}
	});
	$('.header_sidenav').bind({
		'mouseleave': function() {
			$(this).find('.header_sidenav_item').removeClass('header_sidenav_item_curr');
		}
	});
})();

/**
 * 在线客服
 * 在线客服框会根据坐席上下班时间，实现亮和暗（9:00-11:30 13:30-18:30为上班时间）
 */
(function(){
	if ($('.fixed_service_online').length) {
		var xhr = $.ajax({
			type : 'HEAD',
			url : '/customer/assets/images/user_info_ico.gif',
			async : false,
			cache : false
		});
		var serverDate = new Date(xhr.getResponseHeader("Date"));
		function getTime(h,m){
			var curDate = new Date();
			curDate.setHours(h);
			curDate.setMinutes(m);
			return curDate.getTime();
		}
		var serverTime = serverDate.getTime();
		var startTime = getTime(9,0);
		var entTime = getTime(11,30);
		var startTime1 = getTime(13,30);
		var entTime1 = getTime(18,30);
		// 上班时间
		if(!((serverTime > startTime && serverTime < entTime) || (serverTime > startTime1 && serverTime < entTime1))){
			$('.fixed_service_online').addClass('fixed_service_online_off');
		}
	}
})();

/* 我的订单（导航右侧） */
$('.my_order').hover(function() {
	$(this).children('.my_order_btn').addClass('my_order_btn_show').next().slideDown(200);
}, function() {
	$(this).children('.my_order_btn').removeClass('my_order_btn_show').next().hide();
});

/* 返回顶部 */
(function(){
	var $goTop = $('#fixed_gotop');
	$goTop.hide();
	$goTop.bind('click', function() {
		$('body,html').animate({scrollTop: 0},200);
		return false;
	});
	$(window).bind('scroll load resize', function(){
		var scrollTop = $(this).scrollTop();
		if (scrollTop < 500) {
			$goTop.slideUp(200);
		} else {
			$goTop.slideDown(200);
		}
		
	})
})();

/* Input */
$('.ui_input').bind({
	mouseover: function() {

	},
	focus: function() {
		$(this).addClass('ui_input_focus');
	},
	blur: function() {
		$(this).removeClass('ui_input_focus');
	}
});

//切换城市

$(".select_city_wrap .select_city_tab_t a").bind("click",function() {
	var $this = $(this), index = $this.parent().children().index($this);
	$this.addClass("current").siblings().removeClass("current");
	$this.parents(".select_city_wrap").children(".select_city_cnt").children(".select_city_cnt_item2:eq("+index+")").show().siblings().hide();
});
$("#select_city").bind("click",function() {
	$(this).parent().find(".select_city_wrap").show();
});
$(".select_city_wrap .close").bind("click",function() {
	$(this).parent().hide();
});

// 底部Tab切换
$('.footer_menu_tabTitle').bind('click', function(e) {
	if (e.target.nodeName === 'A') {
		var $target = $(e.target), index = $target.index();
		$target.addClass('current').siblings().removeClass('current');
		$(this).next().children('div').eq(index).removeClass('dn').siblings().addClass('dn');
	}
});




// 服务中心侧边导航
$('.side_menu_item').bind('click', function () {
	$(this).find('h3.f_c_f63').removeClass('f_c_f63');
    if ($(this).find('.side_submenu').is(':visible')) { 
        $(this).removeClass('side_menu_item_cut');        
        $(this).find('.side_submenu').slideUp();
    } else {
        $(this).addClass('side_menu_item_cut').siblings().removeClass('side_menu_item_cut');
        var that = this;
        $('.side_menu_item .side_submenu:visible').slideUp();
        $(this).find('.side_submenu').slideDown();
    }
});
// 服务中心侧边hover事件
$('.side_menu_item h3').hover(function(){
	if($(this).parent().hasClass('side_menu_item_cut')){
		return;
	}
	$(this).addClass('f_c_f63');
},function(){
	if($(this).parent().hasClass('side_menu_item_cut')){
		return;
	}
	$(this).removeClass('f_c_f63');
});
$(".side_submenu").bind("click", function (event) {
    event.stopPropagation();
});
// 服务中心侧边 - 根据当前URl展开对应菜单项
(function () {
	var leftSidebar = $(".left_sidebar");
	var curSubmemu = leftSidebar.find('ul.'+leftSidebar.parent().attr('id'));
	if(curSubmemu.length>0){
		curSubmemu.show();
		curSubmemu.parents('.side_menu_item').addClass('side_menu_item_cut').siblings().removeClass('side_menu_item_cut');
	}
	var submenuMatch = location.href.match(/(\/[^/#]+)#*$/);
	if(submenuMatch && submenuMatch.length>1){
		var pageurl = submenuMatch[submenuMatch.length-1];	
		 var curSideMenuItem = $('#side_menu .side_submenu').find('a[href*="'+ pageurl +'"]').addClass('f_c_f63').parents('.side_menu_item');
		 if(curSideMenuItem && curSideMenuItem.length>0){
			if(curSideMenuItem.find('.side_submenu').is(':hidden')){
				curSideMenuItem.trigger('click');
			}
		 }
	}
})();



/****/

$(".car_life_pro_temp li").hover(function(){$(this).addClass("li_hover");},function(){$(this).removeClass("li_hover");});
// 头部微信下拉
$(".follow_weixin").hover(
  function () {
    $(this).children().show();
  },
  function () {
   $(this).children().hide();
  }
);
