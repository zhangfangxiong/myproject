<?php

/**
 * 企业后台_家属列表
 * User: xuchuyuan
 * Date: 16/4/11 20:00
 */
class Controller_Company_Family extends Controller_Company_Base
{

	const  ERROR = '不能为空';

	public $cardtype = null;

	public $relation = null;

	public $position = null;

	public $familyId = null;

	public $familyParam = [
		'iEmployeeID' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '员工编号'
		],
		'sRealName' => [
			'need' => true,
			'type' => 'strval',
			'desc' => '家属姓名'
		],
		'iSex' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '性别'
		],
		'iDocumentTypeID' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '证件类型'
		],
		'sDocumentNumber' => [
			'need' => true,
			'type' => 'strval',
			'desc' => '证件号码'
		],
		'sMobile' => [
			'need' => true,
			'type' => 'strval',
			'desc' => '手机号码'
		],
		'iRelationID' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '与本人关系'
		],

		'sBirthDate' => [
			'need' => false,
			'type' => 'strval',
			'desc' => '生日'
		],
		'sCall' => [
			'need' => false,
			'type' => 'strval',
			'desc' => '固定电话'
		],		
		'iPositionID' => [
			'need' => false,
			'type' => 'intval',
			'desc' => '职位'
		],
	];

	public function actionBefore ()
	{
		
		parent::actionBefore();

		$this->cardtype = Yaf_G::getConf('aCardType');

		$this->relation = Yaf_G::getConf('aRelationship');

		$this->position = Yaf_G::getConf('aPosition');

		$this->assign('aCardType', $this->cardtype);

		$this->assign('aRelation', $this->relation);

		$this->assign('aPosition', $this->position);
	}


	/**
	 * 新增家属
	 */
	public function addAction ()
	{
		if ($this->isPost()) {
			$aFamily = $this->checkData();
			if (empty($aFamily)) {
				return;
			}

			$aEmployee = Model_Company_Company::getRow([
				'where' => [
					'iCreateUserID' => $this->enterpriseId,
					'iUserID' => $aFamily['iEmployeeID'],
					'iStatus >'	=> Model_Company_Company::STATUS_INVALID
				]
			]);
			if (!$aEmployee) {
				return $this->showMsg('企业员工不存在', false);
			}
			if ($aEmployee) {
				$aFamily['iCCID'] = $aEmployee['iAutoID'];
			}
			
			//家属是否存在(企业+证件号)
			list($row, $desc) = Model_Company_Family::checkExist($aFamily);
			if ($row) {
				$msg = $desc.'员工家属已经存在!';
				$bool = false;
			} else {
				$addID = Model_Company_Family::addData($aFamily);
				$msg   = $addID ? '新增成功' : '新增失败';	
				$bool  = $addID ? true : false;					
			}

			return $this->showMsg($msg, $bool);
		} else {
			$aFamily['iEmployeeID'] = $this->getParam('iEmployeeID');
			$this->assign('aFamily', $aFamily);
		}
	}


	/**
	 * 编辑家属
	 */
	public function editAction ()
	{
		if ($this->isPost()) {
			$aFamily = $this->checkData(2);
			if (empty($aFamily)) {
				return;
			}

			//家属是否存在(企业+证件号)
			list($row, $desc) = Model_Company_Family::checkExist($aFamily);
			if ($row && $row['iAutoID'] != $aFamily['iAutoID']) {
				$msg = $desc.'家属已经存在!';
				$bool = false;						
			} else {
				$update = Model_Company_Family::updData($aFamily);
				$msg    = $update ? '修改成功' : '修改失败';
				$bool = $update ? true : false;			
			}
			
			return $this->showMsg($msg, $bool);
			
		} else {
			$this->familyId = intval($this->getParam('iFamilyID'));
			if (!$this->familyId) {
				return $this->showMsg('家属ID'.self::ERROR, false);
			}

			$aFamily = Model_Company_Family::getDetail($this->familyId);
			if (isset($aFamily['iStatus']) && $aFamily['iStatus'] != Model_Company_Family::STATUS_VALID) {
				$aFamily = [];
			}

			$this->assign('aFamily', $aFamily);
		}
	}

	/**
	 * 删除家属
	 */
	public function delAction ()
	{
		$aFamily = ['iEnterpriseID' => $this->enterpriseId];

		$iFamilyID = intval($this->getParam('iFamilyID'));
		if (!$iFamilyID) {
			return $this->showMsg('家属ID'.self::ERROR, false);
		}

		$aFamily['iAutoID'] = $iFamilyID;
		$aFamily['iStatus'] = Model_Company_Family::STATUS_INVALID;

		$update = Model_Company_Family::updData($aFamily);
		$msg    = $update ? '删除成功' : '删除失败';
		$bool = $update ? true : false;	

		return $this->showMsg($msg, $bool);
	}


	/**
	 * 检测参数
	 * @param  [int] type (1＝新增,2=修改)
	 * @return [array]
	 */
	public function checkData($type = 1)
	{
		$aFamily = ['iEnterpriseID' => $this->enterpriseId];
		$params = $this->getParams();

		if ($type == 2) {
			if (isset($params['iFamilyID']) && intval($params['iFamilyID'])) {
				$aFamily['iAutoID'] = $params['iFamilyID'];
			} else {
				return $this->showMsg('家属ID'.self::ERROR, false);
			}
		}

		foreach ($this->familyParam as $key => $value) {
			if ($value['need']) {
				if (!isset($params[$key])) {
					return $this->showMsg($this->familyParam[$key]['desc'].self::ERROR, false);
				}
			}			
		}

		foreach ($params as $key => $value) {
			if (isset($this->familyParam[$key])) {
				$aFamily[$key] = $this->familyParam[$key]['type']($value);
				
				if ($this->familyParam[$key]['need'] && !$aFamily[$key]) {
					return $this->showMsg($this->familyParam[$key]['desc'].self::ERROR, false);
				}
			}
		}

		return $aFamily;
	}



}