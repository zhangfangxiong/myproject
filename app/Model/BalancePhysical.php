<?php

class Model_BalancePhysical extends Model_Base
{
    const TABLE_NAME = 't_balance_physical';

    /**
     * 按分页取预给信息数据
     * @param int $iBalanceID
     * @return Ambigous <NULL, boolean, string, multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getPhysicalList ($iBalanceID)
    {
    	$sSQL = "SELECT pp.*,bp.iStatus as iBpStatus,u.sRealName,s.sName as sStoreName, p.sProductName,u.iChannel,u.iSex FROM " . self::TABLE_NAME . " AS bp
    			 LEFT JOIN t_physical_product pp ON pp.iAutoID=bp.iPhysicalID
    			 LEFT JOIN t_user u ON u.iUserID=pp.iUserID
    			 LEFT JOIN t_store s ON s.iStoreID=pp.iStoreID
    			 LEFT JOIN t_product p ON p.iProductID=pp.iProductID
    			 WHERE bp.iBalanceID=$iBalanceID
    			";
    	return self::query($sSQL);
    }
}