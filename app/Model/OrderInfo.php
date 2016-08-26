<?php

class Model_OrderInfo extends Model_Base
{
    const TABLE_NAME = 't_order_info';
    const ELECTRONICCARD = 1;//电子卡
    const REALCARD = 2;//实物卡
    const ORDERTYPE_PRODUCT = 3;//体检产品
    const ORDERTYPE_PRODUCT_PLAN = 4;//体检计划
    const ORDERTYPE_UPGRADE = 5;//产品升级
    const PRESON = 1;//个人
    const HR = 2;//hr

    //根据订单号获取订单
    public static function getOrderByOrderCode($sOrderCode)
    {
        $aWhere = array(
            'sOrderCode' => $sOrderCode,
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //生成订单号
    public static function initOrderCode($iType)
    {
        //生成规则未定？
        //E88-46634562
        $sOrderCode = 'E' . $iType . '-' . Util_Tools::passwdGen(8, 1);
        if (self::getOrderByOrderCode($sOrderCode)) {
            self::initOrderCode($iType);
        }
        return $sOrderCode;
    }

    //获取体检卡销售列表
    public static function getPhisycalList($aParam, $iPage, $sOrder = 'iOrderID DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
        $iPage = max($iPage, 1);
        $sOrder = 'ORDER BY ' . $sOrder;
        $sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        $sSQL = "select oi.* from " . self::TABLE_NAME . ' AS oi WHERE 1 = 1';
        $sCountSQL = "select count(*) as total from " . self::TABLE_NAME . ' AS oi WHERE 1 = 1';
        if (!empty($aParam)) {
            $sWhere = array();
            if (isset($aParam['iPayStatus']) && -1 != intval($aParam['iPayStatus'])) {
                $sWhere[] = " oi.iPayStatus = " . intval($aParam['iPayStatus']);
            }
            if (!empty($aParam['iPayChannel'])) {
                $sWhere[] = " oi.iPayChannel = " . $aParam['iPayChannel'];
            }
            if (!empty($aParam['iShippingStatus'])) {
                $sWhere[] = " oi.iShippingStatus = " . intval($aParam['iShippingStatus']);
            }
            if (!empty($aParam['sName'])) {
                $sWhere[] = " oi.sConsignee = '" . $aParam['sName'] . "'";
            }
            if (!empty($aParam['iOrderType'])) {
                if(-1 == $aParam['iOrderType']) {
                    $sWhere[] = " oi.iOrderType in(1, 2)";
                }else {
                    $sWhere[] = " oi.iOrderType = " . $aParam['iOrderType'];
                }
            }

            if (!empty($aParam['iUserType'])) {
                $sWhere[] = " oi.iUserType = " . $aParam['iUserType'];
            }

            if (!empty($aParam['iUserID'])) {
                $sWhere[] = " oi.iUserID = " . $aParam['iUserID'];
            }
            if (!empty($sWhere)) {
                $sWhere = implode(' and ', $sWhere);
                $sSQL .= ' and' . $sWhere;
                $sCountSQL .= ' and' . $sWhere;
            }
        }
        $sSQL .= ' ' . $sOrder . ' ' . $sLimit;

        $aRet['aList'] = self::getOrm()->query($sSQL);
        if (!empty($aRet['aList'])) {
            foreach ($aRet['aList'] as &$order) {
                $orderID = $order['iOrderID'];
                $order['iCardNum'] = Model_OrderProduct::getProductNumByOrderID($orderID);
            }
        }

        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            $ret = self::getOrm()->query($sCountSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam);
        }

        return $aRet;
    }

    /**
     * 下订单处理
     * @param $iUserID 用户id
     * @param $iUserType 用户类型,1=个人,2=hr
     * @param $iOrderType 订单类型,1=电子卡，2=实体卡
     * @param $sConsignee 收货人的姓名
     * @param $sMobile 收货人的手机
     * @param $sProductAmount 商品总金额
     * @param array $aParam 选填参数
     * @param string $sOrderCode 可以自己传也可以自动生成
     */
    public static function initOrder($iUserID, $iUserType, $iOrderType, $sConsignee, $sMobile, $sProductAmount, $aParam = array(), $sOrderCode = '')
    {
        $aOrderParam['sOrderCode'] = $sOrderCode ? $sOrderCode : self::initOrderCode($iUserType);
        $aOrderParam['iUserID'] = $iUserID;
        $aOrderParam['iUserType'] = $iUserType;
        $aOrderParam['iOrderType'] = $iOrderType;
        $aOrderParam['sConsignee'] = $sConsignee;
        $aOrderParam['sMobile'] = $sMobile;
        $aOrderParam['sProductAmount'] = $aOrderParam['sOrderAmount'] = $sProductAmount;//如遇打折，活动等，在下面做处理
        //以上为必填的

        if (!empty($aParam['iCardType'])) {
            $aOrderParam['iCardType'] = $aParam['iCardType'];
        }
        if (!empty($aParam['sAddress'])) {
            $aOrderParam['sAddress'] = $aParam['sAddress'];
        }
        if (!empty($aParam['sZipcode'])) {
            $aOrderParam['sZipcode'] = $aParam['sZipcode'];
        }
        if (!empty($aParam['sEmail'])) {
            $aOrderParam['sEmail'] = $aParam['sEmail'];
        }
        if (!empty($aParam['sRemark'])) {
            $aOrderParam['sRemark'] = $aParam['sRemark'];
        }
        if (!empty($aParam['iIfInv'])) {
            $aOrderParam['iIfInv'] = $aParam['iIfInv'];
        }
        if (!empty($aParam['sInvPayee'])) {
            $aOrderParam['sInvPayee'] = $aParam['sInvPayee'];
        }

        if (!empty($aParam['sOrderAmount'])) {
            $aOrderParam['sOrderAmount'] = $aParam['sOrderAmount'];
        }
        if (!empty($aParam['sDiscount'])) {
            $aOrderParam['sDiscount'] = $aParam['sDiscount'];
        }
        if (isset($aParam['iOrderStatus'])) {
            $aOrderParam['iOrderStatus'] = $aParam['iOrderStatus'];
        }
        if (!empty($aParam['iPlanID'])) {
            $aOrderParam['iPlanID'] = $aParam['iPlanID'];
        }
        if (!empty($aParam['iParentOrderID'])) {
            $aOrderParam['iParentOrderID'] = $aParam['iParentOrderID'];
        }

        //以上为选填

        return self::addData($aOrderParam);
    }

    /**
     * 发货后的处理（电子卡发邮件，实体卡寄出后点确认）
     */
    public static function shoppingSeccuss($iOrderID, $aOrderProduct)
    {

    }

    /**
     * 支付完成后的订单处理(体检产品和体检计划一开始就已经入卡库)
     * @param $iOrderID
     * @param $sPayOrderID
     * @param $iPayChannel
     * @param $sProductAmount
     * @param $aOrderProduct
     * @param $aParam
     * @return int
     */
    public static function paySeccuss($aOrder, $aOrderProduct, $sPayOrderID, $iPayChannel, $sProductAmount)
    {
        $iTime = time();
        //更新订单状态
        $aOrderParam['iPayStatus'] = 1;
        $aOrderParam['sPayOrderID'] = $sPayOrderID;//微信返回的支付订单号
        $aOrderParam['iPayTime'] = $iTime;
        $aOrderParam['iOrderID'] = $aOrder['iOrderID'];
        $aOrderParam['iPayChannel'] = $iPayChannel;
        $aOrderParam['sMoneyPaid'] = $sProductAmount;//已支付金额

        if ($aOrder['iOrderType'] == self::ELECTRONICCARD) {//电子卡
            $aOrderParam['iShippingStatus'] = $aOrderProductParam['iShippingStatus'] = 2;//已发货
            $aOrderParam['iShippingTime'] = $aOrderProductParam['iShippingTime'] = $iTime;
            self::begin();
            if (!self::updData($aOrderParam)) {
                self::rollback();
                return 0;
            }
            //ordercard表中新插入卡的数据
            foreach ($aOrderProduct as $key => $value) {
                $aOrderProductParam['iAutoID'] = $value['iAutoID'];
                if (!Model_OrderProduct::updData($aOrderProductParam)) {
                    self::rollback();
                    return 0;
                }
                for ($i = 0; $i < $value['iProductNumber']; $i++) {
                    $aCardParam['iSex'] = $value['iSex'];
                    $aCardParam['sProductName'] = $value['sProductName'];
                    $aCardProductParam['iPayStatus'] = $aCardParam['iStatus'] = $aCardParam['iSendStatus'] = 1;
                    //预约表中插入数据
                    $iCardID = Model_OrderCard::initCard($aOrder['iOrderType'], Model_OrderCard::PAYTYPE_PERSON, $aOrder['iUserID'], $aOrder['iUserType'], $value['iAutoID'], $aOrder['iOrderID'], $aCardParam);
                    if ($iCardID <= 0) {
                        self::rollback();
                        return 0;
                    } else {
                        //ordercardproduct表插入产品
                        $iCardProductID = Model_OrderCardProduct::initCardProduct($iCardID, $value['iProductID'], $value['sProductName'], $value['iAutoID'], $aOrder['iOrderID'],$aCardProductParam);
                        if ($iCardProductID <= 0) {
                            self::rollback();
                            return 0;
                        }
                    }
                }
            }
        } elseif ($aOrder['iOrderType'] == self::ORDERTYPE_PRODUCT || $aOrder['iOrderType'] == self::ORDERTYPE_PRODUCT_PLAN) {//体检产品，体检计划
            self::begin();
            if (!self::updData($aOrderParam)) {
                self::rollback();
                return -1;//订单状态更新失败
            }

            foreach ($aOrderProduct as $key => $value) {
                $aProductAttr = json_decode($value['sProductAttr'], true);

                //体检卡处理(不用处理体检卡)
                //体检卡产品处理
                $aOrderCardProductParam['iAutoID'] = $aProductAttr['iCardProductID'];
                $aOrderCardProductParam['iPayStatus'] = 1;

                if (Model_OrderCardProduct::updData($aOrderCardProductParam)) {
                    Model_OrderInfo::commit();
                    return 1;//成功
                } else {
                    Model_OrderInfo::rollback();
                    return -3;//卡产品状态更新失败
                }
            }
        } elseif ($aOrder['iOrderType'] == self::ORDERTYPE_UPGRADE) {//升级产品

            $aOrderParam['iShippingStatus'] = $aOrderProductParam['iShippingStatus'] = 2;//已发货
            $aOrderParam['iShippingTime'] = $aOrderProductParam['iShippingTime'] = $iTime;
            self::begin();
            if (!self::updData($aOrderParam)) {
                self::rollback();
                return -1;//订单状态更新失败
            }

            foreach ($aOrderProduct as $key => $value) {
                $aOrderProductParam['iAutoID'] = $value['iAutoID'];
                //更新订单产品表状态
                if (!Model_OrderProduct::updData($aOrderProductParam)) {
                    self::rollback();
                    return -2;//订单产品状态更新失败
                }

                $aProductAttr = json_decode($value['sProductAttr'],true);

                //体检卡处理(不用处理体检卡)
                //体检卡产品处理
                $aOrderCardProductParam['iAutoID'] = $aProductAttr['iCardProductID'];
                $aOrderCardProductParam['iOPID'] = $value['iAutoID'];
                $aOrderCardProductParam['iOrderID'] = $value['iOrderID'];
                $aOrderCardProductParam['iProductID'] = $value['iProductID'];
                $aOrderCardProductParam['sProductName'] = $value['sProductName'];
                $aOrderCardProductParam['iLastOPID'] = $aProductAttr['iLastOPID'];
                $aOrderCardProductParam['iLastProductID'] = $aProductAttr['iLastProductID'];
                $aOrderCardProductParam['iLastOrderID'] = $aProductAttr['iLastOrderID'];
                $aOrderCardProductParam['sLastProductName'] = $aProductAttr['iLastProductName'];
                $aOrderCardProductParam['iBookStatus'] = 0;//升级之后，退订状态已经要改成已预约，不然会预约不了
                if (Model_OrderCardProduct::updData($aOrderCardProductParam)) {
                    Model_OrderInfo::commit();
                    return 1;//成功
                } else {
                    Model_OrderInfo::rollback();
                    return -3;//卡产品状态更新失败
                }
            }
        }
        self::commit();
        return 1;
    }

    /**
     * 购卡通知
     * @return [type] [description]
     */
    public static function sendMailMsg ($aOrder, $type, $sProductName='')
    {   
        if ($aOrder['iUserType'] == 1) {
            $aUser = Model_CustomerNew::getDetail($aOrder['iUserID']);
        } else {
            $aUser = Model_User::getDetail($aOrder['iUserID']);
        }

        if ($type == 1) {
            $content = Yaf_G::getConf('paymsg', 'physical');
            $msg  = Yaf_G::getConf('paymsg', 'physical');
        
            $content = preg_replace('/\【员工姓名\】/', $aUser['sRealName'], $content);
            $content = preg_replace('/\【体检价格\】/', $aOrder['sProductAmount'], $content);
            $content = preg_replace('/\【体检套餐\】/', $sProductName, $content);
            $mailRes = Util_Mail::send($aUser['sEmail'], '体检产品支付成功通知', $content);

            $msg = preg_replace('/\【员工姓名\】/', $aUser['sRealName'], $msg);
            $msg = preg_replace('/\【体检价格\】/', $aOrder['sProductAmount'], $msg);
            $msg = preg_replace('/\【体检套餐\】/', $sProductName, $msg);
            $smsRes = Sms_Joying::sendBatch($aUser['sMobile'], $msg);
        } else {
            $content = Yaf_G::getConf('cardmail', 'physical');
            $msg  = Yaf_G::getConf('cardmsg', 'physical');

            $content = preg_replace('/\【员工姓名\】/', $aUser['sRealName'], $content);
            $mailRes = Util_Mail::send($aUser['sEmail'], '支付成功订单邮件', $content);

            $msg = preg_replace('/\【员工姓名\】/', $aUser['sRealName'], $msg);
            $smsRes = Sms_Joying::sendBatch($aUser['sMobile'], $msg);
        }
    }
}