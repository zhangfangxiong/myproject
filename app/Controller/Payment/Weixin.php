<?php

/**
 * 支付回调
 */
class Controller_Payment_Weixin extends Controller_Index_Base
{
    public function payAction ()
    {
        $sOrderCode = $this->getParam('ordercode');
        $subject = Model_Pay::SUBJECT;
        $body = Model_Pay::BODY;
        $aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
        //判断订单是否存在
        if (empty($aOrder)) {
            return $this->showMsg('该订单不存在！', false);
        }
        //判断订单是否已支付
        if (!empty($aOrder['iPayStatus'])) {
            return $this->showMsg('该订单已支付不能重新支付！', false);
        }
        $total_fee = $aOrder['sProductAmount'];
        //判断订单是否已支付
        if(Model_Pay::checkPay($sOrderCode)) {
            return $this->showMsg('该订单已经支付，订单支付状态有误，请联系管理员！', false);
        }
        //echo $body;die;
        $sQRUrl = Payment_Weixin::qrcode($sOrderCode, $body, $total_fee,$subject);
        if (is_array($sQRUrl)) {
            return $this->showMsg($sQRUrl['err_code_des'].'，请联系管理员！', false);
        }
         $this->assign('sQRUrl', $sQRUrl);
        $aParam['ordercode'] = $sOrderCode;
        $aParam['totalfee'] = $total_fee;
        $this->assign('aParam', $aParam);
    }

    /**
     * 将回调数据记录到数据库中
     * @param unknown $aParam
     * @return Ambigous <int/false, last_insert_id, number>
     */
    private function logPay($aParam)
    {
        $aData = array(
            'iPayType' => 2,//支付方式(1=支付宝，2=微信)
            'sPayAccount' => $aParam['openid'],
            'sPayOrderID' => $aParam['transaction_id'],
            'sMyOrderID' => $aParam['attach'],
            'sData' => json_encode($aParam, JSON_UNESCAPED_UNICODE),
            'iStatus' => 0
        );
        return Model_Pay::logPay($aData);
    }
    
    /**
     * 微信异步调用
     */
    public function notifyAction ()
    {
        Payment_Weixin::notify(array($this, 'callback'));
        return false;
    }
    
    public function callback($data)
    {
        $this->logPay($data);
        //更改订单状态
        $aOrder = Model_OrderInfo::getOrderByOrderCode($data['attach']);
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        Model_OrderInfo::paySeccuss($aOrder, $aOrderProduct,$data['transaction_id'], 2, $data['total_fee']/100);
        return false;
    }
}