<?php

class Model_CustomerNew extends Model_Base
{

    const TABLE_NAME = 't_customer_new';
    
    const TYPE_USER = 1;
    const TYPE_PC = 2;
    const TYPE_WX = 3;

    const NOCHECK = 0;

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

    //根据身份证获取用户
    public static function getUserByIdentityCard($sIdentityCard)
    {
        $aWhere = array(
            'sIdentityCard' => $sIdentityCard,
            'iStatus' => self::STATUS_VALID
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


    /**
     * 根据真实姓名获取用户id列表
     * @param  $sRealName
     */
    public static function getCustomerIDsByName($sRealName)
    {
        $aIDs = [];
        $sIDs = '';
        $sRealName = '%' . $sRealName . '%';
        $aCustomer = self::getAll([
            'where' => [
                'sRealName LIKE' => $sRealName
            ]
        ]);
        if ($aCustomer) {
            foreach ($aCustomer as $key => $value) {
                $aIDs[] = $value['iUserID'];
            }

            $sIDs = implode(',', $aIDs);
        }

        return $sIDs;
    }

    /**
     * 通过身份证+手机号 判断用户是否存在
     * @return [array]
     */
    public static function checkIsExist($sRealName, $sIdentityCard)
    {
        return self::getRow([
            'where' => [
                'sRealName' => $sRealName,
                'sIdentityCard' => $sIdentityCard,
                'iStatus >' => self::STATUS_INVALID,
            ]
        ]);
    }

}