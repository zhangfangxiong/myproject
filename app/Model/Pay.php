<?php

class Model_Pay extends Model_Base
{

    const TABLE_NAME = 't_pay';
    const SUBJECT = '51joing';
    const BODY = '51joing';

    
    public static function logPay($aData) 
    {
        $iAutoID = (int)self::query('SELECT iAutoID from t_pay WHERE sPayOrderID="' . $aData['sPayOrderID'] . '" AND iPayType=' . $aData['iPayType'], 'one');
        if ($iAutoID == 0) {
            $iAutoID = self::addData($aData);
        }
        return $iAutoID;
    }

    //判断是否支付成功
    public static function checkPay($sMyOrderID)
    {
        return self::query('SELECT * from t_pay WHERE sMyOrderID="' . $sMyOrderID.'"' , 'row');
    }
}