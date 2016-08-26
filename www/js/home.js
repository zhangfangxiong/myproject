;(function() {

    var ele;

    ele = {
        winH : $(window).height(),
        flag : false,
        aBtn : $('.loginContent_title li a'),
        switchLi : $('.switchMenu>ul>li'),
        switchPanel : $('.switchPanel'),
        login : {
            submitBtn : $('#u-submitBtn'),
            submitBtn2 : $('#u-submitBtn2'),
            _form1 : $('#_form1'),
            _form2 : $('#_form2'),
            userName : $('#username'),
            userName2 : $('#username2'),
            userPassword : $('#password'),
            userPassword2 : $('#password2'),
            userYZM : $('#login_YZM'),
            userYZM2 : $('#login_YZM2')
        }
    }
    
   

    /*login切换*/
    ele.aBtn.on('click',function() {

        var oParent = $(this).parent('li');

        if(!oParent.hasClass('current')) {
            var oIndex = oParent.index();
            oParent.addClass('current').siblings().removeClass('current');
            $('.m-loginPanel .m-loginForm').eq(oIndex).show().siblings().hide();

            if($('.login_welcome').length === 2) {
              $('.login_welcome').eq(oParent.index()).css('z-index','9').siblings('.login_welcome').css('z-index','8')
            }else if ($('#adv_login_tab_content_true').length && oParent.index() === 1) {
              $('#adv_login_tab_content_true').hide();
            }else if ($('#med_login_tab_content_true').length && oParent.index() === 0) {
              $('#med_login_tab_content_true').hide();
            }else if ($('#adv_login_tab_content_true').length && oParent.index() === 0) {
              $('#adv_login_tab_content_true').show();
            }else if ($('#med_login_tab_content_true').length && oParent.index() === 1) {
              $('#med_login_tab_content_true').show();
            }
        }

    })

    /*分类列表、案例列表切换*/
    ele.switchLi.eq(0).addClass('first');
    ele.switchPanel.find('ul').eq(0).show();

    ele.switchLi.on('mouseover',function() {
        var self = $(this),
            oIndex = self.index(),
            switchPanel = self.parents('.switchMenu').next('.switchPanel');

        if(!self.hasClass('first')) {
            self.addClass('first').siblings().removeClass('first');
            switchPanel.find('ul').eq(oIndex).show().siblings().hide();
        }
    })

}())
