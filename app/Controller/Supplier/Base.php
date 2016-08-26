<?php

/**
 * 供应商后台_基类
 * User: xuchuyuan
 * Date: 16/5/30 16:00
 */
class Controller_Supplier_Base extends Controller_Admin_Base
{

	public $aStatus = null;

	public $aBalanceStatus = null;

	public $sStoreIDs = null;

	public $iSupplierID = null;

	public $aStoreCity = null;

	public function actionBefore ()
	{
		parent::actionBefore();

		$this->aStatus = Yaf_G::getConf('status', 'physical');
		$this->assign('aStatus', $this->aStatus);

		$this->aBalanceStatus = Yaf_G::getConf('aStatus', 'balance');
		$this->assign('aBalanceStatus', $this->aBalanceStatus);

		$aUser = Model_User::getDetail($this->aCurrUser['iUserID']);
		if ($aUser && $aUser['iStatus'] !== Model_Store::STATUS_INVALID) {	
			$this->iSupplierID = $aUser['iSupplierID'];	
			$where = [
                'iSupplierID' => $this->iSupplierID,
                'iStatus' => Model_Store::STATUS_VALID
            ];	

            $this->getParam('iCityID') ? $where['iCityID'] = $this->getParam('iCityID') : '';
    		$aStore = Model_Store::getAll(['where' => $where]);
	        if ($aStore) {
	        	$aStoreIDs = [];
		        $sStoreIDs = '';
		        foreach ($aStore as $key => $value) {
		        	if ($value['iStoreID']) {
		        		$aStoreIDs[] = $value['iStoreID']; 	
		        	}	        	
		        }
		        if ($aStoreIDs) {
		        	$this->sStoreIDs = implode(',', $aStoreIDs);
		        }
	        }

	        $aCity = Model_City::getAll([
	        	'where' => [
	        		'iStatus' => Model_City::STATUS_VALID
	        	],
	        	'order' => 'sPinyin ASC'    
	        ]);
	        foreach ($aCity as $k => $v) {
	        	$this->aStoreCity[$v['iCityID']] = $v['sCityName'];
	        }
	        $this->assign('aStoreCity', $this->aStoreCity);
    	} else {
    		$this->redirect('/admin/supplierlogin');
    	}
	}
}