var a = '<div id="izl_rmenu" class="izl-rmenu"><a href="http://wpa.qq.com/msgrd?v=3&amp;uin=2544745674&amp;site=qq&amp;menu=yes" class="btn-services btn-qq" target=\'_blank\'></a><div class="btn-services btn-wx"><img class="pic" src="/front/images/kefu/little-s.png"/></div><div class="btn-services btn-phone"><div class="phone">400-878-9551</div></div><div class="btn-services btn-top"></div></div>',
b = !1;
$(window).scroll(function() {
    var a = $(window).scrollTop();
    a > 200 ? $("#izl_rmenu").data("expanded", !0) : $("#izl_rmenu").data("expanded", !1),
    $("#izl_rmenu").data("expanded") != b && (b = $("#izl_rmenu").data("expanded"), b ? $("#izl_rmenu .btn-top").slideDown() : $("#izl_rmenu .btn-top").slideUp())
}),
$(document).delegate(".btn-wx", "mouseenter",
function() {
    $(this).find(".pic").fadeIn("fast")
}),
$(document).delegate(".btn-wx", "mouseleave",
function() {
    $(this).find(".pic").fadeOut("fast")
}),
$(document).delegate(".btn-phone", "mouseenter",
function() {
    $(this).find(".phone").fadeIn("fast")
}),
$(document).delegate(".btn-phone", "mouseleave",
function() {
    $(this).find(".phone").fadeOut("fast")
}),
$(document).delegate(".btn-top", "click",
function() {
    $("html, body").animate({
        "scroll-top": 0
    },
    "fast")
});
var c = function() {
    var a = $(window).width(),
    b = a / 2 - 100;
    $(".izl-rmenu").css("marginLeft", b)
};

$("body").append(a);
c();
