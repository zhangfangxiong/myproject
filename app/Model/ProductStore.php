<?php

class Model_ProductStore extends Model_Base
{
    const TABLE_NAME = 't_product_store';

    const ITEM = 1;//单项
    const BASEPRODUCT = 2;//基础产品
    const EXPANDPRODUCT = 3;//拓展产品

    //产品或单项包含的所有门店
    public static function getProductStores($iProductID, $iType, $iStatus = null,$iNum = false)
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
        if ($iNum) {
            return self::getCnt(array(
                'where' => $aWhere
            ));
        }
        $aData = self::getAll(array(
            'where' => $aWhere
        ));
        return $aData;
    }

    //判断产品或单项是否含有某门店
    public static function getDataByStoreID($iProductID,$iStoreID, $iType)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iType' => $iType,
            'iStoreID' => $iStoreID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    public static function getPStoreByIDs($aParam) 
    {
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus=' . self::STATUS_VALID;
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus=' . self::STATUS_VALID;

        if (!empty($aParam['iProductID'])) {
            $sSQL .= ' AND iProductID IN ' . '('.$aParam['iProductID'].')';
            $sCntSQL .= ' AND iProductID IN ' . '('.$aParam['iProductID'].')';
        }

        if (!empty($aParam['sStoreID'])) {
            $sSQL .= ' AND iStoreID IN ' . '('.$aParam['sStoreID'].')';
            $sCntSQL .= ' AND iStoreID IN ' . '('.$aParam['sStoreID'].')';
        }

        if (!empty($aParam['sType'])) {
            $sSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
            $sCntSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
        }
        
        return self::query($sSQL);
    }

    //获取所有包含某指定门店的产品或单项
    public static function getDataByStores($aStoreIDs,$iType,$iStatus = null)
    {
        $aWhere = array(
            'iStoreID IN' => $aStoreIDs,
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