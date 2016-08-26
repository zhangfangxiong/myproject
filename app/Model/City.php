<?php

class Model_City extends Model_Base
{

    const TABLE_NAME = 't_city';
    
    const TYPE_FRONT = 1;
    
    const TYPE_BACKEND = 0;

    /**
     * 跟据城市id取得城市
     *
     * @param int $iCid            
     * @return array
     */
    public static function getCityByID ($iCid)
    {
        return self::getDetail($iCid);
    }

    /**
     * 跟据城市名称取得城市
     *
     * @param unknown $sCityName            
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getCityByName ($sCityName)
    {
        return self::getRow(array(
            'where' => array(
                'sCityName' => $sCityName
            )
        ));
    }

    /**
     * 跟据城市简拼取得城市
     *
     * @param unknown $sPinyin            
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getCityByCode ($sCityCode)
    {
        return self::getRow(array(
            'where' => array(
                'sCityCode' => $sCityCode
            )
        ));
    }

    /**
     * 取得所有启用城市的ID => Name数组
     *
     * @return Ambigous <number, multitype:multitype:, multitype:unknown >
     */
    public static function getPairCitys ($iType = 0)
    {
        $aWhere = array(
            'iStatus' => 1
        );
        
        if ($iType == 0) {
            $aWhere['iBackendShow'] = 1;
        } else {
            $aWhere['iFrontShow'] = 1;
        }
        
        return self::getPair(array(
            'where' => $aWhere,
            // 'order' => 'iOrder ASC'
            'order' => 'sPinyin ASC'            
        ), 'iCityID', 'sCityName');
    }

    /**
     * 取得指定城市的ID => Name数组
     *
     * @return Ambigous <number, multitype:multitype:, multitype:unknown >
     */
    public static function getPairCitysByIDS ($aIDs)
    {
        if (! empty($aIDs)) {
            return self::getPair(array(
                'where' => array(
                    'iStatus' => 1,
                    'iCityID IN' => $aIDs
                ),
                'order' => 'sPinyin ASC'
                // 'order' => 'iOrder ASC'
            ), 'iCityID', 'sCityName');
        }
        return array();
    }
}