<?php

class Model_ProductViewRange extends Model_Base
{
    const TABLE_NAME = 't_product_channel_viewlist';

    /**
     * 判断是否存在该渠道客户
     * @param $iProductChannelID
     * @param $iUserID
     * @return int
     */
    public static function getUserByViewRangeID($iProductChannelID,$iUserID,$iViewRange)
    {
        $aWhere = array(
            'iProductChannelID' => $iProductChannelID,
            'iUserID' => $iUserID,
            'iViewRange' => $iViewRange,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 获取渠道下所有客户（可见或不可见的）
     * @param $iProductChannelID
     * @param $iUserID
     * @return int
     */
    public static function getDataByViewRangeID($iProductChannelID,$iViewRange)
    {
        $aWhere = array(
            'iProductChannelID' => $iProductChannelID,
            'iStatus' => 1,
            'iViewRange'=>$iViewRange
        );
        return self::getAll(array(
            'where' => $aWhere
        ));
    }

}