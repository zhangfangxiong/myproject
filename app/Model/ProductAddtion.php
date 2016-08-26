<?php

class Model_ProductAddtion extends Model_Base
{
    const TABLE_NAME = 't_product_addtion';

    const BASEPRODUCT = 1;//基础产品
    const EXPANDPRODUCT = 2;//拓展产品

    //产品包含的所有加项
    public static function getProductAddtions($iProductID, $iType, $iStatus = null)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iType' => $iType
        );
        if ($iStatus === null) {
            $aWhere['iStatus >'] = 0;
        } else {
            $aWhere['iStatus'] = $iStatus;
        }
        return self::getAll(array(
            'where' => $aWhere
        ));
    }

    //判断产品或加项是否含有某加项
    public static function getDataByAddtionID($iProductID,$iAddtionID, $iType)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iType' => $iType,
            'iAddtionID' => $iAddtionID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //获取所有包含某指定加项的产品
    public static function getDataByAddtions($aAddtionIDs,$iType,$iStatus = null)
    {
        $aWhere = array(
            'iAddtionID IN' => $aAddtionIDs,
            'iType' => $iType,
        );
        if ($iStatus===null) {
            $aWhere['iStatus >'] = 0;
        } else {
            $aWhere['iStatus'] = $iStatus;
        }
        return self::getAll(array(
            'where' => $aWhere
        ));
    }
}