<?php

class Model_UserProductAddtion extends Model_Base
{
    const TABLE_NAME = 't_user_product_addtion';

    /**
     * 获取客户的门店列表
     * @param $iProductID
     * @param $iUserID
     * @param bool $isUser
     * @return array
     */
    public static function getUserProductAddtion($iProductID,$iUserID,$iChannelType,$iChannelID)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        $aData =  self::getAll(array(
            'where' => $aWhere
        ));
        if (empty($aData)) {
            $aData = Model_ProductAddtion::getProductAddtions($iProductID, Model_ProductAddtion::EXPANDPRODUCT);
        }
        return $aData;
    }

    //判断是否编辑过
    public static function getHasEdit($iProductID,$iUserID,$iChannelType,$iChannelID)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
        );
        $aData =  self::getAll(array(
            'where' => $aWhere
        ));
        return $aData;
    }

    //判断客户是否含有该门店
    public static function getUserHasAddtion($iProductID,$iUserID,$iAddtionID,$iChannelType,$iChannelID)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iAddtionID' => $iAddtionID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }
}