<?php

/**
 * 企业后台_公司架构设置
 * User: xuchuyuan
 * Date: 16/4/10 15:00
 */
class Controller_Company_Employer extends Controller_Company_Base
{

	const  ERROR = '不能为空';

	public $departmentId = null;

	public $page = null;

	public $deptParam = [
		'sDeptName' => [
			'need' => true,
			'type' => 'strval',
			'desc' => '机构名称'
		],
		'iParentID' => [
			'need' => false,
			'type' => 'intval',
			'desc' => '上级机构'
		],
		'sRemark'  => [
			'need' => false,
			'type' => 'strval',
			'desc' => '备注'
		]
	];

	/**
	 * @return [array]
	 */
	public function indexAction ()
	{
		$this->departmentId = $this->getParam('iDeptID') ? intval($this->getParam('iDeptID')) : 0;
		$this->page = $this->getParam('page') ? intval($this->getParam('page')) : 1;
		if (empty($this->dept[$this->departmentId])) {
			$this->departmentId = 0;			
		} else {
			$this->deptAutoId = $this->departmentId;
		}	

		//公司架构设置
		$where = [
			'iEnterpriseID' => $this->enterpriseId,
			'iStatus'		=> Model_Company_Department::STATUS_VALID
		];

		$this->departmentId ? $where['iAutoID'] = $this->departmentId : $where['iParentID'] = 0;
		$sDeptID = $this->departmentId ? Model_Company_Department::getAllSubDeptIDs($this->departmentId) : '';
		$aDepartment = Model_Company_Department::getRow([ 'where' => $where ]);
		$aTree = Model_Company_Department::getTree($where);
		if ($aDepartment && !$aDepartment['iParentID']) {
			$this->departmentId = 0;
		}
		if (!$sDeptID) {
			$sDeptID = $this->departmentId;
		}
		
		//当前机构员工
		$aEmployee = Model_Company_Company::getEmployeeByDeptID($this->enterpriseId, $sDeptID, $this->page);
		if ($aEmployee['aList']) {
			foreach ($aEmployee['aList'] as $key => &$value) {
				$aCustomer = Model_CustomerNew::getDetail($value['iUserID']);
				$value['sRealName'] = $aCustomer['sRealName'];
				$value['sMobile'] = $aCustomer['sMobile'];
			}
		}
    	$this->assign('aTree', $aTree);    
		$this->assign('aDepartment', $aDepartment);
		$this->assign('aEmployee', $aEmployee);
		$this->assign('iDeptID', $this->deptAutoId);
	}


	/**
	 * 新增机构
	 */
	public function addAction ()
	{
		if ($this->isPost()) {
			$aDept = $this->checkParam();
			if (empty($aDept)) {
				return;
			}

			//机构是否已经存在(同企业+同名称)
			$row  = Model_Company_Department::checkExist($this->enterpriseId, $aDept);
			if ($row) {
				$msg = '机构已经存在';
			} else {
				$addID = Model_Company_Department::addDept($aDept);
				$msg   = $addID ? '新增成功' : '新增失败';			
			}

			return $this->showMsg($msg, true);
		} else {
			$this->departmentId = $this->getParam('iDeptID') ? intval($this->getParam('iDeptID')) : 0;
			if (empty($this->dept[$this->departmentId])) {
				$this->redirect('/company/employer/index');
			}

			$aDept['iParentID'] = $this->departmentId;
			$aDept['iAutoID'] = $this->departmentId;			
			$this->assign('aDept', $aDept);
		}
	}


	/**
	 * 编辑机构信息
	 * @return [array]
	 */
	public function editAction ()
	{
		if ($this->isPost()) {	
			$aDept = $this->checkParam(2);
			if (empty($aDept)) {
				return;
			}

			//机构是否已经存在(同名+同级)
			$row  = Model_Company_Department::checkExist($this->enterpriseId, $aDept);
			if ($row && $row['iAutoID'] != $aDept['iAutoID']) {
				$msg = '机构已经存在';
			} else {
				$update = Model_Company_Department::updDept($aDept);
				$msg    = $update ? '修改成功' : '修改失败';			
			}
			
			return $this->showMsg($msg, true);
		} else {
			$this->departmentId = $this->getParam('iDeptID') ? intval($this->getParam('iDeptID')) : 0;
			if (empty($this->dept[$this->departmentId])) {
				$this->redirect('/company/employer/index');
			}

			$aDept = Model_Company_Department::getDetail($this->departmentId);
			if (isset($aDept['iStatus']) && $aDept['iStatus'] != Model_Company_Department::STATUS_VALID) {
				$aDept = [];
			}

			if (isset($aDept['iParentID']) && $aDept['iParentID'] == 0) {
				$this->assign('selectable', 'disabled');
				$this->assign('readable', 'readonly');
			} else {
				$this->assign('selectable', '');
				$this->assign('readable', '');
			}

			$this->assign('aDept', $aDept);
		}
	}


	/**
	 * 检测参数
	 * @param  [int] type (1＝新增,2=修改)
	 * @return [array]
	 */
	private function checkParam($type = 1)
	{
		$aDept = ['iEnterpriseID' => $this->enterpriseId];
		$params = $this->getParams();

		if ($type == 2) {
			if (isset($params['iDeptID']) && intval($params['iDeptID'])) {
				$aDept['iAutoID'] = $params['iDeptID'];
			} else {
				return $this->showMsg('机构ID'.self::ERROR, false);
			}
		}

		foreach ($this->deptParam as $key => $value) {
			if ($value['need']) {
				if (!isset($params[$key])) {
					return $this->showMsg($this->deptParam[$key]['desc'].self::ERROR, false);
				}
			}			
		}

		foreach ($params as $key => $value) {
			if (isset($this->deptParam[$key])) {
				$aDept[$key] = $this->deptParam[$key]['type']($value);
				
				if ($this->deptParam[$key]['need'] && !$aDept[$key]) {
					return $this->showMsg($this->deptParam[$key]['desc'].self::ERROR, false);
				}
			}
		}

		return $aDept;
	}

}