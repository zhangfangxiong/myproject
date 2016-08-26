<?php

class Model_UserProductUpgrade extends Model_Base
{
    const TABLE_NAME = 't_user_product_upgrade';

    public static function getUserProductUpgrade($iProductID,$iUserID,$iChannelType,$iChannelID)
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
            $aData = Model_ProductUpgrade::getProductUpgrades($iProductID, Model_ProductUpgrade::EXPANDPRODUCT);
        }
        return $aData;
    }

    //判断是否同步过
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

    //判断客户是否含有该升级产品
    public static function getUserHasUpgrades($iProductID,$iUserID,$iUpgradesID,$iChannelType,$iChannelID)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iUpgradeID' => $iUpgradesID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //获取某产品同步过的所有记录(group by)
    public static function getUserSysByProduct($iProductID)
    {
        //$iProductID = 71;
        $sSql = 'SELECT * FROM `t_user_product_upgrade` WHERE iProductID = '.$iProductID.' AND iStatus = 1 GROUP BY iProductID,iUserID,iType,iChannelID;';
        $aData = self::query($sSql);
        return $aData;
    }
}