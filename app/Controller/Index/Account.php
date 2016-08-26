<?php

/**
 * 登陆，注册相关
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/4/08
 * Time: 下午2:32
 */
class Controller_Index_Account extends Controller_Index_Base
{
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        parent::actionBefore();
        $this->_frame = 'pcbasic.phtml';
        if ($this->aUser) {
            // $this->redirect('/index/ucenter/index');
        }
    }

    public function baseinfoAction ()
    {
        $this->_frame = 'pcmenu.phtml';
        if (!$this->iCurrUserID) {
            return $this->redirect('/index/account/cdlogin');
        }

        if ($this->isPost()) {
            $aParam = $this->_checkBaseinfo();
            if (empty($aParam)) {
                return null;
            }
            $aCustomer = Model_Customer::getRow(['where' => [
                'sIdentityCard' => $aParam['sIdentityCard'],
                'iStatus >' => Model_Customer::STATUS_INVALID
            ]]);
            if ($aCustomer && $aCustomer['iUserID'] != $this->iCurrUserID) {
                return $this->showMsg('身份证已被注册', false);
            }

            Model_Customer::updData($aParam);
            return $this->showMsg('保存成功', true);
        } else {
            $aEmployee = Model_CustomerNew::getDetail($this->iCurrUserID);
            $this->assign('aEmployee', $aEmployee);
        }
    }

    public function logoutAction ()
    {
        Util_Cookie::delete(Yaf_G::getConf('indexuserkey', 'cookie'));
        $this->redirect('/index/account/cdlogin');
    }

    /**
     * 首页忘记密码
     */

    public function forgetpwdAction ()
    {
        if ($this->isPost()) {
            $aParam = $this->_checkResetPassword();
            if (empty($aParam)) {
                return null;
            }
            Model_CustomerNew::updData($aParam);
            
            return $this->showMsg('修改密码成功', true);
        }

        $this->assign('iCodeType', Util_Verify::TYPE_SYS_LOGIN);
    }

    /**
     * 个人 重置密码
     * @return [type] [description]
     */
    public function resetpwdAction ()
    {
        $this->_frame = 'pcmenu.phtml';
        
        if ($this->isPost()) {
            $aParam = $this->_checkResetPassword();
            if (empty($aParam)) {
                return null;
            }
            Model_CustomerNew::updData($aParam);
            
            return $this->showMsg('修改密码成功', true);
        }

        $this->assign('iCodeType', Util_Verify::TYPE_SYS_LOGIN);
    }

    //注册
    public function registerAction()
    {
        if ($this->isPost()) {
            $aParam = $this->_checkRegData();
            if (empty($aParam)) {
                return null;
            }
            //入库操作
            $aParam['sUserName'] = Model_CustomerNew::initUserName(Model_CustomerNew::TYPE_USER);
            $aParam['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $param['sPassword']);
            $aParam['iStatus'] = 1;

            $aUser = Model_User::getRow(['sUserName' => 'registers']);
            $aParam['iCreateUserID'] = $aUser['iUserID'];
            $aParam['iUserID'] = Model_CustomerNew::addData($aParam);
            if ($aParam['iUserID']) {
                $aCUser = Model_User::getDetail($aUser['iCreateUserID']);
                $aParam['iChannelID'] = $aCUser['iChannel'];
                Util_Cookie::set(Yaf_G::getConf('indexuserkey', 'cookie'), $aParam);
                return $this->showMsg('注册成功！', true);
            } else {
                return $this->showMsg('注册失败', false);
            }                    
        }
        
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType', Util_Verify::TYPE_REGISTER_IMAGE);
        $this->assign('iCodeType2', Util_Verify::TYPE_SYS_LOGIN);
    }

    //注册第二步
    public function registerStepTwoAction()
    {
        $iType = 10;
        $aParam = $this->_getParams();
        $p_sKey = Yaf_G::getConf('cryptkey', 'register');
        $sPhoneNum = Util_Crypt::decode ($aParam['verify'], $p_sKey);//手机解密
        if (!Util_Validate::isMobile($sPhoneNum) || !Model_User::getUserByMobile($sPhoneNum,Model_User::TYPE_USER)) {
            return $this->showMsg('参数有误', false);
        }
        if ($this->isPost()) {
            //验证手机验证码是否正确
            if (!Util_Verify::checkSMSCode($iType,$aParam['iPhoneCode'])) {
                return $this->showMsg('参数有误', false);
            }
            //更改用户的status状态
            $aUser = Model_User::getUserByMobile($sPhoneNum,Model_User::TYPE_USER);
            if (!empty($aUser)) {
                $aUser['iStatus'] = 1;
                $aResult = Model_User::updData($aUser);
                if ($aResult) {
                    return $this->showMsg('注册成功！', true);
                }
            }
            return $this->showMsg('服务器繁忙，请稍后再试!', false);
        }
        //发送手机验证码
        //$aRet['status'] = 1;
        $aRet = Util_Verify::makeSMSCode($sPhoneNum, $iType);
        if (!empty($aRet['status'])) {
            $this->assign('sPhoneNum',$sPhoneNum);
            $this->assign('verify',$aParam['verify']);
            $this->assign('iType',$iType);
        } else {
            return $this->showMsg($aRet['data'], false);
        }
    }

    //用户名登陆
    public function publicLoginAction()
    {
        if ($this->isPost()) {
            $aParam = $this->_checkUsernameLoginData();
            if (empty($aParam)) {
                return null;
            }

            $aCUser = Model_User::getDetail($aParam['iCreateUserID']);
            $aParam['iChannelID'] = $aCUser['iChannel'];
            //加cookie操作
            Util_Cookie::set(Yaf_G::getConf('indexuserkey', 'cookie'),$aParam);
            return $this->showMsg('登陆成功!', true);
        }
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType',Util_Verify::TYPE_LOGIN_IMAGE);
    }

    /*
     * 证件登陆(后台录入的用户)
     * 后台录入时，如果手机号有重复，直接在原有字段上修改身份证号码
     */
    public function identityCardLoginAction()
    {
        if ($this->isPost()) {
            $aParam = $this->_checkIdentityCardLoginData();
            if (empty($aParam)) {
                return null;
            }

            $aCUser = Model_User::getDetail($aParam['iCreateUserID']);
            $aParam['iChannelID'] = $aCUser['iChannel'];
            //加cookie操作
            Util_Cookie::set(Yaf_G::getConf('indexuserkey', 'cookie'), $aParam);
            return $this->showMsg('登陆成功!', true);
        }

        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType',Util_Verify::TYPE_SYS_LOGIN);
    }

    /*
    * 体检卡登陆(后台录入的用户)
    * 后台录入时，如果手机号有重复，直接在原有字段上修改体检卡号码
    */
    public function medicalCardLoginAction()
    {
        if ($this->isPost()) {
            list($aParam, $aCard) = $this->_checksMedicalCardLoginData();
            if (empty($aParam)) {
                return null;
            }
            if ($aCard['iUserID']) { //用户id不为空 则卡已绑定
                Model_CustomerNew::setCookie($aCard['iUserID']);
                $url = '/index/record/list/';
            } else {
                $iOrderID = $aCard['iOrderID'];
                $iCardID  = $aCard['iAutoID'];
                if (!$iOrderID) {
                    return $this->showMsg('订单不存在!', false);
                }
                $url = '/order/baseinfo/type/2/id/' . $iCardID . '/pid/' . $iCardID;
            }
            
            return $this->showMsg('登陆成功!', true, $url);
        }

        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType', Util_Verify::TYPE_CARD_LOGIN_IMAGE);
    }

    /**
     * 接收param方法
     * return array
     */
    private function _getParams()
    {
        $aParam = [
            //注册第一步传的参数
            'sRealName' => trim($this->getParam('sRealName')),
            'iSex' => intval($this->getParam('iSex')),
            'sMobile' => intval($this->getParam('sMobile')),
            'sEmail' => trim($this->getParam('sEmail')),
            'sPassword' => trim($this->getParam('sPassword')),
            'sPassword2' => trim($this->getParam('sPassword2')),
            'sCode' => trim($this->getParam('sCode')),
            'iAgree' => intval($this->getParam('iAgree')),
            //注册第二步传的参数（加密的手机号码）
            'verify' => trim($this->getParam('verify')),
            //注册第二步post传的参数（手机验证码）
            'iPhoneCode' => intval($this->getParam('iPhoneCode')),
            //用户名登陆
            'sUserName' => trim($this->getParam('sUserName')),//还有sCode和sPassword
            //证件登陆
            'sIdentityCard' => trim($this->getParam('sIdentityCard')),//还有iPhoneCode和sMobile
            //体检卡登陆
            'sMedicalCard' => trim($this->getParam('sMedicalCard')),//还有sCode
        ];

        return $aParam;
    }

    /**
     * 注册请求数据检测
     * @param array $param
     * @return bool
     */
    private function _checkRegData($param = array())
    {
        $param = !empty($param) ? $param : $this->_getParams();
        if (empty($param['iAgree'])) {
            return $this->showMsg('请先同意条款',false);
        }
        if (empty($param['sRealName'])) {
            return $this->showMsg('姓名不能为空！', false);
        }
        if (empty($param['iSex'])) {
            return $this->showMsg('请选择性别！', false);
        }
        if (!empty($param['sIdentityCard']) && !Util_Validate::isIdcard($param['sIdentityCard'])) {
            return $this->showMsg('身份证不符合规范！', false);
        }
        if (!Util_Validate::isMobile($param['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (!empty($param['sEmail']) && !Util_Validate::isEmail($param['sEmail'])) {
            return $this->showMsg('邮箱不符合规范！', false);
        }
        if (!Util_Validate::isLength($param['sPassword'], 6, 20)) {
            return $this->showMsg('密码长度为6到20个字！', false);
        }
        if ($param['sPassword2'] != $param['sPassword']) {
            return $this->showMsg('两次密码不一致！', false);
        }

        //验证验证码
        if (!Util_Verify::checkImageCode(Util_Verify::TYPE_REGISTER_IMAGE, $param['sCode'])) {
            return $this->showMsg('验证码不正确！', false);
        }
        //验证手机验证码是否正确
        if (!Util_Verify::checkSMSCode(Util_Verify::TYPE_SYS_LOGIN, $param['iPhoneCode'])) {
            return $this->showMsg('手机验证码不正确！', false);
        }
        //验证身份证是否被注册
        if (Model_CustomerNew::getUserByIdentityCard($param['sIdentityCard'])) {
            return $this->showMsg('该身份证被注册！', false);
        }

        return $param;
    }

    /**
     * 用户名登陆请求数据检测
     * @param array $param
     * @return bool
     */
    private function _checkUsernameLoginData($param = array())
    {
        $param = !empty($param) ? $param : $this->_getParams();

        if(empty($param['sUserName'])) {
            return $this->showMsg('请输入用户名!', false);
        }

        if (empty($param['sPassword'])) {
            return $this->showMsg('请输入密码!', false);
        }

        $aUser = Model_CustomerNew::getUserByUserName($param['sUserName']);
        if (empty($aUser)) {
            return $this->showMsg('用户名不存在!', false);
        }
        if ($aUser['sPassword'] != md5(Yaf_G::getConf('cryptkey', 'cookie') . $param['sPassword'])) {
            return $this->showMsg('密码不正确!', false);
        }
        //验证验证码
        if (!Util_Verify::checkImageCode(Util_Verify::TYPE_LOGIN_IMAGE, $param['sCode'])) {
            return $this->showMsg('验证码不正确！', false);
        }
        return $aUser;
    }

    /**
     * 证件号登陆请求数据检测
     * @param array $param
     * @return bool
     */
    private function _checkIdentityCardLoginData($param = array())
    {        
        $param = !empty($param) ? $param : $this->_getParams();
        if (empty($param['sMobile'])) {
            return $this->showMsg('请输入手机号', false);
        }
        if (empty($param['sIdentityCard'])) {
            return $this->showMsg('请输入身份证', false);
        }
        //验证手机验证码是否正确
        if (!Util_Verify::checkSMSCode(Util_Verify::TYPE_SYS_LOGIN, $param['iPhoneCode'])) {
            return $this->showMsg('验证码错误', false);
        }

        $aUser = Model_CustomerNew::getUserByIdentityCard($param['sIdentityCard']);
        if ($aUser && trim($aUser['sMobile']) == trim($param['sMobile'])) {
            return $aUser;
        } else {
           return $this->showMsg('手机号和身份证不匹配不正确',false);
        }
        
    }

    /**
     * 体检卡登陆请求数据检测
     * @param array $param
     * @return bool
     */
    private function _checksMedicalCardLoginData($param = array())
    {
        $param = !empty($param) ? $param : $this->_getParams();
        if(empty($param['sMedicalCard'])) {
            return $this->showMsg('请输入体检卡号!', false);
        }
        //验证验证码
        if (!Util_Verify::checkImageCode(Util_Verify::TYPE_CARD_LOGIN_IMAGE, $param['sCode'])) {
            return $this->showMsg('验证码不正确！', false);
        }

        $aCard = Model_OrderCard::getCardByMedicalCard($param['sMedicalCard']);
        if (empty($aCard)) {
            return $this->showMsg('体检卡号不存在!', false);
        }
        if ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_RCARD && $aCard['sEndDate'] < date('Y-m-d', time())) {
            return $this->showMsg('该体检卡已过有效期，不能使用!', false);   
        }

        return [$param, $aCard];
    }

    /**
     * 修改密码请求数据检测
     * @param array $param
     * @return bool
     */
    private function _checkResetPassword($param = array())
    {
        $param = $this->getParams();
        if (empty($param['sPassword'])) {
            return $this->showMsg('请输入新密码!', false);
        } else {
            $param['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $param['sPassword']);
        }
        if (empty($param['sMobile'])) {
            return $this->showMsg('请输入手机号!', false);
        }
        
        if (empty($param['sIdentityCard'])) {
            return $this->showMsg('请输入身份证号!', false);
        }

        $aUser = Model_Customer::getRow(['where' => [
            'sMobile' => $param['sMobile'],
            'sIdentityCard' => $param['sIdentityCard'],
            'iStatus >' => Model_Customer::STATUS_INVALID
        ]]);

        if ($this->aUser['sRealName'] != $aUser['sRealName']) {
            return $this->showMsg('身份证和姓名不一致!', false);
        }

        //验证手机验证码是否正确
        if (!Util_Verify::checkSMSCode(Util_Verify::TYPE_SYS_LOGIN, $param['iPhoneCode'])) {
            return $this->showMsg('验证码不正确', false);
        }
        
        $param['iUserID'] = $aUser['iUserID'];
        return $param;
    }

    private function _checkBaseinfo ($param = array())
    {
        $param = $this->getParams();
        if (empty($param['iUserID'])) {
            return $this->showMsg('修改人不能空!', false);
        }
        if (empty($param['sMobile'])) {
            return $this->showMsg('请输入手机号!', false);
        }
        if (empty($param['sRealName'])) {
            return $this->showMsg('请输入姓名!', false);
        }
        if (empty($param['sIdentityCard'])) {
            return $this->showMsg('请输入身份证!', false);
        }
        if (empty($param['sBirthDate'])) {
            return $this->showMsg('请输入生日!', false);
        }
        if (empty($param['sMobile'])) {
            return $this->showMsg('请输入手机号!', false);
        }

        return $param;
    }

    public function sendcodeAction ()
    {
        $iType = $this->getParam('type');
        $sMobile = $this->getParam('mobile');
        $ret = Util_Verify::makeSMSCode($sMobile, $iType);

        $this->showMsg($ret, true);
    }

    /**
     * 新身份证登陆页面
     * @return [type] [description]
     */
    public function idloginAction ()
    {
        $this->_frame = 'none.phtml';

        if ($this->isPost()) {
            $aParam = $this->_checkIdentityCardLoginData();
            if (empty($aParam)) {
                return null;
            }

            $aCUser = Model_User::getDetail($aParam['iCreateUserID']);
            $aParam['iChannelID'] = $aCUser['iChannel'];
            
            //加cookie操作
            Util_Cookie::set(Yaf_G::getConf('indexuserkey', 'cookie'), $aParam);
            return $this->showMsg('登陆成功!', true);
        }

        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType',Util_Verify::TYPE_SYS_LOGIN);
    }

    //新用户名登陆
    public function pbLoginAction()
    {
        $this->_frame = 'none.phtml';

        if ($this->isPost()) {
            $aParam = $this->_checkUsernameLoginData();
            if (empty($aParam)) {
                return null;
            }

            $aCUser = Model_User::getDetail($aParam['iCreateUserID']);
            $aParam['iChannelID'] = $aCUser['iChannel'];
            
            //加cookie操作
            Util_Cookie::set(Yaf_G::getConf('indexuserkey', 'cookie'), $aParam);
            return $this->showMsg('登陆成功!', true);
        }
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType',Util_Verify::TYPE_LOGIN_IMAGE);
    }

    /**
     * 设置卡密码
     * @return 
     */
    public function setcardpwdAction ()
    {
        $id = $this->getParam('id');
        $aCard = Model_OrderCard::getDetail($id);

        if ($this->isPost()) {
            $aParam = $this->getParams();
            if (!$aParam['sCardCode']) {
                return $this->showMsg('卡号不能为空', false);  
            }
            if (!$aParam['sCardPwd'] || !$aParam['sConfirmCardPwd']) {
                return $this->showMsg('密码不能为空', false);     
            }
            if ($aParam['sCardPwd'] != $aParam['sConfirmCardPwd']) {
                return $this->showMsg('密码不一致', false);        
            }

            $card = Model_OrderCard::getCardByCode($aParam['sCardCode']);
            if (!$card) {
                return $this->showMsg('卡号不存在', false);  
            }

            $card['sCardPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aParam['sCardPwd']);
            Model_OrderCard::updData($card);

            Model_CustomerNew::setCookie($card['iUserID']);
            return $this->showMsg('设置成功', true, '/index/record/list/');
        }
        $this->assign('aCard', $aCard);
    }

    /**
     * 不设体检卡密码
     * @return [type] [description]
     */
    public function nosetcardpwdAction ()
    {
        $id = $this->getParam('id');
        $aCard = Model_OrderCard::getDetail($id);

        if ($aCard) {
            Model_CustomerNew::setCookie($aCard['iUserID']);
            return $this->redirect('/index/record/list/');
        } else {
            return  $this->redirect('/index/account/cdlogin/');
        }
    }

    /**
     * 确认卡密码
     * @return 
     */
    public function cardpwdAction ()
    {
        $id = $this->getParam('id');
        $aCard = Model_OrderCard::getDetail($id);

        if ($this->isPost()) {
            $aParam = $this->getParams();
            if (!$aParam['sCardCode']) {
                return $this->showMsg('卡号不能为空', false);  
            }
            if (!$aParam['sCardPwd']) {
                return $this->showMsg('密码不能为空', false);     
            }

            $card = Model_OrderCard::getCardByCode($aParam['sCardCode']);
            if (!$card) {
                return $this->showMsg('卡号不存在', false);  
            }

            $sCardPwd = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aParam['sCardPwd']);
            if ($sCardPwd != $card['sCardPassword']) {
                return $this->showMsg('密码错误', false);  
            }

            Model_CustomerNew::setCookie($card['iUserID']);
            return $this->showMsg('登陆成功', true, '/index/record/list/');
        }
        
        $this->assign('aCard', $aCard);
    }

    /**
     * 忘记卡密码
     * @return 
     */
    public function forgetcardpwdAction ()
    {
        $id = $this->getParam('id');
        $aCard = Model_OrderCard::getDetail($id);
        $aCustomer = Model_CustomerNew::getDetail($aCard['iUserID']);
        
        if ($this->isPost()) {
            $aParam = $this->getParams();
            if (!$aParam['sCardCode']) {
                return $this->showMsg('卡号不能为空', false);  
            }
            if (!$aParam['sCardPwd'] || !$aParam['sConfirmCardPwd']) {
                return $this->showMsg('密码不能为空', false);     
            }
            if ($aParam['sCardPwd'] != $aParam['sConfirmCardPwd']) {
                return $this->showMsg('密码不一致', false);        
            }
            if (!Util_Verify::checkSMSCode(Util_Verify::TYPE_SYS_REGISTER, $aParam['sVerifyCode'])) {
                return $this->showMsg('验证码不正确', false);
            }

            $card = Model_OrderCard::getCardByCode($aParam['sCardCode']);
            if (!$card) {
                return $this->showMsg('卡号不存在', false);  
            }
            
            $card['sCardPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aParam['sCardPwd']);
            Model_OrderCard::updData($card);

            return $this->showMsg('修改成功', true, '/index/account/cardpwd/id/' . $card['iAutoID']);
        }
        $this->assign('aCard', $aCard);
        $this->assign('aCustomer', $aCustomer);
        $this->assign('iCodeType', Util_Verify::TYPE_SYS_REGISTER);
    }

    //新卡号登陆
    public function cdloginAction ()
    {
        $this->_frame = 'none.phtml';

        if ($this->isPost()) {
            list($aParam, $aCard) = $this->_checksMedicalCardLoginData();
            if (empty($aParam)) {
                return null;
            }
            if ($aCard['iUserID']) { //用户id不为空 则卡已绑定
                // Model_CustomerNew::setCookie($aCard['iUserID']);
                // $url = '/index/record/list/';
                if (!$aCard['sCardPassword']) {
                    $url = '/index/account/setcardpwd/id/'.$aCard['iAutoID'];   
                } else {
                    $url = '/index/account/cardpwd/id/'.$aCard['iAutoID'];
                }
            } else {
                $iOrderID = $aCard['iOrderID'];
                $iCardID  = $aCard['iAutoID'];
                if (!$iOrderID) {
                    return $this->showMsg('订单不存在!', false);
                }
                $url = '/order/baseinfo/type/2/id/' . $iCardID . '/pid/' . $iCardID;
            }
            
            return $this->showMsg('登陆成功!', true, $url);
        }

        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType', Util_Verify::TYPE_CARD_LOGIN_IMAGE);
    }
}