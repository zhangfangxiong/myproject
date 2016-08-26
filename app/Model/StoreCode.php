<?php

class Model_StoreCode extends Model_Base
{
    const TABLE_NAME = 't_store_code';

    //判断是否插入过
    public static function getData($iProductID, $iStoreID, $iSex)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iStoreID' => $iStoreID,
            'iSex' => $iSex,
            'iStatus >' => 0,
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 返回供应商 AF结算价
     * @param  [type] $iStoreID   [description]
     * @param  [type] $iSex       [description]
     * @param  [type] $iProductID [description]
     * @return [type]             [description]
     */
    public static function getSupplierCost ($iStoreID, $iSex, $iProductID)
    {
        $aPStore = self::getRow(['where' => [
            'iStoreID' => $iStoreID,
            'iSex' => $iSex,
            'iProductID' => $iProductID,
            'iStatus >' => 0,
        ]]);
        
        if (!$aPStore) {
            return [-1, -1];
        }

        return [$aPStore['sSupplierPrice'], $aPStore['sChannelPrice']];
    }
}