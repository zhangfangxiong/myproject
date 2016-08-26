<?php

class Model_Company_Company extends Model_Base
{
	
	const TABLE_NAME = 't_customer_company';

    const ORDER_DESC = 'iCreateTime DESC';

	/**
     * 通过员工id+ 公司id判断信息是否存在
     * @return [array]
     */
    public static function checkIsExist($iUserID, $iCreateUserID)
    {
        return self::getRow([
            'where' => [
                'iUserID' => $iUserID,
                'iCreateUserID' => $iCreateUserID,
                'iStatus >' => self::STATUS_INVALID,
            ]
        ]);
    }

    //生成用户名（前端注册后，用户名是自动生成的）
    public static function initUserName()
    {
        $sUserName = 'E-'.Util_Tools::passwdGen(8,1);
        if(self::getUserByUserName($sUserName)) {
            self::initUserName();
        }
        return $sUserName;
    }

    //根据用户名+企业id获取用户
    public static function getUserByUserName($sUserName, $iCreateUserID = 0)
    {
        $where = [
            'sUserName' => $sUserName,
            'iStatus >' => 0
        ];
        
        if ($iCreateUserID) {
            $where['iCreateUserID'] = $iCreateUserID;
        }
        
        return self::getRow(array(
            'where' => $where
        ));
    }

    /**
     * 根据机构ID获取员工信息
     * @param  [int] $deptID 
     * @return [array]
     */
    public static function getEmployeeByDeptID ($enterpriseId, $deptID, $page = 1)
    {
        $where = [
            'iCreateUserID' => $enterpriseId,
            'iStatus' => self::STATUS_VALID 
        ];

        if ($deptID) {
            $where['iDeptID IN'] = $deptID;
        }
        
        return self::getList($where, $page, self::ORDER_DESC);
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
}