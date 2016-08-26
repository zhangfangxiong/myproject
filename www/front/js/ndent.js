$(function () {
    $(".datetimepicker").datetimepicker({
        lang:"ch",           //语言选择中文
        format:"Y-m-d H:i",      //格式化日期
        i18n:{
            // 以中文显示月份
            de:{
                months:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月",],
                // 以中文显示每周（必须按此顺序，否则日期出错）
                dayOfWeek:["日","一","二","三","四","五","六"]
            }
        }
        // 显示成年月日，时间--


    });

    $('.search').click(function () {
        var star = $('input[name="iStarTime"]').val();
        var over = $('input[name="iOverTime"]').val();
        var iss = $('.iSource').val();
        var iss_a = $('.iSource_a').val();
//            四个值
        location.href = "/acenter/recharge/iss/"+iss+"/star/" + star + "/over/" + over + ".html";
    })

})