<?php

/**
 * 用户管理(登录、注册、激活、忘记密码等)
 * Created by PhpStorm.
 * User: xiejinci
 * Date: 14/12/24
 * Time: 下午2:32
 */
class Controller_Index_User extends Controller_Index_Base
{
    
    /**
     * 检测是否存在
     */
    public function checkAction ()
    {
        $iType = (int) $this->getParam('type');
        $sField = $this->getParam('field');
        $sValue = $this->getParam('value');
        
        $aErr['sEmail'] = '该邮箱已经存在！';
        $aErr['sMobile'] = '该手机号码已经存在！';
        if (! isset($aErr[$sField])) {
            return $this->showMsg('操作错误!', false);
        }
        
        if ($sField == 'sEmail') {
            if (! Util_Validate::isEmail($sValue)) {
                return $this->showMsg('邮箱格式错误，请重新输入', false);
            }
        }
        if ($sField == 'sMobile') {
            if (! Util_Validate::isMobile($sValue)) {
                return $this->showMsg('手机格式错误，请重新输入', false);
            }
        }
        
        $iCnt = Model_User::getCnt(array(
            $sField => $sValue,
            'iType' => $iType,
            'iStatus !=' => 0
        ));
        
        if ($iCnt > 0) {
            return $this->showMsg($aErr[$sField], false);
        } else {
            return $this->showMsg('OK', true);
        }
    }

    /**
     * 用户注册
     */
    public function registerAction ()
    {
        if ($this->isPost()) {
            $aParam = $this->getParams();
            $iType = intval($aParam['iType']);
            
            $aErr = array();
            if (empty($aParam['sCode']) || ! Util_Verify::checkImageCode($iType, $aParam['sCode'])) {
                $aErr['sCode'] = '验证码错误';
            }
            if (empty($aParam['sEmail']) || ! Util_Validate::isEmail($aParam['sEmail'])) {
                $aErr['sEmail'] = '邮箱格式错误，请重新输入';
            }
            if (empty($aParam['sPassword']) || ! Util_Validate::isLength($aParam['sPassword'], 6, 20)) {
                $aErr['sPassword'] = '密码长度为6-20个字符';
            }
            if ($aParam['sPassword'] != $aParam['sRePassword']) {
                $aErr['sRePassword'] = '密码两次输入不一致';
            }
            if (empty($aParam['sMobile']) || ! Util_Validate::isMobile($aParam['sMobile'])) {
                $aErr['sMobile'] = '手机号码格式错误';
            }
            if ($iType == Model_User::TYPE_AD) {
                if (! Util_Validate::isCLength($aParam['sRealName'], 2, 20)) {
                    $aErr['sRealName'] = '联系人名称为2-5个汉字';
                }
            } else {
                if (! Util_Validate::isCLength($aParam['sRealName'], 1, 20)) {
                    $aErr['sRealName'] = '联系人名称为2-5个汉字';
                }
            }
            
            if (Model_User::getUserByEmail($aParam['sEmail'], $aParam['iType'])) {
                $aErr['sEmail'] = '该邮箱账号已经被注册';
            }
            if (Model_User::getUserByMobile($aParam['sMobile'], $aParam['iType'])) {
                $aErr['sMobile'] = '手机号码已存在';
            }
            if (! empty($aErr)) {
                return $this->showMsg($aErr, false);
            }
            /*
             * if (! Util_Validate::isCLength($aParam['sCoName'], 2, 20)) { return $this->showMsg('企业名称长度为1-20个汉字!', false); }
             */
            
            unset($aParam['sRePassword']);
            $aParam['iStatus'] = 2;
            $aParam['sPassword'] = Model_User::makePassword($aParam['sPassword']);
            $iUserID = Model_User::addData($aParam);
            $aParam['iUserID'] = $iUserID;
            if ($iUserID > 0) {
                $sActiveCode = Model_VerifyCode::makeCode($aParam, Model_VerifyCode::TYPE_USER_ACTIVE);
                $sSubject = Model_Kv::getValue('user_active_email_title');
                $sActiveUrl = 'http://' . ENV_DOMAIN . '/user/active?u=' . $iUserID . '&c=' . $sActiveCode;
                $sBody = Model_Kv::getValue('user_active_email_content');
                $sBody = str_replace('{sActiveUrl}', $sActiveUrl, $sBody);
                $mRet = Util_Mail::send($aParam['sEmail'], $sSubject, $sBody);
                
                // if ($mRet !== true) {
                // $aErr['sEmail'] = '激活邮件发送失败，请确认邮箱是否正确！';
                // return $this->showMsg($aErr, false);
                // }
                return $this->showMsg($iUserID, true);
            } else {
                return $this->showMsg(array(
                    'sCode' => '注册失败，请稍后再试'
                ), false);
            }
        } else {
            $this->assign('iType', intval($this->getParam('type')));
            
            $this->setMeta('user_reg', array(
                'sTitle' => '用户注册'
            ));
        }
    }

    /**
     * 注册成功后的激活提醒
     */
    public function regokAction ()
    {
        $iUserID = intval($this->getParam('uid'));
        $aUser = Model_User::getDetail($iUserID);
        $sMailServer = '';
        if (empty($aUser)) {
            return $this->show404();
        }
        
        $aEmail = explode('@', $aUser['sEmail']);
        $aMailServer = Util_Common::getConf('aMailServer');
        if (isset($aMailServer[$aEmail[1]])) {
            $sMailServer = $aMailServer[$aEmail[1]];
        }
        $this->assign('sEmail', $aUser['sEmail']);
        $this->assign('sMailServer', $sMailServer);
        
        $this->setMeta('user_regok', array(
            'sTitle' => '用户注册成功'
        ));
    }

    /**
     * 用户激活
     */
    public function activeAction ()
    {
        $iUserID = (int) $this->getParam('u');
        $sCode = $this->getParam('c');
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser) || $aUser['iStatus'] != 2) {
            return $this->show404();
        }
        
        $aCode = Model_VerifyCode::getCode($iUserID, Model_VerifyCode::TYPE_USER_ACTIVE, $sCode);
        if (! empty($aCode)) {
            Model_User::updStatus($iUserID, 1);
        }
        
        // 注册激活之后登录
        $aCookie = Model_User::login($aUser);
        
        $this->assign('aUser', $aUser);
        $this->setMeta('user_active', array(
            'sTitle' => '用户激活成功'
        ));
    }

    /**
     * 修改用户密码
     */
    public function chgpwdAction ()
    {
        if ($this->isPost()) {
            $aParam = $this->getParams();
            $aErr = array();
            if (empty($aParam['sNewPassword']) || empty($aParam['sRePassword']) || ! Util_Validate::isLength($aParam['sNewPassword'], 6, 12)) {
                $aErr['sNewPassword'] = '登录密码长度为6-12个字符';
            }
            if ($aParam['sNewPassword'] != $aParam['sRePassword']) {
                $aErr['sRePassword'] = '登录密码两次输入不一致';
            }
            
            $aUser = $this->getCurrUser($aParam['iType']);
            if (empty($aUser)) {
                $aErr['sNewPassword'] = '请先登录';
            }
            
            $aUser = Model_User::getDetail($aUser['iUserID']);
            if (Model_User::makePassword($aParam['sOldPassword']) != $aUser['sPassword']) {
                $aErr['sOldPassword'] = '原密码输入错误 ';
            }
            
            if (! empty($aErr)) {
                return $this->showMsg($aErr, false);
            }
            $sNewPassword = Model_User::makePassword($aParam['sNewPassword']);
            Model_User::updData(array(
                'sPassword' => $sNewPassword,
                'iUserID' => $aUser['iUserID']
            ));
            return $this->showMsg('密码修改成功！', true);
        } else {
            $iType = $this->getParam('type');
            $aUser = $this->getCurrUser($iType);
            if (empty($aUser)) {
                return $this->show404();
            }
            $aUser = Model_user::getDetail($aUser['iUserID']);
            $this->assign('aUser', $aUser);
        }
    }

    /**
     * 修改用户信息
     */
    public function chginfoAction ()
    {
        if ($this->isPost()) {
            $aParam = $this->getParams();
            $aUser = Model_User::getDetail($aParam['iUserID']);
            
            $aErr = array();
            if (empty($aParam['sEmail']) || ! Util_Validate::isEmail($aParam['sEmail'])) {
                $aErr['sEmail'] = '登录邮箱格式不正确！';
            }
            if (empty($aParam['sMobile']) || ! Util_Validate::isMobile($aParam['sMobile'])) {
                $aErr['sMobile'] = '手机号码格式不正确！';
            }
            if ($aUser['sEmail'] != $aParam['sEmail'] && Model_User::getUserByEmail($aParam['sEmail'], $aParam['iType'], $aParam['iUserID'])) {
                $aErr['sEmail'] = '该邮箱已经被注册了!';
            }
            if ($aUser['sMobile'] != $aParam['sMobile'] && Model_User::getUserByMobile($aParam['sMobile'], $aParam['iType'], $aParam['iUserID'])) {
                $aErr['sMobile'] = '该手机号码已经被注册了!';
            }
            if (! Util_Validate::isCLength($aParam['sCoName'], 1, 50)) {
                $aErr['sCoName'] = '企业名称长度为1-50个汉字!';
            }
            if (! Util_Validate::isCLength($aParam['sRealName'], 2, 5)) {
                $aErr['sRealName'] = '联系人名称长度为2-5个汉字!';
            }
            if (! Util_Validate::isAbsoluteUrl($aParam['sCoWebsite'])) {
                $aErr['sCoWebsite'] = '网址格式不正确！';
            }
            if (! Util_Validate::isCLength($aParam['sCoDesc'], 2, 200)) {
                $aErr['sCoDesc'] = '公司介绍长度为2-500个汉字!';
            }
            if (empty($aParam['sWeixin'])) {
                $aErr['sWeixin'] = '请输入你的微信号!';
            }
            if (! Util_Validate::isQQ($aParam['sQQ'])) {
                $aErr['sQQ'] = 'QQ号码输入不正确！';
            }
            
            if (! empty($aErr)) {
                return $this->showMsg($aErr, false);
            }
            
            Model_User::updData($aParam);
            return $this->showMsg('个人信息修改成功！', true);
        } else {
            $iType = $this->getParam('type');
            $aUser = $this->getCurrUser($iType);
            if (empty($aUser)) {
                return $this->show404();
            }
            $aUser = Model_user::getDetail($aUser['iUserID']);
            $aIndustry = Model_Domain::getOption(Model_Domain::TYPE_CO_INDUSTRY);
            
            $this->assign('aUser', $aUser);
            $this->assign('aIndustry', $aIndustry);
        }
    }

    /**
     * 忘记密码
     */
    public function forgetAction ()
    {
        if ($this->isPost()) {
            $sMobile = $this->getParam('mobile');
            $iType = (int) $this->getParam('type');
            $sCode = $this->getParam('code');
            $sPassword = $this->getParam('pass');
            $sRePassword = $this->getParam('repass');
            $aUser = Model_User::getUserByMobile($sMobile, $iType);
            
            $aErr = array();
            if (empty($aUser)) {
                $aErr['mobile'] = '该手机用户不存在';
            }
            
            if (! Util_Verify::checkSMSCode($sMobile, $iType, $sCode)) {
                $aErr['code'] = '验证码输入不正确';
            }
            if (empty($sPassword) || empty($sRePassword) || ! Util_Validate::isLength($sPassword, 6, 12)) {
                $aErr['pass'] = '登录密码长度为6-12个字符';
            }
            if ($sRePassword != $sPassword) {
                $aErr['repass'] = '登录密码两次输入不一致';
            }
            
            if (! empty($aErr)) {
                return $this->showMsg($aErr, false);
            }
            
            $sNewPassword = Model_User::makePassword($sPassword);
            Model_User::updData(array(
                'sPassword' => $sNewPassword,
                'iUserID' => $aUser['iUserID']
            ));
            
            return $this->showMsg('密码修改成功！', true);
            
            /*
             * $sActiveCode = Model_VerifyCode::makeCode($aUser, Model_VerifyCode::TYPE_USER_FORGET); $sSubject = Model_Kv::getValue('user_forget_email_title'); $sForgetUrl = 'http://' . ENV_DOMAIN . '/user/forgetd?u=' . $aUser['iUserID'] . '&c=' . $sActiveCode; $sBody = Model_Kv::getValue('user_forget_email_content'); $sBody = str_replace('{sForgetUrl}', $sForgetUrl, $sBody); Util_Mail::send($aUser['sEmail'], $sSubject, $sBody); return $this->showMsg('邮件已经发送到你的邮箱里！', true);
             */
        } else {
            $this->assign('iType', intval($this->getParam('iType')));
            
            $this->setMeta('user_forget', array(
                'sTitle' => '用户忘记密码'
            ));
        }
    }

    /**
     * 忘记密码后的修改密码
     *
     * @return boolean
     */
    public function forgetdAction ()
    {
        if ($this->isPost()) {
            $iUserID = (int) $this->getParam('u');
            $sCode = $this->getParam('c');
            $aCode = Model_VerifyCode::getCode($iUserID, Model_VerifyCode::TYPE_USER_FORGET, $sCode);
            if (empty($aCode)) {
                return $this->show404();
            }
            
            $aParam = $this->getParams();
            if (empty($aParam['sPassword']) || empty($aParam['sRePassword']) || ! Util_Validate::isLength($aParam['sPassword'], 6, 20)) {
                return $this->showMsg('登录密码长度为6-20个字符!', false);
            }
            if ($aParam['sPassword'] != $aParam['sRePassword']) {
                return $this->showMsg('登录密码两次输入不一致!', false);
            }
            
            $sNewPassword = Model_User::makePassword($aParam['sPassword']);
            Model_User::updData(array(
                'sPassword' => $sNewPassword,
                'iUserID' => $aCode['iUserID']
            ));
            
            return $this->showMsg('密码修改成功！', true);
        } else {
            $iUserID = (int) $this->getParam('u');
            $sCode = $this->getParam('c');
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->show404();
            }
            
            $aCode = Model_VerifyCode::getCode($iUserID, Model_VerifyCode::TYPE_USER_FORGET, $sCode);
            if (empty($aCode)) {
                return $this->show404();
            }
            
            $this->assign('aCode', $aCode);
            
            $this->setMeta('common_page', array(
                'sTitle' => '用户忘记密码'
            ));
        }
    }

    /**
     * 用户登录
     */
    public function loginAction ()
    {
        if ($this->isPost()) {
            $iType = $this->getParam('type');
            $sUser = $this->getParam('user');
            $sPass = $this->getParam('pass');
            $sCode = $this->getParam('code');
            
            $aErr = array();
            if (! Util_Verify::checkImageCode($iType, $sCode)) {
                $aErr['code'] = '验证码错误';
            }
            
            if (empty($sUser)) {
                $aErr['user'] = '请输入登录邮箱';
            } else {
                $aUser = Model_User::getUserByEmail($sUser, $iType);
                if (empty($aUser)) {
                    $aErr['user'] = '该邮箱尚未注册';
                } elseif (Model_User::makePassword($sPass) != $aUser['sPassword']) {
                    $aErr['pass'] = '登录密码错误';
                }
            }
            
            if (! empty($aErr)) {
                return $this->showMsg($aErr, false);
            }
            
            $aCookie = Model_User::login($aUser);
            
            return $this->showMsg($aCookie, true);
        } else {
            $this->assign('iType', max(1, intval($this->getParam('type'))));
            $this->assign('retUrl', $this->getParam('ret'));
            
            $this->setMeta('user_login', array(
                'sTitle' => '用户登录'
            ));
        }
    }

    /**
     * 用户退出
     */
    public function logoutAction ()
    {
        $iType = $this->getParam('type');
        $sKey = Model_User::getUserType($iType);
        Util_Cookie::delete(Yaf_G::getConf($sKey, 'cookie'));
        if ($this->isPost()) {
            return $this->showMsg('登出成功！', true);
        } else {
            return $this->redirect('/');
        }
    }

    /**
     * 首次发布广告或自媒体
     */
    public function firstAction ()
    {
        $iUserID = (int) $this->getParam('id');
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser) || $aUser['iFirst'] >= 3) {
            return $this->showMsg('已处理', true);
        }
        
        Model_User::updData(array(
            'iUserID' => $iUserID,
            'iFirst' => 3
        ));
        
        return $this->showMsg('已处理', true);
    }
}