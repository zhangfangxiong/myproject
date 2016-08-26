<?php

class Model_Employee extends Model_User
{

    const TABLE_NAME = 't_employee';

    /**
     * 登录
     * @param unknown $aUser
     */
    public static function login($aUser)
    {
        $aCookie = array(
            'iUserID' => $aUser['iUserID'],
            'sEmail' => $aUser['sEmail'],
            'sMobile' => $aUser['sMobile'],
            'iType' => $aUser['iType'],
            'sRealName' => $aUser['sRealName']
        );
        $sKey = Model_User::getUserType($aUser['iType']);
        $expire = Yaf_G::getConf('frontexpire', 'cookie');
        Util_Cookie::set(Yaf_G::getConf($sKey, 'cookie'), $aCookie, $expire);
        
        $sKey = Model_User::getUserType($aUser['iType'] == Model_User::TYPE_AD ? Model_User::TYPE_MEDIA : Model_User::TYPE_AD);
        Util_Cookie::delete(Yaf_G::getConf($sKey, 'cookie'));
        
        return $aCookie;
    }
    
    /**
     * 生成用户密码
     *
     * @param unknown $sPassword            
     * @return string
     */
    public static function makePassword ($sPassword)
    {
        $sCryptkey = Util_Common::getConf('cryptkey', 'passwd');
        return md5($sCryptkey . $sPassword);
    }

    /**
     * 取得用户类型
     *
     * @param unknown $iType            
     * @return string
     */
    public static function getUserType ($iType)
    {
        return $iType == self::TYPE_AD ? 'ad' : 'media';
    }

    /**
     * 根据邮箱取得用户
     *
     * @param string $sEmail            
     * @param string $iType            
     * @return array
     */
    public static function getUserByEmail ($sEmail, $iType, $iUserID = 0)
    {
        $aWhere = array(
            'sEmail' => $sEmail,
            'iType' => $iType
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
     *
     * @param string $sMobile            
     * @param string $iType            
     * @return array
     */
    public static function getUserByMobile ($sMobile, $iType, $iUserID = 0)
    {
        $aWhere = array(
            'sMobile' => $sMobile,
            'iType' => $iType
        );
        if ($iUserID > 0) {
            $aWhere['iUserID !='] = $iUserID;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }
}