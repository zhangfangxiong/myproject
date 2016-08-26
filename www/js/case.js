$(function() {
    //自增加变量
    var index = 0;
    //获取.ad类的高度
    var adWidth = $(".roll").width();
    //声明一个定时器
    var timer = null;
    timer = setInterval(show, 4000);
    //鼠标移上和鼠标移开相关
    $(document).delegate(".ad","mouseenter",enter);
    $(document).delegate(".ad","mouseleave",leave);
    function enter(){
        clearInterval(timer);
    }
//    鼠标移到小图标上启动定时器
    function leave(){
        timer=setInterval(function(){
            index++;
            show();
        },4000);
    }
    //网页新打开启动定时器
    timer = setInterval(function () {
        index++;
        show();
    }, 4000);
    //按钮的点击
    $(".imgright").on("click",function(){
        index++;
        show();
    })
    $(".imgleft").on("click",function(){
        index--;
        //判断当index的值小于0时，给index赋值4
        if(index<0){
            index=4;
        }
        show();
    })
    function show() {
        //判断，当Index=5时，重新拉回来
        if (index >= $(".roll_ul li").length) {
            index = 0;
        }
        $(".roll_ul").stop().animate({
            //top: $(".slider").position().top - $(".slider img").height()
            left: -adWidth * index
        }, 500);
        $(".roll_li").find("li").eq(index).addClass("on").siblings("li").removeClass("on");
//                console.log($(".slider").position().top)
    }

})
