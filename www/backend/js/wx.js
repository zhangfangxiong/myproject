var latitude; // 纬度，浮点数，范围为90 ~ -90
var longitude; // 经度，浮点数，范围为180 ~ -180。
var speed; // 速度，以米/每秒计
var accuracy; // 位置精度

var EARTH_RADIUS = 6378137.0;    //单位M
var PI = Math.PI;

var gtimers = function (h, m, s, callback) {
    this.h = h;
    this.m = m;
    this.s = s;
    this.callback = callback;
}


gtimers.prototype.desTime = function () {

    var _this = this, desInter;

    if (_this.s == 0 && _this.m == 0 && _this.s == 0) {
        return false;
    }

    !!desInter && clearInterval(desInter);

    desInter = setInterval(function () {

        var _s = Number(_this.s), _m = Number(_this.m), _h = Number(_this.h);

        _s > 0 ? _s-- : ( _m > 0 ? (_s = 59 , _m--) : ( _h > 0 ? (_s = 59 , _m = 59 , _h--) : clearInterval(desInter), window.location.href = window.location.href));

        _this.s = _s > 9 ? _s = '' + _s : _s = '0' + _s;
        _this.m = _m > 9 ? _m = '' + _m : _m = '0' + _m;
        _this.h = _h > 9 ? _h = '' + _h : _h = '0' + _h;

        !!_this.callback && _this.callback(_h, _m, _s);

    }, 1000);
};

function share(iShareHeader) {
    var host = window.location.host;
    $.post(host + "/wx/guess/share", {
        iLoupanID: shareObj.iLoupanID,
        iShareType: shareObj.iShareType,
        iShareHeader: iShareHeader
    }, function (ret) {
    }, 'json');
}

function getRad(d) {
    return d * PI / 180.0;
}

/**
 * caculate the great circle distance
 * @param {Object} lat1
 * @param {Object} lng1
 * @param {Object} lat2
 * @param {Object} lng2
 */
function getGreatCircleDistance(lat1, lng1, lat2, lng2) {
    var radLat1 = getRad(lat1);
    var radLat2 = getRad(lat2);

    var a = radLat1 - radLat2;
    var b = getRad(lng1) - getRad(lng2);

    var s = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) + Math.cos(radLat1) * Math.cos(radLat2) * Math.pow(Math.sin(b / 2), 2)));
    s = s * EARTH_RADIUS;
    s = Math.round(s * 10000) / 10000.0;

    return s;
}

//获取当前页面需要显示相对页面的元素
function getNeedDistance() {
    var need_distance = $(".need_distance");
    //alert(latitude);
    if (need_distance.length > 0) {
        need_distance.each(function () {
            Loulat = parseFloat($(this).attr('data-lat'));
            Loulng = parseFloat($(this).attr('data-lng'));
            if (Loulat && Loulng) {
                distance = getGreatCircleDistance(Loulat, Loulng, latitude, longitude);
                distance = Math.ceil(distance / 1000);//转化成千米
                $(this).find('.curr_distance').html(distance);
            }
        })
    }
}

//全局设置
wx.config({
    debug: false,
    appId: shareObj.appId,
    timestamp: shareObj.timestamp,
    nonceStr: shareObj.nonceStr,
    signature: shareObj.signature,
    jsApiList: [
        'checkJsApi',
        'onMenuShareTimeline',
        'onMenuShareAppMessage',
        'hideMenuItems',
        'showMenuItems',
        'getLocation',
        'openLocation'
    ]
});

wx.ready(function () {
    //隐藏相关按钮
    wx.hideMenuItems({
        menuList: [
            'menuItem:readMode', // 阅读模式按钮
            'menuItem:share:qq', // 分享到QQ按钮
            'menuItem:copyUrl', // 复制链接按钮
            'menuItem:openWithSafari', //safari中打开按钮
            'menuItem:share:email' //邮件按钮
        ]
    });
    // 监听分享给朋友
    wx.onMenuShareAppMessage({
        title: shareObj.title,
        link: shareObj.link,
        imgUrl: shareObj.imgUrl,
        success: function (res) {
            share(2);
        }
    });
    // 监听分享给朋友圈
    wx.onMenuShareTimeline({
        title: shareObj.title,
        link: shareObj.link,
        imgUrl: shareObj.imgUrl,
        success: function (res) {
            share(1);
        }
    });

    wx.getLocation({
        type: 'gcj02', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
        success: function (res) {
            latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
            longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。
            speed = res.speed; // 速度，以米/每秒计
            accuracy = res.accuracy; // 位置精度
            //getNeedDistance();
        },
        cancel:function (res) {
            alert(res.errMsg);
        }
    });

    /**
     * 打开腾讯地图
    var need_distance = $(".need_distance");
    need_distance.on('click', function () {
        var Loulat = parseFloat($(this).data('lat'));
        var Loulng = parseFloat($(this).data('lng'));
        var LouName = $(this).data('name');
        var LouAddress = $(this).data('address');
        wx.openLocation({
            latitude: Loulat,
            longitude: Loulng,
            name: LouName,
            address: LouAddress,
            scale: 14,
            infoUrl: ''
        });
    });
     */
});