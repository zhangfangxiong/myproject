<?php

class Model_UserProductStore extends Model_Base
{
    const TABLE_NAME = 't_user_product_store';

    /**
     * 获取客户的所有门店列表
     * @param $iProductID
     * @param $iUserID
     * @param bool $isUser
     * @return array
     */
    public static function getUserProductStore($iProductID, $iUserID, $iChannelType, $iChannelID, $aParam = array())
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        $aWhere = array_merge($aWhere,$aParam);
        $aData = self::getAll(array(
            'where' => $aWhere
        ));
        if (empty($aData)) {
            $aData = Model_ProductStore::getProductStores($iProductID, Model_ProductStore::EXPANDPRODUCT);
        }
        return $aData;
    }

    /**
     * 获取客户的门店列表(分页)
     * @param $iProductID
     * @param $iUserID
     * @param bool $isUser
     * @return array
     */
    public static function getUserProductStoreList($iProductID, $iUserID, $iChannelType, $iChannelID, $iPage,$aParam = array(), $sOrder = '')
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        $aWhere = array_merge($aWhere,$aParam);
        $aData = self::getList($aWhere, $iPage, $sOrder);
        if (empty($aData['aList'])) {
            $aWhere = array(
                'iProductID' => $iProductID,
                'iType' => Model_ProductStore::EXPANDPRODUCT
            );
            $aWhere = array_merge($aWhere,$aParam);
            $aData = Model_ProductStore::getList($aWhere, $iPage, $sOrder);
        }
        return $aData;
    }

    //判断是否编辑过
    public static function getHasEdit($iProductID, $iUserID, $iChannelType, $iChannelID)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
        );
        $aData = self::getAll(array(
            'where' => $aWhere
        ));
        return $aData;
    }

    /**
     * 判断客户是否含有该门店
     * @param $iProductID
     * @param $iUserID
     * @param $iStoreID
     * @param $iChannelType
     * @param $iChannelID
     * @param $fUpper 是否到productstore里查找，默认不查找
     * @return int
     */
    public static function getUserHasStore($iProductID, $iUserID, $iStoreID, $iChannelType, $iChannelID,$fUpper = false)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStoreID' => $iStoreID,
            'iStatus' => 1
        );
        $aData =  self::getRow(array(
            'where' => $aWhere
        ));
        if (empty($aData) && !empty($fUpper)) {
            $aData = Model_ProductStore::getDataByStoreID($iProductID,$iStoreID, Model_ProductStore::EXPANDPRODUCT);
        }
        return $aData;
    }
}