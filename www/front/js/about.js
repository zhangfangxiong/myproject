$(function () {
    var _speed = 3000;
    var _len = 0;
    var _size =650;
    var _direction = 'left';

    function mar() {
        if (_direction == 'left') {
            if (_len>=1800) {
                _direction = 'right';
            } else {
                $(".center_ul").animate({
                    "marginLeft": "-=" + _size + "px"
                });
                _len += _size;
            }
        } else {
            if (_len <= 0) {
                _direction = 'left';
            } else {
                $(".center_ul").animate({
                    "marginLeft": "+=" + _size + "px"
                });
                _len -= _size;
            }
        }
        //console.log(_len);
        if(_len==0){
            $(".center_li").find("li").eq(0).addClass("red").siblings("li").removeClass("red");
//            alert(1)
        }else if(_len==650){
            $(".center_li").find("li").eq(1).addClass("red").siblings("li").removeClass("red");
//            alert(2)
        }else if(_len==1300){
            $(".center_li").find("li").eq(2).addClass("red").siblings("li").removeClass("red");
//            alert(3)
        }else if(_len==1950){
            $(".center_li").find("li").eq(3).addClass("red").siblings("li").removeClass("red");
//            alert(3)
        }
    }
    console.log();
    var _go = setInterval(mar, _speed);
    $(".cont-left").click(function () {
        _direction = 'right';
        mar();
    });
    $(".cont-right").click(function () {
        _direction = 'left';
        mar();
    });
    $(".flow li").mouseover(function () {
        clearInterval(_go);
    }).mouseout(function () {
        _go = setInterval(mar, _speed);
    });

});
