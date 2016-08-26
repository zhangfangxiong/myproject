<?php

/**
 * Date: 16/6/06
 * Time: 10:00
 */
class Controller_Index_Order extends Controller_Index_Base
{
	
	public $aType = [ 1, 2];

	public $aPriceKey = [
		1 => 'iManPrice',
		2 => 'iWomanPrice1',
		3 => 'iWomanPrice2'
	];
	public $aOrderType = [
		1 => '体检预约',
		2 => '体检卡'
	];

	public $aPayStatus = [
		0 => '待付款',
		1 => '已付款'
	];

	public $sTitle = null;

	public function actionBefore ()
	{
		parent::actionBefore();
		$this->_frame = 'pcmenu.phtml';

		//type=2表示 卡号未绑定登录
		$type = $this->getParam('type');
		if ($type == 2) {

		} else if (!$this->aUser['iUserID']) {
			return $this->redirect('/index/account/logout');
		}
	}

	/**
	 * 订单列表 (待支付 已支付)
	 * @return [type] [description]
	 */
	public function listAction ()
	{
		$type = intval($this->getParam('type'));//支付类型
        if (!in_array($type, $this->aType)) {
			$type = 1;
		}

		$iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aWhere = array(
            'iUserID' => $this->iCurrUserID,
            'iUserType' => Model_OrderInfo::PRESON,
            'iOrderStatus' => 1,
            'iPayStatus' => $type - 1,
        );
        $aOrder = Model_OrderInfo::getList($aWhere, $iPage, 'iOrderID Desc');
        if (!empty($aOrder['aList'])) {
            foreach ($aOrder['aList'] as $key => $value) {
                //组装需要数据
                $aOrder['aList'][$key]['iTotalNum'] = Model_OrderProduct::getProductNumByOrderID($value['iOrderID']);
                $aOrder['aList'][$key]['sCreateTime'] = date('Y-m-d H:i:s', $value['iCreateTime']);
                $aProduct = Model_OrderProduct::getAll(['where' => ['iOrderID' => $value['iOrderID']]]);
				if (!$aProduct) {
					unset($aOrder['aList'][$key]);
					continue;
				}
				if (in_array($value['iOrderType'], [1, 2])) {
					$count = 0;
					if ($aProduct) {						
						foreach ($aProduct as $k => $v) {
							$count += $v['iProductNumber'];
						}						
					}
					$aOrder['aList'][$key]['sType'] = '体检卡';					
					$aOrder['aList'][$key]['sProductName'] = '中盈体检卡(' . $count .'张)';

					if ($value['iPayStatus'] == 1) {
						$aOrder['aList'][$key]['sDesc'] = '查看订单';
						$aOrder['aList'][$key]['sLink'] = '/order/cardorder/id/'.$value['iOrderID'];
					} else {
						$sLink = '/order/cardinfo/id/'.$value['iOrderID'];
						$aOrder['aList'][$key]['sLink'] = $sLink;
						$aOrder['aList'][$key]['sDesc'] = '查看订单并支付';
					}
				} else {
					if ($value['iPayStatus'] == 0) { 
						$aOrder['aList'][$key]['sDesc'] = '查看订单并支付';
					}
					if ($value['iPayStatus'] == 1) { 
						$aOrder['aList'][$key]['sDesc'] = '查看订单';
					}
					$aOrder['aList'][$key]['sProductName'] = $aProduct[0]['sProductName'];
					$aOrder['aList'][$key]['sType'] = '体检预约';

					$sLink = '/order/cardorder/type/2/id/'.$value['iOrderID'];
					$aOrder['aList'][$key]['sLink'] = $sLink;
				}
            }
        }

        $this->assign('aOrderType', Yaf_G::getConf('aOrderType', 'order'));
        $this->assign('aPayStatus', Yaf_G::getConf('aPayStatus', 'order'));
        $this->assign('aList', $aOrder);
        $this->assign('sTitle', '我购买的体检套餐');

		$this->setMenu($type);
	}

	/*
	 * 根据TYPE设置搜索参数
	 */
	public function setParam ( $type )
	{
		$where = [];
		if ($type == 1) {
			$where = [
				'iPayStatus' => 0,
			];
			$this->sTitle = '待支付的订单';
		}

		if ($type == 2) {
			$where = [
				'iPayStatus' => 1,
			];
			$this->sTitle = '已支付的订单';
		}

		$where['iUserID'] = $this->aUser['iUserID'];

		return $where;
	}

	/**
	 * 已支付/已预约的订单详情
	 * @return
	 */
	public function detailAction () 
	{
		$aCard = $aStore = $aProduct = $aEmployee = [];

		$id = $this->getParam('id');
		$pid = $this->getParam('pid');
		$type = 2 == $this->getParam('type') ? 2 : 1;

		if ($type == 2) {
			$url = '/index/record/list/';
		} else {
			$url = '/index/order/list/';
		}
		$aCard = Model_OrderCard::getDetail($id);
		if (!$id || !$aCard) {
			return $this->redirect($url);
		}
		
		$aEmployee = Model_CustomerNew::getDetail($aCard['iUserID']);
		if ($aCard['iCreateUserType'] == 2) {
			$aUser = Model_User::getDetail($aCard['iCreateUserID']);
		} else {
			$aUser = $aEmployee;
		}

		$aCardProduct = Model_OrderCardProduct::getCardProduct($id, $pid);
		if (empty($aCardProduct)) {
            return $this->redirect($url);
        }
		$aStore = Model_Store::getDetail($aCardProduct['iStoreID']);
		$aProduct = Model_UserProductBase::getUserProductBase($pid, $aCard['iCompanyID'], 2, $this->aUser['iChannelID']);

		
		$eaurl = '/order/buyfourth/type/2/id/'.$id.'/pid/'.$pid.'/sid/'.$aCardProduct['iStoreID'];
		$caurl = '/order/cancel/id/'.$id.'/pid/'.$pid;

		if ($aCard['iPhysicalType'] == 1) {
			$aSupplier = Yaf_G::getConf('aHasApi', 'suppliers');
			$data['orderid'] = $aCardProduct['sApiReserveOrderID'];
			$data['cardnumber'] = $aCardProduct['sApiCardID'];
			$data['hospid'] = $aStore['sStoreCode'];	
			$data['iPhysicalType'] = $aCard['iPhysicalType'];			
			$sData = json_encode($data);
			$supplier = Model_Type::getDetail($aStore['iSupplierID']);
			if (isset($aSupplier[$supplier['sCode']])) {
				$classname = $aSupplier[$supplier['sCode']]['classname'];
				$dpurl = '/api/' . $classname . '/downloadreport?data=' . $sData;
				$this->assign('sDPUrl', $dpurl);		
			}			
		}

		$this->assign('aOrder', $aOrder);
		$this->assign('aCard',  $aCard);
		$this->assign('aStore', $aStore);
		$this->assign('aProduct', $aProduct);
		$this->assign('aUser', $aUser);
		$this->assign('aEmployee', $aEmployee);
		$this->assign('aCardProduct', $aCardProduct);
		$this->assign('sUrl', $url);
		$this->assign('sEAUrl', $eaurl);
		$this->assign('sCAUrl', $caurl);
	}

	/**
	 * 填写体检信息
	 * @return [type] [description]
	 */
	public function baseinfoAction ()
	{
		$this->_frame = 'pcframe.phtml';
		$this->assign('class1', 'complete');

		$type = $this->getParam('type') == 2 ? 2 : 1;
		$id = intval($this->getParam('id'));
		$pid = intval($this->getParam('pid'));
		$uid = intval($this->getParam('iUserID'));
		$plan = intval($this->getParam('plan'));

		$aCard = Model_OrderCard::getDetail($id);
		if ($this->isPost()) {
			$data = $this->checkParam($type, $uid);
			if (!$data) {
				return null;
			}
				
			//绑卡时检测性别婚姻与卡是否一致
			$iSex = $data['iSex'] != 1 ? ($data['iSex'] == 2 && $data['iMarriage'] == 1) ? 2 : 3 : 1;
			$bool = Model_OrderCard::checkSex($id, $iSex, $this->aUser['iChannelID']);
			if ($bool != 0) {
				return $this->show404($bool, false);
			}

			if (empty($data['iUserID'])) {
				$row = Model_CustomerNew::getRow(['where' => [
					'sIdentityCard' => $data['sIdentityCard'],
					'iStatus >' => Model_CustomerNew::STATUS_INVALID,
				]]);
				if (!$row) {
					$data['iType'] = 2;
					$aCompany = Model_User::getRow(['where' => [
						'sUserName' => 'registers',
						'iStatus >' => Model_User::STATUS_INVALID,
					]]);
					$data['sUserName'] = Model_CustomerNew::initUserName();
					$data['iCreateUserID'] = $aCompany['iUserID'];

					$iCustomerID = Model_CustomerNew::addData($data);
				} else {
					if ($data['sRealName'] != $row['sRealName']) {
						return $this->showMsg('身份证与姓名不一致，请联系相关人员', false);
					}
					$iCustomerID = $data['iUserID'] = $row['iUserID'];
					Model_CustomerNew::updData($data);
				}

				if ($aCard) {
					$aTmp['iAutoID'] = $aCard['iAutoID'];
					$aTmp['iUserID'] = $iCustomerID;
					$aTmp['iBindStatus'] = 1;
					Model_OrderCard::updData($aTmp);
				}

				Model_CustomerNew::setCookie($iCustomerID);
			} else {
				$aEmployee = Model_CustomerNew::getDetail($data['iUserID']);
				if (!$aEmployee) {
					return $this->showMsg('人员不存在', false);
				}

				//员工是否存在(身份证)
				list($row, $desc) = Model_Company_Employee::checkExist($data);
				if ($row && $row['iUserID'] != $data['iUserID']) {
					$msg = $desc.'员工已经存在!';	
					return $this->showMsg($msg, false);					
				}

				Model_CustomerNew::updData($data);
				Model_CustomerNew::setCookie($data['iUserID']);
			}

			$url = '/order/buynext/id/' . $id . '/pid/' . $pid;
			if ($plan == 1) {
				$url .= '/plan/' . $plan;
			}

			$from = $this->getParam('from');
			if ($from == 2) {
				$upid = $this->getParam('upid');
				$sid = $this->getParam('sid');
				$url = '/order/buyfourth/id/' . $id . '/pid/' . $pid . '/sid/' . $sid . '/upid/' . $upid;
			}

			return $this->showMsg('保存成功', true, $url);
		} else {
			$aEmployee = [];
			if ($aCard['iPlanID'] > 0) {
				$aCard['sPName'] = '体检计划';	
				$aCard['sPhysicalType'] = ($aCard['iPhysicalType'] == 2) ? '入职体检' : '普通体检';
			} else {
				if (!$pid) {
					$aCP = Model_OrderCardProduct::getAll(['where' => [
						'iCardID' => $id
					]]);
					$aCardProduct = $aCP[0];
				} else {
					$aCardProduct = Model_OrderCardProduct::getCardProduct($id, $pid);		
				}
				
				$aCard['sPName'] = $aCardProduct['sProductName'];	
				$aCard['sPhysicalType'] = ($aCard['iPhysicalType'] == 2) ? '入职体检' : '普通体检';
			}
		
			if ($aCard['iUserID']) {
				$aEmployee = Model_CustomerNew::getDetail($aCard['iUserID']);	
			}
			if ($aCard['iAutoID'] != $id) {
				$this->redirect('/index/account/logout');
			}

			$this->assign('aCard', $aCard);
			$this->assign('aEmployee', $aEmployee);
			$this->assign('iOrderID', $id);
			$this->assign('iCardID', $cid);
		}
	}

	public function checkParam ($type, $iUserID = 0)
	{
		$params = $this->getParams();
		if ($type == 1) {
			if (!$iUserID) {
				return $this->showMsg('请选择人员', false);
			} else {
				$params['iUserID'] = $iUserID;
			}
		}
		
		if (!$params['sRealName']) {
			return $this->showMsg('请填写姓名', false);
		}
		if (!$params['iSex']) {
			return $this->showMsg('请选择性别', false);
		}
		if (!$params['iMarriage']) {
			return $this->showMsg('请选择婚姻状况', false);
		}
		if (!$params['sIdentityCard']) {
			return $this->showMsg('请填写证件号码', false);
		}
		if (!$params['sBirthDate']) {
			return $this->showMsg('请填写生日信息', false);
		}
		if (!$params['sMobile']) {
			return $this->showMsg('请填写手机号码', false);
		}
		if (!$params['sEmail']) {
			return $this->showMsg('请填写邮箱', false);
		}
		if (!$params['iCityID']) {
			return $this->showMsg('请选择城市', false);
		}
		
		if (!$params['iCardType']) {
			$params['iCardType'] = 1;
		}
		
		return $params;
	}

	/**
	 * 继续付款 显示可选择列表
	 * @return 
	 */
	public function buynextAction()
	{
		$id = intval($this->getParam('id'));
		$pid = intval($this->getParam('pid'));
		$plan = intval($this->getParam('plan'));
		$this->assignUrl($id, $pid);
		$this->assign('class2', 'complete');
		$this->_frame = 'pcframe.phtml';

		$aCard = Model_OrderCard::getDetail($id);
		$where = [
			'iCardID' => $id,
			'iStatus' => 1,
			'iBookStatus IN' => [0, 3, 6],
		];
		$aCardProduct = Model_OrderCardProduct::getAll(['where' => $where]);
		if (!$aCard || !$aCardProduct) {
			return $this->redirect('/index/record/list/');
		}
		if ($aCardProduct['iLastProductID'] >0 && $aCardProduct['iBookStatus'] == 6 && $aCardProduct['iCheckRefund'] == 0) {
			return $this->redirect('/index/record/list/');
		}

		foreach ($aCardProduct as $key => &$value) {
			$aProduct = Model_UserProductBase::getUserProductBase($value['iProductID'], $aCard['iCompanyID'], 2, $this->aUser['iChannelID']);
			$value['sPName'] = $aProduct['sProductName'];
			$value['sPDesc'] = $aProduct['sRemark'];
			$value['sPImg'] = $aProduct['sImage'] ? Util_Uri::getDFSViewURL($aProduct['sImage']) : '';
			list($value['sPSCnt'], $value['sPCnt']) = $this->getStoreAndPersonNumber($value['iProductID']);
			$value['sNextUrl'] = '/order/buythird/id/' . $id . '/pid/' . $value['iProductID'];
			$value['sPPUrl'] = '/index/web/planproductdetail/id/' . $id . '/pid/' . $value['iProductID'];
		}

		$this->assign('aOP', $aCardProduct);
	}

	/**
	 * 选择体检机构
	 * @return
	 */
	public function buythirdAction ()
	{
		$aParam = $this->getParams();
		$id = $aParam['id'];
		$pid = $aParam['pid'];
		$upid = $aParam['upid'];
        if (empty($aParam['id']) && empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        if (!empty($aParam['id']) && empty($aParam['pid'])) {
        	return $this->redirect('/order/buynext/id/'.$id, false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }

        if ($upid) {
        	$aUProduct = Model_Product::getDetail($upid);
        	$sProductName = $aUProduct['sProductName'];
        	$this->assign('sProductName', $sProductName);
        }

        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
        $aProduct = Model_UserProductBase::getUserProductBase($aCardProduct['iProductID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }

        $sNeedPrice = '0.00';
        if (!empty($aParam['upid'])) {
            $aUpProduct = Model_UserProductBase::getUserProductBase($aParam['upid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);//升级产品
            if (empty($aUpProduct)) {
                return $this->show404('体检卡对应升级产品不存在', false);
            }
            $sNeedPrice = $aUpProduct[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
        }
        //个人支付的未支付状态的体检计划和体检产品
        if (($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT ) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_PERSON && empty($aCardProduct['iPayStatus'])) {
            $sNeedPrice = $aProduct[$this->aPriceKey[$aCard['iSex']]];
        }

        $aUser = Model_Customer::getDetail($this->iCurrUserID);
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        $aSupplier = Model_Store::getStoreSupplier($pid, $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannelID']);

        $this->assign('aParam', $aParam);
        $this->assign('aProduct', $aProduct);
        $this->assign('iChannelType', $iChannelType);
        $this->assign('aCard', $aCard);
        $this->assign('aCardProduct', $aCardProduct);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aUser', $aUser);
        $this->assign('aSex', Yaf_G::getConf('aSex'));
        $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
        $this->assign('sTitle', '2,选择体检门店（共三步）');
        $this->assign('sNeedPrice', $sNeedPrice);
        $this->assign('iHomeIcon', 0);

		$this->assign('class3', 'complete');
		$this->_frame = 'pcframe.phtml';

		$params = $this->getParams();
		$params['iRegionID'] ? $where['iRegionID'] = intval($params['iRegionID']) : '';
		$params['iSupplierID'] ? $where['iSupplierID'] = intval($params['iSupplierID']) : '';
		$page = intval($this->getParam('page'));
		if ($params['iCityID'] > 0) {
			$where['iCityID'] = intval($params['iCityID']);	
		}  
		if (empty($params['iCityID'])) {
			$aEmployee = Model_CustomerNew::getDetail($this->aUser['iUserID']);
			if ($aEmployee['iCityID']) {
				$params['iCityID'] = $where['iCityID'] = $aEmployee['iCityID'];	
			}
		}
		
		$iProductID = $aCardProduct['iProductID'];
		$aStore = ['aList' => [], 'iTotal' => 0];
		if ($upid) {
			$iProductID = $upid;
		}
		//该体检产品所有门店id
		$aStore = Model_Store::getProductStore($iProductID, $iChannelType, $this->aUser['iCreateUserID'], $this->aUser['iChannelID'], $page, $params);
		$aRegion = [];
		$rWhere = [ 
        	'iStatus' => Model_Region::STATUS_VALID
    	];
    	if ($where['iCityID'] > 0) {
    		$rWhere['iCityID'] = $where['iCityID'];
    	}
        $aRegions = Model_Region::getAll([
        	'where' => $rWhere
        ]);     
        if ($aRegions) {
            foreach ($aRegions as $key => $value) {
                $aRegion[$value['iRegionID']] =  $value['sRegionName'];
            }
        }

        $this->assign('aRegion', $aRegion);
		$this->assign('iCardID', $id);
		$this->assign('iProductID', $pid);
		$this->assign('iUpgradeID', $upid);
		$this->assign('iCityID', $where['iCityID']);
		$this->assign('aParam', $params);
		$this->assign('aStore', $aStore);

		$this->assignUrl($id, $pid);
		$this->assign('sBaseInfoUrl', '/order/baseinfo/id/' . $id . '/pid/' . $pid);
		if ($upid) {
			$this->assign('sBaseInfoUrl', '');
		}
	}

	/**
	 * 核对信息并支付
	 * @return
	 */
	public function buyfourthAction ()
	{
		$this->assign('class4', 'complete');
		$this->_frame = 'pcframe.phtml';

		$aParam = $this->getParams();
		$aParam['iOrderTime'] = $aParam['sPhysicalDate'];
		$id = $aParam['id'];
		$pid = $aParam['pid'];
		$sid = $aParam['sid'];
		$url = '/order/buyfourth/id/'.$id.'/pid/'.$pid.'/sid/'.$sid;
		if (!empty($aParam['upid'])) {
        	$aUProduct = Model_Product::getDetail($aParam['upid']);
        	$sProductName = $aUProduct['sProductName'];
        	$this->assign('sProductName', $sProductName);
   			$this->assign('sBaseInfoUrl2', '/order/baseinfo/from/2/id/' . $id . '/pid/' . $pid . '/sid/' . $sid  . '/upid/' . $$aParam['upid']);
		} else {
			$this->assign('sBaseInfoUrl2', '/order/baseinfo/from/2/id/' . $id . '/pid/' . $pid . '/sid/' . $sid);
		}
		
        if (empty($aParam['id']) || empty($aParam['sid']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aCardProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aCardProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        if (!empty($aCardProduct['iBookStatus']) && $aCardProduct['iBookStatus'] != 3 && $aCardProduct['iBookStatus'] != 6 && ($aCardProduct['iStoreID'] != $aParam['sid'])) {
            return $this->show404('你已经预约过门店，请取消预约后再更换门店', false);
        }
        if ($aCardProduct['iBookStatus'] == 3 && $aCardProduct['iLastProductID'] > 0 && empty($aCardProduct['iCheckRefund'])) {
            return $this->show404('该产品升级过，退订后还未退款，不能预约', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;

        $aProduct = Model_UserProductBase::getUserProductBase($aParam['pid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
        //预约性别判断(当前性别,这个性别在base做估处理和卡买的性别不符，且三个价格不一样的，不能去预约)
        if ($this->aUser['iSex'] != $aCard['iSex'] && !empty($aProduct['iNeedSex'])) {
            return $this->show404('该卡有性别限制，且和你购买的性别不符，请联系客服', false);
        }

        $aStore = Model_Store::getDetail($aParam['sid']);
        if (empty($aStore)) {
            return $this->show404('门店不存在', false);
        }
        $aSupplier = Model_Type::getDetail($aStore['iSupplierID']);
        if (empty($aSupplier['sCode'])) {
            return $this->show404('供应商代码不存在，请联系管理员', false);
        }

        if (!empty($aParam['upid'])) {
            $aUpGrade = Model_UserProductBase::getUserProductBase($aParam['upid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannel']);//升级产品
            if (empty($aUpGrade)) {
                return $this->show404('体检卡对应升级产品不存在', false);
            }
        }
        $iProductID = !empty($aParam['upid']) ? $aParam['upid'] : $aParam['pid'];
        $aProductStore = Model_UserProductStore::getUserHasStore($iProductID, $this->aUser['iCreateUserID'], $aParam['sid'], $iChannelType, $this->aUser['iChannel'], true);
        if (empty($aProductStore)) {
            return $this->show404('该门店不存在该产品', false);
        }

        if ($this->isPost()) {
            //先判断是否个人支付的未支付状态的体检计划和体检产品
            if (($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_PERSON && empty($aCardProduct['iPayStatus'])) {
            	if ($aCardProduct['iAttribute'] == 2) {
                    return $this->show404('入职套餐不能升级', false);
                }

                $sNeedPrice = $aProduct[$this->aPriceKey[$aCard['iSex']]];
                //判断该卡的产品是否已经有支付订单
                if (!empty($aCardProduct['iOrderID'])) {
                    $aOrder = Model_OrderInfo::getDetail($aCardProduct['iOrderID']);
                    if (!empty($aOrder['sOrderCode'])) {
                        return $this->showMsg('确认去支付', true, '/order/orderpay/ordercode/' . $aOrder['sOrderCode']);
                    }
                }
                //入库操作
                Model_OrderInfo::begin();
                //入orderinfo表
                $sOrderCode = Model_OrderInfo::initOrderCode(Model_OrderInfo::PRESON);
                $aUser = Model_Customer::getDetail($this->iCurrUserID);
                $sConsignee = $aUser['sRealName'];
                $sMobile = $aUser['sMobile'];
                $aOrderParam['sEmail'] = $aUser['sEmail'];
                $aOrderParam['iPlanID'] = $aCard['iPlanID'];
                $aOrderParam['iShippingStatus'] = $aOrderProductParam['iShippingStatus'] = 2;//已发货
                $aOrderParam['iShippingTime'] = $aOrderProductParam['iShippingTime'] = time();
                if ($iOrderID = Model_OrderInfo::initOrder($this->iCurrUserID, Model_OrderInfo::PRESON, $aCard['iOrderType'], $sConsignee, $sMobile, $sNeedPrice, $aOrderParam, $sOrderCode)) {
                    $aOrderProductParam['iSex'] = $aCard['iSex'];//选择的性别
                    $aOrderProductParam['sProductAttr']['iCardID'] = $aParam['id'];//卡号ID
                    $aOrderProductParam['sProductAttr']['iCardProductID'] = $aCardProduct['iAutoID'];//卡对应产品的autoid
                    $aOrderProductParam['sProductAttr']['sProductPrice'] = $aProduct[$this->aPriceKey[$aCard['iSex']]];
                    $aOrderProductParam['sProductAttr']['iStoreID'] = $aParam['sid'];//要预约的门店
                    //入orderproduct表
                    if ($sOrderProductID = Model_OrderProduct::initOrder($iOrderID, $aCardProduct['iProductID'], $aCardProduct['sProductName'], 1, $sNeedPrice, $sNeedPrice, $aCard['iOrderType'], $aOrderProductParam)) {

                        //体检卡产品处理（这里把订单号带到卡表即可）
                        $aOrderCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
                        $aOrderCardProductParam['iOPID'] = $sOrderProductID;
                        $aOrderCardProductParam['iOrderID'] = $iOrderID;
                        if (Model_OrderCardProduct::updData($aOrderCardProductParam)) {
                            Model_OrderInfo::commit();
                            return $this->showMsg('确认去支付', true, '/order/orderpay/ordercode/' . $sOrderCode);
                        } else {
                            Model_OrderInfo::rollback();
                            return $this->show404('卡产品状态更新失败，请稍后重试！', false);
                        }
                    } else {
                        Model_OrderInfo::rollback();
                        return $this->show404('生成订单产品失败，请稍后重试！', false);
                    }
                } else {
                    Model_OrderInfo::rollback();
                    return $this->show404('生成订单失败，请稍后重试！', false);
                }
            } elseif (!empty($aParam['upid']) && $aParam['upid'] != $aParam['pid']) {//再判断是否升级产品
				//判断该卡的产品是否已经有支付订单（这个字段是升级订单独有，不能在这里把订单号带到卡表，会覆盖原产品订单ID，需要等支付后）
                if (!empty($aCardProduct['iPayOrderID'])) {
                    $aOrder = Model_OrderInfo::getDetail($aCardProduct['iPayOrderID']);
                    if (!empty($aOrder['sOrderCode'])) {
                        return $this->showMsg('确认去支付', true, '/order/orderpay/ordercode/' . $aOrder['sOrderCode']);
                    }
                }
                if (($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_COMPANY) {

                } else {
                    //找出对应的原产品订单
                    $aOrderProductLast = Model_OrderProduct::getDetail($aCardProduct['iOPID']);

                    if (empty($aOrderProductLast)) {
                        return $this->show404('原产品订单不存在！', false);
                    }
                    //找出对应orderinfo中父订单
                    $aOrderInfoLast = Model_OrderInfo::getDetail($aOrderProductLast['iOrderID']);
                    if (empty($aOrderInfoLast)) {
                        return $this->show404('原订单不存在！', false);
                    }
                    $aOrderParam['iParentOrderID'] = $aOrderInfoLast['iOrderID'];
                }
                if (empty($aCardProduct['iPayStatus']) && $aCard['iPayType'] == Model_OrderCard::PAYTYPE_PERSON) {
                    return $this->show404('当前卡未支付，不能升级！', false);
                }

                if (!empty($aCardProduct['iLastProductID'])) {
                    return $this->show404('当前卡已升级过，不能多次升级！', false);
                }
                $sNeedPrice = $aUpGrade[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
                $aUser = Model_Customer::getDetail($this->iCurrUserID);
                $sConsignee = $aUser['sRealName'];
                $sMobile = $aUser['sMobile'];
                $aOrderParam['sEmail'] = $aUser['sEmail'];

                //入库操作
                Model_OrderInfo::begin();
                //入orderinfo表
                $sOrderCode = Model_OrderInfo::initOrderCode(Model_OrderInfo::PRESON);
                //if ($iOrderID=1){
                if ($iOrderID = Model_OrderInfo::initOrder($this->iCurrUserID, Model_OrderInfo::PRESON, Model_OrderInfo::ORDERTYPE_UPGRADE, $sConsignee, $sMobile, $sNeedPrice, $aOrderParam, $sOrderCode)) {
                    $aOrderProductParam['iSex'] = $aCard['iSex'];//选择的性别
                    $aOrderProductParam['sProductAttr']['iCardID'] = $aParam['id'];//卡号ID
                    $aOrderProductParam['sProductAttr']['iCardProductID'] = $aCardProduct['iAutoID'];//卡对应产品的autoid
                    $aOrderProductParam['sProductAttr']['iLastProductID'] = $aProduct['iProductID'];//升级前的产品ID
                    $aOrderProductParam['sProductAttr']['iLastProductName'] = $aProduct['sProductName'];//升级前的产品名称
                    $aOrderProductParam['sProductAttr']['iLastOPID'] = $aCard['iOPID'];//升级前opid
                    $aOrderProductParam['sProductAttr']['iLastOrderID'] = $aCard['iOrderID'];//升级前orderID

                    $aOrderProductParam['sProductAttr']['sProductPrice'] = $aProduct[$this->aPriceKey[$aCard['iSex']]];
                    $aOrderProductParam['sProductAttr']['iStoreID'] = $aParam['sid'];//要预约的门店
                    //入orderproduct表
                    //if ($sOrderProductID=2){
                    if ($sOrderProductID = Model_OrderProduct::initOrder($iOrderID, $aUpGrade['iProductID'], $aUpGrade['sProductName'], 1, $sNeedPrice, $sNeedPrice, Model_OrderInfo::ORDERTYPE_UPGRADE, $aOrderProductParam)) {
                        //体检卡产品处理
                        $aOrderCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
                        $aOrderCardProductParam['iPayOrderID'] = $iOrderID;
                        if (Model_OrderCardProduct::updData($aOrderCardProductParam)) {
                            Model_OrderInfo::commit();
                            // return $this->show404('upgrade', false, $sOrderCode);
                            return $this->showMsg('确认去支付', true, '/order/orderpay/ordercode/' . $sOrderCode);
                        } else {
                            Model_OrderInfo::rollback();
                            return $this->show404('卡产品状态更新失败，请稍后重试！', false);
                        }
                    } else {
                        Model_OrderInfo::rollback();
                        return $this->show404('生成订单失败，请稍后重试！', false);
                    }
                } else {
                    Model_OrderInfo::rollback();
                    return $this->show404('生成订单失败，请稍后重试！', false);
                }
            }

            $aCardProductParam['iPreStatus'] = 0;

            // 有API接口的调用预约接口
            $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
            if (!empty($aHasApiConf[$aSupplier['sCode']])) {
                $aStoreCode = Model_StoreCode::getData($iProductID, $aStore['iStoreID'], $aCard['iSex']);
                if (empty($aStoreCode)) {
                    return $this->show404('产品代码不存在，请联系管理员', false);
                }
                if (!empty($aParam['type'])) {//预约改期
                    $aReserve = Model_Supplier::reReserveDate($aSupplier['sCode'],$aStore['sStoreCode'],$aStoreCode['sCode'],$aParam['iOrderTime'],$aCard,$aCardProduct);
                    if (empty($aReserve)) {
                        return $this->show404('接口预约改期失败，请联系管理员', false);
                    }
                } else {
                    $aReserve = Model_Supplier::reserve($aSupplier['sCode'],$aStore['sStoreCode'],$aStoreCode['sCode'],$aParam['iOrderTime'],$aCard,$aCardProduct);                    
                    if (empty($aReserve)) {
                    	$aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
			            $aCardProductParam['iOrderTime'] = strtotime($aParam['iOrderTime']);
			            $aCardProductParam['iReserveTime'] = time();
			            $aCardProductParam['iStoreID'] = $aParam['sid'];
			            $aCardProductParam['iBookStatus'] = 6;
			            Model_OrderCardProduct::updData($aCardProductParam);
                        return $this->show404('接口预约失败，请联系管理员', false);
                    }
                }

                $aCardProductParam['iPreStatus'] = 1;
            }

            $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
            $aCardProductParam['iOrderTime'] = strtotime($aParam['iOrderTime']);
            $aCardProductParam['iReserveTime'] = time();
            $aCardProductParam['iGetReportType'] = $aParam['iGetReportType'];
            $aCardProductParam['iStoreID'] = $aParam['sid'];
            $aCardProductParam['iBookStatus'] = 1;
            $aCardProductParam['iUseStatus'] = 1;//个人支付的体检计划也只能在这里把使用状态改为已用
            //预约的时候把性别带进去，结算的时候要用这个性别(通用卡要传当前性别，性别要传卡的性别)
            $aCardProductParam['iSex'] = !empty($aProduct['iNeedSex']) 
            							? $aCard['iSex'] : $this->aUser['iSex'];
            $aCardProductParam['iPlat'] = Model_OrderCardProduct::RESERVEPLAT_PC;
            Model_OrderCardProduct::begin();
            if (Model_OrderCardProduct::updData($aCardProductParam)) {
                if ($aCard['iUseType'] == Model_OrderCard::USETYPE_OR) {
                    if (Model_OrderCardProduct::updateCardProductStatus($aParam['id'], $aParam['pid'])) {
                        Model_OrderCardProduct::commit();
                        //接口供应商不需要确认的直接发短信
                        if ($aCardProductParam['iPreStatus'] = 1) {
                        	Model_OrderCardProduct::sendMailMsg($aCardProduct['iAutoID'], $this->iCurrUserID);
                        }                                                
                        return $this->show404('预定成功!待供应商确认后您可以去体检', true, '/order/detail/type/2/id/'.$id.'/pid/'.$pid);
                    } else {
                        Model_OrderCardProduct::rollback();
                        return $this->show404('预定失败，请稍后再试', false);
                    }
                } else {
                    Model_OrderCardProduct::commit();
                    if ($aCardProductParam['iPreStatus'] = 1) {
                    	Model_OrderCardProduct::sendMailMsg($aCardProduct['iAutoID'], $this->iCurrUserID);
                    }
                    return $this->show404('预定成功!待供应商确认后您可以去体检', true, '/order/detail/type/2/id/'.$id.'/pid/'.$pid);
                }
            } else {
                Model_OrderCardProduct::rollback();
                return $this->show404('预定失败，请稍后再试', false);
            }
        } else {
        	$iStoreID = intval($this->getParam('sid'));
        	$iUPID = intval($this->getParam('upid'));
        	$aStore = Model_Store::getDetail($iStoreID);
        	$iProductID = $aParam['pid'];
        	if ($iUPID) {
        		$iProductID = $iUPID;
        	}

        	$iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
        	$aStores = Model_Store::getAllProductStore($iProductID, $iChannelType, $this->aUser['iCreateUserID'], $this->aUser['iChannelID'], $params);
          	foreach ($aStores as $key => $value) {
        		$aStoreIDs[] = $value['iStoreID'];
        	}

        	// $aStoreIDs = $this->getStoreIDs($iProductID);
        	if ($aStore && $aStore['iStatus']
				&& in_array($iStoreID, $aStoreIDs)) {			
			} else {
				return $this->redirect('/order/buythird/id/' . $id . '/pid/' . $pid . '/sid/' . $sid);
			}

			
			$aProduct = Model_UserProductBase::getUserProductBase($pid, $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);
			$aOP['sPName'] =  $aProduct['sProductName'];
			
			$aEmployee = Model_CustomerNew::getDetail($aCard['iUserID']);
			if ($aEmployee) {
				$aEmployee['sSex'] = (2 == $aEmployee['iSex']) ? '女' : '男';
				$aEmployee['sMarriage'] = (2 == $aEmployee['iMarriage']) ? '已婚' : '未婚';
			}

			if ($aCard['iPayType'] == 2) {
				$aCard['sProductAmount'] = '0.00';
			} else {
				$aCard['sProductAmount'] =  $aProduct[$this->aPriceKey[$aCard['iSex']]];
			}

			$sNeedPrice = '0.00';
	        if (!empty($aParam['upid'])) {
	            $aUpProduct = Model_UserProductBase::getUserProductBase($aParam['upid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);//升级产品
	            if (empty($aUpProduct)) {
	                return $this->show404('体检卡对应升级产品不存在', false);
	            }
	            $aCard['sProductAmount'] = $sNeedPrice = $aUpProduct[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
	            $aUp = Model_OrderInfo::getDetail($aCardProduct['iPayOrderID']);
	            if ($aUp && $aUp['iPayStatus'] == 1) {
	            	$iUpPayStatus = 1;
	            } else {
	            	$iUpPayStatus = 0;
	            }

	            $this->assign('iUpPayStatus', $iUpPayStatus);
	        }

        	$aSupplier = Model_Type::getDetail($aStore['iSupplierID']);
            if (empty($aSupplier['sCode'])) {
                return $this->show404('供应商代码不存在，请联系管理员', false);
            }

            $aReserveStatus = Yaf_G::getConf('aStatus', 'suppliers');
            $aReserDateList = Model_Supplier::getReserveTimeByCode($aSupplier['sCode'], 0, $aStore['sStoreCode'], $aCard['iPhysicalType']);
            $sTitle = empty($aParam['type']) ? '3,提交体检预约（共三步）' : '预约改期';
            $this->assign('sSupplierCode', $aSupplier['sCode']);
            $this->assign('sStoreCode', $aStore['sStoreCode']);
            $this->assign('aReserDateList', $aReserDateList);
            $this->assign('aReserveStatus', $aReserveStatus);
            $this->assign('aParam', $aParam);
            $this->assign('aProduct', $aProduct);
            $this->assign('aCard', $aCard);
            $this->assign('aStore', $aStore);
            $this->assign('aSex', Yaf_G::getConf('aSex'));
            $this->assign('aMarriage', Yaf_G::getConf('aMarriage'));
            $this->assign('aProductType', Yaf_G::getConf('aType', 'product'));
            $this->assign('sTitle', $sTitle);
            $this->assign('iHomeIcon', 0);
            $this->assign('aOP', $aOP);
            $this->assign('aEmployee', $aEmployee);
            $this->assign('aCardProduct', $aCardProduct);
        }
	}

	//根据订单id获取基本信息
	public function getStoreIDs ($iProductID)
	{
		$aStoreIDs = [];
		$aPStore = Model_ProductStore::getAll(['where' => [
			'iProductID' => $iProductID,
			'iType' => 3
		]]);
		if ($aPStore) {
			foreach ($aPStore as $key => $value) {
				$aStoreIDs[] = $value['iStoreID'];
			}	
		}
		
		return $aStoreIDs;
	}

	public function assignUrl($id, $pid)
	{
		$this->assign('sBaseInfoUrl', '/order/baseinfo/id/' . $id . '/pid/' . $pid);
		$this->assign('sBuyNextUrl', '/order/buynext/id/' . $id . '/pid/' . $pid);
		$this->assign('sBuyThirdUrl', '/order/buythird/id/' . $id . '/pid/' . $pid);
		$this->assign('sBuyFourthUrl', '/order/buyfourth/id/' . $id .'/pid/' . $pid);
		$this->assign('sBuyLastUrl', '/order/buylast/id/' . $id . '/pid/' . $pid);
	}

	/*
	 * SET MENU
	 */
    private function setMenu($iMenu)
    {
        $aMenu = [            
            1 => [
                'url' => '/index/order/list/type/1',
                'name' => '待支付的订单',
            ],
            2 => [
                'url' => '/index/order/list/type/2',
                'name' => '已支付的订单',
            ]                    
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }

    /**
     * 体检卡信息核对
     */
    public function cardinfoAction ()
    {
    	$this->_frame = 'pcbasic.phtml';
		$this->assign('class1', 'complete');

		$id = $this->getParam('id');
		$aOrder = Model_OrderInfo::getDetail($id);
		if (!$id || !$aOrder) {
			return $this->redirect('/index/order/list');
		}
		$this->getOrderAmount($id);

		$iUserID = $this->aUser['iUserID'];
		$aEmployee = Model_Company_Employee::getDetail($iUserID);

		$this->assign('iOrderID', $id);
		$this->assign('aOrder', $aOrder);
		$this->assign('aEmployee', $aEmployee);
    }

    /**
     * 获取订单金额
     * @return
     */
    public function getOrderAmount ($iOrderID)
    {
    	$aOP = Model_OrderProduct::getAll(['where' => [
			'iOrderID' => $iOrderID,
		]]);

		$iAmount = 0;
		$iCount = 0;
		if ($aOP) {
			foreach ($aOP as $key => $value) {
				$iAmount += $value['sTotalPrice'];
				$iCount += $value['iProductNumber'];
			}
		}

		$this->assign('aOP', $aOP);
		$this->assign('iAmount', $iAmount);
		$this->assign('iCount', $iCount);

		return [$aOP, $iAmount, $iCount];
    }

    /**
     * 体检卡购买信息
     * @return
     */
    public function cardorderAction ()
    {
    	$id = $this->getParam('id');
    	$type = $this->getParam('type');
    	$aOrder = Model_OrderInfo::getDetail($id);
    	if (!$id || !$aOrder) {
			return $this->redirect('/index/order/list');
		}

		$aOrder['sPayStatus'] = $this->aPayStatus[$aOrder['iPayStatus']];
		if ($aOrder['iPayStatus'] == 0) {
			 $aOrder['sPayStatus'] = $aOrder['sPayStatus'] . '(在线支付)';
		}

		$aBuyer = Model_Customer::getDetail($aOrder['iUserID']);
		$aOP = Model_OrderProduct::getAll(['where' => [
			'iOrderID' => $id
		]]);
		if ($aOP) {
			foreach ($aOP as $key => $value) {
				$aProduct = Model_UserProductBase::getUserProductBase($value['iProductID'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID']);
				$aOP[$key]['sProductName'] = $aProduct['sProductName'];

				$aCard = Model_OrderCard::getAll(['where' => [
					'iOPID' => $value['iAutoID']
				]]);
				foreach ($aCard as $k => $v) {
					if (empty($v['iUserID'])) {
						$status = '未绑定';
					} else {
						$status = '已绑定';
					}
					$aOP[$key]['sCardNumber'] .= $v['sCardCode'] . '(' . $status . ' )<br />';
				}
			}
		}

		$this->assign('aOrder', $aOrder);
		$this->assign('iType', $type);
		$this->assign('aBuyer', $aBuyer);
		$this->assign('aOP', $aOP);
    }

    /**
	 * 支付
	 * @return [type] [description]
	 */
	public function orderpayAction ()
	{
		$this->_frame = 'pcbasic.phtml';
		$iOrderID = $this->getParam('iOrderID');
		$sOrderCode = $this->getParam('ordercode');
		if ($sOrderCode) {
			$aOrder = Model_OrderInfo::getOrderByOrderCode($sOrderCode);
			$iOrderID = $aOrder['iOrderID'];
		}
		if (!$sOrderCode) {
			$aOrder = Model_OrderInfo::getDetail($iOrderID);
		}

		$this->assign('aOrder', $aOrder);

		list($aOP, $iAmount, $iCount) = $this->getOrderAmount($iOrderID);
		$sOrderName = '中盈体检卡(' . $iCount . '张)';
		$this->assign('sOrderName', $sOrderName);
	}

	//预约取消
    public function cancelAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $aProduct = Model_OrderCardProduct::getCardProduct($aParam['id'], $aParam['pid']);
        if (empty($aProduct)) {
            return $this->show404('该体检卡没有绑定该产品', false);
        }
        if ($aProduct['iBookStatus'] != 1) {
            return $this->show404('该体检卡还未预约，不能取消预约', false);
        }
        
        $aStore = Model_Store::getDetail($aProduct['iStoreID']);
        $aProduct['iBookStatus'] = 3;
        $aProduct['iPreStatus'] = 0;
        $aProduct['iOrderTime'] = 0;
        $aProduct['iStoreID'] = 0;
        $aProduct['iUseStatus'] = 0;
        $aProduct['iReserveTime'] = 0;
        $aProduct['iCanncalReserveTime'] = time();

        //有API接口的调用预约接口
        $aSupplier = Model_Type::getDetail($aStore['iSupplierID']);
        if (empty($aSupplier['sCode'])) {
            return $this->show404('供应商代码不存在，请联系管理员', false);
        }
        $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
        if (!empty($aHasApiConf[$aSupplier['sCode']])) {
            $aReserve = Model_Supplier::cancalReserve($aSupplier['sCode'],$aStore['sStoreCode'],$aProduct, $aCard['iPhysicalType']);
            if (empty($aReserve)) {
                return $this->show404('取消预约接口失败，请联系管理员', false);
            }
        }

        Model_OrderCardProduct::begin();
        if (Model_OrderCardProduct::updData($aProduct)) {
            if ($aCard['iUseType'] == Model_OrderCard::USETYPE_OR) {
                if (Model_OrderCardProduct::updateCardProductStatus($aParam['id'], $aParam['pid'], 1)) {
                    Model_OrderCardProduct::commit();
                    return $this->show404('取消成功', true, '/index/record/list/');
                } else {
                    Model_OrderCardProduct::rollback();
                    return $this->show404('取消失败，请稍后再试', false);
                }
            } else {
                Model_OrderCardProduct::commit();
                return $this->show404('取消成功', true, '/index/record/list/');
            }
        } else {
            Model_OrderCardProduct::rollback();
            return $this->show404('取消失败，请稍后再试', false);
        }
    }

    /**
     * 升级产品列表
     */
    public function upgradecheckAction ()
    {
    	$aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $id = $aParam['id'];
        $pid = $aParam['pid'];
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
        $aProduct = Model_UserProductBase::getUserProductBase($aParam['pid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
    	$aUpgrade = Model_UserProductUpgrade::getUserProductUpgrade($aParam['pid'], $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannel']);
        if (!$aUpgrade) {
        	return $this->show404('该产品没有升级套餐！', false);
        }

        $iTime = time();
        //组装升级产品所需数据
        if (!empty($aUpgrade)) {
            $aCardProducts = Model_OrderCardProduct::getProductByCardIDs($aParam['id']);//获取该卡中所有产品id
            foreach ($aUpgrade as $key => $value) {
                $aUpgradeDetail = Model_UserProductBase::getUserProductBase($value['iUpgradeID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);
                //过滤用户级别已经添加过，且价格不满足条件的升级产品
                if ($aUpgradeDetail['iManPrice'] - $aProduct['iManPrice'] <= 0 || $aUpgradeDetail['iWomanPrice1'] - $aProduct['iWomanPrice1'] <= 0 || $aUpgradeDetail['iWomanPrice2'] - $aProduct['iWomanPrice2'] <= 0) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤未发布和已过期的产品
                if ($aUpgradeDetail['iStatus'] != 1 || ($aUpgradeDetail['sStartDate'] != '0000-00-00' && strtotime($aUpgradeDetail['sStartDate']) > $iTime) || ($aUpgradeDetail['sEndDate'] != '0000-00-00' && strtotime($aUpgradeDetail['sEndDate']) < $iTime)) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤该卡中所有已有的产品
                if (in_array($aUpgradeDetail['iProductID'], $aCardProducts)) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤通用卡升级成性别卡
                if ($aUpgradeDetail['iNeedSex'] != $aProduct['iNeedSex']) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                if (!empty($aUpgradeDetail)) {
                    $aUpgrade[$key]['sProductName'] = $aUpgradeDetail['sProductName'];
                    $aUpgrade[$key]['iPrice'] = $aUpgradeDetail[$this->aPriceKey[$aCard['iSex']]];
                    $aUpgrade[$key]['sProductCode'] = $aUpgradeDetail['sProductCode'];
                    $aUpgrade[$key]['sAlias'] = $aUpgradeDetail['sAlias'];
                    $aUpgrade[$key]['iNeedPrice'] = $aUpgradeDetail[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
                }

                list($aUpgrade[$key]['iStoreNum'], $aUpgrade[$key]['iCardNum']) 
                = $this->getStoreAndPersonNumber($value['iUpgradeID']);
                $aUpgrade[$key]['sImage'] = $aUpgradeDetail['sImage'];
                $aUpgrade[$key]['sRemark'] = $aUpgradeDetail['sRemark'];
            }
        }

        
        $sReturnUrl = '/order/buythird/id/'.$id.'/pid/'.$pid;
        $sUpgradeUrl = '/order/upgrade/id/'.$id.'/pid/'.$pid;
        return $this->showMsg($sUpgradeUrl, true);

        $this->assign('aUpgrade', $aUpgrade);
        $this->assign('sDetailUrl', '/index/web/detail/');
        $this->assign('sReturnUrl', $sReturnUrl);
        $this->assign('iPID', $pid);
    }

    public function upgradeAction ()
    {
    	$this->_frame = 'pcbasic.phtml';
    	$aParam = $this->getParams();
        if (empty($aParam['id']) || empty($aParam['pid'])) {
            return $this->show404('参数不全', false);
        }
        $id = $aParam['id'];
        $pid = $aParam['pid'];
        $aCard = Model_OrderCard::getDetail($aParam['id']);
        if (empty($aCard)) {
            return $this->show404('体检卡号不存在', false);
        }
        if ($aCard['iUserID'] != $this->iCurrUserID) {
            return $this->show404('你无权操作该体检卡', false);
        }
        $iChannelType = ($aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT || $aCard['iOrderType'] == Model_OrderCard::ORDERTYPE_PRODUCT_PLAN) ? 1 : 2;
        $aProduct = Model_UserProductBase::getUserProductBase($aParam['pid'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);
        if (empty($aProduct)) {
            return $this->show404('产品不存在', false);
        }
    	$aUpgrade = Model_UserProductUpgrade::getUserProductUpgrade($aParam['pid'], $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannel']);
        if (!$aUpgrade) {
        	return $this->show404('该产品没有升级套餐！', false);
        }

    	$iTime = time();
        //组装升级产品所需数据
        if (!empty($aUpgrade)) {
            $aCardProducts = Model_OrderCardProduct::getProductByCardIDs($aParam['id']);//获取该卡中所有产品id
            foreach ($aUpgrade as $key => $value) {
                $aUpgradeDetail = Model_UserProductBase::getUserProductBase($value['iUpgradeID'], $aCard['iCompanyID'], $iChannelType, $this->aUser['iChannelID']);
                //过滤用户级别已经添加过，且价格不满足条件的升级产品
                if ($aUpgradeDetail['iManPrice'] - $aProduct['iManPrice'] <= 0 || $aUpgradeDetail['iWomanPrice1'] - $aProduct['iWomanPrice1'] <= 0 || $aUpgradeDetail['iWomanPrice2'] - $aProduct['iWomanPrice2'] <= 0) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤未发布和已过期的产品
                if ($aUpgradeDetail['iStatus'] != 1 || ($aUpgradeDetail['sStartDate'] != '0000-00-00' && strtotime($aUpgradeDetail['sStartDate']) > $iTime) || ($aUpgradeDetail['sEndDate'] != '0000-00-00' && strtotime($aUpgradeDetail['sEndDate']) < $iTime)) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                //过滤该卡中所有已有的产品
                if (in_array($aUpgradeDetail['iProductID'], $aCardProducts)) {
                    unset($aUpgrade[$key]);
                    continue;
                }
                if (!empty($aUpgradeDetail)) {
                    $aUpgrade[$key]['sProductName'] = $aUpgradeDetail['sProductName'];
                    $aUpgrade[$key]['iPrice'] = $aUpgradeDetail[$this->aPriceKey[$aCard['iSex']]];
                    $aUpgrade[$key]['sProductCode'] = $aUpgradeDetail['sProductCode'];
                    $aUpgrade[$key]['sAlias'] = $aUpgradeDetail['sAlias'];
                    $aUpgrade[$key]['iNeedPrice'] = $aUpgradeDetail[$this->aPriceKey[$aCard['iSex']]] - $aProduct[$this->aPriceKey[$aCard['iSex']]];
                }

                list($aUpgrade[$key]['iStoreNum'], $aUpgrade[$key]['iCardNum']) 
                = $this->getStoreAndPersonNumber($value['iUpgradeID']);
                $aUpgrade[$key]['sImage'] = $aUpgradeDetail['sImage'];
                $aUpgrade[$key]['sRemark'] = $aUpgradeDetail['sRemark'];
            }
        }

        $sReturnUrl = '/order/buythird/id/'.$id.'/pid/'.$pid;
        $this->assign('aUpgrade', $aUpgrade);
        $this->assign('sDetailUrl', '/index/web/detail/');
        $this->assign('sReturnUrl', $sReturnUrl);
        $this->assign('iPID', $pid);
    }
    /**
     * 产品对比
     */
    public function upgradeCompareAction ()
    {
    	$pid = $this->getParam('pid');
    	$upid = $this->getParam('upid');

    	//获取该产品包含的单项
        $aItem = Model_ProductItem::getProductItems($upid, Model_ProductItem::EXPANDPRODUCT, null, true);
        if (!empty($aItem)) {
            $sItem = implode(',', $aItem);
            $aItemCat = Model_Item::setGroupByCategory($sItem, true);
        } else {
            $aItemCat = [];
        }
        //获取原产品包含的单项
        $aItem1 = Model_ProductItem::getProductItems($pid, Model_ProductItem::EXPANDPRODUCT, null, true);
        //获取两个产品的单项合集
        $aTmp = array_merge($aItem, $aItem1);
        $aItems = [];
        if (!empty($aTmp)) {
            $aItemsDetailParam['iStatus'] = 1;
            $aItemsDetailParam['iItemID IN'] = array_values($aTmp);
            $aItems = Model_Item::getAll($aItemsDetailParam, true);
        }
        
        $aMerge = ['aItems' => $aItems, 'aItem' => $aItem, 'aItem1' => $aItem1];

        return $this->showMsg($aMerge, true);
    }

}