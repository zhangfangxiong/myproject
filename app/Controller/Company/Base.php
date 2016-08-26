<?php

/**
 * 企业后台_基类
 * User: xuchuyuan
 * Date: 16/4/14 15:00
 */
class Controller_Company_Base extends Controller_Admin_Base
{

	public $enterpriseId = null;

	public $aLevel = null;

	public $level = null;
	
	public $dept  = null;
	
	public $state = null;

	public $enterprise = null;

	public $deptAutoId = null;

	public $familyRelation = null;

	public $sexPrice = [
		'1' => 'iManPrice', 
		'2' => 'iWomanPrice1',
		'3' => 'iWomanPrice2',
	];

	const MAIL_KEY = 'MAIL_FORMAT_';

	public function actionBefore ()
	{
		parent::actionBefore();
		if ($this->aCurrUser['iType'] == 1) {
			$iHrID = $this->getParam('iCookieHRID');
			if (!$iHrID) {				
				$this->enterpriseId = Util_Cookie::get('iHrID');
			} else {
				$this->enterpriseId = $iHrID;				
			}

 			$row = Model_User::getRow(['where' => [
 				'iUserID' => $this->enterpriseId,
 				'iCustomerManager' => $this->aCurrUser['iUserID'],
 				'iStatus >' => Model_Company_Department::STATUS_INVALID
 			]]);

 			if (!$row) {
 				$this->redirect('/admin/user/myclient');
 			}

 			Util_Cookie::set('iHrID', $this->enterpriseId, '86400 * 7');
		}

		if ($this->aCurrUser['iType'] == 2) {
			$this->enterpriseId = intval($this->aCurrUser['iUserID']);	
		}	

		$aUser = Model_User::getDetail($this->enterpriseId);
		if (!$this->enterpriseId || !$aUser || $aUser['iStatus'] == Model_User::STATUS_INVALID 
			|| $aUser['iType'] != Model_User::TYPE_HR) {
			return $this->showMsg('不存在该企业', false);
		}

		$aDept = Model_Company_Department::getRow([
			'where' => [
				'iEnterpriseID' => $this->enterpriseId,
				'iParentID'     => 0,
				'iStatus'		=> Model_Company_Department::STATUS_VALID
			]
		]);		

		$data['iEnterpriseID'] = $this->enterpriseId;
		$data['sDeptName'] = $aUser['sRealName'];
		if (!$aDept) {			
			$this->deptAutoId = Model_Company_Department::addData($data);
			if (!$this->deptAutoId) {
				return $this->showMsg('操作失败,请重试', false);
			}
		} else if($aDept['sDeptName'] != $aUser['sRealName']) {
			$data['iAutoID']  = $aDept['iAutoID'];
			$this->deptAutoId = $aDept['iAutoID'];		
			Model_Company_Department::updData($data);
		} else {
			$this->deptAutoId = $aDept['iAutoID'];
		}
		
		$this->setBasicData();
		$this->enterprise = $aUser;

		$this->assign('iEnterpriseId', $this->enterpriseId);
	}


	/**
	 * 设置基础数据(部门、职级、状态)
	 */
	public function setBasicData()
	{
		$where = [ 
			'iStatus' => Model_Company_Level::STATUS_VALID,
			'iEnterpriseID' => $this->enterpriseId 
		];
		
		$this->aLevel = Model_Company_Level::getAll([
			'where' => $where
		]);
		if ($this->aLevel) {
			foreach ($this->aLevel as $key => $value) {
				$this->level[$value['iAutoID']] = $value['sLevelName'];
			}
		}		

		$dept = Model_Company_Department::getAll([
			'where' => $where
		]);
		if ($dept) {
			foreach ($dept as $key => $value) {
				$this->dept[$value['iAutoID']] = $value['sDeptName'];
			}
		}	

		$this->state = Yaf_G::getConf('aCompanyState');

		$this->familyRelation = Yaf_G::getConf('aRelationship');

		$this->assign('aLevel', $this->level);
		$this->assign('aDeptSet', $this->dept);
		$this->assign('aState', $this->state);
		$this->assign('aFamilyRelation', $this->familyRelation);
	}

	/**
	 * 封装完整员工信息
	 * @param [array]
	 */
	public function setEmployeeData ($aEmployee)
	{
		if ($aEmployee) {
			foreach ($aEmployee as $key => &$value) {
				$aCustomer = Model_CustomerNew::getDetail($value['iUserID']);
				$value['sRealName'] = $aCustomer['sRealName'];
				$value['sMobile'] = $aCustomer['sMobile'];
				$value['iSex'] = $aCustomer['iSex'];
				$value['iMarriage'] = $aCustomer['iMarriage'];
				$value['sIdentityCard'] = $aCustomer['sIdentityCard'];
			}
		}	

		return $aEmployee;
	}


	/**
	 * 获取最近三个月的体检员工id
	 * @param  $[iPlanID] [<体检计划id>]
	 * @param  $[iHRID] [<体检产品id>]
	 * @return [array]
	 */
	public function getCustomerLastThreeMonths ($iHRID, $iPlanID=0)
	{
		$sCardID = $sUserID = '';
		$aAppointment = [];
		$where = [
			'iCreateUserID' => $iHRID,			
			'iStatus' => Model_OrderCard::STATUS_VALID,

		];
		if ($iPlanID) {
			$where['iPlanID'] = $iPlanID;
		}

		$aOrder = Model_OrderCard::getAll(['where' => $where]);
		if ($aOrder) {
			foreach ($aOrder as $key => $value) {
				$aCardID[] = $value['iAutoID'];
			}
			if ($aCardID) {
				$sCardID = implode(',', $aCardID);
				$aAppointment = Model_OrderCardProduct::getAll(['where' => [
					'iCardID IN'  => $sCardID,
					'iPhysicalTime >' => strtotime("-3 Months")
				]]);
			}
		}
		if ($aAppointment) {
			foreach ($aAppointment as $key => $value) {
				if (!in_array($value['iAutoID'], $aUserID)) {
					$aCardIDs[] = $value['iAutoID'];
				}
			}
			if ($aCardIDs) {
				$aCards = Model_OrderCard::getListByPKIDs($aCardIDs);
				if ($aCards) {
					foreach ($aCards as $key => $value) {
						if ($value['iUserID']) {
							$aUserID[] = $value['iUserID'];
						}							
					}	
				}

				$sUserID = implode(',', $aUserID);			
			}
			
		}

		return $sUserID;
	}

	/**
	 * 获取体检计划下的用户ids
	 * @param  [type] $iPlanID [description]
	 * @return [string]
	 */
	public function getPlanUserIDs ($iPlanID)
	{
		$aUserIDs = [];
		$aOrder = Model_OrderCard::getAll(['where' => [
        	'iPlanID' => $iPlanID,
        	'iStatus IN' => ['-99', 1]
        ]]);
        if ($aOrder) {
        	foreach ($aOrder as $key => $value) {
        		if ($value['iUserID']) {
        			$aUserIDs[] = $value['iUserID'];	
        		}
        	}	
        }
        
        $ids = implode(',', $aUserIDs);
        return $ids;
	}

	/**
	 * 去除空id
	 * @param  [type] $ids [description]
	 * @return [string]
	 */
	public function removeEmptyIDs ($ids)
	{
		$aIDs = explode(',', $ids);
		foreach ($aIDs as $key => $value) {
			if (!intval($value)) {
				unset($aIDs[$key]);
			} else {
				$aIDs[$key] = intval($value);
			}
		}
		$ids = implode(',', $aIDs);

		return $ids;
	}


	/**
	 * 获取体检计划下的产品列表
	 * @param  [type] $iPlanID [description]
	 * @return [array]
	 */
	public function getPlanProduct ($iPlanID)
	{
		$aSelProduct = [];
		$aUser = Model_User::getDetail($this->enterpriseId);
		$aPlanProduct = Model_Physical_PlanProduct::getAll(['where' => [
			'iPlanID' => $iPlanID,
			'iProductID >' => 0,
			'iStatus' => Model_Physical_PlanProduct::STATUS_VALID,
		]]);
		if ($aPlanProduct) {
			foreach ($aPlanProduct as $key => $value) {
				$aHrProductIDs[] = $value['iProductID'];
			}
			$sHrProductID = implode(',', $aHrProductIDs);
			$aHrProduct = Model_Product::getAllUserProduct($this->enterpriseId, 1, $aUser['iChannel'], $sHrProductID);

			foreach ($aHrProduct as $key => $value) {
				$aSelProduct[$value['iProductID']] = $value['sProductName'];
			}
		}

		return $aSelProduct;
	}

	/**
	 * 获取体检计划下的订单id
	 */
	public function getPlanOrder ($iPlanID)
	{
		$aOrderIDs = [];
		$aOrder = Model_OrderInfo::getAll(['where' => [
        	'iPlanID' => $iPlanID,
        	'iOrderType' => 4,
        	'iStatus IN' => [0, 1]
        ]]);
        if ($aOrder) {
        	foreach ($aOrder as $key => $value) {
        		$aOrderIDs[] = $value['iOrderID'];
        	}	
        }
        
        return $aOrderIDs;
	}

}