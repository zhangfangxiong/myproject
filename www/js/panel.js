
var popupStatus = 0;
var iframeSrc;
function loadPopup(o) {
    if (popupStatus == 0) {
        var windowWidth = document.documentElement.clientWidth;
        var windowHeight = document.documentElement.clientHeight;
        var popupHeight = $("#" + o).height();
        var popupWidth = $("#" + o).width();
        $("#backgroundPopup").css({
            "opacity": "0.5"
        });
        $("#backgroundPopup").fadeIn("slow");
        $("#" + o).fadeIn("slow");
        popupStatus = 1;
    }
}

function disablePopup() {
    if (popupStatus == 1) {
        $("#backgroundPopup").fadeOut("slow");
        $(".base_pop").fadeOut("slow");
        popupStatus = 0;
    }
}

function centerPopup(o) {
    var windowWidth = document.documentElement.clientWidth;
    var windowHeight = document.documentElement.clientHeight;
    var popupHeight = jQuery("#" + o).height();
    var popupWidth = jQuery("#" + o).width();
    $("#" + o).css({
        "position": "absolute",
        "top": windowHeight / 2 + document.documentElement.scrollTop + document.body.scrollTop - popupHeight / 2,
        "left": windowWidth / 2 - popupWidth / 2
    });
    $("#backgroundPopup").css({
        "height": windowHeight + document.documentElement.scrollTop
    });
}
function popUp(o) {
    centerPopup(o);
    loadPopup(o);
}

//蒙版右上角的关闭按钮
$(".pop_close").bind("click", function () {
    disablePopup();
})


function showTos() {
    disablePopup();
    popUp("tos");
    return false;
}

function showBuy() {
    popUp("buy");
    return false;
}

function closeTos() {
    disablePopup();
}

function OpenBuy() {
    if ($("input[id*='client']").val() == "1") {
        showBuy();
        return false;
    }
    else {
        return true;
    }
}

function OpenLogin() {
    if ($("input[id*='client']").val() == "1") {
        showTos();
        return false;
    }
    else {
        return true;
    }
}

function Login() {
    var url = "../Ajax/AccountHandler.ashx?time=" + Math.random();
    var parameter = {};
    parameter["Type"] = "login";
    parameter["ValidateCode"] = $("input[id$='txtValidateCode']").val();
    parameter["Pwd"] = $("input[id$='txtPassword']").val();
    parameter["UserName"] = $("input[id$='txtAccount']").val();
    $.ajax({
        type: "POST",
        url: url,
        data: parameter,
        timeout: 20000,
        success: function (data) {
            if (data == "验证码错误") {
                alert(data);
            }
            else if (data == "账号或密码错误") {
                alert(data);
            }
            else if (data == "登录成功") {
                location.reload();
            }
            fGetCode();
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert(textStatus);
            fGetCode();
        }
    });
}