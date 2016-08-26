<?php

/**
 * 提供验证码服务
 * Created by PhpStorm.
 * User: xiejinci
 * Date: 14/12/24
 * Time: 下午2:32
 */
class Controller_Index_Verify extends Controller_Index_Base
{

    /**
     * 获取图片验证码
     */
    public function imageAction ()
    {
        $iType = (int) $this->getParam('type');
        $sImg = Util_Verify::makeImageCode($iType);
        $this->getResponse()->setHeader('Content-Type', 'gif');
        echo $sImg;
        return false;
    }

    /**
     * 发送手机验证码
     */
    public function smsAction ()
    {
        $sMobile = $this->getParam('mobile');
        $iType = $this->getParam('type');

        $aRet = Util_Verify::makeSMSCode($sMobile, $iType);

        if ($aRet['status']) {
            return $this->showMsg('手机验证码发送成功', true);
        } else {
            return $this->showMsg($aRet['data'], false);
        }
    }
}