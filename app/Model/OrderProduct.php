<?php

class Model_OrderProduct extends Model_Base
{
    const TABLE_NAME = 't_order_product';

    public static function getProductByOrderID($iOrderID)
    {
        $aWhere = array(
            'iOrderID' => $iOrderID,
        );
        return self::getAll(array(
            'where' => $aWhere
        ));
    }

    public static function getProductNumByOrderID($iOrderID)
    {
        $sSql = 'SELECT SUM(iProductNumber) as sum FROM `t_order_product` WHERE iOrderID ='.$iOrderID;
        return self::query($sSql,'one');
    }


    /**
     * 下订单处理
     * @param $iOrderID
     * @param $iProductID
     * @param $sProductName
     * @param $iProductNumber
     * @param $sProductPrice
     * @param $sTotalPrice
     * @param $iOrderType
     * @param array $aParam
     * @return int
     */
    public static function initOrder($iOrderID,$iProductID,$sProductName,$iProductNumber,$sProductPrice,$sTotalPrice,$iOrderType,$aParam = array())
    {
        $aOrderParam['iOrderID'] = $iOrderID;
        $aOrderParam['iProductID'] = $iProductID;
        $aOrderParam['sProductName'] = $sProductName;
        $aOrderParam['iOrderType'] = $iOrderType;
        $aOrderParam['iProductNumber'] = $iProductNumber;
        $aOrderParam['sProductPrice'] = $aOrderParam['sProductLastPrice']= $sProductPrice;//如遇打折，活动等，在下面做处理
        $aOrderParam['sTotalPrice'] = $sTotalPrice;
        //以上为必填的

        if (!empty($aParam['sProductLastPrice'])){
            $aOrderParam['sProductLastPrice'] = $aParam['sProductLastPrice'];
        }
        if (!empty($aParam['iCardType'])){
            $aOrderParam['iCardType'] = $aParam['iCardType'];
        }
        if (!empty($aParam['iPCard'])){
            $aOrderParam['iPCard'] = $aParam['iPCard'];
        }
        if (!empty($aParam['iUseType'])){
            $aOrderParam['iUseType'] = $aParam['iUseType'];
        }
        if (!empty($aParam['iSex'])){
            $aOrderParam['iSex'] = $aParam['iSex'];
        }
        if (!empty($aParam['sProductAttr'])){
            $aOrderParam['sProductAttr'] = json_encode($aParam['sProductAttr']);
        }
        //以上为选填
        return self::addData($aOrderParam);
    }

}