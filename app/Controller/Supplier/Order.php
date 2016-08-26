<?php
/**
 * User: xcy
 * Date: 2016/5/30
 * Time: 13:00
 */
class Controller_Supplier_Order extends Controller_Supplier_Base 
{
	
	/**
     * 预定信息
     * @return [array]
     */
    public function indexAction ()
    {
    	$type = $this->getParam('type');
    	if ($type == 2) {
    		$this->setMenu(2);
    		$status = 3;
    	} else {
    		$this->setMenu(1);
    		$status = 1;
    		$type = 1;
    	}    	
    	$aUser = Model_User::getDetail($this->aCurrUser['iUserID']);
    	if ($aUser && $aUser['iStatus'] !== Model_Store::STATUS_INVALID) {
	        if ($this->sStoreIDs) {
	        	$aPhysical = Model_OrderCardProduct::getAll([
	                'where' => [
	                    'iStoreID IN' => $this->sStoreIDs,
	                    'iBookStatus'  => $status,
	                    'iPreStatus' => Model_OrderCardProduct::STATUS_INVALID
	                ]
	            ]);
	            if ($aPhysical) {
	            	foreach ($aPhysical as $key => $value) {
	            		$aCard = Model_OrderCard::getDetail($value['iCardID']);
	            		if (!$aCard) {
	            			unset($aPhysical[$key]);
	            			continue;
	            		}
	            		$userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
	            		if (!$userInfo) {
	            			unset($aPhysical[$key]);
	            			continue;
	            		}
	            		$aPhysical[$key]['sUserName'] = $userInfo['sRealName'];
	            		$aPhysical[$key]['sIdentityCard'] = $userInfo['sIdentityCard'];
	            		$aPhysical[$key]['sSex'] = ($userInfo['iSex'] == 2) ? '女' : '男';
	            		$aPhysical[$key]['sOrderDate'] = $value['iOrderTime'] ? date('Y-m-d', $value['iOrderTime']) : '';
	            		
	            		$storeInfo = Model_Store::getDetail($value['iStoreID']);
	            		$aPhysical[$key]['sStoreName'] = $storeInfo['sName'];
	            	}
	            }
	        }

	        $this->assign('aPhysical', $aPhysical);
	        $this->assign('iType', $type);
    	} else {
    		$this->redirect('/admin/supplierlogin');
    	}
    }

    /**
     * 查看详情
     * @return [type] [description]
     */
    public function detailAction ()
    {
    	if ($this->isPost()) {
    		$params = $this->getParams();
    		if (!$params['sOrderDate']) {
    			return $this->showMsg('预约日期不能为空', false, '');
    		}

    		$aPhysical = Model_OrderCardProduct::getDetail($params['id']);
    		if ($aPhysical && $aPhysical['iStatus']) {
    			$data['iAutoID'] = $params['id'];    			
    			$data['iOrderTime'] = strtotime($params['sOrderDate']);
    			$data['iPreStatus'] = 1;
    			Model_OrderCardProduct::sendMailMsg($params['id']);
    			Model_OrderCardProduct::updData($data);
    			return $this->showMsg('修改成功', true, '/supplier/order/detail/type/'.$params['type']);
    		} else {
    			return $this->showMsg('无此信息', false, '/supplier/order/detail/type/'.$params['type']);
    		}

    		return $this->showMsg('确认成功', true);
    	} else {
    		$id = $this->getParam('id');
	    	$type = ($this->getParam('type') == 2) ? 2 : 1; 
	    	if (!intval($id)) {
	    		$this->redirect('/supplier/order/index/type'.$type);
	    	}

	    	$aDetail = Model_OrderCardProduct::getDetail($id);	 
	    	if ($aDetail && $aDetail['iPreStatus'] == 0) {
	    		$aCard = Model_OrderCard::getDetail($aDetail['iCardID']);
    			$aOrder = Model_OrderInfo::getDetail($aDetail['iOrderID']);
    			$aDetail['sOrderCode'] = $aOrder['sOrderCode'];

	    		$productInfo = Model_Product::getDetail($aDetail['iProductID']);
			    $aDetail['sProductName'] = $productInfo['sProductName'];
			    
			    $storeInfo = Model_Store::getDetail($aDetail['iStoreID']);
			    $aDetail['sStoreName'] = $storeInfo['sName'];

			    $userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
				$aDetail['sRealName'] = $userInfo['sRealName'];
				$aDetail['sSex'] = ($userInfo['iSex'] == 2) ? '女' : '男';
				$aDetail['sMarriage'] = ($userInfo['iMarriage'] == 2) ? '已婚' : '未婚';
				$aDetail['sIdentityCard'] = $userInfo['sIdentityCard'];
				$aDetail['sBirthDate'] = $userInfo['sBirthDate'];
				$aDetail['sMobile'] = $userInfo['sMobile'];
				$aDetail['sEmail'] = '';

				$aDetail['sOrderDate'] = $aDetail['iOrderTime'] ? date('Y-m-d', $aDetail['iOrderTime']) : '';
				$aDetail['sPhysicalTime'] = $aDetail['iPhysicalTime'] ? date('Y-m-d H:i:s', $aDetail['iPhysicalTime']) : '';
				$aDetail['sReportTime'] = $aDetail['iReportTime'] ? date('Y-m-d H:i:s', $aDetail['iReportTime']) : '';
				$aDetail['sPaperReport'] = ($aDetail['iPaperReport'] == 1) ? '是' : '否';

				$aStatus = Yaf_G::getConf('status', 'physical');
				$aDetail['sOrderStatus'] = $aStatus[$aDetail['iBookStatus']];
				$aDetail['sPreStatus'] = ($aDetail['iPreStatus'] == 1) ? '已确认' : '待确认';

				$this->assign('aDetail', $aDetail);
				$this->assign('iType', $type);
	    	} else {
	    		$this->redirect('/supplier/order/index/type/'.$type);
	    	}
    	}    	
    }

    /**
	 * 导出
	 */
	public function exportAction() {
		$aUser = Model_User::getDetail($this->aCurrUser['iUserID']);
    	if ($aUser && $aUser['iStatus'] !== Model_Store::STATUS_INVALID) {
    		$aStore = Model_Store::getAll([
	            'where' => [
	                'iSupplierID' => $aUser['iSupplierID'],
	                'iStatus' => Model_Store::STATUS_VALID
	            ]    
	        ]);

	        $aPhysical = [];
	        if ($aStore) {
	        	$aStoreIDs = [];
		        $sStoreIDs = '';
		        foreach ($aStore as $key => $value) {
		        	if ($value['iStoreID']) {
		        		$aStoreIDs[] = $value['iStoreID']; 	
		        	}	        	
		        }
		        if ($aStoreIDs) {
		        	$sStoreIDs = implode(',', $aStoreIDs);
		        	$aPhysical = Model_OrderCardProduct::getAll([
		                'where' => [
		                    'iStoreID IN' => $sStoreIDs,
		                    'iBookStatus'  => $status,
		                    'iPreStatus' => Model_OrderCardProduct::STATUS_INVALID
		                ]
		            ]);
		            if ($aPhysical) {
		            	foreach ($aPhysical as $key => $value) {
		            		$aCard = Model_OrderCard::getDetail($value['iCardID']);
		            		if (!$aCard) {
		            			unset($aPhysical[$key]);
		            			continue;
		            		}
		            		$userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
		            		if (!$userInfo) {
		            			unset($aPhysical[$key]);
		            			continue;
		            		}
		            		$aPhysical[$key]['sUserName'] = $userInfo['sRealName'];
		            		$aPhysical[$key]['sIdentityCard'] = $userInfo['sIdentityCard'];
		            		$aPhysical[$key]['sSex'] = ($userInfo['iSex'] == 2) ? '女' : '男';
		            		$aPhysical[$key]['sMarriage'] = ($userInfo['iMarriage'] == 2) ? '已婚' : '未婚';
		            		$aPhysical[$key]['sMobile'] = $userInfo['sMobile'];

		            		$storeInfo = Model_Store::getDetail($value['iStoreID']);
		            		$aPhysical[$key]['sStoreName'] = $storeInfo['sName'];
		            		
		            		$aPhysical[$key]['sOrderDate'] = $value['iOrderTime'] ? date('Y-m-d', $value['iOrderTime']) : '';
		            		$aPhysical[$key]['sCreateTime'] = date('Y-m-d H:i:s', $value['iCreateTime']);
		            		$aPhysical[$key]['sPaperReport'] = ($value['iPaperReport'] == 1) ? '是' : '否';
		            	}

		            	$aData = array ();
						foreach ( $aPhysical as $v ) {
							$aData [] = array (
								'sRealName' => $v ['sUserName'],
								'sSex' => $v ['sSex'],
								'sMarriage' => $v ['sMarriage'],
								'sIdentityCard' => $v ['sIdentityCard'].'\\n',
								'sMobile' => $v['sMobile'],
								'sOrderDate' => $v['sOrderDate'],
								'sStoreName' => $v ['sStoreName'],
								'sProductName' => $v ['sProductName'],
								'sPaperReport' => $v['sPaperReport'],
								'sCreateTime' => $v ['sCreateTime'],
							);
						}
						
						$aTitle = array (
								'sRealName' => '姓名',
								'sSex' => '性别',
								'sMarriage' => '婚姻状况',
								'sIdentityCard' => '证件号码',
								'sMobile' => '联系电话',
								'sOrderDate' => '预约日期',
								'sStoreName' => '预约门店',
								'sProductName' => '体检项目',
								'sPaperReport' => '是否提供纸质报告',
								'sCreateTime' => '下单时间',
						);
						
						Util_File::exportCsv ('预约待确认名单.csv', $aData, $aTitle );
						return false;
		            }
		        }
	        }
    	}		
	}

    private function setMenu($iMenu)
    {
        $aMenu = [
            1 => [
                'url' => '/supplier/order/index/type/1',
                'name' => '预订信息',
            ],
            2 => [
                'url' => '/supplier/order/index/type/2',
                'name' => '退订信息',
            ]                        
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }
}