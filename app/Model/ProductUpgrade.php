<?php

class Model_ProductUpgrade extends Model_Base
{
    const TABLE_NAME = 't_product_upgrade';

    const BASEPRODUCT = 1;//基础产品
    const EXPANDPRODUCT = 2;//拓展产品

    //产品或升级产品包含的所有升级产品
    public static function getProductUpgrades($iProductID, $iType, $iStatus = null)
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

    //判断产品是否含有某升级产品
    public static function getDataByUpgradeID($iProductID,$iUpgradeID, $iType)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iType' => $iType,
            'iUpgradeID' => $iUpgradeID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //获取所有包含某指定升级产品的产品
    public static function getDataByUpgrades($aUpgradeIDs,$iType,$iStatus = null)
    {
        $aWhere = array(
            'iUpgradeID IN' => $aUpgradeIDs,
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