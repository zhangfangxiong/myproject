<?php

class Model_ProductChannel extends Model_Base
{
    const TABLE_NAME = 't_product_channel';
    const TYPE_COMPANY = 1;
    const TYPE_INDIVIDUAL = 2;

    /**
     * 获取某产品某渠道数据
     * @param $iProductID
     * @param $iType
     * @param $iChannelID
     * @return int
     */
    public static function getData($iProductID,$iType,$iChannelID)
    {
        $aParam['where'] = [
          'iProductID' => $iProductID,
            'iType' => $iType,
            'iChannelID' => $iChannelID,
            'iStatus' => 1
        ];
        return self::getRow($aParam);
    }

    /**
     * 获取某产品的所有渠道信息
     * @param $iProductID
     * @param int $iType
     * @param int $iChannel
     * @return array
     */
    public static function getChannelInfoByProductID($iProductID,$iType = 0,$iChannel = 0)
    {
        $aParam['where'] = [
            'iProductID' => $iProductID,
            'iStatus' => 1
        ];
        if ($iType > 0) {
            $aParam['where']['iType'] = $iType;
        }
        if ($iChannel > 0) {
            $aParam['where']['iChannelID'] = $iChannel;
        }
        return self::getAll($aParam);
    }

    /**
     * 根据渠道获取产品
     * @param $aChannelIDs
     * @return array
     */
    public static function getProductByChannel($aChannelIDs)
    {
        $aParam['where'] = [
            'iChannelID IN' => $aChannelIDs,
            'iStatus' => 1
        ];
        $aParam['group'] = 'iProductID';
        return self::getAll($aParam);
    }
}