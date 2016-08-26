<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/14
 * Time: 9:50
 */
class Controller_Admin_Balance extends Controller_Admin_Base {
	
	/**
	 * 标记为已打款
	 */
	public function balanceAction() {
		$iBalanceID = intval ( $this->getParam ( 'id' ) );
		Model_Balance::updData ( array (
				'iStatus' => 3,
				'iBalanceID' => $iBalanceID 
		) );
		
		return $this->showMsg('该结算标记为已打款', true);
	}
	
	/**
	 * 结算明细
	 */
//	public function detailAction() {
//		$iBalanceID = intval ( $this->getParam ( 'id' ) );
//		$aBalance = Model_Balance::getDetail ( $iBalanceID );
//		$aList = Model_BalancePhysical::getPhysicalList ( $iBalanceID );
//		$aSupplier = Model_Type::getDetail ( $aBalance ['iSupplierID'] );
//
//		$this->assign ( 'aList', $aList );
//		$this->assign ( 'aBalance', $aBalance );
//		$this->assign ( 'aSupplier', $aSupplier );
//		$this->assign ( 'aStatus', Util_Common::getConf ( 'status', 'physical' ) );
//		$this->assign ( 'aChannel', Util_Common::getConf ( 'aChannel' ) );
//	}

	public function detailAction(){
		$id = $this->getParam('id');
		if (!intval($id)) {
			$this->redirect('/admin/order/balance');
		}

		$aDetail = Model_Balance::getDetail(intval($id));
		$aSupplier = array();

		if ($aDetail && $aDetail['iStatus'] > Model_Balance::STATUS_INVALID) {
			$aSupplier = Model_Type::getDetail ( $aDetail ['iSupplierID'] );

			$aDetail['iCount'] = 0;
			$aPhysical = Model_BalancePhysical::getAll(['where' => [
				'iBalanceID' => $id,
				'iStatus' => Model_BalancePhysical::STATUS_VALID
			]]);

			$aProduct = [];
			if ($aPhysical) {
				$aPhysicalIDs = [];
				$sPhysicalIDs = '';
				foreach ($aPhysical as $key => $value) {
					if ($value['iPhysicalID']) {
						$aPhysicalIDs[] = $value['iPhysicalID'];
					}
				}
				if ($aPhysicalIDs) {
					$sPhysicalIDs = implode(',', $aPhysicalIDs);
					$aDetail['iCount'] = count($aPhysicalIDs);

					$aProduct = Model_OrderCardProduct::getListByPKIDs($sPhysicalIDs);
					foreach ($aProduct as $k => $val) {
						$storeInfo = Model_Store::getDetail($val['iStoreID']);
						$aProduct[$k]['sStoreName'] = $storeInfo['sName'];
						$aProduct[$k]['sCityName'] = $this->aStoreCity[$storeInfo['iCityID']];

						$aCard = Model_OrderCard::getDetail($val['iCardID']);
						$userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
						if (!$userInfo) {
							unset($aProduct[$k]);
							continue;
						}
						$aProduct[$k]['isPhysicalCard'] = ( 1 == intval($aCard['iOrderType']) || 2 == intval($aCard['iOrderType']) ) ? "是" : "否";

						$aProduct[$k]['sRealName'] = $userInfo['sRealName'];
						$aProduct[$k]['sSex'] = ($userInfo['iSex'] == 2) ? '女' : '男';

						$aProduct[$k]['sPhysicalTime'] = $val['iOrderTime'] ? date('Y-m-d H:i:s', $val['iOrderTime']) : '';
						$aProduct[$k]['sOrderStatus'] = $this->aStatus[$val['iBookStatus']];

						$aProduct[$k]['sChannelPrice'] = 0;
						$aProduct[$k]['sSupplierPrice'] = 0;
						$aProduct[$k]['iPersonPrice'] = 0;
						$aProduct[$k]['sChannel'] = "";

						$storeCodeInfo = Model_StoreCode::getData($val['iProductID'], $val['iStoreID'], $userInfo['iSex']);
						if(!empty($storeCodeInfo)) {
							$aProduct[$k]['sChannelPrice'] = $storeCodeInfo['sChannelPrice'];
							$aProduct[$k]['sSupplierPrice'] = $storeCodeInfo['sSupplierPrice'];
						}

						if(!empty($aCard)) {
							if( !(2 == $aCard['iCreateUserType'] && Model_OrderCard::PAYTYPE_COMPANY == $aCard['iPayType']) ) {
								$orderProduct = Model_OrderProduct::getDetail($aProduct['iOPID']);
								if(!empty($orderProduct)) {
									$aProduct[$k]['iPersonPrice'] = $orderProduct['sTotalPrice'];
								}
							}

							if( 2 == $aCard['iCreateUserType'] && !empty($aCard['iCreateUserID']) ) {
								$company = Model_User::getDetail($aCard['iCreateUserID']);
								if(!empty($company)) {
									$iChnnel = $company['iChannel'];
									if(!empty($iChnnel)) {
										$aChannel = Yaf_G::getConf('aChannel');
										$aProduct[$k]['sChannel'] = isset($aChannel[$iChnnel]) ? $aChannel[$iChnnel] : '';
									}
								}
							}
						}
					}
				}
			}

			$this->assign ( 'aSupplier', $aSupplier );
			$this->assign ( 'aStatus', Util_Common::getConf ( 'status', 'physical' ) );
			$this->assign ( 'aBalanceStatus', Util_Common::getConf ( 'aStatus', 'balance' ) );
			$this->assign('aBalance', $aDetail);
			$this->assign('aList', $aProduct);
		} else {
			$this->redirect('/admin/order/balance');
		}
	}
	
	/**
	 * 导出结算明细
	 */
	public function exportAction() {
		$id = intval($this->getParam('id'));

		$aDetail = Model_Balance::getDetail(intval($id));
		$aSupplier = array();
		$states = Util_Common::getConf ( 'status', 'physical' );

		if ($aDetail && $aDetail['iStatus'] > Model_Balance::STATUS_INVALID) {
			$aSupplier = Model_Type::getDetail ( $aDetail ['iSupplierID'] );

			$aDetail['iCount'] = 0;
			$aPhysical = Model_BalancePhysical::getAll(['where' => [
				'iBalanceID' => $id,
				'iStatus' => Model_BalancePhysical::STATUS_VALID
			]]);

			$aProduct = [];
			if ($aPhysical) {
				$aPhysicalIDs = [];
				$sPhysicalIDs = '';
				foreach ($aPhysical as $key => $value) {
					if ($value['iPhysicalID']) {
						$aPhysicalIDs[] = $value['iPhysicalID'];
					}
				}
				$aData = array();

				if ($aPhysicalIDs) {
					$sPhysicalIDs = implode(',', $aPhysicalIDs);
					$aDetail['iCount'] = count($aPhysicalIDs);

					$aProduct = Model_OrderCardProduct::getListByPKIDs($sPhysicalIDs);

					foreach ($aProduct as $k => $val) {
						$storeInfo = Model_Store::getDetail($val['iStoreID']);

						$aCard = Model_OrderCard::getDetail($val['iCardID']);
						$userInfo = Model_CustomerNew::getDetail($aCard['iUserID']);
						if (!$userInfo) {
							unset($aProduct[$k]);
							continue;
						}

						$item = array(
							'sRealName' => $userInfo['sRealName'],
							'sSex' => ($userInfo['iSex'] == 2) ? '女' : '男',
							'sProduceName' => $val['sProductName'],
							'sStoreName' => $storeInfo['sName'],
							'iPayMoney' => ( 1 == intval($aCard['iOrderType']) || 2 == intval($aCard['iOrderType']) ) ? "是" : "否",
							'siPhysicalDate' => !empty($val['iOrderTime']) ? date('Y-m-d H:i:s', $val['iOrderTime']) : '',
							'iPayMoney' => 0,
							'sAFCost' => 0,
							'sSupplierCost' => 0,
							'sChannel' => "",
							'sStatus' => $states[$val['iBookStatus']]
						);


						$storeCodeInfo = Model_StoreCode::getData($val['iProductID'], $val['iStoreID'], $userInfo['iSex']);
						if(!empty($storeCodeInfo)) {
							$item['sAFCost'] = $storeCodeInfo['sChannelPrice'];
							$item['sSupplierCost'] = $storeCodeInfo['sSupplierPrice'];
						}

						if(!empty($aCard)) {
							if( !(2 == $aCard['iCreateUserType'] && Model_OrderCard::PAYTYPE_COMPANY == $aCard['iPayType']) ) {
								$orderProduct = Model_OrderProduct::getDetail($aProduct['iOPID']);
								if(!empty($orderProduct)) {
									$item['iPayMoney'] = $orderProduct['sTotalPrice'];
								}
							}

							if( 2 == $aCard['iCreateUserType'] && !empty($aCard['iCreateUserID']) ) {
								$company = Model_User::getDetail($aCard['iCreateUserID']);
								if(!empty($company)) {
									$iChnnel = $company['iChannel'];
									if(!empty($iChnnel)) {
										$aChannel = Yaf_G::getConf('aChannel');
										$item['sChannel'] = isset($aChannel[$iChnnel]) ? $aChannel[$iChnnel] : '';
									}
								}
							}
						}

						$aData[] = $item;

					}
				}

				$aTitle = array (
					'sRealName' => '姓名',
					'sSex' => '性别',
					'sProduceName' => '体验项目',
					'sStoreName' => '体检地点',
					'iPayMoney' => '体检卡',
					'siPhysicalDate' => '体检日期',
					'iPayMoney' => '个人支付',
					'sAFCost' => 'AF结算',
					'sSupplierCost' => '供应商结算',
					'sChannel' => '客户来源',
					'sStatus' => '状态'
				);
				Util_File::exportCsv ( '订单明细-' . $id . '.csv', $aData, $aTitle );
				return false;
			}


		} else {
			$this->redirect('/admin/order/balance');
		}



	}
}