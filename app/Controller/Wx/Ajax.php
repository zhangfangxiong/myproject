<?php

/**
 * ajax需求
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/06/24
 * Time: 下午2:32
 */
class Controller_Wx_Ajax extends Yaf_Controller
{
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        parent::actionBefore();
    }

    /**
     * 执行动作之后
     */
    public function actionAfter()
    {
    }

    //ajax验证卡号是否有效
    public function ajaxCheckCardAction()
    {
        $sCardNum = $this->getParam('cardnum') ? $this->getParam('cardnum') : '';
        $aCard = Model_OrderCard::getCardByCode($sCardNum);
        if (empty($sCardNum) || empty($aCard)) {
            return $this->show404('卡号不存在', false);
        }
        if ($aCard['iStatus'] != 1) {
            return $this->show404('该卡号无效！', false);
        }
        if ($aCard['iBindStatus'] == 1 || !empty($aCard['iUserID'])) {
            return $this->show404('该卡已被他人绑定！', false);
        }
        return $this->show404('卡号存在', true);
    }

    //ajax验证卡号验证码
    public function ajaxCheckCardVerifyAction()
    {
        $sVerifyCode = $this->getParam('verifycode') ? $this->getParam('verifycode') : '';
        if (empty($sVerifyCode) || !Util_Verify::checkImageCode(Util_Verify::TYPE_CARD_ADD_IMAGE_WX, $sVerifyCode)) {
            return $this->show404('验证码有误', false);
        }
        return $this->show404('验证码正常', true);
    }

    //ajax验证绑定手机是否有效(已弃用)
    public function ajaxCheckBindPhoneAction()
    {
        $sMobile = $this->getParam('phonenum') ? $this->getParam('phonenum') : '';
        $aUser = Model_Customer::getUserByMobile ($sMobile);
        if (!empty($aUser['sOpenID'])) {
            return $this->show404('手机已被其他微信用户绑定', false);
        }
        return $this->show404('手机有效', true);
    }
}