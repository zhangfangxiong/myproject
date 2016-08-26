/**
 * Created by len on 2015/2/5.
 */

window.ShareUrl = {
    sina: "http://service.weibo.com/share/share.php?url=@url&title=@desc",
    kaixin: "http://www.kaixin001.com/rest/records.php?url=@url&style=11&content=@desc&stime=&sig= ",
    wangyi: "http://t.163.com/article/user/checkLogin.do?info=@desc @url&source=&togImg=true",
    renren: "http://widget.renren.com/dialog/share?resourceUrl=@url&srcUrl=@url&title=@desc&description=@desc",
    qq: "http://share.v.t.qq.com/index.php?c=share&a=index&url=@desc @url",
    qzone: "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=@url&title=@desc&desc=&summary=&site=",
    baidu: "http://cang.baidu.com/do/add?iu=@url&it=@desc",
    pengyou: "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?to=pengyou&url=@url&title=@desc&desc=@desc&summary=",
    baidu_hi: "http://hi.baidu.com/pub/show/share?url=@url&title=@desc&content=@desc",
    douban: "http://shuo.douban.com/%21service/share?href=@url&name=@desc",
    sohu: "http://t.sohu.com/third/post.jsp?url=@url&title=@desc",
    baidu_new: "http://www.baidu.com/home/page/show/url?url=@url&name=@desc",
    qq_bm: "http://shuqian.qq.com/post?uri=@url&title=@desc",
    hexun: "http://t.hexun.com/channel/shareweb.aspx?&url=@url&title=@desc",
    taobao: "http://share.jianghu.taobao.com/share/addShare.htm?url=@url&title=@desc",
    baidu_tieba: "http://tieba.baidu.com/f/commit/share/openShareApi?url=@url&title=@desc&desc=&comment=",
    c_qq: "http://connect.qq.com/widget/shareqq/index.html??&url=@url&title=@desc&showcount=0",
    esfShareTitle: "你的房产升值了吗？我刚在房价点评网上查看了@unitName的房价报告，还挺准的。——房价点评，独立第三方房产价格指导平台。买房、卖房、租房，先查价，房价点评，一房给你一个指导价。",
    xfShareTitle: "我正在了解@unitName这个楼盘，该楼盘实际售价为@salePrice元/㎡，房价点评分析师认为@analystOpinion。给予的购房意见是@levelCode，你们觉得怎么样？"
};

$(function() {
    $("#Share_li").hover(function () {
        $("#share_unit").show();
    }, function () {
        $("#share_unit").hide();
    });
});
function openShare(url, currentTarget, desc, id) {
    var content = "";
    try {
        content = $(currentTarget).parents(".comment_share_icon ").prev().prev().text();
    } catch(e) {

    }
    if (desc) {
        content = desc;
    }



    $.post(
        "/ajax/news/updShare",
        {iNewsID:id},
        function(result){
            console.log(result);
        }
    );

    window.Share(url, content);
}
//分享
window.Share = function (url, title, desc,salePrice,analystOpinion,levelCode) {
    if (title == null) {
        title = document.title;
    }
    if (desc == null) {
        desc = title;
    } else {
        desc = desc.replace(/@unitName/gi, title);
        desc = desc.replace(/@salePrice/gi, salePrice);
        desc = desc.replace(/@analystOpinion/gi, analystOpinion);
        desc = desc.replace(/@levelCode/gi, levelCode);
    }
    desc = encodeURIComponent(desc);
    var loc = encodeURIComponent(window.location);
    url = url.replace(/@desc/gi, desc).replace(/@url/gi, loc).replace(/@content/gi, "");
    window.open(url);
    $("#share_unit").hide();
};


