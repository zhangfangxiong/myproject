<?php

class Model_User extends Model_Base
{

    //所有用户初始密码都定为用户名

    const TABLE_NAME = 't_user';

    static $aType = array(
        1 => '中盈员工',
        2 => '企业用户',
        3 => '个人用户'
    );

    static $aSex = array(
        1=>'男',
        2=>'女'
    );

    static $aStatus = array(
        1=>'正常',
        2=>'锁定',
        3=>'离职',
        4=>'待验证'
    );

    static $aIsCheck = array(
        0 => '待审核',
        1 => '已审核',
        2 => '已拒绝',
    );

    const TYPE_ADMIN =1;//1=管理员,2=企业HR，3=个人用户 4=供应商
    const TYPE_HR = 2;
    const TYPE_USER = 3;
    const TYPE_SUPPLIER = 4;

    const STATUS_TYPE_DELETE = 0;//状态(0=已删除，1=正常，2=锁定，3=离职)
    const STATUS_TYPE_NORMAL = 1;
    const STATUS_TYPE_LOCK = 2;
    const STATUS_TYPE_LEAVE = 3;
    const ISCHECK = 1;//已验证
    const NOCHECK = 0;//待验证
    const REFUSE = 2;//已拒绝


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

    //根据用户名获取用户
    public static function getUserByUserName($sUserName,$iType)
    {
        $aWhere = array(
            'sUserName' => $sUserName,
            'iType' => $iType,
            'iStatus >' => 0
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //根据真实名或公司名获取用户
    public static function getUserByRealName($sRealName,$iType)
    {
        $aWhere = array(
            'sRealName' => $sRealName,
            'iType' => $iType
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //生成用户名（前端注册后，用户名是自动生成的）
    public static function initUserName($iType)
    {
        //生成规则未定？
        //E88-46634562
        $sUserName = 'E'.$iType.'-'.Util_Tools::passwdGen(8,1);
        if(self::getUserByUserName($sUserName,$iType)) {
            self::initUserName($iType);
        }
        return $sUserName;
    }

    //根据身份证获取用户
    public static function getUserByIdentityCard($sIdentityCard,$iType)
    {
        $aWhere = array(
            'sIdentityCard' => $sIdentityCard,
            'iType' => $iType
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    //根据体检卡获取用户
    public static function getUserByMedicalCard($sMedicalCard,$iType)
    {
        $aWhere = array(
            'sMedicalCard' => $sMedicalCard,
            'iType' => $iType
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 取得所有角色的ID => Name数组
     * @param int $iType
     * @param null $iStatus ==null时为启用和启用的都返回
     * @return int
     */
    public static function getPairUser ($iType=0,$iStatus=1)
    {
        if ($iStatus !== null) {
            $aParam['where']['iStatus'] = $iStatus;
        } else {
            $aParam['where']['iStatus >'] = 0;
        }
        if (!empty($iType)) {
            $aParam['where']['iType'] = $iType;
        }
        return self::getPair($aParam, 'iUserID', 'sRealName');
    }
    
    /**
     * 取得作者ID
     * @param unknown $sUserIDs
     */
    public static function getAuthors($sUserIDs) 
    {
    	$aParam['where']['iStatus'] = 1;
    	$aParam['where']['iUserID IN'] = $sUserIDs;
    	return self::getPair($aParam, 'iUserID', 'sRealName');
    }
    
    /**
     * 取得作者的头像和名字
     * @param unknown $iUserID
     */
    public static function getAvatarAndName($iUserID)
    {
    	$aUser = self::getDetail($iUserID);
    	
    	if (empty($aUser)) {
    		return array('sName' => '--', 'sImage' => '');
    	} else {
    		return array('sName' => $aUser['sRealName'], 'sImage' => $aUser['sAvatar']);
    	} 
    }
}