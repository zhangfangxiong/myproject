<?php

/**
 * 企业后台_员工列表
 * User: xuchuyuan
 * Date: 16/4/10 16:00
 */
class Controller_Company_Employee extends Controller_Company_Base
{

	const  ERROR = '不能为空';

	public $where = null;

	public $param = null;

	public $employeeId = null;

	/**
	 * 员工列表
	 * @return [array]
	 */
	public function listAction ()
	{
		list($this->where, $param) = $this->checkParam();
		$page  = $this->getParam('page') ? intval($this->getParam('page')) : 1;
		
		if (!empty($param['sRealName'])) {
			$sUserIDs = Model_CustomerNew::getCustomerIDsByName($param['sRealName']);
			if ($sUserIDs) {
				$this->where['iUserID IN'] = $sUserIDs;
				$aList = Model_Company_Company::getList($this->where, $page);
			} else {
				$aList = [];
			}
		} else {
			$aList = Model_Company_Company::getList($this->where, $page);
		}

		if ($aList) {
			foreach ($aList['aList'] as $key => &$value) {
				$aCustomer = Model_CustomerNew::getDetail($value['iUserID']);
				$value['sRealName'] = $aCustomer['sRealName'];
				$value['sMobile'] = $aCustomer['sMobile'];
			}
		}
		
		$this->assign('aList', $aList);
		$this->assign('aParam', $param);
		$this->assign('iPage',  $page);
	}

	/**
	 * 列表参数
	 * @return [array]
	 */
	public function checkParam()
	{
		$where = [
			'iCreateUserID' => $this->enterpriseId
		];
		$param = $this->getParams();
		
		if ($param) {
			!empty($param['sUserName']) ? $where['sUserName LIKE'] = 
			'%' . strval($param['sUserName']) . '%' : '';

			isset($param['iLevelID']) && intval($param['iLevelID']) 
			? $where['iJobGradeID'] = intval($param['iLevelID']) : '';

			isset($param['iDeptID']) && intval($param['iDeptID']) 
			? $where['iDeptID'] = intval($param['iDeptID']) : '';

			isset($param['iStatus']) && intval($param['iStatus']) 
			? $where['iStatus'] = intval($param['iStatus']) 
			: $where['iStatus >'] = Model_Company_Employee::STATUS_INVALID;		
		}

		return [$where, $param];
	}


	/**
	 * 员工详情
	 * @return [array]
	 */
	public function detailAction () {
		$this->employeeId = intval($this->getParam('iEmployeeID'));
		$aEmployee = Model_Company_Company::getRow([
			'where' => [
				'iCreateUserID' => $this->enterpriseId,
				'iUserID' => $this->employeeId,
				'iStatus >'	=> Model_Company_Company::STATUS_INVALID
			]
		]);	

		if (!$aEmployee || !$this->employeeId) {
			$this->redirect('/company/employee/list');
		}
		if ($aEmployee) {
			$aCustomer = Model_CustomerNew::getDetail($aEmployee['iUserID']);
			$aEmployee['sRealName'] = $aCustomer['sRealName'];
			$aEmployee['iSex'] = $aCustomer['iSex'];
			$aEmployee['iMarriage'] = $aCustomer['iMarriage'];
			$aEmployee['sIdentityCard'] = $aCustomer['sIdentityCard'];
			$aEmployee['sMobile'] = $aCustomer['sMobile'];
			$aEmployee['sBirthDate'] = $aCustomer['sBirthDate'];
		}	

		$aFamily = Model_Company_Family::getAll([
			'where' => [
				'iEnterpriseID' => $this->enterpriseId,
				'iCCID' => $aEmployee['iAutoID'],
				'iStatus >'	=> Model_Company_Family::STATUS_INVALID
			]
		]);

		$this->assign('aEmployee', $aEmployee);	
		$this->assign('aFamily', $aFamily);
	}



	/**
	 * 新增员工
	 */
	public function addAction ()
	{
		if ($this->isPost()) {
			list($aCustomer, $aCompany) = $this->checkData2(); 
			if (empty($aCustomer)) {
				return;
			}

			//若有iuserid，则员工基本信息已经存在，可能是其他企业创建的
			if (!empty($aCustomer['iUserID'])) {
				$row = Model_Company_Company::checkIsExist($aCompany['iUserID'], $aCompany['iCreateUserID']);
				if ($row) {
					return $this->showMsg('该员工已存在', false);
				}
				Model_CustomerNew::updData($aCustomer);
			} else {
				$aCustomer['iUserID'] = Model_CustomerNew::addData($aCustomer);
			}

			$aCompany['iUserID'] = $aCustomer['iUserID'];
			Model_Company_Company::addData($aCompany);

			return $this->showMsg('新增成功', true);
		}
	}


	/**
	 * 编辑员工
	 */
	public function editAction ()
	{
		if ($this->isPost()) {
			list($aCustomer, $aCompany) = $this->checkData2(2);
			if (empty($aCustomer)) {
				return;
			}

			$aCompany['iUserID'] = $aCustomer['iUserID'];
			$row = Model_Company_Company::checkIsExist($aCompany['iUserID'], $aCompany['iCreateUserID']);
			if ($row) {
				Model_CustomerNew::updData($aCustomer);

				$aCompany['iAutoID'] = $row['iAutoID'];
				Model_Company_Company::updData($aCompany);
				return $this->showMsg('修改成功', true);
			} else {
				return $this->showMsg('员工不存在', false);
			}
		} else {
			$this->detailAction();
		}
	}

	/**
	 * 检测参数
	 * @param  [int] type (1＝新增,2=修改)
	 * @return [array]
	 */
	public function checkData2($type = 1)
	{
		$params = $this->getParams();

		$aCustomer = [];
		$aCompany = [];

		if (!$params['sRealName']) {
			return $this->showMsg('员工姓名不能为空', false);
		}
		if (!$params['iSex']) {
			return $this->showMsg('员工性别不能为空', false);
		}
		if (!$params['iMarriage']) {
			return $this->showMsg('员工婚姻状况不能为空', false);
		}
		if (!$params['sIdentityCard']) {
			return $this->showMsg('员工身份证号不能为空', false);
		}
		if (!$params['sMobile']) {
			return $this->showMsg('员工手机号码不能为空', false);
		}
		if (!$params['sUserName']) {
			$params['sUserName'] = Model_Company_Company::initUserName();
		}

		$aCustomer['sRealName'] = $params['sRealName'];
		$aCustomer['iSex'] = $params['iSex'];
		$aCustomer['iMarriage'] = $params['iMarriage'];
		$aCustomer['sIdentityCard'] = $params['sIdentityCard'];
		$aCustomer['sMobile'] = $params['sMobile'];
		$aCustomer['iCreateUserID'] = $this->enterpriseId;
		if ($params['sBirthDate']) {
			$aCustomer['sBirthDate'] = $params['sBirthDate'];	
		}

		$customer = Model_CustomerNew::getUserByIdentityCard($params['sIdentityCard']);
		if ($type == 1) {
			$aCustomer['sUserName'] = Model_CustomerNew::initUserName();
			if ($customer && $customer['sRealName'] != $params['sRealName']) {
				return $this->showMsg('相同身份证号员工已存在', false);
			}
			if ($customer && $customer['sRealName'] == $params['sRealName']) {
				$aCustomer['iUserID'] = $customer['iUserID'];
			}
			if (!$customer) {
				//设置初始密码=手机号
				$aCustomer['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') .$aCustomer['sUserName']);
			}
		} else {
			if (!$params['iUserID']) {
				return $this->showMsg('员工不能为空', false);	
			}
			if ($customer && $customer['iUserID'] != $params['iUserID']) {
				return $this->showMsg('相同身份证手机号员工已存在', false);	
			}

			$aCustomer['iUserID'] = $params['iUserID'];
		}

		$aCompany['sUserName'] = $params['sUserName'];
		$aCompany['iCompanyID'] = $this->enterpriseId;
		$aCompany['iCreateUserID'] = $this->enterpriseId;
		$aCompanyUser = Model_User::getDetail($this->enterpriseId);
		$aCompany['sCompanyName']  = $aCompanyUser['sRealName'];
		$aCompany['sCompanyCode']  = $aCompanyUser['sUserName'];
		if ($params['iDeptID']) {
			$aCompany['iDeptID'] = $params['iDeptID'];	
		}
		if ($params['iIsHR']) {
			$aCompany['iIsHR'] = $params['iIsHR'];	
		}
		if ($params['sEmail']) {
			$aCompany['sEmail'] = $params['sEmail'];	
		}
		if ($params['iJobGradeID']) {
			$aCompany['iJobGradeID'] = $params['iJobGradeID'];	
		}
		if ($params['sJobTitleName']) {
			$aCompany['sJobTitleName'] = $params['sJobTitleName'];	
		}
		if ($params['sJobTitleName']) {
			$aCompany['sJobTitleName'] = $params['sJobTitleName'];	
		}
		if ($params['sJobDate']) {
			$aCompany['sJobDate'] = $params['sJobDate'];	
		}
		if ($params['iStatus']) {
			$aCompany['iStatus'] = $params['iStatus'];	
		}
		if ($params['sCiicNumber']) {
			$aCompany['sCiicNumber'] = $params['sCiicNumber'];	
		}
		if ($params['sExpNumber']) {
			$aCompany['sExpNumber'] = $params['sExpNumber'];	
		}
		if ($params['sRemark']) {
			$aCompany['sRemark'] = $params['sRemark'];	
		}

		return [$aCustomer, $aCompany];
	}


	/**
	 * 转移人员列表
	 * @return [array]
	 */
	public function transferListAction ()
	{
		$this->listAction();
	}


	/**
	 * 批量转移员工
	 * @return [json]
	 */
	public function transferAction ()
	{
		$deptId = intval($this->getParam('iDeptID'));
		if (!$deptId || !isset($this->dept[$deptId])) {
			return $this->showMsg('转移部门'.self::ERROR, false);
		}

		$employeeIds = strval($this->getParam('employeeIds'));
		if ($employeeIds) {
			$aEmployeeID = explode(',', $employeeIds);
			
			foreach ($aEmployeeID as $key => $value) {
				if (intval($value) < 1) {
					unset($aEmployeeID[$key]);
				}
			}

			if (!$aEmployeeID) {
				return $this->showMsg('员工ID'.self::ERROR, false);
			}

			$aEmployee = Model_CustomerNew::getListByPKIDs($aEmployeeID);
			foreach ($aEmployee as $key => $value) {
				$row = Model_Company_Company::checkIsExist($value['iUserID'], $this->enterpriseId);
				if (!$row) {
					unset($aEmployeeID[$key]);
				}
				$aRow[$key] = $row;
			}

			$data['iDeptID'] = $deptId;
			foreach ($aEmployeeID as $key => $value) {
				$data['iUserID'] = $value;
				$data['iAutoID'] = $aRow[$key]['iAutoID'];
				try {
					$upd = Model_Company_Company::updData($data);
				} catch (Exception $e) {
					echo $e->getMessage();
				}				
			}

			return $this->showMsg('批量转移成功', true); 
		} else {
			return $this->showMsg('员工ID'.self::ERROR, false);
		}
	}

}
