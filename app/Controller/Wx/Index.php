<?php

/**
 * 提供微信首页
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/05/23
 * Time: 下午2:32
 */
class Controller_Wx_Index extends Controller_Wx_Base
{
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        parent::actionBefore();
    }

    public function indexAction()
    {
        $this->assign('sTitle', '安心服务');
        $this->assign('iHomeIcon', 0);
    }

    public function listAction()
    {
        //购物车信息
        $aChart = Model_Cart::getCart($this->iCurrUserID);
        $this->assign('aChart', $aChart);
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $t = $this->getParam('t');
        $aHRData = $this->getHrCanViewProduct();
        $aHrProductID = [];
        if (!empty($aHRData)) {
            foreach ($aHRData as $key => $value) {//整理hr能看到的产品ID
                $aHrProductID[] = $value['iProductID'];
            }
        }
        $sHrProductID = implode(',',$aHrProductID);
        $aData = $this->getAllUserCanViewProduct($iPage,$sHrProductID);
        if ($t) {
            return $this->show404($aData, true);
        }
        $this->assign('sTitle', '产品列表');
        $this->assign('iCartIcon', 1);
        $this->assign('aData', $aData);
        $this->assign('aHRData', $aHRData);
    }

    /**
     * 获取区域列表
     * @return
     */
    public function getRegionAction()
    {
        $iCityID = $this->getParam('iCityID');
        $aData = Model_Region::getAll(['where' => ['iCityID' => $iCityID]]);

        return $this->show404($aData, true);
    }

    /**
     * 获取所有门店列表
     * @return
     */
    public function getStoreListAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aWhere = ['iStatus >' => Model_Store::STATUS_INVALID];

        $aParam['iCityID'] = $this->getParam('iCityID');
        $aParam['iRegionID'] = $this->getParam('iRegionID');
        $aParam['iSupplierID'] = $this->getParam('iSupplierID');

        if (!empty($aParam['iCityID'])) {
            $aWhere['iCityID'] = intval($aParam['iCityID']);
        }
        if (!empty($aParam['iRegionID'])) {
            $aWhere['iRegionID'] = intval($aParam['iRegionID']);
        }
        if (!empty($aParam['iSupplierID'])) {
            $aWhere['iSupplierID'] = intval($aParam['iSupplierID']);
        }

        $aRegion = Model_Region::getPair(['where' => ['iStatus' => 1]], 'iRegionID', 'sRegionName');
        $aCity = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aCreditLevel = Yaf_G::getConf('aShopLevel', 'store');

        $aData = Model_Store::getList($aWhere, $iPage);
        if (!empty($aData['aList'])) {
            //组装需要数据
            foreach ($aData['aList'] as $key => $value) {
                $aData['aList'][$key]['sRegionName'] = !empty($aRegion[$value['iRegionID']]) ? $aRegion[$value['iRegionID']] : '';
                $aData['aList'][$key]['sCityName'] = !empty($aCity[$value['iCityID']]) ? $aCity[$value['iCityID']] : '';
                $aData['aList'][$key]['sLevel'] = empty($aCreditLevel[$value['iShopLevel']]) ? 0 : $aCreditLevel[$value['iShopLevel']];
            }
        }
        return $this->show404($aData, true);
    }

    /**
     * 获取产品门店列表
     * @return
     */
    public function getStoreAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $iProductID = $this->getParam('iProductID') ? intval($this->getParam('iProductID')) : 0;
        $iChannelType = $this->getParam('iChannelType') ? intval($this->getParam('iChannelType')) : 2;

        if (empty($iProductID)) {
            return $this->show404('参数不全', false);
        }

        $aProduct = Model_UserProductBase::getUserProductBase($iProductID, $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannel']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        $aWhere = ['iStatus >' => Model_Store::STATUS_INVALID];

        $aParam['iCityID'] = $this->getParam('iCityID');
        $aParam['iRegionID'] = $this->getParam('iRegionID');
        $aParam['iSupplierID'] = $this->getParam('iSupplierID');

        if (!empty($aParam['iCityID'])) {
            $aWhere['iCityID'] = intval($aParam['iCityID']);
        }
        if (!empty($aParam['iRegionID'])) {
            $aWhere['iRegionID'] = intval($aParam['iRegionID']);
        }
        if (!empty($aParam['iSupplierID'])) {
            $aWhere['iSupplierID'] = intval($aParam['iSupplierID']);
        }

        $aStore = Model_Store::getPair($aWhere, 'iStoreID', 'sName');

        $aRegion = Model_Region::getPair(['where' => ['iStatus' => 1]], 'iRegionID', 'sRegionName');
        $aCity = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        if (empty($aStore)) {
            $aData['aList'] = [];
            return $this->show404($aData, true);
        }
        $aStoreParam['iStoreID IN'] = array_keys($aStore);

        $aData = Model_UserProductStore::getUserProductStoreList($iProductID, $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannel'], $iPage, $aStoreParam);
        if (!empty($aData['aList'])) {
            //组装需要数据
            foreach ($aData['aList'] as $key => $value) {
                $aStore = Model_Store::getDetail($value['iStoreID']);
                $aStore['sRegionName'] = !empty($aRegion[$aStore['iRegionID']]) ? $aRegion[$aStore['iRegionID']] : '';
                $aStore['sCityName'] = !empty($aCity[$aStore['iCityID']]) ? $aCity[$aStore['iCityID']] : '';
                $aData['aList'][$key]['detail'] = $aStore;
            }
        }
        return $this->show404($aData, true);
    }

    public function storeAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->show404('参数不全', false);
        }

        $aProduct = Model_UserProductBase::getUserProductBase($aParam['id'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel'], true);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Type::getOption('supplier');
        //购物车信息
        $aChart = Model_Cart::getCart($this->iCurrUserID);
        $this->assign('iProductID', $aProduct['iProductID']);
        $this->assign('aProduct', $aProduct);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aChart', $aChart);
        $this->assign('sTitle', '门店查询');
        $this->assign('iCartIcon', 1);
        $this->assign('iCartFoot', 1);
    }

    public function detailAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->show404('参数不全', false);
        }
        $aData = Model_UserProductBase::getUserProductBase($aParam['id'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel'], true);
        if (empty($aData)) {
            return $this->show404('产品不存在', false);
        }
        //获取门店数目
        $aProductStore = Model_UserProductStore::getUserProductStore($aData['iProductID'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
        $aTmp = [];
        foreach ($aProductStore as $key => $value) {
            $aTmp[$value['iAutoID']] = $value['iStoreID'];
        }
        $aProductStore = $aTmp;
        $aData['iStoreNum'] = count($aProductStore);
        //获取门店包含的代理商
        if (!empty($aProductStore)) {
            $aSupplier = Model_Type::getOption('supplier');
            $aSupplierID = Model_Store::getSupplierByStores($aProductStore);
            $aSupplierName = [];
            if (!empty($aSupplierID)) {
                foreach ($aSupplierID as $key => $value) {
                    $aSupplierName[] = !empty($aSupplier[$value]) ? $aSupplier[$value] : '';
                }
            }
            $sSupplierName = implode(',', $aSupplierName);
            $aData['sSupplierName'] = $sSupplierName;
        }
        //获取该产品包含的单项
        $aItem = Model_ProductItem::getProductItems($aData['iProductID'], Model_ProductItem::EXPANDPRODUCT, null, true);
        if (!empty($aItem)) {
            $sItem = implode(',', $aItem);
            $aItemCat = Model_Item::setGroupByCategory($sItem, true);
        } else {
            $aItemCat = [];
        }
        //购物车信息
        $aChart = Model_Cart::getCart($this->iCurrUserID);
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aItemCat', $aItemCat);
        $this->assign('aChart', $aChart);
        $this->assign('sTitle', '产品详情');
        $this->assign('aData', $aData);
        $this->assign('iCartIcon', 1);
        $this->assign('iCartBur', 1);
        $this->assign('iCartFoot', 1);
        $this->assign('hassnotoremenu', 1);
    }

    //加入购物车
    public function addCartAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->show404('参数不全', false);
        }
        $iProductID = $aParam['id'];

        $aData = Model_UserProductBase::getUserProductBase($iProductID, $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
        if (empty($aData)) {
            return $this->show404('产品不存在', false);
        }
        if ($aCart = Model_Cart::addCart($iProductID, $this->iCurrUserID)) {
            return $this->show404($aCart, true);
        } else {
            return $this->show404('加入失败', false);
        }
    }

    //删除购物车
    public function deleteCartAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->show404('参数不全', false);
        }
        $iProductID = $aParam['id'];
        if (Model_Cart::deleteCart($iProductID, $this->iCurrUserID)) {
            return $this->show404('删除成功', true);
        } else {
            return $this->show404('删除失败', false);
        }
    }

    //购物车列表
    public function cartListAction()
    {
        $aCart = Model_Cart::getCart($this->iCurrUserID);
        if (!empty($aCart)) {
            foreach ($aCart as $key => $value) {
                $aDetail = Model_UserProductBase::getUserProductBase($key, $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
                if (empty($aDetail) || $aDetail['iStatus'] != 1) {//如果不存在该产品或已下架，删除该条购物车
                    unset($aCart[$key]);
                    Model_Cart::deleteCart($key, $this->iCurrUserID);
                } else {
                    $aCart[$key]['detail'] = $aDetail;
                }
            }
        }
        $this->assign('aSex', Yaf_G::getConf('aSex', 'product'));
        $this->assign('aCart', $aCart);
        $this->assign('sTitle', '购物车');
    }

    //结算验证
    public function balanceValidateAction()
    {
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
            $aProduct = Model_UserProductBase::getUserProductBase($key, $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
            //$aProduct['iStatus'] = 2;
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
            $aTmp['iProductID'] = $aProduct['iProductID'];
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
        return $this->show404($aData, true, '/wx/balance');
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

    //结算操作
    public function balancePostAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['aProduct']['iProductID'])) {
            $aMsg = [
                'msg' => '生成订单失败，你没有订购任何产品！',
            ];
            return $this->show404($aMsg, false);
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
        $aProduct = Model_Product::getAllUserProduct($this->aUser['iCreateUserID'], 2, $this->aUser['iChannel'], implode(',', $aParam['aProduct']['iProductID']), false, 'iProductID');
        //以下为各种情况分析
        $iUserID = $this->aUser['iUserID'];
        $iUserType = Model_OrderInfo::PRESON;
        $iOrderType = Model_OrderInfo::ELECTRONICCARD;
        $sConsignee = $aParam['sConsignee'];
        $sMobile = $aParam['sMobile'];
        $sProductAmount = 0;
        $aOrderProductParam = [];
        $sOrderCode = Model_OrderInfo::initOrderCode($iUserType);
        $aOrderParam['iIfInv'] = $aParam['iIfInv'];
        $aOrderParam['sAddress'] = $aParam['sAddress'];
        $aOrderParam['sZipcode'] = $aParam['sZipcode'];
        $aOrderParam['sEmail'] = $aParam['sEmail'];
        $aOrderParam['sInvPayee'] = $aParam['sInvPayee'];
        $aOrderParam['sAddress'] = $aParam['sAddress'];
        $aOrderParam['sZipcode'] = $aParam['sZipcode'];

        //重新计算价格,并验证
        foreach ($aParam['aProduct']['iProductID'] as $key => $value) {
            if ($aProduct[$value][$this->aPriceKey[$aParam['aProduct']['iSex'][$key]]] * $aParam['aProduct']['iProductNumber'][$key] != $aParam['aProduct']['sTotalPrice'][$key]) {
                $aMsg = [
                    'msg' => '非法操作！',
                ];
                return $this->show404($aMsg, false);
            }
            $sProductAmount += $aParam['aProduct']['sTotalPrice'][$key];
        }

        //入库操作
        Model_OrderInfo::begin();
        //入orderinfo表
        if ($iOrderID = Model_OrderInfo::initOrder($iUserID, $iUserType, $iOrderType, $sConsignee, $sMobile, $sProductAmount, $aOrderParam, $sOrderCode)) {
            foreach ($aParam['aProduct']['iProductID'] as $key => $value) {
                $aOrderProductParam['iSex'] = $aParam['aProduct']['iSex'][$key];//选择的性别
                //入orderproduct表
                if (!Model_OrderProduct::initOrder($iOrderID, $aProduct[$value]['iProductID'], $aProduct[$value]['sProductName'], $aParam['aProduct']['iProductNumber'][$key], $aParam['aProduct']['sPrice'][$key], $aParam['aProduct']['sPrice'][$key] * $aParam['aProduct']['iProductNumber'][$key], Model_OrderInfo::ELECTRONICCARD, $aOrderProductParam)) {
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
        return $this->show404($aMsg, true);
    }

    //微信支付页面(已废弃)
    public function payAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['ordercode'])) {
            return $this->show404('你没有订购任何产品！', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['ordercode']);
        if ($aOrder['iPayStatus']) {
            return $this->show404('该订单已支付！', false);
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
            return $this->show404('订单不存在', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['ordercode']);
        if (!$aOrder['iPayStatus']) {
            return $this->show404('该订单未支付！', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        if (!empty($aOrderProduct)) {//组装卡号数据
            foreach ($aOrderProduct as $key => &$value) {
                if ($aOrder['iOrderType'] == Model_OrderInfo::ORDERTYPE_UPGRADE) {
                    if (count($aOrderProduct) != 1) {
                        return $this->show404('该订单有误！', false);
                    }
                    $aProductAttr = json_decode($aOrderProduct[0]['sProductAttr'], true);
                    if (empty($aProductAttr['iCardID'])) {
                        return $this->show404('该订单有误！', false);
                    }
                    $aCardParam['iAutoID'] = $aProductAttr['iCardID'];
                } else {
                    $aCardParam['iOPID'] = $value['iAutoID'];
                }

                $value['aCardList'] = Model_OrderCard::getAll($aCardParam);

                if (!empty($value['aCardList'])) {
                    foreach ($value['aCardList'] as $k => $val) {
                        $aCardProductParam['iStatus >'] = 0;
                        $aCardProductParam['iCardID'] = $val['iAutoID'];
                        $value['aCardList'][$k]['aProductList'] = Model_OrderCardProduct::getAll($aCardProductParam);
                        if (!empty($value['aCardList'][$k]['aProductList'])) {
                            foreach ($value['aCardList'][$k]['aProductList'] as $a => $b) {
                                $aProduct = Model_UserProductBase::getUserProductBase($b['iProductID'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
                                $value['aCardList'][$k]['aProductList'][$a] = array_merge($aProduct, $value['aCardList'][$k]['aProductList'][$a]);
                            }
                        }
                    }
                }
            }
        }
        $this->assign('aSex', Yaf_G::getConf('aSex', 'product'));
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('sTitle', '订单详情');
    }

    //支付post页面
    public function payPostAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['sOrderCode']) || empty($aParam['sProductAmount'])) {
            return $this->show404('参数有误', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['sOrderCode']);
        if (empty($aOrder)) {
            return $this->show404('没有对应的订单', false);
        }
        if ($aOrder['sProductAmount'] != $aParam['sProductAmount']) {
            return $this->show404('订单有误', false);
        }
        if ($aOrder['iPayStatus'] == 1) {
            return $this->show404('已支付过该订单', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        //检查订单中产品是否有效
        foreach ($aOrderProduct as $key => $value) {
            $aProduct = Model_UserProductBase::getUserProductBase($value['iProductID'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
            $aOrderProduct[$key]['iAttribute'] = $aProduct['iAttribute'];
            //检查是否存在该商品
            if (empty($aProduct)) {
                return $this->show404('订单中商品"' . $value['sProductName'] . '"不存在，支付失败!', false);
            }
            //检查是否下架
            if ($aProduct['iStatus'] != 1) {
                return $this->show404('订单中商品"' . $value['sProductName'] . '"已经下架，支付失败!', false);
            }
            //检查库存是否足够，虚拟卡暂时没有需求TODO

        }

        return $this->show404('订单有效，可以支付!', true);
    }

    //订单列表
    public function orderListAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aWhere = array(
            'iUserID' => $this->iCurrUserID,
            'iUserType' => Model_OrderInfo::PRESON,
            'iOrderStatus' => 1
        );
        $aOrder = Model_OrderInfo::getList($aWhere, $iPage);
        if (!empty($aOrder['aList'])) {
            foreach ($aOrder['aList'] as $key => $value) {
                //组装需要数据
                $aOrder['aList'][$key]['iTotalNum'] = Model_OrderProduct::getProductNumByOrderID($value['iOrderID']);
            }
        }
        $t = $this->getParam('t');
        if ($t) {
            return $this->show404($aOrder, true);
        }
        $this->assign('aOrderType', Yaf_G::getConf('aOrderType', 'order'));
        $this->assign('aPayStatus', Yaf_G::getConf('aPayStatus', 'order'));
        $this->assign('aOrder', $aOrder);
        $this->assign('sTitle', '我购买的体检套餐');
    }

    //取消订单
    public function cancelOrderAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['ordercode'])) {
            return $this->show404('订单不存在', false);
        }
        $aOrder = Model_OrderInfo::getOrderByOrderCode($aParam['ordercode']);
        if (!empty($aOrder['iPayStatus'])) {
            return $this->show404('该订单已支付，不能取消！', false);
        }
        if($aOrder['iUserType'] == Model_OrderInfo::PRESON && $aOrder['iUserID'] == $this->iCurrUserID) {
            $aOrderParam['iOrderID'] = $aOrder['iOrderID'];
            $aOrderParam['iOrderStatus'] = 2;//取消
            if (Model_OrderInfo::updData($aOrderParam)) {
                return $this->show404('取消成功！', true);
            } else {
                return $this->show404('取消失败，请稍后再试！', false);
            }
        } else {
            return $this->show404('不能操作他人订单！', false);
        }
    }

    //关于我们
    public function aboutAction()
    {
        $this->assign('sTitle', '关于我们');
    }

    //合作伙伴
    public function partnerAction()
    {
        $this->assign('sTitle', '合作伙伴');
    }
}