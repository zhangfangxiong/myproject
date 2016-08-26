<?php

/**
 * 安心体检
 * User: xuchuyuan
 * Date: 16/6/23 00:00
 */
class Controller_Index_Balance extends Controller_Index_Account
{
    protected $aPriceKey = [
        1 => 'iManPrice',
        2 => 'iWomanPrice1',
        3 => 'iWomanPrice2'
    ];

	public $bCheckLogin = true;

	public function actionBefore ()
	{
		parent::actionBefore();
        if (!$this->aUser) {
            return $this->showMsg('请先登陆！', false, '/index/account/publicLogin');
        }


		$this->_frame = 'pcbasic.phtml';
        $this->_assignUrl();
	}

	protected function _assignUrl()
    {

    }

	//结算验证
    public function balanceValidateAction()
    {
        $aProductID = [];
        $aParam = $this->getParams();
        if (empty($aParam['cartnum'])) {
            return $this->show404('没有要结算的产品', false);
        }
        $sTotalPrice = 0;
        $aData = [
            'sAllTotalPrice' => '',
            'aList' => []
        ];
        foreach ($aParam['cartnum'] as $key => $value) {
            $aProduct = Model_UserProductBase::getUserProductBase($key, $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID']);
            if (empty($aProduct)) {
                return $this->show404('购物车中有不存在或已下架的产品，不能结算', false);
            }
            if ($aProduct['iStatus'] != 1) {
                $sMsg = !empty($aProduct['sProductName']) ? !empty($aProduct['sAlias']) ? $aProduct['sAlias'] : $aProduct['sProductName'] : '';
                $sMsg .= '已下架，请先删除该购物车产品';
                return $this->show404($sMsg, false);
            }
            $sTotalPrice += $aProduct[$this->aPriceKey[$aParam['aProductSex'][$key]]] * $value;
            //组装post数据
            $aProductID[] = $aTmp['iProductID'] = $aProduct['iProductID'];
            $aTmp['sProductName'] = !empty($aProduct['sProductName']) ? !empty($aProduct['sAlias']) ? $aProduct['sAlias'] : $aProduct['sProductName'] : '';
            $aTmp['sPrice'] = $aProduct[$this->aPriceKey[$aParam['aProductSex'][$key]]];
            $aTmp['sTotalPrice'] = strval(round($aProduct[$this->aPriceKey[$aParam['aProductSex'][$key]]] * $value, 2));
            $aTmp['iNum'] = $value;
            $aTmp['iSex'] = $aParam['aProductSex'][$key];
            $aData['aList'][] = $aTmp;
        }        
        $aData['sAllTotalPrice'] = strval(round($sTotalPrice, 2));
        if ($aData['sAllTotalPrice'] != strval($aParam['total_price'])) {//当成字符串处理，浮点数不能直接比较
            return $this->show404('结算不成功！', false);
        }
        if (empty($aParam['sConsignee'])) {
            $aMsg = [
                'inputid' => 'sConsignee',
            ];
        } elseif (empty($aParam['sMobile'])) {
            $aMsg = [
                'inputid' => 'sMobile',
            ];
        } elseif (empty($aParam['sEmail'])) {
            $aMsg = [
                'inputid' => 'sEmail',
            ];
        } elseif (!empty($aParam['iIfInv'])) {
            if (empty($aParam['sInvPayee'])) {
                $aMsg = [
                    'inputid' => 'sInvPayee',
                ];
            } elseif (empty($aParam['sAddress'])) {
                $aMsg = [
                    'inputid' => 'sAddress',
                ];
            } elseif (empty($aParam['sZipcode'])) {
                $aMsg = [
                    'inputid' => 'sZipcode',
                ];
            }
        }
        if (!empty($aMsg)) {
            return $this->show404($aMsg, false);
        }
        $aProduct = Model_Product::getAllUserProduct($this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID'], implode(',', $aProductID), false, 'iProductID');

        //以下为各种情况分析
        $iUserID = $this->aUser['iUserID'];
        $iUserType = Model_OrderInfo::PRESON;
        $iOrderType = Model_OrderInfo::ELECTRONICCARD;
        $sConsignee = $aParam['sConsignee'];
        $sMobile = $aParam['sMobile'];
        $sProductAmount = $aData['sAllTotalPrice'];
        $aOrderProductParam = [];
        $sOrderCode = Model_OrderInfo::initOrderCode($iUserType);
        $aOrderParam['iIfInv'] = $aParam['iIfInv'];
        $aOrderParam['sAddress'] = $aParam['sAddress'];
        $aOrderParam['sZipcode'] = $aParam['sZipcode'];
        $aOrderParam['sEmail'] = $aParam['sEmail'];
        $aOrderParam['sInvPayee'] = $aParam['sInvPayee'];
        $aOrderParam['sAddress'] = $aParam['sAddress'];
        $aOrderParam['sZipcode'] = $aParam['sZipcode'];

        //入库操作
        Model_OrderInfo::begin();
        //入orderinfo表
        if ($iOrderID = Model_OrderInfo::initOrder($iUserID, $iUserType, $iOrderType, $sConsignee, $sMobile, $sProductAmount, $aOrderParam, $sOrderCode)) {
            foreach ($aProductID as $key => $value) {
                $aOrderProductParam['iSex'] = $aParam['aProductSex'][$value];//选择的性别
                //入orderproduct表
                if (!Model_OrderProduct::initOrder($iOrderID, $aProduct[$value]['iProductID'], 
                    $aProduct[$value]['sProductName'], $aParam['cartnum'][$value], 
                    $aProduct[$value][$this->aPriceKey[$aParam['aProductSex'][$value]]],
                    $aProduct[$value][$this->aPriceKey[$aParam['aProductSex'][$value]]] * $aParam['cartnum'][$value],
                    Model_OrderInfo::ELECTRONICCARD, $aOrderProductParam)) {
                    Model_OrderInfo::rollback();
                    $aMsg = [
                        'msg' => '生成订单失败，请稍后重试！',
                    ];
                    return $this->show404($aMsg, false);
                }
            }
        } else {
            Model_OrderInfo::rollback();
            $aMsg = [
                'msg' => '生成订单失败，请稍后重试！',
            ];
            return $this->show404($aMsg, false);
        }

        Model_OrderInfo::commit();
        
        //清空购物车
        Model_Cart::flushCart($this->iCurrUserID);
        $aMsg = [
            'iCreateTime' => time(),
            'sOrderCode' => $sOrderCode,
            'msg' => '生成订单成功！',
        ];

        return $this->showMsg('生成订单成功！', true, '/order/cardinfo/id/'.$iOrderID);
    }

    //结算页面
    public function balanceAction()
    {
        $aParam = $this->getParams();
        if (!empty($aParam['checkdata'])) {
            $aParam['checkdata'] = json_decode($aParam['checkdata'], true);
        }

        $this->assign('aSex', Yaf_G::getConf('aSex', 'product'));
        $this->assign('aParam', $aParam);
        $this->assign('sTitle', '订单信息');
    }

    //支付页面
    public function payAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['ordercode'])) {
            return $this->showMsg('你没有订购任何产品！', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['ordercode']);
        if ($aOrder['iPayStatus']) {
            return $this->showMsg('该订单已支付！', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('sTitle', '订单支付');
    }

    //订单详情
    public function orderDetailAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['ordercode'])) {
            return $this->showMsg('订单不存在', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['ordercode']);
        if (!$aOrder['iPayStatus']) {
            return $this->showMsg('该订单未支付！', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        if (!empty($aOrderProduct)) {//组装卡号数据
            foreach ($aOrderProduct as $key => $value) {
                $aCardParam['iOPID'] = $value['iAutoID'];
                $aOrderProduct[$key]['aCardList'] = Model_OrderCard::getPair($aCardParam,'sCardCode','iStatus');
            }
        }
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('sTitle', '订单详情');
    }

    //支付post页面
    public function payPostAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['sOrderCode']) || empty($aParam['sProductAmount'])) {
            return $this->showMsg('参数有误', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['sOrderCode']);
        if (empty($aOrder)) {
            return $this->showMsg('没有对应的订单', false);
        }
        if ($aOrder['sProductAmount'] != $aParam['sProductAmount']) {
            return $this->showMsg('订单有误', false);
        }
        if ($aOrder['iPayStatus'] == 1) {
            return $this->showMsg('已支付过该订单', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        //检查订单中产品是否有效
        foreach ($aOrderProduct as $key => $value) {
            $aProduct = Model_Product::getDetail($value['iProductID']);
            $aOrderProduct[$key]['iAttribute'] = $aProduct['iAttribute'];
            //检查是否存在该商品
            if (empty($aProduct)) {
                return $this->showMsg('订单中商品"'.$value['sProductName'].'"不存在，支付失败!', false);
            }
            //检查是否下架
            if ($aProduct['iStatus'] != 1) {
                return $this->showMsg('订单中商品"'.$value['sProductName'].'"已经下架，支付失败!', false);
            }
            //检查库存是否足够，虚拟卡暂时没有需求TODO

        }

        //调用微信支付接口todo
        $aPay = 1;

        if ($aPay) {
            //更新订单状态
            $aOrderParam['iPayStatus'] = 1;
            $aOrderParam['sPayOrderID'] = 'todo';//微信返回的支付订单号
            $aOrderParam['iPayTime'] = time();
            $aOrderParam['iOrderID'] = $aOrder['iOrderID'];
            $aOrderParam['iPayChannel'] = 2;
            $aOrderParam['sMoneyPaid'] = $aOrderParam['sOrderAmount'] = $aParam['sProductAmount'];//已支付金额,应支付金额
            //ordercard表中新插入卡的数据
            Model_OrderProduct::begin();
            foreach ($aOrderProduct as $key => $value) {
                //2,预约表中插入数据
                $aOrderCardParam['iOPID'] = $value['iAutoID'];
                $aOrderCardParam['iPhysicalType'] = $value['iAttribute'];
                $aOrderCardParam['sProductName'] = $value['sProductName'];
                $aOrderCardParam['iProductID'] = $value['iProductID'];
                for ($i = 0; $i < $value['iProductNumber']; $i++) {
                    $aOrderCardParam['sCardCode'] = Model_OrderCard::initCardCode();
                    if (!Model_OrderCard::addData($aOrderCardParam)) {
                        Model_OrderInfo::rollback();
                        $aMsg = [
                            'msg' => '生成订单失败，请稍后重试！',
                        ];
                        return $this->showMsg($aMsg, false);
                    }
                }
            }
            if (!Model_OrderInfo::updData($aOrderParam)) {
                Model_OrderProduct::rollback();
                return $this->showMsg('支付失败!请联系管理员', false);
            }
            Model_OrderProduct::commit();
            return $this->showMsg('支付成功！', true);
        }
        return $this->showMsg('支付接口出错，请稍后重试!', false);
    }
}
