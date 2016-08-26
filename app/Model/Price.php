<?php

class Model_Price extends Model_Base
{

    const TABLE_NAME = 't_price';

    /**
     * 取得价格段
     * 
     * @return multitype:unknown
     */
    public static function getOption ()
    {
        $aList = self::getAll(array(
            'where' => array(
                'iStatus' => 1
            )
        ));
        
        $aOption = array();
        foreach ($aList as $k => $v) {
            $aOption[$v['iMinPrice'] . '~' . $v['iMaxPrice']] = $v['sTitle'];
        }
        
        return $aOption;
    }

    /**
     * 返回最小价格和最大价格
     * @param unknown $sPrice
     * @return multitype:
     */
    public static function parsePrice ($sPrice)
    {
        return explode('~', $sPrice);
    }
}