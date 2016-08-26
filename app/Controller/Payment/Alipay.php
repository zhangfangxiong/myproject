<?php

/**
* 支付宝在线支付
* User: xiejinci
* Date: 14/12/24
* Time: 下午2:32
*/
class Controller_Payment_Alipay extends Controller_Index_Base
{

    /**
     * 支付宝支付跳转
     */
    public function payAction ()
    {

        $sOrderCode = $this->getParam('ordercode');
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
        
        echo Payment_Alipay::makePay($sOrderCode, $subject, $body, $total_fee);
        
        return false;
    }

    /**
     * 将回调数据记录到数据库中
     * @param unknown $aParam
     * @return Ambigous <int/false, last_insert_id, number>
     */
    private function logPay($aParam) 
    {
        $aData = array(
            'iPayType' => 1,//支付方式(1=支付宝，2=微信)
            'sPayAccount' => $aParam['buyer_email'],
            'sPayOrderID' => $aParam['trade_no'],
            'sMyOrderID' => $aParam['out_trade_no'],
            'sData' => json_encode($aParam, JSON_UNESCAPED_UNICODE),
            'iStatus' => 0
        );
        return Model_Pay::logPay($aData);
    }
    
    /**
     * 支付成功回写
     */
    public function returnAction ()
    {
        $aParam = $this->getParams();
        Yaf_Logger::debug('return: ' . json_encode($aParam, JSON_UNESCAPED_UNICODE) . "\n", 'alipay');

        $bRet = Payment_Alipay::callback($aParam);
        if ($bRet) { // 支付成功
            $iPayID = $this->logPay($aParam);

            //更改订单状态
            $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['out_trade_no']);
            $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
            Model_OrderInfo::paySeccuss($aOrder, $aOrderProduct,$aParam['trade_no'], 1, $aParam['buyer_email']/100);

            $aPay = Model_Pay::getDetail($iPayID);
            $aOrder = Model_OrderInfo::getRow(['where' => ['sOrderCode' => $aPay['sMyOrderID']]]);
            if (in_array($aOrder['iOrderType'], [3, 4, 5])) {
                $aOP = Model_OrderProduct::getRow(['where' => [
                    'iOrderID' => $aOrder['iOrderID']
                ]]);
                $aCP = Model_OrderCardProduct::getRow(['where' => [
                    'iOrderID' => $aOrder['iOrderID']
                ]]);
                
                $aAttr = json_decode($aOP['sProductAttr'], true);
                $url = '/order/buyfourth/id/'.$aAttr['iCardID'].'/pid/'.$aCP['iProductID'].'/sid/'.$aAttr['iStoreID'];
            } else {
                $url = "/payment/pay/success/id/" . $iPayID . '.html';    
            }
        } else { // 支付失败
            $url = "/payment/pay/fail/id/" . $aParam['out_trade_no'] . '.html';
        }
        
        // $this->setViewScript('404.phtml');
        return $this->redirect($url);
    }

    /**
     * 支付宝(这个好像没用到)
     */
    public function notifyAction ()
    {
        $aParam = $this->getParams();
        Yaf_Logger::debug('notify: ' . json_encode($aParam, JSON_UNESCAPED_UNICODE) . "\n", 'alipay');
        
        $bRet = Payment_Alipay::callback($aParam);
        
        $iOrderID = $aParam['out_trade_no'];
        $iMoney = $aParam['total_fee'];
        if ($bRet) { // 支付成功
            $this->logPay($aParam);
            
            $aArg = array(
                'iPayType' => Model_Finance::TYPE_ALIPAY,
                'sPayAccount' => $aParam['buyer_email'],
                'sPayOrder' => $aParam['trade_no']
            );
            
            Model_Finance::pay($iOrderID, $iMoney, $aArg);
            echo "success";
        } else { // 支付失败
            echo "fail";
        }
        
        return false;
    }
}