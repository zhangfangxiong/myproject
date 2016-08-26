<?php

class Model_Company_Employee extends Model_CustomerNew
{
	
	const ORDER_DESC = 'iCreateTime DESC';

	/**
	 * 根据机构ID获取员工信息
	 * @param  [int] $deptID 
	 * @return [array]
	 */
	public static function getEmployeeByDeptID ($enterpriseId, $deptID, $page = 1)
	{
		$where = [
			'iType' => self::TYPE_USER,
			'iCreateUserID' => $enterpriseId,
			'iStatus' => self::STATUS_VALID 
		];

		if ($deptID) {
			$where['iDeptID IN'] = $deptID;
		}
		
		return self::getList($where, $page, self::ORDER_DESC);
	}


	/**
	 * 成员是否存在
	 * @return [array]
	 */
	public static function checkExist ($employee)
	{	
		if ($employee['sUserName']) {
			$param = [
				'where' => [
					'sUserName' => trim($employee['sUserName']),
					// 'iCreateUserID' => $employee['iCreateUserID'],
					'iStatus >'  => self::STATUS_INVALID
				]
			];

			$row = self::getRow($param);
			if ($row) {
				return [$row, '相同员工编号'];
			}
		}

		// if ($employee['sMobile']) { 
		// 	$param = [
		// 		'where' => [
		// 			'sMobile'  => trim($employee['sMobile']),
		// 			// 'iCreateUserID' => $employee['iCreateUserID'],
		// 			'iStatus >'  => self::STATUS_INVALID
		// 		]
		// 	];
		// 	$row = self::getRow($param);
		// 	if ($row) {
		// 		return [$row, '相同手机号'];
		// 	}
		// }

		if ($employee['sIdentityCard']) { 
			$param = [
				'where' => [
					'sIdentityCard' => trim($employee['sIdentityCard']),
					'iStatus >'  => self::STATUS_INVALID
				]
			];		
			$row = self::getRow($param);
			if ($row) {
				return [$row, '相同身份证号'];
			}
		}
		
		return [[], ''];
	}

	/**
	 * 根据身份证获取生日
	 * @param  [type] $IDCard [description]
	 * @return [type]         [description]
	 */
	public static function getBirthdateByID ($IDCard)
	{ 
	    $tdate = '0000-00-00';
	    if(!eregi("^[1-9]([0-9a-zA-Z]{17}|[0-9a-zA-Z]{14})$", $IDCard)) {
	        return $tdate; 
	    }

        if(strlen($IDCard)==18) { 
            $tyear = intval(substr($IDCard, 6, 4)); 
            $tmonth = intval(substr($IDCard, 10, 2)); 
            $tday = intval(substr($IDCard, 12, 2)); 
           	$tdate = $tyear."-".$tmonth."-".$tday;
    	}

	    return $tdate; 
	} 

}