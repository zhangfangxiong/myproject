<?php

class Model_Kv extends Model_Base
{

    const TABLE_NAME = 't_kv';
    
    /**
     * 取得KV值
     * @param unknown $sKey
     * @param string $isJson
     * @return NULL|mixed
     */
    public static function getValue($sKey, $isJson = false)
    {
        $aKv = self::getDetail($sKey);
        if (empty($aKv)) {
            return null;
        }
        
        return $isJson ? json_decode($aKv['sData'], true) : $aKv['sData'];
    }
    
    /**
     * 设置KV值
     * @param unknown $sKey
     * @param unknown $aData
     */
    public static function setValue($sKey, $sData)
    {
        if (is_array($sData)) {
            $sData = json_encode($sData, JSON_UNESCAPED_UNICODE);
        }
        
        $aKv = array(
            'sKey' => $sKey,
            'sData' => $sData
        );
        $aTmp = self::getDetail($sKey);
        if (empty($aTmp)) {
            self::addData($aKv);
        } else {
            self::updData($aKv);
        }
    }
}