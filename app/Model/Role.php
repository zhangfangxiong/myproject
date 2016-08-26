<?php

class Model_Role extends Model_Base
{

    const TABLE_NAME = 't_role';

    static $aStatus = array(//是否启用
        1=>'是',
        2=>'否'
    );

    const HRROLENAME = 'hr后台系统管理员';//这个名称的角色是默认加给hr用户的，不能删除
    const SUPPLIERROLENAME = '供应商管理员';//这个名称的角色是默认加给供应商用户的，不能删除

    /**
     * 根据角色名取得角色信息
     * @param unknown $sRoleName
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getRoleByName ($sRoleName,$iType)
    {
        return self::getRow(array(
            'where' => array(
                'sRoleName' => $sRoleName,
                'iType' => $iType,
                'iStatus >' => 0
            )
        ));
    }

    /**
     * 取得所有角色的ID => Name数组
     * @param int $iType
     * @param null $iStatus ==null时为启用和启用的都返回
     * @return int
     */
    public static function getPairRoles ($iType=0,$iStatus=1)
    {
        if ($iStatus !== null) {
            $aParam['where']['iStatus'] = $iStatus;
        } else {
            $aParam['where']['iStatus >'] = 0;
        }
        if (!empty($iType)) {
            $aParam['where']['iType'] = $iType;
        }
        return self::getPair($aParam, 'iRoleID', 'sRoleName');
    }
}