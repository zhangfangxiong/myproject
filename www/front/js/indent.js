$(function(){
    var $logo=$(".logo_one .logo_title");
    $logo.find("li").click(function(){
        $(this).addClass("sub").siblings().removeClass("sub");
    })
})