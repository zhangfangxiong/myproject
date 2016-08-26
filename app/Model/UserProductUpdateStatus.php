<?php
//用于判断用户级别是否做过修改
class Model_UserProductUpdateStatus extends Model_Base
{
    const TABLE_NAME = 't_user_product_updatestatus';
    const UPGRADE = 1;//升级产品
    const STORE = 2;//门店
    const ADDTION = 3;//加项包

    //判断是否编辑过
    public static function getData($iProductID,$iUserID,$iChannelType,$iChannelID,$iInitType)
    {
        $aWhere = array(
            'iUserID' => $iUserID,
            'iProductID' => $iProductID,
            'iType' => $iChannelType,
            'iChannelID' => $iChannelID,
            'iInitType' => $iInitType,
            'iStatus' => 1
        );
        $aData =  self::getRow(array(
            'where' => $aWhere
        ));
        return $aData;
    }
}