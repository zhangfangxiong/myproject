<?php

class Model_Company_Level extends Model_Base
{

	const TABLE_NAME = 't_company_level';

	public static function checkExist ($enterpriseId, $level)
	{
		$param = [
			'where' => [
				'iEnterpriseID' => intval($enterpriseId),
				'sLevelName' => trim($level['sLevelName']),
				'iStatus'    => self::STATUS_VALID
			]
		];

		$row = self::getRow($param);
		
		return $row;
	}

	/**
	 * 新增职级model
	 */
	public static function addLevel ($level)
	{
		if ($level) {
			$level['iStatus'] = self::STATUS_VALID;
			return self::addData($level);
		}

		return 0;
	}

	/**
	 * 修改职级model
	 */
	public static function updLevel ($level)
	{
		if ($level) {
			$level['iStatus'] = self::STATUS_VALID;
			return self::updData($level);
		}

		return 0;
	}

	/**
	 * 取得所有部门的ID => Name数组
	 * @param int $iType
	 * @param null $iStatus ==null时为启用和启用的都返回
	 * @return int
	 */
	public static function getPairLevel ($iEnterpriseID,$iStatus=1)
	{
		$aParam['iEnterpriseID'] = $iEnterpriseID;
		$aParam['iStatus'] = $iStatus;
		return self::getPair($aParam, 'iAutoID', 'sLevelName');
	}
}
	