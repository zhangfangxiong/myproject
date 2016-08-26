<?php

/**
 * 登陆，注册相关
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/4/08
 * Time: 下午2:32
 */
class Controller_Wx_Account extends Controller_Wx_Base
{
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        parent::actionBefore();
    }

    //身份证绑定
    public function bindIdCardAction()
    {
        if (!empty($this->aUser['iUserID'])) {
            return $this->show404('非法操作！', false);
        }
        if ($this->isPost()) {
            $sOpenID = $this->getParam('openid') ? $this->getParam('openid') : '';
            $sIdCard = $this->getParam('idcard') ? $this->getParam('idcard') : '';
            $sRealName = $this->getParam('realname') ? $this->getParam('realname') : '';
            if (!Util_Validate::isIdcard($sIdCard)) {
                return $this->show404('身份证不符合规范！', false);
            }
            if (empty($sRealName)) {
                return $this->show404('真实性别不能为空！', false);
            }
            $aUser = Model_Customer::getUserByIdentityCard($sIdCard);
            if (!empty($aUser) && $aUser['sRealName'] != $sRealName) {
                return $this->show404('该身份证已被他人绑定，且真实姓名和您不一致！', false);
            }
            //绑定操作(cookie中只存三个字段，channel和创建人不能存到cookie)
            if (empty($aUser)) {
                //插入新数据
                $aUserParam['sIdentityCard'] = $sIdCard;
                $aUserParam['sRealName'] = $sRealName;
                $aCookie['sOpenID'] = $aUserParam['sOpenID'] = $sOpenID;
                $aCookie['sUserName'] = $aUserParam['sUserName'] = Model_Customer::initUserName();
                $aUserParam['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aUserParam['sUserName']);
                $aUserParam['sBirthDate'] = substr($sIdCard, 6, 8);
                $aCreateUser = Model_User::getUserByUserName($this->sCompanyName, 2);
                if (empty($aCreateUser)) {
                    return $this->show404('请先在admin后台添加一个用户名为“registers”的hr用户', false);
                }
                $aUserParam['iCreateUserID'] = $aCreateUser['iUserID'];
                $aUserParam['iChannel'] = $aCreateUser['iChannel'];
                if ($aCookie['iUserID'] = Model_Customer::addData($aUserParam)) {
                    //设置cookie
                    // Util_Cookie::set(Yaf_G::getConf('wxuserkey', 'cookie'), $aUserParam);
                    Util_Cookie::set(Yaf_G::getConf('wxuserkey', 'cookie'), $aCookie);
                    return $this->show404('绑定成功', true);
                } else {
                    return $this->show404('绑定失败', false);
                }
            } else {
                $aCookie['iUserID'] = $aUserParam['iUserID'] = $aUser['iUserID'];
                $aUserParam['sIdentityCard'] = $sIdCard;
                $aUserParam['sRealName'] = $sRealName;
                $aCookie['sOpenID'] = $aUserParam['sOpenID'] = $sOpenID;
                $aUserParam['sBirthDate'] = substr($sIdCard, 6, 8);
                $aCookie['sUserName'] = $aUser['sUserName'];
                if (Model_Customer::updData($aUserParam)) {
                    //设置cookie
                    Util_Cookie::set(Yaf_G::getConf('wxuserkey', 'cookie'), $aCookie);
                    return $this->show404('绑定成功', true);
                } else {
                    return $this->show404('绑定失败', false);
                }
            }
        }
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType', Util_Verify::TYPE_SYS_BIND_WX);
        $this->assign('sTitle', '身份证绑定');
        $this->assign('sCurrOpenID', $this->sCurrOpenID ? $this->sCurrOpenID : $this->aUser['sOpenID']);
    }

    //手机绑定
    public function bindPhoneAction()
    {
        if ($this->isPost()) {
            $sMobile = $this->getParam('phonenum') ? $this->getParam('phonenum') : '';
            $sVerifyCode = $this->getParam('verifycode') ? $this->getParam('verifycode') : '';
            if (!Util_Validate::isMobile($sMobile)) {
                return $this->show404('手机不符合规范！', false);
            }
            //验证手机验证码是否正确
            if (!Util_Verify::checkSMSCode(Util_Verify::TYPE_SYS_BIND_WX, $sVerifyCode)) {
                return $this->show404('手机验证码错误，请重新发送', false);
            }

            $aUserParam['iUserID'] = $this->aUser['iUserID'];
            $aUserParam['sMobile'] = $sMobile;
            if (Model_Customer::updData($aUserParam)) {
                return $this->show404('绑定成功', true);
            } else {
                return $this->show404('绑定失败', false);
            }
        }
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->assign('iCodeType', Util_Verify::TYPE_SYS_BIND_WX);
        $this->assign('sTitle', '手机绑定');
        $this->assign('sCurrOpenID', $this->sCurrOpenID);
    }

    //个人信息
    public function userInfoAction()
    {
        $aParam = $this->getParams();
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aRegionParam['where']['iStatus'] = 1;
        if (!empty($this->aUser['iCityID'])) {
            $aRegionParam['where']['iCityID'] = $this->aUser['iCityID'];
        }
        $aRegion = Model_Region::getPair($aRegionParam, 'iRegionID', 'sRegionName');
        $this->assign('id', $aParam['id']);
        $this->assign('aCitys', $aCitys);
        $this->assign('aRegion', $aRegion);
        $this->assign('aSex', Yaf_G::getConf('aSex'));
        $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
        $this->assign('sTitle', '个人信息');
    }


    //个人信息编辑
    public function userInfoEditAction()
    {
        $aParam = $this->getParams();
        if ($this->isPost()) {
            if (Model_Customer::updData($aParam)) {
                return $this->show404('个人信息编辑成功！', true);
            }
            return $this->show404('编辑失败!', false);
        } else {
            $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
            $aRegionParam['where']['iStatus'] = 1;
            if (!empty($this->aUser['iCityID'])) {
                $aRegionParam['where']['iCityID'] = $this->aUser['iCityID'];
            }
            $aRegion = Model_Region::getPair($aRegionParam, 'iRegionID', 'sRegionName');
            $this->assign('id', $aParam['id']);
            $this->assign('aCitys', $aCitys);
            $this->assign('aRegion', $aRegion);
            $this->assign('aSex', Yaf_G::getConf('aSex'));
            $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
            $this->assign('aProductType', Yaf_G::getConf('aType', 'product'));
            $this->assign('sTitle', '个人信息编辑');
        }
    }
}