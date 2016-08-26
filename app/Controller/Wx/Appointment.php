<?php

/**
 * 提供微信预约服务
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/06/01
 * Time: 下午2:32
 */
class Controller_Wx_Appointment extends Controller_Wx_Base
{
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        parent::actionBefore();
    }

    //我的体验卡列表
    public function cardListAction()
    {
        $aPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aCard = Model_OrderCard::getUserCardList($this->iCurrUserID, $aPage);
        if (!empty($aCard['aList'])) {
            //组装需要的数据
            foreach ($aCard['aList'] as $key => &$value) {
                $aCardProductParam['iStatus >'] = 0;
                $aCardProductParam['iBookStatus NOT'] = 4;//作废的不取
                $aCardProductParam['iCardID'] = $value['iAutoID'];
                $aCardProduct = Model_OrderCardProduct::getAll($aCardProductParam);
                $aAttribute = Yaf_G::getConf('aAttribute', 'product');
                $aStatus = Yaf_G::getConf('status', 'physical');
                $value['sAttribute'] = !empty($aAttribute[$value['iPhysicalType']]) ? $aAttribute[$value['iPhysicalType']] : '';


                if (!empty($aCardProduct)) {
                    foreach ($aCardProduct as $k => $v) {
                        $iChannelType = ($value['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $value['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;

                        $aProduct = Model_UserProductBase::getUserProductBase($v['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
                        $v['sProductCode'] = !empty($aProduct['sProductCode']) ? $aProduct['sProductCode'] : '';
                        $v['sBookStatus'] = !empty($aStatus[$v['iBookStatus']]) ? $aStatus[$v['iBookStatus']] : '';
                        //状态和有效期的特殊处理
                        $v['sStartDate'] = max($aProduct['sStartDate'],$value['sStartDate']);
                        $v['sEndDate'] = $aProduct['sEndDate'] != '0000-00-00' && $value['sEndDate'] != '0000-00-00'  ? min($aProduct['sEndDate'],$value['sEndDate']) : max($aProduct['sEndDate'],$value['sEndDate']);

                        $value['aDetail'][] = array_merge($aProduct, $v);
                    }
                } else {
                    unset($aCard['aList'][$key]);
                }
            }
        }
        $this->assign('aCard', $aCard);
        $t = $this->getParam('t');
        if ($t) {
            return $this->show404($aCard, true);
        }
        $this->assign('aPayType', Yaf_G::getConf('paytype', 'physical'));
        $this->assign('aOrderType', Yaf_G::getConf('aOrderType', 'order'));
        $this->assign('aSex', Yaf_G::getConf('aSex', 'product'));
        $this->assign('aCardUseType', Yaf_G::getConf('aUseType', 'card'));
        $this->assign('aStatus', Yaf_G::getConf('aStatus', 'cardproduct'));
        $this->assign('sTitle', '体验卡列表');
        $this->assign('iCartFoot', 1);
        $this->assign('iCodeType', Util_Verify::TYPE_CARD_ADD_IMAGE_WX);
    }

    //ajax添加体检卡
    public function addCardAction()
    {
        $sCardNum = $this->getParam('cardnum') ? $this->getParam('cardnum') : '';
        $aCard = Model_OrderCard::getCardByCode($sCardNum);
        $aSex = Yaf_G::getConf('aSex');
        if (empty($sCardNum) || empty($aCard)) {
            return $this->show404('卡号不存在', false);
        }
        if ($aCard['iStatus'] != 1) {
            return $this->show404('该卡号无效！', false);
        }
        if ($aCard['iBindStatus'] == 1 || !empty($aCard['iUserID'])) {
            return $this->show404('该卡已被他人绑定！', false);
        }
        if ($aCard['iSex'] != $this->aUser['iSex']) {
            //性别不符
            $aCardProductID = Model_OrderCardProduct::getProductByCardIDs($aCard['iAutoID']);
            if (!empty($aCardProductID)) {
                $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
                foreach ($aCardProductID as $key => $value) {
                    $aProduct = Model_UserProductBase::getUserProductBase($value, $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
                    if (!empty($aProduct)) {
                        if (!empty($aProduct['iNeedSex'])) {
                            return $this->show404('该卡含有仅支持'.$aSex[$aCard['iSex']].'的产品套餐和您的性别不符！', false);
                        }
                    } else {
                        return $this->show404('该卡含有不符合你渠道的产品，请联系客服！', false);
                    }
                }
            } else {
                return $this->show404('该卡不含有效产品套餐，请联系客服！', false);
            }
        }
        //2,更改t_order_card表的状态
        $aAppointmentParam['iAutoID'] = $aCard['iAutoID'];
        $aAppointmentParam['iUserID'] = $this->iCurrUserID;
        $aAppointmentParam['iBindStatus'] = 1;
        if (Model_OrderCard::updData($aAppointmentParam)) {
            return $this->show404('添加成功！', true);
        }
        return $this->show404('添加失败，请稍后再试！', false);
    }

    //个人信息编辑
    public function userInfoEditAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
        $aProduct = Model_UserProductBase::getUserProductBase($aParam['pid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        if ($this->isPost()) {
            if (Model_Customer::updData($aParam)) {
                return $this->show404('个人信息编辑成功！', true);
            }
            return $this->show404('编辑失败!', false);
        } else {
            $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
            $this->assign('aParam', $aParam);
            $this->assign('aProduct', $aProduct);
            $this->assign('aCard', $aCard);
            $this->assign('aCitys', $aCitys);
            $this->assign('aSex', Yaf_G::getConf('aSex'));
            $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
            $this->assign('aProductType', Yaf_G::getConf('aType', 'product'));
            $this->assign('sTitle', '个人信息编辑');
            $this->assign('iHomeIcon', 0);
        }
    }

    public function mapAction()
    {
        $iStorID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        if (empty($iStorID)) {
            return $this->show404('参数有误！', false);
        }
        $aStore = Model_Store::getDetail($iStorID);
        if (empty($aStore)) {
            return $this->show404('门店不存在！', false);
        }
        $this->assign('aStore', $aStore);
        $this->assign('sTitle', '门店地图');
        $this->assign('iCartFoot', 1);
    }

    //门店列表
    public function storeListAction()
    {
        $aParam = $this->getParams();
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('id', $aParam['id']);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('sTitle', '体检网点查询');
        //$this->assign('iHomeIcon', 0);
    }

    //预约选择门店
    public function reserveStoreAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
        $aProduct = Model_UserProductBase::getUserProductBase($aCardProduct['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        $sNeedPrice = '0.00';

        if (!empty($aParam['upid'])) {
            $aUpProduct = Model_UserProductBase::getUserProductBase($aParam['upid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);//升级产品
            if (empty($aUpProduct)) {
                return $this->show404('体检卡对应升级产品不存在', false);
            }
            $sNeedPrice = $aUpProduct[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
        }
        //个人支付的未支付状态的体检计划和体检产品
        if (($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_PERSON && empty($aCardProduct['iPayStatus'])) {
            $sNeedPrice = $aProduct[$this->aPriceKey[$aCard['iSex']]];
        }

        $aUser = Model_Customer::getDetail($this->iCurrUserID);
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('aParam', $aParam);
        $this->assign('aProduct', $aProduct);
        $this->assign('aUpProduct', !empty($aUpProduct) ? $aUpProduct : $aProduct);
        $this->assign('iChannelType', $iChannelType);
        $this->assign('aCard', $aCard);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aUser', $aUser);
        $this->assign('aSex', Yaf_G::getConf('aSex'));
        $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
        $this->assign('sTitle', '2,选择体检门店（共三步）');
        $this->assign('sNeedPrice', $sNeedPrice);
        $this->assign('iHomeIcon', 0);
    }

    //提交体检预约
    public function reserveCommitAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['sid']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        if (!empty($aCardProduct['iBookStatus']) && $aCardProduct['iBookStatus'] != 3 && $aCardProduct['iBookStatus'] != 6 && ($aCardProduct['iStoreID'] != $aParam['sid'])) {
            return $this->show404('你已经预约过门店，请取消预约后再更换门店', false);
        }
        if ($aCardProduct['iBookStatus'] == 3 && $aCardProduct['iLastProductID'] > 0 && empty($aCardProduct['iCheckRefund'])) {
            return $this->show404('该产品升级过，退订后还未退款，不能预约', false);
        }

        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;

        $aProduct = Model_UserProductBase::getUserProductBase($aParam['pid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);

        //状态和有效期的特殊处理
        $aProduct['sStartDate'] = max($aProduct['sStartDate'],$aCard['sStartDate']);
        $aProduct['sEndDate'] = $aProduct['sEndDate'] != '0000-00-00' && $aCard['sEndDate'] != '0000-00-00'  ? min($aProduct['sEndDate'],$aCard['sEndDate']) : max($aProduct['sEndDate'],$aCard['sEndDate']);

        if ($aProduct['iStatus'] != 1) {
            //return $this->show404('该产品未发布不能预约，请联系客服', false);
        }
        $iTime = time();

        if ( (($aProduct['sStartDate']>date('Y-m-d',$iTime) && $aProduct['sStartDate'] != '0000-00-00') || ($aProduct['sEndDate'] != '0000-00-00' && $aProduct['sEndDate'] < date('Y-m-d',$iTime)))) {
            return $this->show404('该卡或卡内产品不在有效期内，请联系客服', false);
        }

        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        //预约性别判断(当前性别,这个性别在base做估处理和卡买的性别不符，且三个价格不一样的，不能去预约)
        if ($this->aUser['iSex'] != $aCard['iSex'] && !empty($aProduct['iNeedSex'])) {
            return $this->show404('该卡有性别限制，且和你购买的性别不符，请联系客服', false);
        }
        $aStore = Model_Store::getDetail($aParam['sid']);
        if (empty($aStore)) {
            return $this->show404('门店不存在', false);
        }
        if (!empty($aParam['upid'])) {
            $aUpGrade = Model_UserProductBase::getUserProductBase($aParam['upid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);//升级产品
            if (empty($aUpGrade)) {
                return $this->show404('体检卡对应升级产品不存在', false);
            }
        }
        $iProductID = !empty($aParam['upid']) ? $aParam['upid'] : $aParam['pid'];
        $aProductStore = Model_UserProductStore::getUserHasStore($iProductID, $aCard['iCompanyID'], $aParam['sid'], $iChannelType, $this->aUser['iChannel'], true);
        if (empty($aProductStore)) {
            return $this->show404('该门店不存在该产品', false);
        }

        $aSupplier = Model_Type::getDetail($aStore['iSupplierID']);
        if (empty($aSupplier['sCode'])) {
            return $this->show404('供应商代码不存在，请联系管理员', false);
        }
        $aReserveStatus = Yaf_G::getConf('aStatus', 'suppliers');

        if ($this->isPost()) {
            //先判断是否个人支付的未支付状态的体检计划和体检产品
            if (($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_PERSON && empty($aCardProduct['iPayStatus'])) {

                if ($aCardProduct['iAttribute'] == 2) {
                    return $this->show404('入职套餐不能升级', false);
                }

                $sNeedPrice = $aProduct[$this->aPriceKey[$aCard['iSex']]];
                //判断该卡的产品是否已经有支付订单
                if (!empty($aCardProduct['iOrderID'])) {
                    $aOrder = Model_OrderInfo::getDetail($aCardProduct['iOrderID']);
                    if (!empty($aOrder['sOrderCode'])) {
                        return $this->show404('pay', false, $aOrder['sOrderCode']);
                    }
                }
                //入库操作
                Model_OrderInfo::begin();
                //入orderinfo表
                $sOrderCode = Model_OrderInfo::initOrderCode(Model_OrderInfo::PRESON);
                $aUser = Model_Customer::getDetail($this->iCurrUserID);
                $sConsignee = $aUser['sRealName'];
                $sMobile = $aUser['sMobile'];
                $aOrderParam['sEmail'] = $aUser['sEmail'];
                $aOrderParam['iPlanID'] = $aCard['iPlanID'];
                $aOrderParam['iShippingStatus'] = $aOrderProductParam['iShippingStatus'] = 2;//已发货
                $aOrderParam['iShippingTime'] = $aOrderProductParam['iShippingTime'] = time();
                if ($iOrderID = Model_OrderInfo::initOrder($this->iCurrUserID, Model_OrderInfo::PRESON, $aCard['iOrderType'], $sConsignee, $sMobile, $sNeedPrice, $aOrderParam, $sOrderCode)) {
                    $aOrderProductParam['iSex'] = $aCard['iSex'];//选择的性别
                    $aOrderProductParam['sProductAttr']['iCardID'] = $aParam['id'];//卡号ID
                    $aOrderProductParam['sProductAttr']['iCardProductID'] = $aCardProduct['iAutoID'];//卡对应产品的autoid
                    $aOrderProductParam['sProductAttr']['sProductPrice'] = $aProduct[$this->aPriceKey[$aCard['iSex']]];
                    $aOrderProductParam['sProductAttr']['iStoreID'] = $aParam['sid'];//要预约的门店
                    //入orderproduct表
                    if ($sOrderProductID = Model_OrderProduct::initOrder($iOrderID, $aCardProduct['iProductID'], $aCardProduct['sProductName'], 1, $sNeedPrice, $sNeedPrice, $aCard['iOrderType'], $aOrderProductParam)) {

                        //体检卡产品处理（这里把订单号带到卡表即可）
                        $aOrderCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
                        $aOrderCardProductParam['iOPID'] = $sOrderProductID;
                        $aOrderCardProductParam['iOrderID'] = $iOrderID;

                        if (Model_OrderCardProduct::updData($aOrderCardProductParam)) {
                            Model_OrderInfo::commit();
                            return $this->show404('pay', false, $sOrderCode);
                        } else {
                            Model_OrderInfo::rollback();
                            return $this->show404('卡产品状态更新失败，请稍后重试！', false);
                        }
                    } else {
                        Model_OrderInfo::rollback();
                        return $this->show404('生成订单产品失败，请稍后重试！', false);
                    }
                } else {
                    Model_OrderInfo::rollback();
                    return $this->show404('生成订单失败，请稍后重试！', false);
                }
            } elseif (!empty($aParam['upid']) && $aParam['upid'] != $aParam['pid']) {//再判断是否升级产品

                //判断该卡的产品是否已经有支付订单（这个字段是升级订单独有，不能在这里把订单号带到卡表，会覆盖原产品订单ID，需要等支付后）
                if (!empty($aCardProduct['iPayOrderID'])) {
                    $aOrder = Model_OrderInfo::getDetail($aCardProduct['iPayOrderID']);
                    if (!empty($aOrder['sOrderCode'])) {
                        return $this->show404('pay', false, $aOrder['sOrderCode']);
                    }
                }

                if (($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_COMPANY) {

                } else {
                    //找出对应的原产品订单
                    $aOrderProductLast = Model_OrderProduct::getDetail($aCardProduct['iOPID']);

                    if (empty($aOrderProductLast)) {
                        return $this->show404('原产品订单不存在！', false);
                    }
                    //找出对应orderinfo中父订单
                    $aOrderInfoLast = Model_OrderInfo::getDetail($aOrderProductLast['iOrderID']);
                    if (empty($aOrderInfoLast)) {
                        return $this->show404('原订单不存在！', false);
                    }
                    $aOrderParam['iParentOrderID'] = $aOrderInfoLast['iOrderID'];
                }
                if (empty($aCardProduct['iPayStatus']) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_PERSON) {
                    return $this->show404('当前卡未支付，不能升级！', false);
                }

                if (!empty($aCardProduct['iLastProductID'])) {
                    return $this->show404('当前卡已升级过，不能多次升级！', false);
                }
                $sNeedPrice = $aUpGrade[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
                $aUser = Model_Customer::getDetail($this->iCurrUserID);
                $sConsignee = $aUser['sRealName'];
                $sMobile = $aUser['sMobile'];
                $aOrderParam['sEmail'] = $aUser['sEmail'];


                //入库操作
                Model_OrderInfo::begin();
                //入orderinfo表
                $sOrderCode = Model_OrderInfo::initOrderCode(Model_OrderInfo::PRESON);
                //if ($iOrderID=1){
                if ($iOrderID = Model_OrderInfo::initOrder($this->iCurrUserID, Model_OrderInfo::PRESON, Model_OrderInfo::ORDERTYPE_UPGRADE, $sConsignee, $sMobile, $sNeedPrice, $aOrderParam, $sOrderCode)) {
                    $aOrderProductParam['iSex'] = $aCard['iSex'];//选择的性别
                    $aOrderProductParam['sProductAttr']['iCardID'] = $aParam['id'];//卡号ID
                    $aOrderProductParam['sProductAttr']['iCardProductID'] = $aCardProduct['iAutoID'];//卡对应产品的autoid
                    $aOrderProductParam['sProductAttr']['iLastProductID'] = $aProduct['iProductID'];//升级前的产品ID
                    $aOrderProductParam['sProductAttr']['iLastProductName'] = $aProduct['sProductName'];//升级前的产品名称
                    $aOrderProductParam['sProductAttr']['iLastOPID'] = $aCard['iOPID'];//升级前opid
                    $aOrderProductParam['sProductAttr']['iLastOrderID'] = $aCard['iOrderID'];//升级前orderID

                    $aOrderProductParam['sProductAttr']['sProductPrice'] = $aProduct[$this->aPriceKey[$aCard['iSex']]];
                    $aOrderProductParam['sProductAttr']['iStoreID'] = $aParam['sid'];//要预约的门店
                    //入orderproduct表
                    //if ($sOrderProductID=2){
                    if ($sOrderProductID = Model_OrderProduct::initOrder($iOrderID, $aUpGrade['iProductID'], $aUpGrade['sProductName'], 1, $sNeedPrice, $sNeedPrice, Model_OrderInfo::ORDERTYPE_UPGRADE, $aOrderProductParam)) {

                        //体检卡产品处理
                        $aOrderCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
                        $aOrderCardProductParam['iPayOrderID'] = $iOrderID;
                        $aOrderCardProductParam['iBookStatus'] = 0;
                        if (Model_OrderCardProduct::updData($aOrderCardProductParam)) {
                            Model_OrderInfo::commit();
                            return $this->show404('upgrade', false, $sOrderCode);
                        } else {
                            Model_OrderInfo::rollback();
                            return $this->show404('卡产品状态更新失败，请稍后重试！', false);
                        }
                    } else {
                        Model_OrderInfo::rollback();
                        return $this->show404('生成订单失败，请稍后重试！', false);
                    }
                } else {
                    Model_OrderInfo::rollback();
                    return $this->show404('生成订单失败，请稍后重试！', false);
                }
            }


            $aCardProductParam['iPreStatus'] = 0;
            //有API接口的调用预约接口
            $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
            if (!empty($aHasApiConf[$aSupplier['sCode']])) {
                $aStoreCode = Model_StoreCode::getData($iProductID, $aStore['iStoreID'], $aCard['iSex']);
                if (empty($aStoreCode)) {
                    return $this->show404('产品代码不存在，请联系管理员', false);
                }
                if (!empty($aParam['type'])) {//预约改期
                    if ($aSupplier['sCode'] == 'ruici') {
                        //return $this->show404('瑞慈供应商的门店不支持预约改期', false);
                    }
                    $aReserve = Model_Supplier::reReserveDate($aSupplier['sCode'],$aStore['sStoreCode'],$aStoreCode['sCode'],$aParam['iOrderTime'],$aCard,$aCardProduct);
                    if (empty($aReserve)) {
                        return $this->show404('接口预约改期失败，请联系管理员', false);
                    }
                } else {
                    $aReserve = Model_Supplier::reserve($aSupplier['sCode'],$aStore['sStoreCode'],$aStoreCode['sCode'],$aParam['iOrderTime'],$aCard,$aCardProduct);
                    if (empty($aReserve)) {
                        $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
                        $aCardProductParam['iBookStatus'] = 6;//预约失败的情况
                        $aCardProductParam['iReserveTime'] = time();
                        $aCardProductParam['iOrderTime'] = strtotime($aParam['iOrderTime']);
                        $aCardProductParam['iStoreID'] = $aParam['sid'];
                        Model_OrderCardProduct::updData($aCardProductParam);
                        return $this->show404('接口预约失败，请联系管理员', false);
                    }
                }

                $aCardProductParam['iPreStatus'] = 1;
            }

            $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
            $aCardProductParam['iOrderTime'] = strtotime($aParam['iOrderTime']);
            $aCardProductParam['iGetReportType'] = $aParam['iGetReportType'];
            $aCardProductParam['iStoreID'] = $aParam['sid'];
            $aCardProductParam['iBookStatus'] = 1;
            $aCardProductParam['iSex'] = !empty($aProduct['iNeedSex']) ? $aCard['iSex'] : $this->aUser['iSex'];//预约的时候把性别带进去，结算的时候要用这个性别(通用卡要传当前性别，性别要传卡的性别)
            $aCardProductParam['iReserveTime'] = time();
            $aOrderCardProductParam['iUseStatus'] = 1;//个人支付的体检计划也只能在这里把使用状态改为已用
            $aCardProductParam['iPlat'] = Model_OrderCardProduct::RESERVEPLAT_WX;
            Model_OrderCardProduct::begin();
            if (Model_OrderCardProduct::updData($aCardProductParam)) {
                if ($aCard['iUseType'] == Model_OrderCard::USETYPE_OR) {
                    if (Model_OrderCardProduct::updateCardProductStatus($aParam['id'], $aParam['pid'])) {
                        Model_OrderCardProduct::commit();
                        if ($aCardProductParam['iPreStatus'] = 1) {
                            Model_OrderCardProduct::sendMailMsg($aCardProduct['iAutoID'], $this->iCurrUserID);
                        }   
                        return $this->show404('预定成功!待供应商确认后您可以去体检', true);
                    } else {
                        Model_OrderCardProduct::rollback();
                        return $this->show404('预定失败，请稍后再试', false);
                    }
                } else {
                    Model_OrderCardProduct::commit();
                    if ($aCardProductParam['iPreStatus'] = 1) {
                        Model_OrderCardProduct::sendMailMsg($aCardProduct['iAutoID'], $this->iCurrUserID);
                    }   
                    return $this->show404('预定成功!待供应商确认后您可以去体检', true);
                }
            } else {
                Model_OrderCardProduct::rollback();
                return $this->show404('预定失败，请稍后再试', false);
            }
        } else {
            $aReserDateList = Model_Supplier::getReserveTimeByCode($aSupplier['sCode'],0,$aStore['sStoreCode'], $aCard['iPhysicalType']);
            $sTitle = empty($aParam['type']) ? '3,提交体检预约（共三步）' : '预约改期';
            $this->assign('aReserDateList', $aReserDateList);
            $this->assign('aReserveStatus', $aReserveStatus);
            $this->assign('sSupplierCode', $aSupplier['sCode']);
            $this->assign('sStoreCode', $aStore['sStoreCode']);
            $this->assign('aReserveStatus', $aReserveStatus);
            $this->assign('aParam', $aParam);
            $this->assign('aProduct', $aProduct);
            $this->assign('aUpProduct', !empty($aUpProduct) ? $aUpProduct : $aProduct);
            $this->assign('aCard', $aCard);
            $this->assign('aStore', $aStore);
            $this->assign('aSex', Yaf_G::getConf('aSex'));
            $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
            $this->assign('aProductType', Yaf_G::getConf('aType', 'product'));
            $this->assign('sTitle', $sTitle);
            $this->assign('iHomeIcon', 0);
        }
    }

    //获取上一月，下一月的可预约日期（ajax）
    public function getReserveDateAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['sSupplierCode']) || empty($aParam['sStoreCode']) || empty($aParam['sdate'])) {
            return $this->showMsg('参数不全', false);
        }
        $aReserDateList = Model_Supplier::getReserveTimeByCode($aParam['sSupplierCode'], $aParam['sdate'],$aParam['sStoreCode'], $aParam['iPhysicalType']);
        return $this->showMsg($aReserDateList, true);
    }

    //预约取消
    public function reservecancelAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        if ($aProduct['iBookStatus'] != 1) {
            return $this->show404('该体检卡还未预约，不能取消预约', false);
        }
        $aStore = Model_Store::getDetail($aProduct['iStoreID']);

        $aProductParam['iAutoID'] = $aProduct['iAutoID'];
        $aProductParam['iBookStatus'] = 3;
        $aProductParam['iPreStatus'] = 0;
        $aProductParam['iOrderTime'] = 0;
        $aProductParam['iStoreID'] = 0;
        $aProductParam['iUseStatus'] = 0;
        $aProductParam['iCanncalReserveTime'] = time();
        $aProductParam['iReserveTime'] = 0;

        //有API接口的调用预约接口
        $aSupplier = Model_Type::getDetail($aStore['iSupplierID']);
        if (empty($aSupplier['sCode'])) {
            return $this->show404('供应商代码不存在，请联系管理员', false);
        }
        $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
        if (!empty($aHasApiConf[$aSupplier['sCode']])) {
            $aReserve = Model_Supplier::cancalReserve($aSupplier['sCode'],$aStore['sStoreCode'],$aProduct, $aCard['iPhysicalType']);
            if (empty($aReserve)) {
                return $this->show404('取消预约接口失败，请联系管理员', false);
            }
        }
        Model_OrderCardProduct::begin();
        if (Model_OrderCardProduct::updData($aProductParam)) {
            if ($aCard['iUseType'] == Model_OrderCard::USETYPE_OR) {
                if (Model_OrderCardProduct::updateCardProductStatus($aParam['id'], $aParam['pid'], 1)) {
                    Model_OrderCardProduct::commit();
                    return $this->show404('取消成功', true);
                } else {
                    Model_OrderCardProduct::rollback();
                    return $this->show404('取消失败，请稍后再试', false);
                }
            } else {
                Model_OrderCardProduct::commit();
                return $this->show404('取消成功', true);
            }
        } else {
            Model_OrderCardProduct::rollback();
            return $this->show404('取消失败，请稍后再试', false);
        }
    }

    //体检预约详情
    public function reserveDetailAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;

        $aProduct = Model_UserProductBase::getUserProductBase($aParam['pid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        if (empty($aCardProduct['iBookStatus'])) {
            return $this->show404('非法操作', false);
        }
        $aStore = Model_Store::getDetail($aCardProduct['iStoreID']);
        if (empty($aStore)) {
            return $this->show404('门店不存在', false);
        }
        $aProductStore = Model_UserProductStore::getUserHasStore($aParam['pid'], $aCard['iCompanyID'], $aCardProduct['iStoreID'], $iChannelType, $this->aUser['iChannel'], true);
        if (empty($aProductStore)) {
            return $this->show404('该门店不存在该产品', false);
        }

        $aUser = Model_Customer::getDetail($this->iCurrUserID);
        $this->assign('aParam', $aParam);
        $this->assign('aCardProduct', $aCardProduct);
        $this->assign('aProduct', $aProduct);
        $this->assign('aCard', $aCard);
        $this->assign('aUser', $aUser);
        $this->assign('aStore', $aStore);
        $this->assign('aSex', Yaf_G::getConf('aSex'));
        $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
        $this->assign('aProductType', Yaf_G::getConf('aType', 'product'));
        $this->assign('aPreStatus', Yaf_G::getConf('prestatus', 'physical'));
        $this->assign('sTitle', '预约明细');
        $this->assign('iHomeIcon', 0);
        $aWeekarray = array("日", "一", "二", "三", "四", "五", "六");
        $this->assign('aWeekarray', $aWeekarray);
    }

    //升级支付（已废弃）
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
            return $this->show404('该订单已支付不能重新支付！', false);
        }
        $total_fee = $aOrder['sProductAmount'];
        //判断订单是否已支付
        if (Model_Pay::checkPay($sOrderCode)) {
            return $this->show404('该订单已经支付，订单支付状态有误，请联系管理员！', false);
        }
        $aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
        if (empty($aOrderProduct) || count($aOrderProduct) != 1) {
            return $this->show404('该订单产品信息有误，请联系管理员！', false);
        }
        $aProductAttr = json_decode($aOrderProduct[0]['sProductAttr'], true);
        $aData = Payment_Weixin::jspay($this->aUser['sOpenID'], $sOrderCode, $body, $total_fee);
        $this->assign('aOrder', $aOrder);
        $this->assign('aOrderProduct', $aOrderProduct);
        $this->assign('aProductAttr', $aProductAttr);
        $this->assign('aData', $aData);
        $this->assign('sTitle', '套餐升级支付');
        $this->assign('iHomeIcon', 0);
    }

    //升级详情页
    public function upgradeDetailAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid']) || empty($aParam['upid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;

        $aProduct = Model_UserProductBase::getUserProductBase($aCardProduct['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);//原产品
        if (empty($aProduct)) {
            return $this->show404('体检卡对应产品不存在', false);
        }
        $aUpgrade = Model_UserProductBase::getUserProductBase($aParam['upid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);//升级产品
        if (empty($aUpgrade)) {
            return $this->show404('体检卡对应升级产品不存在', false);
        }
        $aUpgrade['iNeedPrice'] = $aUpgrade[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];


        //获取门店数目
        $aProductStore = Model_UserProductStore::getUserProductStore($aUpgrade['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        $aTmp = [];
        foreach ($aProductStore as $key => $value) {
            $aTmp[$value['iAutoID']] = $value['iStoreID'];
        }
        $aProductStore = $aTmp;
        $aUpgrade['iStoreNum'] = count($aProductStore);
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
            $aUpgrade['sSupplierName'] = $sSupplierName;
        }
        //获取该产品包含的单项
        $aItem = Model_ProductItem::getProductItems($aUpgrade['iProductID'], Model_ProductItem::EXPANDPRODUCT, null, true);
        if (!empty($aItem)) {
            $sItem = implode(',', $aItem);
            $aItemCat = Model_Item::setGroupByCategory($sItem, true);
        } else {
            $aItemCat = [];
        }
        //获取原产品包含的单项
        $aItem1 = Model_ProductItem::getProductItems($aProduct['iProductID'], Model_ProductItem::EXPANDPRODUCT, null, true);
        //获取两个产品的单项合集
        $aTmp = array_merge($aItem, $aItem1);
        $aItems = [];
        if (!empty($aTmp)) {
            $aItemsDetailParam['iStatus'] = 1;
            $aItemsDetailParam['iItemID IN'] = array_values($aTmp);
            $aItems = Model_Item::getAll($aItemsDetailParam, true);
        }
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('aParam', $aParam);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aItemCat', $aItemCat);
        $this->assign('aItems', $aItems);
        $this->assign('aItem', $aItem);
        $this->assign('aItem1', $aItem1);
        $this->assign('sTitle', '体检套餐升级详情');
        $this->assign('iHomeIcon', 0);
        $this->assign('aUpgrade', $aUpgrade);
        $this->assign('iCartFoot', 1);
        $this->assign('hassnotoremenu', 1);
    }


    //预约详情页
    public function detailAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;

        $aProduct = Model_UserProductBase::getUserProductBase($aCardProduct['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        //获取门店数目
        $aProductStore = Model_UserProductStore::getUserProductStore($aProduct['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        $aTmp = [];
        foreach ($aProductStore as $key => $value) {
            $aTmp[$value['iAutoID']] = $value['iStoreID'];
        }
        $aProductStore = $aTmp;
        $aProduct['iStoreNum'] = count($aProductStore);
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
            $aProduct['sSupplierName'] = $sSupplierName;
        }
        //获取该产品包含的单项
        $aItem = Model_ProductItem::getProductItems($aProduct['iProductID'], Model_ProductItem::EXPANDPRODUCT, null, true);
        if (!empty($aItem)) {
            $sItem = implode(',', $aItem);
            $aItemCat = Model_Item::setGroupByCategory($sItem, true);
        } else {
            $aItemCat = [];
        }
        //获取所有升级产品
        $aUpgrade = Model_UserProductUpgrade::getUserProductUpgrade($aProduct['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        $iTime = time();
        //组装升级产品所需数据
        if (!empty($aUpgrade)) {
            $aCardProducts = Model_OrderCardProduct::getProductByCardIDs($aParam['id']);//获取该卡中所有产品id
            foreach ($aUpgrade as $key => $value) {
                $aUpgradeDetail = Model_UserProductBase::getUserProductBase($value['iUpgradeID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
                //过滤用户级别已经添加过，且价格不满足条件的升级产品
                if ($aUpgradeDetail['iManPrice'] - $aProduct['iManPrice'] <= 0 || $aUpgradeDetail['iWomanPrice1'] - $aProduct['iWomanPrice1'] <= 0 || $aUpgradeDetail['iWomanPrice2'] - $aProduct['iWomanPrice2'] <= 0) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤未发布和已过期的产品
                if ($aUpgradeDetail['iStatus'] != 1 || ($aUpgradeDetail['sStartDate'] != '0000-00-00' && strtotime($aUpgradeDetail['sStartDate']) > $iTime) || ($aUpgradeDetail['sEndDate'] != '0000-00-00' && strtotime($aUpgradeDetail['sEndDate']) < $iTime)) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤该卡中所有已有的产品
                if (in_array($aUpgradeDetail['iProductID'], $aCardProducts)) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤通用卡升级成性别卡
                if ($aUpgradeDetail['iNeedSex']!=$aProduct['iNeedSex']) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                if (!empty($aUpgradeDetail)) {
                    $aUpgrade[$key]['sProductName'] = $aUpgradeDetail['sProductName'];
                    $aUpgrade[$key]['iPrice'] = $aUpgradeDetail[$this->aPriceKey[$aCard['iSex']]];
                    $aUpgrade[$key]['sProductCode'] = $aUpgradeDetail['sProductCode'];
                    $aUpgrade[$key]['sAlias'] = $aUpgradeDetail['sAlias'];
                    $aUpgrade[$key]['iNeedPrice'] = $aUpgradeDetail[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
                }                
            }
            
        }
        //价格显示当前性别的价格
        $aProduct['iPrice'] = $aProduct[$this->aPriceKey[$aCard['iSex']]];
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('id', $aParam['id']);
        $this->assign('pid', $aParam['pid']);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCard', $aCard);
        $this->assign('aCitys', $aCitys);
        $this->assign('aItemCat', $aItemCat);
        $this->assign('aUpgrade', $aUpgrade);
        $this->assign('sTitle', '1,浏览体检套餐详情（共三步）');
        $this->assign('iHomeIcon', 0);
        $this->assign('aCardProduct', $aCardProduct);
        $this->assign('aProduct', $aProduct);
        $this->assign('iCartFoot', 1);
        $this->assign('hassnotoremenu', 1);
    }
}