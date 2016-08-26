<?php

class Model_CustomerCompany extends Model_Base
{

    const TABLE_NAME = 't_customer_company';

    //根据员工编号获取用户
    public static function getUserByUserName($sUserName)
    {
        $where = [
            'sUserName' => $sUserName,
            'iStatus >' => 0
        ];
        $aWhere = $where;

        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //生成员工编号（注意：员工编号不能用来登陆）
    public static function initUserName($iCompanyID)
    {
        $sUserName = $iCompanyID.'-'.Util_Tools::passwdGen(8,1);
        if(self::getUserByUserName($sUserName)) {
            self::initUserName($iCompanyID);
        }
        return $sUserName;
    }
}