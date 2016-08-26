<?php
/**
 * User: xcy
 * Date: 2016/5/30
 * Time: 16:00
 */
class Controller_Supplier_Physical extends Controller_Supplier_Base 
{

	public function actionBefore ()
	{
		parent::actionBefore();
	}


	/**
	 * 体检人员列表
	 * @return [type] [description]
	 */
	public function listAction ()
	{
		if (!$this->sStoreIDs) {
			return;
		}

		$where = [
			'iStoreID IN' => $this->sStoreIDs,
			'iPreStatus' => Model_OrderCardProduct::STATUS_VALID,
		];
		$page  = $this->getParam('page') ? intval($this->getParam('page')) : 1;
		$param = $this->getParams();
		
		if (!empty($param['sRealName'])) {
			$aUser = Model_CustomerNew::getAll(['where' => [
				'sRealName LIKE' => '%' . strval($param['sRealName']) . '%',
				'iStatus >' => Model_CustomerNew::STATUS_INVALID
			]]);
			if ($aUser) {
				foreach ($aUser as $k => $v) {
					if ($v['iUserID']) $aUserIDs[] = $v['iUserID'];
				}
				if (!empty($aUserIDs)) {
					$sUserIDs = implode(',', $aUserIDs);
					$aOrders = Model_OrderCard::getAll(['where' => [
						'iUserID IN' => $sUserIDs,
					]]);
					if ($aOrders) {
						foreach ($aOrders as $key => $value) {
							if($value['iCardID']) $aOrderIDs[] = $value['iCardID'];
						}	
						$sOrderIDs = implode(',', $aOrderIDs);					
						$where['iCardID IN'] = $sOrderIDs;
					}
				}
			} else {
				$this->redirect('/supplier/physical/list');
			}
		}
		
		!empty($param['sStartDate']) 
		? $where['iOrderTime >='] = strtotime($param['sStartDate'] . '00:00:00') : '';

		!empty($param['sEndDate']) 
		? $where['iOrderTime <='] = strtotime($param['sEndDate'] .' 23:59:59') : '';

		('-1' == $param['iStatus'])	|| !isset($param['iStatus'])	
		? $where['iBookStatus >'] = Model_Physical_Product::STATUS_UNCONFIRM
		: $where['iBookStatus '] = intval($param['iStatus']);		
		
		$aList = Model_OrderCardProduct::getList($where, $page, 'iUpdateTime Desc');
		if ($aList['aList']) {
			foreach ($aList['aList'] as $key => $value) {
				$aCard = Model_OrderCard::getDetail($value['iCardID']);
				$userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
				if (!$userInfo) {
					unset($aList['aList'][$key]);
					continue;
				}
        		$aList['aList'][$key]['sUserName'] = $userInfo['sRealName'];
        		$aList['aList'][$key]['sIdentityCard'] = $userInfo['sIdentityCard'];
        		$aList['aList'][$key]['sSex'] = ($userInfo['iSex'] == 2) ? '女' : '男';
        		$aList['aList'][$key]['sOrderDate'] = $value['iOrderTime'] ? date('Y-m-d', $value['iOrderTime']) : '';
        		$aList['aList'][$key]['sCreateTime'] = $value['iReserveTime'] ? date('Y-m-d H:i:s', $value['iReserveTime']) : '';
        		$aList['aList'][$key]['sStatus'] = $this->aStatus[$value['iBookStatus']];

        		$storeInfo = Model_Store::getDetail($value['iStoreID']);
        		$aList['aList'][$key]['sStoreName'] = $storeInfo['sName'];
			}
		}
		
		$this->assign('aList', $aList);
		$this->assign('aParam', $param);
		$this->assign('iPage',  $page);
	}

	/**
	 * 体检详情
	 * @return [type] [description]
	 */
	public function detailAction ()
	{
		if ($this->isPost()) {
    		$params = $this->getParams();
    		$aPhysical = Model_OrderCardProduct::getDetail($params['id']);
    		if ($aPhysical && $aPhysical['iStatus'] > Model_Physical_Product::STATUS_UNCONFIRM) {
    			$data['iAutoID'] = $params['id'];
    			$data['iIsSerious'] = $params['iIsSerious'];
    			$data['sSeriousRemark'] = $params['sSeriousRemark'];
    			Model_OrderCardProduct::updData($data);
    			return $this->showMsg('提交成功', true, '/supplier/physical/detail/id/'.$params['id']);
    		} else {
    			return $this->showMsg('无此信息', false, '/supplier/physical/list');
    		}
    	} else {
    		$id = $this->getParam('id');
	    	if (!intval($id)) {
	    		$this->redirect('/supplier/physical/list');
	    	}

	    	$aDetail = Model_OrderCardProduct::getDetail($id);	    
	    	if ($aDetail && $aDetail['iStatus'] > Model_Physical_Product::STATUS_UNCONFIRM) {
    			$aOrder = Model_OrderInfo::getDetail($aDetail['iOrderID']);
    			$aCard = Model_OrderCard::getDetail($aDetail['iCardID']);
    			$aDetail['sOrderCode'] = $aOrder['sOrderCode'];
    	
			    $storeInfo = Model_Store::getDetail($aDetail['iStoreID']);
			    $aDetail['sStoreName'] = $storeInfo['sName'];

			    $userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
				$aDetail['sRealName'] = $userInfo['sRealName'];
				$aDetail['sSex'] = ($userInfo['iSex'] == 2) ? '女' : '男';
				$aDetail['sMarriage'] = ($userInfo['iMarriage'] == 2) ? '已婚' : '未婚';
				$aDetail['sIdentityCard'] = $userInfo['sIdentityCard'];
				$aDetail['sBirthDate'] = $userInfo['sBirthDate'];
				$aDetail['sMobile'] = $userInfo['sMobile'];
				$aDetail['sEmail'] = $userInfo['sEmail'];;

				$aDetail['sOrderDate'] = $aDetail['iOrderTime'] ? date('Y-m-d', $aDetail['iOrderTime']) : '';
				$aDetail['sPhysicalTime'] = $aDetail['iPhysicalTime'] ? date('Y-m-d H:i:s', $aDetail['iPhysicalTime']) : '';
				$aDetail['sReportTime'] = $aDetail['iReportTime'] ? date('Y-m-d H:i:s', $aDetail['iReportTime']) : '';
				$aDetail['sPaperReport'] = ($aCard['iPaperReport'] == 1) ? '是' : '否';

				$aDetail['sOrderStatus'] = $this->aStatus[$aDetail['iBookStatus']];
				$aDetail['sPreStatus'] = ($aDetail['iPreStatus'] == 1) ? '已确认' : '待确认';

				$this->assign('aDetail', $aDetail);
	    	} else {
	    		$this->redirect('/supplier/order/index/type/'.$type);
	    	}
    	}
	}

	/**
	 * 到检确认
	 * @return [type] [description]
	 */
	public function confirmAction ()
	{
		$id = $this->getParam('id');
		$type = $this->getParam('type');
		$aPhysical = Model_OrderCardProduct::getDetail($id);
		if ($aPhysical && $aPhysical['iBookStatus'] >= 1) {
			$data['iAutoID'] = $id;
			$data['iBookStatus'] = $type;
			
			if ($type == 2) {
				$data['iPhysicalTime'] = time();
			}
			if ($type == 5) {
				$data['iReportTime'] = time();
				if ($aPhysical) {
					$aCard = Model_OrderCard::getDetail($aPhysical['iCardID']);
					//年度体检报告发送短信
					if (1 == $aCard['iPhysicalType']) {
						Model_OrderCardProduct::sendReportMailMsg($aCard);	
					}					
				}				
			}
			Model_OrderCardProduct::updData($data);
			
			return $this->showMsg('确认成功', true, '/supplier/physical/detail/id/'. $id);
		} else {
			return $this->showMsg('参数错误', false);
		}
	}
}