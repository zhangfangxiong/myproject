<?php

/**
 * 企业后台_购买体检卡
 * User: xuchuyuan
 * Date: 16/5/7 13:00
 */
class Controller_Company_BuyCard extends Controller_Company_Base
{
	public function actionAfter ()
	{
		parent::actionAfter();
		$this->assign('sListUrl', '/company/buycard/list/');
		$this->assign('sBuyListUrl', '/company/buycard/buylist/');
		$this->assign('sBuyUrl', '/company/buycard/buy/');
		$this->assign('sBuyDetailUrl', '/company/buycard/buydetail/');
	}
	public function listAction()
	{
		$iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
		$aUser = Model_User::getDetail($this->enterpriseId);
		$aCardList = Model_Product::getUserViewProductList($this->enterpriseId, 1, $aUser['iChannel'], $iPage,false,'','iPCard DESC');
		$this->assign('aCardList',$aCardList);
		$this->assign('aCardType',Yaf_G::getConf('aPCard', 'product'));
		$this->assign('aCardStatus',Yaf_G::getConf('aStatus', 'product'));
		$this->assign('iType',1);
	}

	/**
	 * 体检卡购买记录
	 */
	public function buylistAction()
	{
		$iPage = intval($this->getParam('page'));
		$aParam['iUserID'] = $this->enterpriseId;
		$aParam['iUserType'] = Model_OrderInfo::HR;
		$aParam['iPayStatus'] = -1;

		$aList = Model_OrderInfo::getPhisycalList($aParam, $iPage);
		$this->assign('aPayStatus', Util_Common::getConf('aPayStatus', 'order'));
		$this->assign('aPayType', Util_Common::getConf('aPayType', 'order'));
		$this->assign('aGenStatus', Util_Common::getConf('aGenStatus', 'order'));
		$this->assign('aCardType', Util_Common::getConf('aCardType', 'order'));
		$this->assign('aShippingStatus', Util_Common::getConf('aShippingStatus', 'order'));
		$this->assign('aList', $aList);
		$this->assign('iType',2);
	}

	/**
	 * 订单详情
	 */
	public function buyDetailAction(){
		$iOrderID = intval($this->getParam('id'));

		$orderDetail = Model_OrderInfo::getDetail($iOrderID);
		$where = array(
			'iOrderID' => $iOrderID
		);
		$products = Model_OrderProduct::getAll(['where' => $where]);

		$this->assign('aPayStatus', Util_Common::getConf('aPayStatus', 'order'));
		$this->assign('aPayType', Util_Common::getConf('aPayType', 'order'));
		$this->assign('aGenStatus', Util_Common::getConf('aGenStatus', 'order'));
		$this->assign('aCardType', Util_Common::getConf('aCardType', 'order'));
		$this->assign('aShippingStatus', Util_Common::getConf('aShippingStatus', 'order'));
		$this->assign('aIfInv',Yaf_G::getConf('aIfInv', 'order'));
		$this->assign('aProductCardType',Yaf_G::getConf('aCardType', 'product'));
		$this->assign('aPCard',Yaf_G::getConf('aPCard', 'product'));

		$this->assign('orderDetail', $orderDetail);
		$this->assign('products', $products);
		$this->assign('iOrderID', $iOrderID);
	}

	public function buyAction()
	{
		if($this->isPost()){
			$aParam = $this->getParams();
			if (empty($aParam['aProductID'])) {
				return $this->showMsg('请选择一个体检产品！',false);
			}

			$aPriceKey = [
				1=>'iManPrice',
				2=>'iWomanPrice1',
				3=>'iWomanPrice2'
			];

			$aUser = Model_User::getDetail($this->enterpriseId);
			$aProduct = Model_Product::getAllUserProduct($this->enterpriseId, 1, $aUser['iChannel'], implode(',',$aParam['aProductID']),false,'iProductID');

			//以下为各种情况分析
			$iUserID = $this->enterpriseId;
			$iUserType = Model_OrderInfo::HR;
			$iOrderType = $aParam['iOrderType'];
			$sConsignee = $aParam['sConsignee'];
			$sMobile = $aParam['sMobile'];
			$sProductAmount = 0;
			$sProductName ='';
			$aOrderProductParam=[];
			$aOrderParam['iIfInv'] = $aParam['iIfInv'];
			$aOrderParam['iCardType'] = $aParam['iCardType'];
			$aOrderParam['sAddress'] = $aParam['sAddress'];
			$aOrderParam['sZipcode'] = $aParam['sZipcode'];
			$aOrderParam['sEmail'] = $aParam['sEmail'];
			$aOrderParam['sInvPayee'] = $aParam['sInvPayee'];
			$aOrderParam['sAddress'] = $aParam['sAddress'];
			$aOrderParam['sZipcode'] = $aParam['sZipcode'];

			if ($iOrderType == Model_OrderInfo::REALCARD) {

				foreach ($aParam['aProductID'] as $key => $value) {
					if (empty($aProduct[$value]['iPCard'])) {
						return $this->showMsg($aProduct[$value]['sProductName'].'不能购买实体卡',false);
					}
					$aProduct[$value]['sProductName'] = !empty($aProduct[$value]['sProductName']) ? !empty($aProduct[$value]['sAlias']) ? $aProduct[$value]['sAlias'] : $aProduct[$value]['sProductName'] : '';
				}
				if (empty($aParam['iCardType'])) {
					return $this->showMsg('购买实物卡，必须选择卡种类！',false);
				}
				if (empty($aParam['sAddress'])) {
					return $this->showMsg('购买实物卡，必须填写地址！',false);
				}
				if (empty($aParam['sZipcode'])) {
					return $this->showMsg('购买实物卡，必须填写邮编！',false);
				}
			} elseif ($iOrderType == Model_OrderInfo::ELECTRONICCARD) {
				if (empty($aParam['sEmail'])) {
					return $this->showMsg('购买电子卡，必须填写邮箱！',false);
				}
			}

			if (!empty($aParam['iIfInv'])) {
				if (empty($aParam['sInvPayee'])) {
					return $this->showMsg('需要发票，必须填写发票抬头！',false);
				}
				if (empty($aParam['sAddress'])) {
					return $this->showMsg('需要发票，必须填写地址！',false);
				}
				if (empty($aParam['sZipcode'])) {
					return $this->showMsg('需要发票，必须填写邮编！',false);
				}
			}

			//计算体检卡名称和价格
			if (!empty($aParam['extenditem'])) {//一张卡内包含多个体检套餐
				$aParam['extenditem'][] = $aParam['aProductID'][0];//把原有产品插进来
				$aProductExtend = Model_Product::getAllUserProduct($this->enterpriseId, 1, $aUser['iChannel'], implode(',',$aParam['extenditem']),false,'iProductID');
				foreach ($aProductExtend as $key => $value) {
					$sProductAmount = $aParam['iUseType'] == 1 ? max($value[$aPriceKey[$aParam['aProductSex'][0]]],$sProductAmount) : $sProductAmount + $value[$aPriceKey[$aParam['aProductSex'][0]]];
					$sProductName .= $aParam['iUseType'] == 1 ? $value['sProductName'].'/' : $value['sProductName'].'+';

					$aOrderProductParam['sProductAttr']['aProductID'][] = $value['iProductID'];
					$aOrderProductParam['sProductAttr']['sProductName'][] = $value['sProductName'];
					$aOrderProductParam['sProductAttr']['sProductPrice'][] = $value[$aPriceKey[$aParam['aProductSex'][0]]];
				}
				$sProductName = trim($sProductName,'+,/');
				$sProductAmount = $sProductAmount*$aParam['aProductNumber'][0];
				if ($sProductAmount!=$aParam['aProductPrice'][0]) {
					return $this->showMsg('非法操作！',false);
				}
				$aOrderProductParam['iUseType'] = $aParam['iUseType'];//使用方式
			} else {
				foreach ($aParam['aProductID'] as $key => $value) {
					if ($aProduct[$value][$aPriceKey[$aParam['aProductSex'][$key]]]*$aParam['aProductNumber'][$key]!=$aParam['aProductPrice'][$key]) {
						return $this->showMsg('非法操作！',false);
					}
					$sProductAmount += $aParam['aProductPrice'][$key];
				}
			}

			//入库操作
			Model_OrderInfo::begin();
			//入orderinfo表
			if ($iOrderID = Model_OrderInfo::initOrder($iUserID,$iUserType,$iOrderType,$sConsignee,$sMobile,$sProductAmount,$aOrderParam)) {
					$aOrderProductParam['iCardType'] = $aParam['iCardType'];//实体卡种类

				if (!empty($aParam['extenditem'])) {
					//入orderproduct表
					$aOrderProductParam['iPCard'] = $aProduct[$aParam['aProductID'][0]]['iPCard'];//实体卡类型
					$aOrderProductParam['iSex'] = $aParam['aProductSex'][0];//选择的性别
					if (!Model_OrderProduct::initOrder($iOrderID,0,$sProductName,$aParam['aProductNumber'][0],$sProductAmount/$aParam['aProductNumber'][0],$sProductAmount,$iOrderType,$aOrderProductParam))
					{
						Model_OrderInfo::rollback();
						return $this->showMsg('购买失败！',false);
					}
				} else {
					foreach ($aParam['aProductID'] as $key => $value) {
						$aOrderProductParam['iPCard'] = $aProduct[$value]['iPCard'];//实体卡类型
						$aOrderProductParam['iSex'] = $aParam['aProductSex'][$key];//选择的性别
						//入orderproduct表
						if (!Model_OrderProduct::initOrder($iOrderID,$aProduct[$value]['iProductID'],$aProduct[$value]['sProductName'],$aParam['aProductNumber'][$key],$aParam['aProductPrice'][$key]/$aParam['aProductNumber'][$key],$aParam['aProductPrice'][$key],$iOrderType,$aOrderProductParam))
						{
							Model_OrderInfo::rollback();
							return $this->showMsg('购买失败！',false);
						}
					}
				}
			} else {
				Model_OrderInfo::rollback();
				return $this->showMsg('购买失败！',false);
			}
			Model_OrderInfo::commit();
			return $this->showMsg('购买申请已提交，待审核后发送至您的邮箱或邮寄到您所填的地址！',true,'/company/buycard/buylist/');
		} else {
			$sPid = $this->getParam('pid') ? trim($this->getParam('pid'),',') : '';
			$aUser = Model_User::getDetail($this->enterpriseId);
			$aCardList = Model_Product::getAllUserProduct($this->enterpriseId, 1, $aUser['iChannel'], $sPid);
			$aAllCardList = count(explode(',',$sPid)) > 1 ? [] : Model_Product::getAllUserProduct($this->enterpriseId, 1, $aUser['iChannel']);
			$this->assign('aCardList',$aCardList);
			$this->assign('aAllCardList',$aAllCardList);
			$this->assign('aCardType',Yaf_G::getConf('aCardType', 'product'));
			$this->assign('aSex',Yaf_G::getConf('aSex', 'product'));
		}
	}
}

?>