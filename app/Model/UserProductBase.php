<?php

class Model_UserProductBase extends Model_Base
{
    const TABLE_NAME = 't_user_product_base';
    const COMPANYNAME = 'registers';//用户所属公司的用户名，定死的

    /**
     * 获取客户所看到的基础产品信息
     * @param $iProductID
     * @param $iUserID
     * @param $iChannelType
     * @param $iChannelID
     * @param bool $sCode 传的是code或产品id
     * @return array
     */
    public static function getUserProductBase($iProductID,$iUserID,$iChannelType,$iChannelID,$sCode=false)
    {
        if ($sCode) {
            $aProduct = $aBaseProduct = Model_Product::getProductByCode($iProductID);
            $iProductID = $aProduct['iProductID'];
        } else {
            $aProduct = $aBaseProduct =Model_Product::getDetail($iProductID);
        }
        if (empty($aProduct)) {
            return [];
        }
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        $aUserProductBase = self::getRow(array(
            'where' => $aWhere
        ));
        if (empty($aUserProductBase)) {//如果没有，读渠道的价格,再读拓展产品价格
            $ProductChannel = Model_ProductChannel::getData($iProductID,$iChannelType,$iChannelID);
            if (empty($ProductChannel)) {
                return $aProduct;
            } else {
                $aProduct = array_merge($aProduct,$ProductChannel);
            }
        } else {
            $aProduct = array_merge($aProduct,$aUserProductBase);
        }
        //价格特殊处理
        if ($aProduct['iManPrice'] == $aProduct['iWomanPrice1'] && $aProduct['iManPrice'] == $aProduct['iWomanPrice2'])
        {
            $aProduct['iPrice'] = $aProduct['iManPrice'];
            //是否有性别需求
            $aProduct['iNeedSex'] = 0;
        } else {
            $aPrice = array($aProduct['iManPrice'],$aProduct['iWomanPrice1'],$aProduct['iWomanPrice2']);
            sort($aPrice);
            $aProduct['iPrice'] = $aPrice[0].'-'.$aPrice[2];
            //是否有性别需求
            $aProduct['iNeedSex'] = 1;
        }
        //bug,用基础信息中的status，判断是否已发布
        $aProduct['iStatus'] = $aBaseProduct['iStatus'];
        return $aProduct;
    }

    /**
     * 判断是否已添加渠道信息
     * @param $iProductID
     * @param $iUserID
     * @param $iChannelType
     * @param $iChannelID
     * @return int
     */
    public static function getUserHasData($iProductID,$iUserID,$iChannelType,$iChannelID)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }
}