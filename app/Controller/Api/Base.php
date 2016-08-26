<?php
/**
 * Created by PhpStorm.
 * User: chasel
 * Date: 2016/7/18
 * Time: 15:30
 */

class Controller_Api_Base extends Yaf_Controller
{
    /**
     * Ajax或API请求时，返回json数据
     * @param unknown $mMsg
     * @param unknown $bRet
     */
    protected function showMsg ($mMsg, $bRet)
    {
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json; charset=utf-8');

        $mMsg = $this->resultFlip($mMsg);

        $sDebug = Util_Common::getDebugData();
        if ($sDebug) {
            $mMsg['debug'] = $sDebug;
        }
        $response->appendBody(json_encode($mMsg, JSON_UNESCAPED_UNICODE));
        $this->autoRender(false);

        return false;
    }

    private function resultFlip($result){
        foreach($result as $key => $value) {
            if(is_array($value)) {
                if(empty($value)) {
                    unset($result[$key]);
                }else {
                    $result[$key] = $this->resultFlip($value);
                }
            }else {
                continue;
            }
        }

        return $result;
    }

    /**
     * 执行Action前执行
     * @see Yaf_Controller::actionBefore()
     */
    public function actionBefore ()
    {
        //加密算法
        //基础数据
        if (!$this->_isInternal()) {
            $iTime = $this->getParam('_time', 0);
            if (time() - $iTime > 1800) {
                return $this->showMsg(['code' => 1060, 'msg' => '密钥错误'], false);
            }
            $sSign = $this->getParam('_sign');
            if ($sSign != md5($iTime . Yaf_G::getConf('signkey'))) {
                return $this->showMsg(['code' => 1060, 'msg' => '密钥错误'], false);
            }
        }

        $params = $this->getParams();
        $params = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $data = array(
            'sControllerName' => $this->_name,
            'sUrl' => $_SERVER['REQUEST_URI'],
            'sRequest' => $params,
            'sResponse' => '',
        );
        Model_Interfacelog::addData($data);

    }


    protected function _isInternal()
    {
        $isInternal = false;
        if (ENV_SCENE == 'dev') {
            $isInternal = true;
        }
        $allowip = Yaf_G::getConf('allowip');
        $sClientIp = $this->_request->getClientIP();
        if (in_array($sClientIp, $allowip)) {
            $isInternal = true;
        }
        return $isInternal;
    }









    /////出自class Controller_Company_Base



    /**
     * 获取体检计划下的用户ids
     * @param  [type] $iPlanID [description]
     * @return [string]
     */
    public function getPlanUserIDs ($iPlanID)
    {
        $aUserIDs = [];
        $aOrder = Model_OrderInfo::getAll(['where' => [
            'iPlanID' => $iPlanID,
            'iStatus IN' => [0, 1]
        ]]);
        if ($aOrder) {
            foreach ($aOrder as $key => $value) {
                if ($value['iUserID']) {
                    $aUserIDs[] = $value['iUserID'];
                }
            }
        }

        $ids = implode(',', $aUserIDs);
        return $ids;
    }

    /**
     * 获取体检计划下的产品列表
     * @param  [type] $iPlanID [description]
     * @return [array]
     */
    public function getPlanProduct ($iPlanID, $iUserID)
    {
        $aSelProduct = [];
        $aUser = Model_User::getDetail($iUserID);
        $aPlanProduct = Model_Physical_PlanProduct::getAll(['where' => [
            'iPlanID' => $iPlanID,
            'iProductID >' => 0,
            'iStatus' => Model_Physical_PlanProduct::STATUS_VALID,
        ]]);
        if ($aPlanProduct) {
            foreach ($aPlanProduct as $key => $value) {
                $aHrProductIDs[] = $value['iProductID'];
            }
            $sHrProductID = implode(',', $aHrProductIDs);
            $aHrProduct = Model_Product::getAllUserProduct($iUserID, 1, $aUser['iChannel'], $sHrProductID);

            foreach ($aHrProduct as $key => $value) {
                $aSelProduct[$value['iProductID']] = $value['sProductName'];
            }
        }

        return $aSelProduct;
    }

}