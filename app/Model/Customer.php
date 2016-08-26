<?php

class Model_Customer extends Model_Base
{

    const TABLE_NAME = 't_customer_new';
    
    const TYPE_USER = 1;
    const TYPE_PC = 2;
    const TYPE_WX = 3;

    const NOCHECK = 0;

    /**
     * 根据openid取得用户(微信用户)
     *
     * @param string $sOpenID
     * @return array
     */
    public static function getUserByOpenID ($sOpenID, $iUserID = 0)
    {
        $aWhere = array(
            'sOpenID' => $sOpenID,
            'iStatus >' => 0
        );
        if ($iUserID > 0) {
            $aWhere['iUserID !='] = $iUserID;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //根据真实名或公司名获取用户
    public static function getUserByRealName($sRealName,$iUserID = 0)
    {
        $aWhere = array(
            'sRealName' => $sRealName,
            'iStatus >' => 0
        );
        if ($iUserID > 0) {
            $aWhere['iUserID !='] = $iUserID;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 根据邮箱取得用户
     *
     * @param string $sEmail
     * @return array
     */
    public static function getUserByEmail ($sEmail, $iUserID = 0)
    {
        $aWhere = array(
            'sEmail' => $sEmail,
        );
        if ($iUserID > 0) {
            $aWhere['iUserID !='] = $iUserID;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 根据手机取得用户
     * @param string $sMobile
     * @return array
     */
    public static function getUserByMobile ($sMobile, $iUserID = 0)
    {
        $aWhere = array(
            'sMobile' => $sMobile,
            'iStatus >' => 0,
        );
        if ($iUserID > 0) {
            $aWhere['iUserID !='] = $iUserID;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //根据用户名获取用户
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

    //生成用户名（前端注册后，用户名是自动生成的）
    public static function initUserName()
    {
        $sUserName = 'C-'.Util_Tools::passwdGen(8,1);
        if(self::getUserByUserName($sUserName)) {
            self::initUserName();
        }
        return $sUserName;
    }

    //根据身份证获取用户
    public static function getUserByIdentityCard($sIdentityCard)
    {
        $aWhere = array(
            'sIdentityCard' => $sIdentityCard,
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 设置cookie
     * @param [type] $iCustomerid [description]
     */
    public static function setCookie($iCustomerid)
    {
        $aCustomer = self::getDetail($iCustomerid);
        $aCUser = Model_User::getDetail($aCustomer['iCreateUserID']);
        $aCustomer['iChannelID'] = $aCUser['iChannel'];

        Util_Cookie::delete(Yaf_G::getConf('indexuserkey', 'cookie'));
        Util_Cookie::set(Yaf_G::getConf('indexuserkey', 'cookie'), $aCustomer);
    }

    /*
     * 根据用户名和公司id获取用户的综合信息
     */
    public static function getCustomerCompanyInfo($iCustomerID, $iCompanyID){
        $sql = "select cus.sMobile, cus.sRealName, cus.iSex, cus.iMarriage, cus.iCardType, cus.sIdentityCard, com.sUserName, com.sCompanyName, com.sCompanyCode, com.sEmail".
                " from ". self::TABLE_NAME. " as cus left join ". Model_CustomerCompany::TABLE_NAME. " as com on cus.iUserID = com.iUserID".
                " where com.iUserID = $iCustomerID and com.iCreateUserID = $iCompanyID";

        return self::query($sql, 'row');
    }
}