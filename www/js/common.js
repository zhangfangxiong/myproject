// window.alert = ShowMessage;

$(function () {
    SetLogo();
    SetFooter();

    //暂时去掉
    //    $(window).resize(function () {
    //        SetFooter();
    //    });

})

function SetLogo() {
    var centerCode = $('#hfBizCenterCode').val();
    if (centerCode != undefined && centerCode != '') {
        if (centerCode == 'CIIC-XC') {
            $('.TopPart').addClass('PortalXC');
        }
        else if (centerCode == 'CIIC-PX') {
            $('.TopPart').addClass('PortalPX');
        }
        else if (centerCode == 'CIIC-ZT-PJ') {
            $('.TopPart').addClass('PortalZTPJ');
        }
    }
}

function SetFooter() {
    var winHeight = $(window).height();
    var docHeight = $(document.body).height();

    if (docHeight <= winHeight) {
        var actualHeight = winHeight - 159;
        //$('.ContentPart').height(actualHeight);
        $('.ContentPart').css('min-height', actualHeight + 'px');
    } 
}

function GetRadWindow() {
    var oWindow = null;
    if (window.radWindow) oWindow = window.radWindow;
    else if (window.frameElement.radWindow) oWindow = window.frameElement.radWindow;
    return oWindow;
}

function CloseRadWindow() {
    var oWnd = GetRadWindow();
    oWnd.close();
}

function PopupRadWinSmall(url, title, argument, callback) {
    var oWin = radopen(url, 'WinSmall');

    oWin.set_title(title);
    if (callback != null) {
        if (oWin.get_events()._list.close.length >= 2) {
            oWin.remove_close(callback);
        }
        oWin.add_close(callback);
    }
    oWin.argument = argument;

}

function PopupRadWinLarge(url, title, argument, callback) {
    var oWin = radopen(url, 'WinLarge');

    oWin.set_title(title);
    if (callback != null) {
        if (oWin.get_events()._list.close.length >= 2) {
            oWin.remove_close(callback);
        }
        oWin.add_close(callback);
    }
    oWin.argument = argument;

}

function ShowMessage(content, callback) {
    if (callback) {
        radalert(content, 450, 100, '信息提示', callback);
    }
    else {
        radalert(content, 450, 100, '信息提示', null);
    }
}

 

function ShowConfirm(content, callBack) {
    var oMgr = $find('ctl00_ctl00_HrShopWindowAlertMgr');
    oMgr.radconfirm(content, callBack,450,100);
}

function ClosePopupRadWin(arg) {
    var oWnd = GetRadWindow();
    oWnd.argument = arg;
    oWnd.close()
}

function SecondCheck(message) {
    return confirm(message);
}

//***Open box
function ToggleBox(obj, intW, intH) {
    var originW;
    var originH;
    if ($(obj).data("originW") == null)
        $(obj).data("originW", $(obj).parent('.Block').width());

    originW = $(obj).data("originW");

    if ($(obj).data("originH") == null)
        $(obj).data("originH", $(obj).parent('.Block').height());

    originH = $(obj).data("originH");

    var toW;
    var toH;

    if ($(obj).parent('.Block').width() == originW && $(obj).parent('.Block').height() == originH) {
        toW = intW;
        toH = intH;
    } else {
        toW = originW;
        toH = originH;
    }
    
    if (!$(obj).parent('.Block').parent('.BlockBox').hasClass('Closed')) {

        $(obj).data("status", 'Closed');
        $(obj).parent('.Block').parent('.BlockBox').addClass('Closed');
        $(obj).children('A').html('展开<b></b>');
    } else {

        $(obj).data("status", 'Opened');
        $(obj).parent('.Block').parent('.BlockBox').removeClass('Closed');
        $(obj).children('A').html('缩进<b></b>');
    }

    $(obj).parent('.Block').parent('.BlockBox').animate({ width: toW, height: toH });
    $(obj).parent('.Block').animate({ width: toW, height: toH });

}

//***Compact box
function ToggleBoxCompact(obj, intW, intH) {
    var originW;
    var originH;
    if ($(obj).data("originW") == null)
        $(obj).data("originW", $(obj).parent('.Block').width());

    originW = $(obj).data("originW");

    if ($(obj).data("originH") == null)
        $(obj).data("originH", $(obj).parent('.Block').height());

    originH = $(obj).data("originH");

    var toW;
    var toH;

    if ($(obj).parent('.Block').width() == originW && $(obj).parent('.Block').height() == originH) {
        toW = intW;
        toH = intH;
    } else {
        toW = originW;
        toH = originH;
    }
    
    if (!$(obj).parent('.Block').parent('.BlockBox').hasClass('Compacted')) {

        $(obj).data("status", 'Compacted');
        $(obj).parent('.Block').parent('.BlockBox').addClass('Compacted');
        $(obj).parent('.Block').toggleClass('ColorBg');
    } else {
        $(obj).data("status", 'Uncompacted');
        $(obj).parent('.Block').parent('.BlockBox').removeClass('Compacted');
        $(obj).parent('.Block').toggleClass('ColorBg');
    }

    $(obj).parent('.Block').parent('.BlockBox').animate({ width: toW, height: toH });
    $(obj).parent('.Block').animate({ width: toW, height: toH });
}

//** Maximize Box
function ToggleBoxMax(obj) {

    var maxH;
    var originH;

    if ($(obj).data("status") != null && $(obj).data("status") == 'Maximized') {
        originH = $(obj).data("originH");
        $(obj).parent('.Block').parent('.BlockBox').animate({ height: originH });
        $(obj).parent('.Block').animate({ height: originH });
        $(obj).data("status", 'Restored');
        $(obj).parent('.Block').parent('.BlockBox').removeClass('Maximized');
    } else {
        $(obj).data("originH", $(obj).parent('.Block').parent('.BlockBox').height());
        maxH = $(obj).parent('.Block').children('.compactDetail').children('.BlockList').height();
        maxH = maxH + 52;
        $(obj).parent('.Block').parent('.BlockBox').animate({ height: maxH });
        $(obj).parent('.Block').animate({ height: maxH });
        $(obj).data("status", 'Maximized');
        $(obj).parent('.Block').parent('.BlockBox').addClass('Maximized');
    }    
}


function OpenFullWindow(strUrl, strName) {
    var Wins = window.open(strUrl, strName, "width=" + window.screen.width + ",height=" + (window.screen.height - 55) + ",menubar=no,toolbar=no,scrollbars=yes,status=yes,titlebar=no,resizable=yes,location=no");
    Wins.moveTo(-3, 0);
    return Wins;
}

//弹出自定义大小窗口，窗口居中
function OpenDefintionWindow(strUrl, strName, width, height) {
    var l = (screen.width - width) / 2;
    var t = (screen.height - height) / 2;
    var s = 'width=' + width + ', height=' + height + ', top=' + t + ', left=' + l;
    s += ', toolbar=no, scrollbars=yes, menubar=no, location=no, resizable=yes';
    open(strUrl, strName, s);
}

function trim(stringToTrim) {
    return stringToTrim.replace(/^\s+|\s+$/g, '');
}

function PopupTagListWin(title,currentID,hidDSURL,hidCurrent,btnSelected) {
    var url = document.getElementById(hidDSURL).value;
           
    var arg = new Object();
    arg.hidDSURL = hidDSURL;
    arg.hidCurrent = hidCurrent;
    arg.btnSelected = btnSelected;
    PopupRadWinLarge(url, title, arg,PopupTagListWin_OnClientClose);
}

function PopupTagListWinSmall(title, currentID, hidDSURL, hidCurrent, btnSelected) {
    var url = document.getElementById(hidDSURL).value;

    var arg = new Object();
    arg.hidDSURL = hidDSURL;
    arg.hidCurrent = hidCurrent;
    arg.btnSelected = btnSelected;
    PopupRadWinSmall(url, title, arg, PopupTagListWin_OnClientClose);
}

function PopupTagListWin_OnClientClose(oWnd)
{
    var arg = oWnd.argument ;
    if(arg)
    {
        if(arg.TagList != null)
        {   
            hidDSURL = arg.hidDSURL;
            hidCurrent = arg.hidCurrent;
            btnSelected = arg.btnSelected;
            document.getElementById(hidCurrent).value = arg.TagList;
            document.getElementById(btnSelected).click();
        }
    }
    oWnd.remove_close(PopupTagListWin_OnClientClose);
}



/*2013-2-21*/

//** TopMenuSlider
$(document).ready(function () {

    $("#TopSlideMenuTrigger").mouseenter(function (event) {


        $("#TopSlideMenu").slideDown(400);



    });

});

$(document).ready(function () {

    $("#TopSlideMenu").mouseleave(function (event) {


        $("#TopSlideMenu").slideUp(500);



    });

});


//** HelpTip
$(document).ready(function () {

    $(".HelpTip").mouseenter(function (event) {


        $(this).children(".TipDetail").fadeIn(400);



    });

});

$(document).ready(function () {

    $(".HelpTip").mouseleave(function (event) {


        $(this).children(".TipDetail").fadeOut(400);



    });


    var iconRollStatus = false;
    $autoFun = setTimeout(autoSlide, 4000);

});

function autoSlide() {

    IconSlider_Next();
    $autoFun = setTimeout(autoSlide, 4000);
}

function IconSlider_Next() {
    /* if($(".FloatingNotice").data("status")=="Rolled"){
    //$(".FloatingNotice .block .face .brief").slideUp(400);
    //$(".FloatingNotice .block .face .icon").show(400);		
    //$(".FloatingNotice").data("status", '');	
		
    IconDown($(".FloatingNotice .block .face"))	;	
	 	
    }else{
    //$(".FloatingNotice .block .face .icon").slideUp(400);
    //$(".FloatingNotice .block .face .brief").slideDown(400);	 	
    //$(".FloatingNotice").data("status", 'Rolled');
    IconUp($(".FloatingNotice .block .face"))	;			 
    }*/
    if ($(".FloatingNotice").data("curIdx") == null) {
        $(".FloatingNotice").data("curIdx", '0');
    }
    var curIdx = $(".FloatingNotice").data("curIdx");
    var curObj = $(".FloatingNotice .block:eq(" + curIdx + ")").children(".face");
    IconRoll(curObj);
    var nextIdx;
    if (curIdx + 1 >= $(".FloatingNotice .block").length) {
        nextIdx = 0;
    } else {
        nextIdx = curIdx + 1;
    }
    $(".FloatingNotice").data("curIdx", nextIdx);

}


function IconRoll(obj) {
    if ($(obj).data("status") == "Rolled") {
        IconDown(obj);
    } else {
        IconUp(obj);
    }
}

function IconUp(obj) {

    $(obj).children(".icon").slideUp(400);
    $(obj).children(".brief").slideDown(400);
    $(obj).data("status", 'Rolled');
}

function IconDown(obj) {

    $(obj).children(".icon").show(400);
    $(obj).children(".brief").slideUp(400);
    $(obj).data("status", '');
}