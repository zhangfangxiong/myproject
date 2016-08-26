<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/14
 * Time: 9:50
 */
class Controller_Admin_Order extends Controller_Admin_Base
{
    public function stat1Action()
    {
		$aParam = $this->getParams();
   		$iPage = intval($this->getParam('page'));
		$aList = Model_OrderCardProduct::getStat1($aParam, $iPage);

		$this->assign('aData', $aList);
   		$this->assign('aSupplier', Model_Type::getOption('supplier'));
   		$this->assign('aCity', Model_City::getPairCitys(0));
   		$this->assign('aPreStatus', Util_Common::getConf('prestatus', 'physical'));
   		$this->assign('aStatus', Util_Common::getConf('status', 'physical'));
   		$this->assign('aPType', Util_Common::getConf('type', 'physical'));
   		$this->assign('aPlat', Util_Common::getConf('plat', 'physical'));
        $this->assign('iType', 1);
        $this->assign('aParam', $aParam);
    }
    
	public function stat2Action()
	{
		$aParam = $this->getParams();
		$aList = Model_Physical_Product::getStat2($aParam);
		$this->assign('aList', $aList);
		$this->assign('aParam', $aParam);
        $this->assign('iType', 2);
	}
	
	public function stat3Action()
	{
		$aParam = $this->getParams();
		$aList = Model_Physical_Product::getStat3($aParam);
		$this->assign('aList', $aList);
		$this->assign('aParam', $aParam);
        $this->assign('iType', 3);
	}
	
	public function stat4Action()
	{
		$aParam = $this->getParams();
		$aList = Model_Physical_Product::getStat4($aParam);
		$this->assign('aList', $aList);
		$this->assign('aParam', $aParam);
        $this->assign('iType', 4);
	}
    
	/**
	 * 体检产品结算
	 */
	public function balanceAction()
	{
		$aParam = $this->getParams();
   		$iPage = intval($this->getParam('page'));
		$aList = Model_Balance::getPageList($aParam, $iPage);
		$this->assign('aData', $aList);
		$this->assign('aParam', $aParam);
   		$this->assign('aSupplier', Model_Type::getOption('supplier'));
   		$this->assign('aStatus', Util_Common::getConf('aStatus', 'balance'));
	}
	
	/**
	 * 体检卡购买记录
	 */
	public function buylistAction()
	{
		$aParam = $this->getParams();
		$aParam['iOrderType'] = -1; //只显示体检卡
		$iPage = intval($this->getParam('page'));
		$aList = Model_OrderInfo::getPhisycalList($aParam, $iPage);
//var_dump($aList);exit;

		$this->assign('aParam', $aParam);
		$this->assign('aPayStatus', Util_Common::getConf('aPayStatus', 'order'));
		$this->assign('aPayType', Util_Common::getConf('aPayType', 'order'));
		$this->assign('aGenStatus', Util_Common::getConf('aGenStatus', 'order'));
		$this->assign('aCardType', Util_Common::getConf('aCardType', 'order'));
		$this->assign('aShippingStatus', Util_Common::getConf('aShippingStatus', 'order'));
		$this->assign('aList', $aList);
	}
	
	/**
	 * 体检数据核对
	 */
	public function checkAction()
	{
		$aParam = $this->getParams();
   		$iPage = intval($this->getParam('page'));
		$aList = Model_OrderCardProduct::getCheckList($aParam, $iPage);

		$iError = Model_OrderCardProduct::getCnt(['where' => ['iBookStatus' => 6]]);
//var_dump($aList);exit;
		$this->assign('aData', $aList);
		$this->assign('aSupplier', Model_Type::getOption('supplier'));
		$this->assign('aCity', Model_City::getPairCitys(0));
		$this->assign('aPreStatus', Util_Common::getConf('prestatus', 'physical'));
		$this->assign('aStatus', Util_Common::getConf('status', 'physical'));
		$this->assign('aPType', Util_Common::getConf('type', 'physical'));
		$this->assign('aMsgStatus', array(0 => '未发送', 1 => '已发送'));
		$this->assign('aEMailStatus', array(0 => '未发送', 1 => '已发送'));
		$this->assign('aShowStatus', array(0 => '是', 1 => '否'));
		$this->assign('aPlat', Util_Common::getConf('plat', 'physical'));
		$this->assign('iType', 1);
		$this->assign('iError', $iError);
		$this->assign('aParam', $aParam);
//		var_dump($aParam);exit;
	}

	/**
	 * 体检数据核对
	 */
	public function errorlistAction()
	{
		$aParam = $this->getParams();
		$iPage = intval($this->getParam('page'));
		$aParam['iBookStatus'] = 6;
		$aList = Model_OrderCardProduct::getErrorList($aParam, $iPage);

//var_dump($aList);exit;
		$this->assign('aData', $aList);
		$this->assign('aSupplier', Model_Type::getOption('supplier'));
		$this->assign('aCity', Model_City::getPairCitys(0));
		$this->assign('aPreStatus', Util_Common::getConf('prestatus', 'physical'));
		$this->assign('aStatus', Util_Common::getConf('status', 'physical'));
		$this->assign('aPType', Util_Common::getConf('type', 'physical'));
		$this->assign('aMsgStatus', array(0 => '未发送', 1 => '已发送'));
		$this->assign('aEMailStatus', array(0 => '未发送', 1 => '已发送'));
		$this->assign('aShowStatus', array(0 => '是', 1 => '否'));
		$this->assign('aPlat', Util_Common::getConf('plat', 'physical'));
		$this->assign('iType', 1);
		$this->assign('aParam', $aParam);
//		var_dump($aParam);exit;
	}
	
	/**
	 * 体检详情数据
	 */
	public function detailAction()
	{
	    $iCradID = intval($this->getParam('iCradID'));
		$icpID = intval($this->getParam('icpID'));

		$aCard = Model_OrderCard::getDetail($iCradID);
		$aCardProduct = Model_OrderCardProduct::getDetail($icpID);
	    $aUser = Model_Customer::getDetail($aCard['iUserID']);

		$aCustomerCompany = array();
		if(1 == intval($aCard['iCreateUserType'])) {
			$aCustomerCompany = Model_CustomerCompany::getRow(['where' => ['iUserID' => $aCard['iUserID'], 'iCreateUserID' => $aCard['iCreateUserID']]]);
		}

		$sPlanName = '';
		if(4 == $aCard['iOrderType']) {
		    $aPlan = Model_Physical_Plan::getDetail($aCard['iPlanID']);
			if(!empty($aPlan)) {
				$sPlanName = $aPlan['sPlanName'];
			}
		}
		$this->assign('sPlanName', $sPlanName);

		$this->assign('aCard', $aCard);
	    $this->assign('aCardProduct', $aCardProduct);
		$this->assign('aCustomerCompany', $aCustomerCompany);
	    $this->assign('aUser', $aUser);

		$this->assign('aPType', Util_Common::getConf('type', 'physical'));
		$this->assign('aPayType', Util_Common::getConf('paytype', 'physical'));
		$this->assign('aStatus', Util_Common::getConf('status', 'physical'));
		$this->assign('aPrestatus', Util_Common::getConf('prestatus', 'physical'));
	}
	
	/**
	 * 修改体检信息
	 */
	public function changeAction()
	{
	    $aParam = $this->getParams();

		Model_OrderCard::updData($aParam);

		$this->showMsg('体检信息修改成功！', true);

	}


	public function discardAction()
	{
		$aParam = $this->getParams();

		$card = Model_OrderCardProduct::getDetail($aParam['iAutoID']);
		if(!empty($card) && (2 == $card['iBookStatus'] || 4 == $card['iBookStatus'] || 5 == $card['iBookStatus'])) {
			$this->showMsg('无法作废！', true);
		}else{
			Model_OrderCardProduct::updData($aParam);

			$this->showMsg('体检信息修改成功！', true);
		}

	}

	/**
	 * 确认付款
	 */
	public function updateOrderAction()
	{
		$aParams = $this->getParams();

		Model_OrderInfo::updData($aParams);

		$this->showMsg('订单更新成功！', true);

	}

	//设置已出报告
	public function reportAction()
	{
		$aParam = $this->getParams();

		Model_OrderCardProduct::updData($aParam);

		$this->showMsg('体检信息修改成功！', true);
	}

	/**
	 * 批量作废卡
	 */
	public function batchDiscardAction()
	{
		$iCardIDs = $this->getParam('iCardIDs');


		Model_OrderCardProduct::batchDiscard($iCardIDs);

		$this->showMsg('批量作废成功！', true);

	}

	/**
	 * 批量发送体检通知
	 */
	public function batchSendAction()
	{
		$iCardIDs = addslashes($this->getParam('iCardIDs'));
		if(!empty($iCardIDs)) {
			$cards = Model_OrderCard::getCardinfoByIDs($iCardIDs);
			$data = array();

			if(!empty($cards)) {
				foreach($cards as $card) {
					if( isset($data[$card['iCardID']]) ) {
						continue;
					}else {
						$data = array(
							'sRealName' => $card['sRealName'],
							'sStartDate' => $card['sStartDate'],
							'sEndDate' => $card['sEndDate'],
							'sPhysicalNumber' => $card['sCardCode'],
							'sEmail' => $card['personEmail'],
							'sMobile' => $card['sMobile'],
							'iAutoID' => $card['iAutoID']
						);

						if(2 == $card['iCreateUserType']) {//公司用户
							$where = array(
								'iUserID' => $card['iUserID'],
								'iCreateUserID' => $card['iCreateUserID']
							);
							$customer_company = Model_CustomerCompany::getRow(['where' => $where]);

							if(!empty($customer_company)) {
								$data['sEnterprise'] = $customer_company['sCompanyName'];
								$data['sEmail'] = $customer_company['sEmail'];
							}

							if(1 == intval($card['iPhysicalType'])) {//年度体检

							}else if(2 == intval($card['iPhysicalType'])){//入职体检

							}else {
								continue;
							}
						}else if(1 == $card['iCreateUserType']){//个人用户

						}else{
							continue;
						}
					}
				}
			}
//			if(!empty($data)) {
//				foreach($data as $d) {
//					$this->sendMail($d);
//				}
//			}
		}

		$this->showMsg('发送通知成功！', true);

	}

	/**
	 * 退款信息
	 */
	public function refundAction()
	{
		$aParam = $this->getParams();
		$iPage = intval($this->getParam('page'));

		$aList = Model_OrderCardProduct::getRefund($aParam, $iPage);

		$this->assign('aData', $aList);
		$this->assign('aParam', $aParam);
		$this->assign('type', intval($aParam['type']));
	}

	public function refundexportAction() {
		$aParam = $this->getParams();

		$aList = Model_OrderCardProduct::getRefund($aParam, 1, 'iUpdateTime DESC', 100000);

		$aData = array ();
		if(!empty($aList['aList'])) {
			foreach ( $aList['aList'] as $aRefund ) {
				$aData [] = array (
					'sRealName' => !empty($aRefund['sRealName']) ? $aRefund['sRealName'] : '',
					'sIdentityCard' => !empty($aRefund['sIdentityCard']) ? $aRefund['sIdentityCard'] : '',
					'sOrderCode' => !empty($aRefund['sOrderCode']) ? $aRefund['sOrderCode'] : '',
					'refund' => $aRefund ['refund'],
					'sPayOrderID' => !empty($aRefund['sPayOrderID']) ? $aRefund['sPayOrderID'] : '',
					'iCanncalReserveTime' => !empty($aRefund['iCanncalReserveTime']) ? date("Y-m-d", $aRefund['iCanncalReserveTime']) : ''
				);
			}
		}

		$aTitle = array (
			'sRealName' => '员工姓名',
			'sIdentityCard' => '身份证',
			'sOrderCode' => '订单流水号',
			'refund' => '退费金额',
			'sPayOrderID' => '第三方订单号',
			'iCanncalReserveTime' => '申请时间'
		);

		Util_File::exportCsv ( '退款.csv', $aData, $aTitle );
		return false;
	}

	/**
	 * 处理退款
	 */
	public function refundcheckAction()
	{
		$icpID = $this->getParam('icpID');

		$ocp = Model_OrderCardProduct::getDetail($icpID);
		if(!empty($ocp)) {
			$curProduct = Model_Product::getDetail($ocp['iProductID']);
			$preProduct = Model_Product::getDetail($ocp['iLastProductID']);
			$refund = 0;

			if(!empty($curProduct) && !empty($preProduct)) {
				if(1 == intval($ocp['iSex'])){//男
					$refund = $curProduct['iManPrice'] - $preProduct['iManPrice'];
				}else if(2 == intval($ocp['iSex'])){//女未婚
					$refund = $curProduct['iWomanPrice1'] - $preProduct['iWomanPrice1'];
				}else if(3 == intval($ocp['iSex'])){//女已婚
					$refund = $curProduct['iWomanPrice2'] - $preProduct['iWomanPrice2'];
				}
			}

			$ocp['iProductID'] = $ocp['iLastProductID'];
			$ocp['iOPID'] = $ocp['iLastOPID'];
			$ocp['iOrderID'] = $ocp['iLastOrderID'];
			$ocp['sProductName'] = $ocp['sLastProductName'];
			$ocp['iPayStatus'] = -1;

			$ocp['iLastProductID'] = 0;
			$ocp['iLastOPID'] = 0;
			$ocp['iLastOrderID'] = 0;
			$ocp['sLastProductName'] = '';

			$ocp['iCheckRefund'] = 1;
			$ocp['iRefund'] = $refund;

			Model_OrderCardProduct::updData($ocp);
		}

		$this->showMsg('退款处理成功！', true);
	}

	/**
	 * 体检卡详情
	 */
	public function physicaldetAction(){
		$iOrderID = intval($this->getParam('iOrderID'));
		$orderDetail = Model_OrderInfo::getDetail($iOrderID);

		$where = array(
			'iOrderID' => $iOrderID
		);
		$products = Model_OrderProduct::getAll(['where' => $where]);
		$cards = Model_OrderCard::getAll(['where' => ['iOrderID' => $iOrderID, 'iOrderType in' => '1,2']]);

		$this->assign('aPayStatus', Util_Common::getConf('aPayStatus', 'order'));
		$this->assign('aPayType', Util_Common::getConf('aPayType', 'order'));
		$this->assign('aGenStatus', Util_Common::getConf('aGenStatus', 'order'));
		$this->assign('aCardType', Util_Common::getConf('aCardType', 'order'));
		$this->assign('aShippingStatus', Util_Common::getConf('aShippingStatus', 'order'));
		$this->assign('cards', $cards);

		$this->assign('orderDetail', $orderDetail);
		$this->assign('products', $products);
		$this->assign('iOrderID', $iOrderID);
	}


	/**
	 * 体检卡激活
	 */
	public function activityAction(){
		$iOrderID = intval($this->getParam('id'));
		$cards = Model_OrderCard::getAll(['where' => ['iOrderID' => $iOrderID]]);

		$this->assign('cards', $cards);
		$this->assign('iOrderID', $iOrderID);
	}

	/**
	 * 更新info信息
	 */
	public function updateinfoAction(){
		$sCardCode = $this->getParam('sCardCode');
		$iStatus = intval($this->getParam('iStatus'));

		$resutl = array(
			'code' => 0,
			'msg' => '激活失败'
		);

		$card = Model_OrderCard::getRow(['where' => array('sCardCode' => $sCardCode)]);
		if(!empty($card)){
			$data = array(
				'iAutoID' => $card['iAutoID'],
				'iStatus' => $iStatus
			);
			$error = Model_OrderCard::updData($data);
			if($error) {
				$resutl['code'] = 1;
				$resutl['msg'] = '激活成功';
			}
		}

		$this->showMsg($resutl, true);
	}

	/**
	 * 更新卡片信息
	 */
	public function updatecardAction(){
		$iOrderID = intval($this->getParam('id'));

		$cards = Model_OrderCard::getAll(['where' => ['iOrderID' => $iOrderID]]);

		$this->assign('cards', $cards);
		$this->assign('iOrderID', $iOrderID);
	}


	/**
	 * 体检卡确认已付款后发卡
	 */
	public function confirmPayAction()
	{
		$sOrderCode = $this->getParam('ordercode');
		if (empty($sOrderCode)) {
			return $this->showMsg('参数不全！',false);
		}
		$aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
		if (empty($aOrder)) {
			return $this->showMsg('订单不存在！',false);
		}
		if ($aOrder['iOrderStatus'] != 1) {
			return $this->showMsg('该订单状态有误，不能操作！',false);
		}
		if ($aOrder['iUserType'] != 2) {
			return $this->showMsg('只有hr的订单才可以在这里确认付款！',false);
		}
		if ($aOrder['iPayStatus'] == 1) {
			return $this->showMsg('该订单已经确认过已付款，不能重复确认！',false);
		}
		$aOrderType = [1,2];
		if (!in_array($aOrder['iOrderType'],$aOrderType)) {
			return $this->showMsg('该订单类型有误，不能确认付款！',false);
		}
		$aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
		if (!empty($aOrderProduct)) {
			Model_OrderInfo::begin();
			$aOrderParam['iOrderID'] = $aOrder['iOrderID'];
			$aOrderParam['iPayStatus'] = 1;
			if (Model_OrderInfo::updData($aOrderParam)) {
			//if (1) {
				foreach ($aOrderProduct as $key => $value) {
					$aCardParam['iPCard'] = $value['iPCard'];
					$aCardParam['iCardType'] = $value['iCardType'];
					$aCardParam['iUseType'] = $value['iUseType'];
					$aCardParam['iSex'] = $value['iSex'];
					$aCardParam['sProductName'] = $value['sProductName'];
					$aCardProductParam['iPayStatus'] =$aCardParam['iStatus'] = 1;
					for ($i=0;$i<$value['iProductNumber'];$i++) {
						$iCardID = Model_OrderCard::initCard($aOrder['iOrderType'], Model_OrderCard::PAYTYPE_COMPANY, $aOrder['iUserID'], $aOrder['iUserType'],$value['iAutoID'],$aOrder['iOrderID'], $aCardParam);
						//$iCardID = 41;
						if($iCardID>0)
						{
							if (!empty($value['iProductID'])) {
								if(!Model_OrderCardProduct::initCardProduct($iCardID, $value['iProductID'],$value['sProductName'], $value['iAutoID'], $aOrder['iOrderID'],$aCardProductParam)) {
									Model_OrderInfo::rollback();
									return $this->showMsg('确认失败7！',false);
								}
							} else {
								$aCardProduct = json_decode($value['sProductAttr'],true);
								if (!empty($aCardProduct['aProductID'])) {
									foreach ($aCardProduct['aProductID'] as $k => $val) {
										$OrderCardProduct['iCardID'] = $iCardID;
										$OrderCardProduct['iProductID'] = $val;
										$OrderCardProduct['sProductName'] = $aCardProduct['sProductName'][$k];
										if(!Model_OrderCardProduct::initCardProduct($iCardID, $val,$aCardProduct['sProductName'][$k], $value['iAutoID'], $aOrder['iOrderID'],$aCardProductParam)) {
											Model_OrderInfo::rollback();
											return $this->showMsg('确认失败1！',false);
										}
									}
								} else {
									return $this->showMsg('该订单没有产品，请联系管理员！',false);
								}
							}
						} else {
							Model_OrderInfo::rollback();
							if ($iCardID == -1) {
								return $this->showMsg('没有足够该类型的实体卡，请后台先生成实体卡！',false);
							}
							if ($iCardID == -2) {
								return $this->showMsg('实体卡购买状态修改失败！',false);
							}
							return $this->showMsg('确认失败2！',false);
						}
					}
				}
			} else {
				Model_OrderInfo::rollback();
				return $this->showMsg('确认失败3！',false);
			}
			Model_OrderInfo::commit();
			$sUrl = $aOrder['iOrderType'] == 1 ? '/' : '';//这里电子卡跳转到发邮件页面，实体卡直接页面刷新即可
			return $this->showMsg('购买成功！',true,$sUrl);
		} else {
			return $this->showMsg('该订单有误，请联系管理员！',false);
		}
	}

	//购买成功后，卡没有生成之类的修复操作
	public function paySeccussRepairAction()
	{
		$sOrderCode = $this->getParam('ordercode');
		if (empty($sOrderCode)) {
			return $this->showMsg('参数不全！', false);
		}
		$aPay = Model_Pay::checkPay($sOrderCode);
		if (empty($aPay)) {
			return $this->showMsg('系统未显示付款成功，请提供支付订单号，先进行查询再处理！', false);
		}
		//更改订单状态
		$aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
		$aOrderProduct = Model_OrderProduct::getProductByOrderID($aOrder['iOrderID']);
		if (empty($aOrderProduct) || empty($aOrder)) {
			return $this->showMsg('订单不存在或订单产品不存在，请联系IT管理员！', false);
		}
		if ($aOrder['iPayStatus'] == 1) {
			return $this->showMsg('该订单没有问题或不能自动修复，请联系IT管理员！', false);
		}
		if(Model_OrderInfo::paySeccuss($aOrder, $aOrderProduct,$aPay['sPayOrderID'], $aPay['iPayType'], $aPay['sMoneyPaid']/100))
		{
			return $this->showMsg('修复成功！',true);
		}else {
			return $this->showMsg('修复失败！',false);
		}

	}


	/**
	 * 发送邮件和短信模板
	 * @param  [type] $tmp [description]
	 * @return [type]      [description]
	 */
	public function sendMail ($tmp, $bSendEmail = true, $bSendMsg = true)
	{
		if ($bSendEmail) {
			$content = Yaf_G::getConf('mail', 'physical');
			$content = preg_replace('/\【员工姓名\】/', $tmp['sRealName'], $content);
			$content = preg_replace('/\【公司名称\】/', $tmp['sEnterprise'], $content);
			$content = preg_replace('/\【体检开始日期\】/', $tmp['sStartDate'], $content);
			$content = preg_replace('/\【体检截止日期\】/', $tmp['sEndDate'], $content);
			$content = preg_replace('/\【体检卡号\】/', $tmp['sPhysicalNumber'], $content);

			$mailRes = Util_Mail::send($tmp['sEmail'], '体检通知', $content);
			if ($mailRes == 1) {
				$data = [];
				$data['iAutoID'] = $tmp['iAutoID'];
				$data['iSendEMail'] = 1;
				Model_OrderCard::updData($data);
			}
		}

//		if ($bSendMsg) {
//			$msg = $this->senddata['msg'];
//			$msg = preg_replace('/\【员工姓名\】/', $tmp['sRealName'], $msg);
//			$msg = preg_replace('/\【公司名称\】/', $tmp['sEnterprise'], $msg);
//			$msg = preg_replace('/\【开始时间\】/', $tmp['sStartDate'], $msg);
//			$msg = preg_replace('/\【结束时间\】/', $tmp['sEndDate'], $msg);
//			$msg = preg_replace('/\【体检卡号\】/', $tmp['sPhysicalNumber'], $msg);
//
//			$smsRes = Util_Sms::sendTemplateSms($tmp['sMobile'], $msg);
//			if ($smsRes == 1) {
//				$data = [];
//				$data['iAutoID'] = $tmp['iAutoID'];
//				$data['iSendMsg'] = 1;
//				Model_OrderCard::updData($data);
//			}
//		}
	}
}