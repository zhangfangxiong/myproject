<?php

/**
 * 支付回调
 */
class Controller_Payment_Wxjs extends Controller_Wx_Base
{

    public function paytestAction ()
    {
        $sOrderCode = $this->getParam('ordercode');
        if (empty($sOrderCode)) {
            return $this->show404('你没有订购任何产品！', false);
        }
        $subject = Model_Pay::SUBJECT;
        $body = Model_Pay::BODY;
        $aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
        //判断订单是否存在
        if (empty($aOrder)) {
            return $this->show404('该订单不存在！', false);
        }
        //判断订单是否已支付
        if (!empty($aOrder['iPayStatus'])) {
            return $this->show404('该订单已支付不能重新支付！', false);
        }
        $total_fee = $aOrder['sProductAmount'];
        //判断订单是否已支付
        if(Model_Pay::checkPay($sOrderCode)) {
            return $this->show404('该订单已经支付，订单支付状态有误，请联系管理员！', false);
        }
        $aData = Payment_Weixin::jspay($this->aUser['sOpenID'],$sOrderCode, $body, $total_fee);
        $this->assign('aData',$aData);

        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('sTitle', '订单支付');
    }

    public function payAction ()
    {
        $sOrderCode = $this->getParam('ordercode');
        if (empty($sOrderCode)) {
            return $this->show404('你没有订购任何产品！', false);
        }
        $subject = Model_Pay::SUBJECT;
        $body = Model_Pay::BODY;
        $aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
        //判断订单是否存在
        if (empty($aOrder)) {
            return $this->show404('该订单不存在！', false);
        }
        //判断订单是否已支付
        if (!empty($aOrder['iPayStatus'])) {
            return $this->show404('该订单已支付不能重新支付！', false);
        }
        $total_fee = $aOrder['sProductAmount'];
        //判断订单是否已支付
        if(Model_Pay::checkPay($sOrderCode)) {
            return $this->show404('该订单已经支付，订单支付状态有误，请联系管理员！', false);
        }
        $aData = Payment_Weixin::jspay($this->aUser['sOpenID'],$sOrderCode, $body, $total_fee);
        $this->assign('aData',$aData);

        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('sTitle', '订单支付');
    }

    //升级支付
    public function upgradePayAction()
    {
        $sOrderCode = $this->getParam('ordercode');
        if (empty($sOrderCode)) {
            return $this->show404('你没有订购任何产品！', false);
        }
        $subject = Model_Pay::SUBJECT;
        $body = Model_Pay::BODY;
        $aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
        //判断订单是否存在
        if (empty($aOrder)) {
            return $this->show404('该订单不存在！', false);
        }
        //判断订单是否已支付
        if (!empty($aOrder['iPayStatus'])) {
            return $this->show404('该升级订单已支付不能重新支付！', false);
        }
        $total_fee = $aOrder['sProductAmount'];
        //判断订单是否已支付
        if(Model_Pay::checkPay($sOrderCode)) {
            return $this->show404('该升级订单已经支付，订单支付状态有误，请联系管理员！', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        if (empty($aOrderProduct) || count($aOrderProduct) != 1) {
            return $this->show404('该升级订单中产品信息有误，请联系管理员！', false);
        }
        $aProductAttr = json_decode($aOrderProduct[0]['sProductAttr'],true);
        //判断原卡内产品是否已预约
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aProductAttr['iCardID'], $aProductAttr['iLastProductID']);
        if (!empty($aCardProduct['iBookStatus']) && $aCardProduct['iBookStatus'] != 3) {
            return $this->show404('该升级订单中原产品已经预约，不能升级', false);
        }
        $aData = Payment_Weixin::jspay($this->aUser['sOpenID'],$sOrderCode, $body, $total_fee);
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('aProductAttr', $aProductAttr);
        $this->assign('aData',$aData);
        $this->assign('sTitle', $aOrder['iOrderType'] == Model_OrderInfo::ORDERTYPE_UPGRADE ? '套餐升级支付' : '套餐支付');
        $this->assign('iHomeIcon', 0);
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
            'sMoneyPaid' => $aParam['total_fee']/100,
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