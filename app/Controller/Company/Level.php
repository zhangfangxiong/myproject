<?php

/**
 * 企业后台_职级设置
 * User: xuchuyuan
 * Date: 16/4/11 15:00
 */
class Controller_Company_Level extends Controller_Company_Base
{

	const  ERROR = '不能为空';

	public $levelId = null;

	public $levelParam = [
		'sLevelName' => [
			'need' => true,
			'type' => 'strval',
			'desc' => '职级名称'
		],
		'iRank' => [
			'need' => false,
			'type' => 'intval',
			'desc' => '排序'
		]
	];

	public function actionBefore ()
	{	
		parent::actionBefore();
	}

	/**
	 * @return [array]
	 */
	public function indexAction ()
	{
		$this->assign('aLevel', $this->aLevel);
	}

	/**
	 * 新增职级
	 */
	public function addAction ()
	{
		if ($this->isPost()) {
			$aLevel = $this->checkParam();
			if (empty($aLevel)) {
				return;
			}

			//职级是否已经存在(同名+同企业)
			$bool  = Model_Company_Level::checkExist($this->enterpriseId, $aLevel);
			if ($bool) {
				$msg = '职级已经存在';
			} else {
				$addID = Model_Company_Level::addLevel($aLevel);
				$msg   = $addID ? '新增成功' : '新增失败';			
			}

			return $this->showMsg($msg, true);
		}		
	}


	/**
	 * 编辑职级
	 * @return [array]
	 */
	public function editAction ()
	{
		if ($this->isPost()) {	
			$aLevel = $this->checkParam(2);
			if (empty($aLevel)) {
				return;
			}

			//职级是否已经存在(同名+同企业)
			$row  = Model_Company_Level::checkExist($this->enterpriseId, $aLevel);
			if ($row && $row['iAutoID'] != $aLevel['iAutoID']) {
				$msg = '职级已经存在';
			} else {
				$update = Model_Company_Level::updLevel($aLevel);
				$msg    = $update ? '修改成功' : '修改失败';			
			}
			
			return $this->showMsg($msg, true);

		} else {
			$this->levelId = $this->getParam('iLevelID') ? intval($this->getParam('iLevelID')) : 0;
			if (!$this->levelId) {
				return $this->showMsg('职级ID'.self::ERROR, false);
			}

			$aLevel = Model_Company_Level::getDetail($this->levelId);
			if (isset($aLevel['iStatus']) && $aLevel['iStatus'] != Model_Company_Level::STATUS_VALID) {
				$aLevel = [];
			}

			$this->assign('aLevel', $aLevel);
		}
	}


	/**
	 * 检测参数
	 * @param  [int] type (1＝新增,2=修改)
	 * @return [array]
	 */
	private function checkParam($type = 1)
	{
		$aLevel = ['iEnterpriseID' => $this->enterpriseId];
		$params = $this->getParams();

		if ($type == 2) {
			if (isset($params['iLevelID']) && intval($params['iLevelID'])) {
				$aLevel['iAutoID'] = $params['iLevelID'];
			} else {
				return $this->showMsg('机构ID'.self::ERROR, false);
			}
		}

		foreach ($this->levelParam as $key => $value) {
			if ($value['need']) {
				if (!isset($params[$key])) {
					return $this->showMsg($this->levelParam[$key]['desc'].self::ERROR, false);
				}
			}			
		}

		foreach ($params as $key => $value) {
			if (isset($this->levelParam[$key])) {
				$aLevel[$key] = $this->levelParam[$key]['type']($value);
				
				if ($this->levelParam[$key]['need'] && !$aLevel[$key]) {
					return $this->showMsg($this->levelParam[$key]['desc'].self::ERROR, false);
				}
			}
		}

		return $aLevel;
	}



}