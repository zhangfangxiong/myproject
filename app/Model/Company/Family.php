<?php

class Model_Company_Family extends Model_Base
{
	
	const TABLE_NAME = 't_company_employee_family';

	/**
	 * 家属是否存在
	 * @return [array]
	 */
	public static function checkExist ($family)
	{
		$param = [
			'where' => [
				'iDocumentTypeID' => $family['iDocumentTypeID'],
				'sDocumentNumber' => trim($family['sDocumentNumber']),
				'iStatus'  => self::STATUS_VALID,
				'iEnterpriseID' => $family['iEnterpriseID']
			]
		];

		$row = self::getRow($param);
		if ($row) {
			return [$row, '相同身份证号'];
		}

		return [[], ''];
	}

}