
window.onload=function (){

      var win_h = $(window).height();
	  var win_w =$(window).width();
	  
	  $("#mask").css({"height":win_h+'px'})
		index_resize();
		$(".add_bt_7 button").click(function (){
			$("#table_fr").show();
			$("#mask").show();
			$("#mask").click(function (){
				$(this).hide();
				$("#table_fr").hide();
			})
		})
	  var swich_bt = $("#sign_2 .switch button")
	  swich_bt.click(function (){
		  swich_bt.removeClass('active');
		  $(this).addClass('active');
		  $("#sign_2 .sign_fr_box").removeClass('active');
		  $("#sign_2 .sign_fr_box").eq(swich_bt.index(this)).addClass('active');
	  })
	  
	  $("#set_nav ul li button").click(function (){
              $("#set_nav ul li button").removeClass("active");
			  $(this).addClass("active");
			  $("#set_details ol li.set_details").hide();
			  $("#set_details ol li.set_details").eq($("#set_nav ul li button").index(this)).show();
	  });
	  
	  $("#notes_nav ul li button").mouseover(function (){
              $("#notes_nav ul li button").removeClass("active");
			  $(this).addClass("active");
			  $("#notes_sub ul li").hide();
			  $("#notes_sub ul li").eq($("#notes_nav ul li button").index(this)).show();
	  });
	  

	  
	  $("#details_pro_nav li button").mouseover(function (){
              $("#details_pro_nav li p").removeClass("active");
			  $("#details_pro_nav li p").eq($("#details_pro_nav li button").index(this)).addClass('active');
              $("#details_pro_nav li button").removeClass("active");
			  $(this).addClass('active');
			  $("#details_pro_sub ul li").hide();
			  $("#details_pro_sub ul li").eq($("#details_pro_nav li button").index(this)).show();
		  $("#move").click(function (){
			  $("#details_pro_sub ul li").show();
		  })
	  });
	  
	  
	  $("#reservation_nav ul li button").mouseover(function (){
			  var re_bt = $("#reservation_nav ul li button")
			  var re_li = $("#reservation_sub ul li.reservation_sub_li")
              $(re_bt).removeClass("active");
			  $(this).addClass("active");
			  $(re_li).hide();
			  $(re_li).eq(re_bt.index(this)).show();
			  
			  var re_last_h = $(re_li).eq(re_bt.index(this)).find("li:last").height();
			  var re_ul_h = $(re_li).eq(re_bt.index(this)).find("ul").height();
	          $(".line_orange").css({"height":re_ul_h-re_last_h})
	  });
	  $("#comment_show").click(function (){
		  var com_ul_h = $("#comment ul").height()
			  $(".block").hide();
		      $("#comment ul li").toggle();
			  if(com_ul_h<=430){
				  $(this).html('收起评论');
			  }
			  else{
				   $(this).html('显示更多评论');
			  }
	  });
	  
	  $("#choice_nav ul li button").click(function (){
		      var cho_nav = $("#choice_nav ul li button")
			  var cho_main = $("#choice_main ul li.choice_main")
              cho_nav.removeClass("active");
			  $(this).addClass("active");
			  cho_main.hide();
			  cho_main.eq(cho_nav.index(this)).show();
	  });
	  
	  $("#info ul li .data").click(function (){
		  $("#info ul li .data_fr").eq($("#info ul li .data").index(this)).toggle();
	  })
	  
	  $("#set_up_nav ul li button").click(function (){
		  $("#set_up_nav ul li button").removeClass("active");
		  $(this).addClass("active");
	  });
	 
}


$(window).resize(function () {
		index_resize();
})

function index_resize(){
	  var win_h = $(window).height();
	  var win_w =$(window).width();
	
	  $(".br_ic_9").css({"margin-top":win_h/4})
	  $("#line_wd100").css({"width":win_w,"margin-left":-(win_w-980)/2});
	  $("#br_lt img").css({"height":win_h})
	 if(win_h>640){	  
	  $("#sign_2").css({"height":win_h})
	  $("#banner_2").css({"height":win_h},{"width":win_w})
	  $("#banner_3").css({"height":win_h},{"width":win_w})
	  }else{
	  	 $("#sign_2").css({"height":"640px"})
	  	 $("#banner_2").css({"height":"640px"},{"width":win_w})
	  	 $("#banner_3").css({"height":"640px"},{"width":win_w})
	  	 
	  }
}
	 
