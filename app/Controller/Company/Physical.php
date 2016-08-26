<?php

/**
 * 企业后台_体检产品
 * User: xuchuyuan
 * Date: 16/5/7 13:00
 */
class Controller_Company_Physical extends Controller_Company_Base
{

	public $senddata = null;

	public $aStatus = [
    		'0' => '未预约',
    		'1' => '已预约',
    		'2' => '已体检',
    		'3' => '已退订',
    		'4' => '作废',
    		'5' => '已出报告',
    		'6' => '预约失败'
    	];

    public $aPlanStatus = [
    		'0' => '未启动',
    		'1' => '进行中',
    		'2' => '已结束'
    	];

	public function actionAfter ()
	{
		parent::actionAfter();
		$this->assign('sInsertItemUrl', '/company/physical/insertitem/');
		$this->assign('sDeleteItemUrl', '/company/physical/deleteitem/');
		$this->assign('sNextItemUrl', '/company/physical/addplanuser');

		$this->assign('sInsertUserUrl', '/company/physical/insertuser/');
		$this->assign('sDeleteUserUrl', '/company/physical/deleteuser/');
		$this->assign('sNextUserUrl', '/company/physical/addnext');
	}

	/**
	 * 体检产品人员列表
	 * @return [array]
	 */
	public function listAction ()
	{
		$page = $this->getParam('page');
		$aUser = Model_User::getDetail($this->enterpriseId);
		$aHrProduct = Model_Product::getUserViewProductList($this->enterpriseId, 1, $aUser['iChannel'], $page);
		$this->assign('aHrProduct', $aHrProduct);
		$this->setMenu(0);
	}

	/**
	 * 按体检产品新增人员
	 */
	public function addAction ()
	{
		$iHRProductID = intval($this->getParam('iHRProductID'));
		if (!$iHRProductID) {
			$this->redirect('list');
		}

		//默认情况下仅显示最近三个月未参加过该体检的员工信息
		//先找出最近三个月的员工 然后去过滤该些员工
		$where = [
			'iCreateUserID' => $this->enterpriseId,
			'iStatus' => Model_Company_Company::STATUS_VALID
		];
		$iIsAll = intval($this->getParam('iIsAll'));
		if (!$iIsAll) {
			$sUserID = $this->getCustomerLastThreeMonths($this->enterpriseId);
			if ($sUserID) {
				$where['iUserID NOT IN'] = $sUserID;
			}
		}

		$this->getParam('sRealName') ? $where['sRealName'] = $this->getParam('sRealName') : '';
		if ($where['sRealName']) {
			$sIDs = Model_CustomerNew::getCustomerIDsByName($where['sRealName']);
			if ($sIDs) {
				$where['iUserID IN'] = $sIDs;
			} else {
				$where['iUserID <'] = 0;
			}
		}
		$this->getParam('iDeptID') ? $where['iDeptID'] = $this->getParam('iDeptID') : '';
		$this->getParam('iJobGradeID') ? $where['iJobGradeID'] = $this->getParam('iJobGradeID') : '';
		$aEmpolyee = Model_Company_Company::getAll([
			'where' => $where
		]);
		$aEmpolyee = $this->setEmployeeData($aEmpolyee);
		

		$this->assign('aEmpolyee', $aEmpolyee);
		$this->assign('aParam', $where);
		$this->assign('iIsAll', $iIsAll);
		$this->assign('iHRProductID', $iHRProductID);
	}
	
	/**
	 * 获取用户卡详情
	 * @param  $iPlanID
	 * @param  $iUserID
	 * @return [array]
	 */
	public function getUserOrderInfo ($iPlanID, $iUserID)
	{
		$aCard = Model_OrderCard::getRow(['where' => [
			'iPlanID' => $iPlanID,
			'iUserID' => $iUserID,
			'iStatus IN' => ['-99', 1] 
		]]);

		if ($aCard) {
			$aCard['iProductID'] = 0;
			$aCard['iCardID'] = $aCard['iAutoID'];
			if ($aCard['iUseType'] == 1) {
				$aCP = Model_OrderCardProduct::getAll(['where' => [
					'iCardID' => $aCard['iAutoID'],
					'iStatus >' => 0
				]]);
				$index = 0;
				foreach ($aCP as $key => &$value) {
					if ($value['iStatus'] == 2) {
						unset($value['iAutoID']);
					}
					if ($value['iStatus'] == 1 || $value['iStatus'] == 3){
						$aCard['iProductID'] = $value['iProductID'];
						$index++;
					}
				}
				
				if ($aCard['iUseAll'] == 1) {
					$aCard['iProductID'] = 0;
				}
			}						
		}

		return $aCard;
	}

	/**
	 * 按体检产品新增人员 第二步
	 * @return [type] [description]
	 */
	public function addnextAction ()
	{
		$type = $this->getParam('stype');
		if ($this->isPost()) {
			$data = $this->checkData();
			if (!$data) {
				return null;
			}

			//判断选择的人性别和卡性别是否一致
			foreach ($data['aUserID'] as $key => $value) {
				$aUser = Model_CustomerNew::getDetail($value);
				$iSex = $aUser['iSex'] != 1 ? ($aUser['iSex'] == 2 && $aUser['iMarriage'] == 1) ? 2 : 3 : 1;
				if ($iSex != $data['aAttribute'][$value]) {
					// return $this->showMsg('卡产品性别必须和体检人性别婚姻状况一致', false);
				}
			}
						
			$sID = Model_OrderCard::createCard($data, $this->enterpriseId);
			$opt = $this->getParam('opt');
			if ($data['iSend'] == 1) {
				$url = '/company/physical/send?ids='.$sID;
			} else {
				if ($opt == 'edit') {
					$url = '/company/physical/plansetvalid/stype/2/iPlanID/'.$data['iPlanID'];
				} else {
					$url = '/company/physical/record';
				}
			}

			return $this->showMsg($url,  true);
		} else {
			$aSelProduct = [];
			if ($type == 2) {
				$aOrderID = [];
				$iPlanID = $this->getParam('iPlanID');
				if (!$iPlanID) {
					return $this->redirect('/company/physical/list');
				}
				$aPlan = Model_Physical_Plan::getDetail($iPlanID);
		        if (!$aPlan) {
		            return $this->redirect('/company/physical/list');
		        }
		        $ids = $this->getPlanUserIDs($iPlanID);
		        $sEndDate = $aPlan['sEndDate'];

		        $this->assign('iPlanID', $iPlanID);
			} else {
				$ids = $this->getParam('ids');
				$ids = $this->removeEmptyIDs($ids);
				$iHRProductID = $this->getParam('iHRProductID');
			}
			if (!$ids) {
				return $this->redirect('/company/physical/list');
			}

			if ($type == 2) {
				$aSelProduct = $this->getPlanProduct($iPlanID);
				$aEmployee = Model_CustomerNew::getListByPKIDs($ids);
				foreach ($aEmployee as $key => $value) {
					$aOrderInfo = $this->getUserOrderInfo($iPlanID, $value['iUserID']);
					// print_r($aOrderInfo);die;
					$aEmployee[$key]['iPayType'] = isset($aOrderInfo['iPayType']) 
													? $aOrderInfo['iPayType'] : 2;
					$aEmployee[$key]['iProductID'] = isset($aOrderInfo['iProductID']) 
													? $aOrderInfo['iProductID'] : 0;
					$aEmployee[$key]['sStartDate'] = isset($aOrderInfo['sStartDate']) 
														&& $aOrderInfo['sStartDate'] != '0000-00-00' 
														? $aOrderInfo['sStartDate'] : $aPlan['sStartDate'];
					$aEmployee[$key]['sEndDate'] = isset($aOrderInfo['sEndDate']) && $aOrderInfo['sEndDate'] 
													!= '0000-00-00' ? $aOrderInfo['sEndDate'] : $aPlan['sEndDate'];
					$aEmployee[$key]['iPhysicalType'] = isset($aOrderInfo['iPhysicalType']) 
													? $aOrderInfo['iPhysicalType'] : 2;
					$aEmployee[$key]['iPaperReport'] = isset($aOrderInfo['iPaperReport']) 
													? $aOrderInfo['iPaperReport'] : 0;
					$aEmployee[$key]['iAttribute'] = isset($aOrderInfo['iSex']) 
													? $aOrderInfo['iSex'] : 1;
					$aEmployee[$key]['iUseType'] = isset($aOrderInfo['iUseType']) 
													? $aOrderInfo['iUseType'] : 1;
					$aEmployee[$key]['iPhysicalProductID'] = isset($aOrderInfo['iCardID']) 
													? $aOrderInfo['iCardID'] : 0;
				}
			} else {
				$aEmployee = Model_CustomerNew::getListByPKIDs($ids);
				foreach ($aEmployee as $key => $value) {
					$aEmployee[$key]['iPayType'] = 2;
					$aEmployee[$key]['iProductID'] = $iHRProductID;
					$aEmployee[$key]['sStartDate'] = date('Y-m-d', time());
					$aEmployee[$key]['sEndDate'] = isset($sEndDate) ? $sEndDate : '';
					$aEmployee[$key]['iPhysicalType'] = 2;
					$aEmployee[$key]['iPaperReport'] = 0;
					$aEmployee[$key]['iAttribute'] = 1;
					$aEmployee[$key]['iUseType'] = 1;
				}
			}

			$this->assign('aEmployee', $aEmployee);
			$this->assign('iHRProductID', $iHRProductID);
			$this->assign('aSelProduct', $aSelProduct);
			$this->assign('stype', $type);
		}
	}

	/**
	 * 发送邮件
	 * @return
	 */
	public function sendAction ()
	{
		$ids = $this->getParam('ids');
		if (!$ids) {
			$this->redirect('list');
		}

		$enterprise = '';
		$aEnterprise = Model_User::getDetail($this->enterpriseId);
		if ($aEnterprise) {
			$enterprise = $aEnterprise['sRealName'];
		}

		$userIds = [];
		$aPhysical = Model_OrderCard::getListByPKIDs($ids);
		if ($aPhysical) {
			foreach ($aPhysical as $key => $value) {
				$userIds[] = $value['iUserID'];
			}
		}

		$aUsers = [];
		$aEmpolyee = [];
		if ($userIds) {
			$aEmpolyee = Model_CustomerNew::getListByPKIDs($userIds);
			if ($aEmpolyee) {
				foreach ($aEmpolyee as $key => $value) {
					$aUsers[$value['iUserID']]['sRealName'] = $value['sRealName'];
					$aUsers[$value['iUserID']]['sMobile'] = $value['sMobile'];

					$aCompany = Model_Company_Company::checkIsExist($value['iUserID'], $this->enterpriseId);
					$aUsers[$value['iUserID']]['sEmail'] = $aCompany['sEmail'];
					$aUsers[$value['iUserID']]['sUserName'] = $aCompany['sUserName'];
					$aUsers[$value['iUserID']]['sPassword'] = $aCompany['sUserName'];
				}
			}
		}

		if ($this->isPost()) {
			$this->senddata = $this->checkParam();
			if (!$this->senddata) {
				return null;
			}
			if (!$aPhysical) {
				return $this->showMsg('没有体检信息',  false);
			}

			$tmp = [];
			foreach ($aPhysical as $key => $value) {
				$tmp['iAutoID'] = $value['iAutoID'];
				$tmp['sRealName'] = $aUsers[$value['iUserID']]['sRealName'];
				$tmp['sUserName'] = $aUsers[$value['iUserID']]['sUserName'];
				$tmp['sPassword'] = $aUsers[$value['iUserID']]['sUserName'];
				$tmp['sEmail'] = $aUsers[$value['iUserID']]['sEmail'];
				$tmp['sMobile'] = $aUsers[$value['iUserID']]['sMobile'];
				$tmp['sEnterprise'] = $enterprise;
				$tmp['sPhysicalNumber'] = $value['sCardCode'];
				$tmp['sStartDate'] = $value['sStartDate'];
				$tmp['sEndDate'] = $value['sEndDate'];
				$tmp['iPhysicalType'] = $value['iPhysicalType'];
				$this->sendMail($tmp);
			}
			return $this->showMsg('发送成功',  true);
		} else {
			if (!$aPhysical) {
				$this->redirect('list');
			}

			$mail1 = $mail2 = $msg1 = $msg2 = '';
			foreach ($aPhysical as $key => $value) {
				if ($value['iPhysicalType'] == 1) {
					$mail1 = Yaf_G::getConf('mail', 'physical');
					$msg1  = Yaf_G::getConf('msg', 'physical');
				} else {
					$mail2 = Yaf_G::getConf('mail2', 'physical');
					$msg2 = Yaf_G::getConf('msg2', 'physical');
				}
			}

			$title = '(代理)体检通知';

			$aMail['users'] = $aUsers;
			$aMail['title'] = $enterprise ? $enterprise . '-' . $title : $title;
			$aMail['content'] = $mail1 . $mail2;
			$aMail['msg'] = $msg1 . $msg2;

			$this->assign('aMail', $aMail);
			$this->assign('ids', $ids);
		}
	}


	/**
	 * 发送邮件和短信模板
	 * @param  [type] $tmp [description]
	 * @return [type]      [description]
	 */
	public function sendMail ($tmp)
	{
		if ($this->senddata['iSendMsg']) {
			if ($tmp['iPhysicalType'] == 1) {
				$msg = Yaf_G::getConf('msg', 'physical');
			} else {
				$msg = Yaf_G::getConf('msg2', 'physical');
			}

			$msg = preg_replace('/\【员工姓名\】/', $tmp['sRealName'], $msg);
			$msg = preg_replace('/\【公司名称\】/', $tmp['sEnterprise'], $msg);
			$msg = preg_replace('/\【开始时间\】/', $tmp['sStartDate'], $msg);
			$msg = preg_replace('/\【结束时间\】/', $tmp['sEndDate'], $msg);
			$msg = preg_replace('/\【体检卡号\】/', $tmp['sPhysicalNumber'], $msg);

			$smsRes = Sms_Joying::sendBatch($tmp['sMobile'], $msg);
			if ($smsRes) {
				$data = [];
				$data['iAutoID'] = $tmp['iAutoID'];
				$data['iSendMsg'] = 1;
				Model_OrderCard::updData($data);
			}
		}

		if ($this->senddata['iSendEmail']) {
			if ($tmp['iPhysicalType'] == 1) {
				$content = Yaf_G::getConf('mail', 'physical');
			} else {
				$content = Yaf_G::getConf('mail2', 'physical');
			}

			if ($this->senddata['sAccount']) {
				$content .= Yaf_G::getConf('account', 'physical');
			}

			$content = preg_replace('/\【员工姓名\】/', $tmp['sRealName'], $content);
			$content = preg_replace('/\【公司名称\】/', $tmp['sEnterprise'], $content);
			$content = preg_replace('/\【体检开始日期\】/', $tmp['sStartDate'], $content);
			$content = preg_replace('/\【体检截止日期\】/', $tmp['sEndDate'], $content);
			$content = preg_replace('/\【体检卡号\】/', $tmp['sPhysicalNumber'], $content);
			$content = preg_replace('/\【账号\】/', $tmp['sUserName'], $content);
			$content = preg_replace('/\【初始密码\】/', $tmp['sUserName'], $content);

			$mailRes = Util_Mail::send($tmp['sEmail'], $this->senddata['title'], $content);
			if ($mailRes == 1) {
				$data = [];
				$data['iAutoID'] = $tmp['iAutoID'];
				$data['iSendEMail'] = 1;
				Model_OrderCard::updData($data);
			}
		}		
	}

	public function checkData ()
	{
		$params = $this->getParams();

		if (!isset($params['aUserID'])) {
			return $this->showMsg('请选择人员',  false);
		}
		foreach ($params['aUserID'] as $key => $value) {
			if (!$value) {
				return $this->showMsg('请选择人员',  false);
			}
		}

		if (!isset($params['aPhysicalType'])) {
			return $this->showMsg('请选择体检类型',  false);
		}
		foreach ($params['aPhysicalType'] as $key => $value) {
			if (!$value) {
				return $this->showMsg('请选择体检类型',  false);
			}
		}

		if (!isset($params['aPayType'])) {
			return $this->showMsg('请选择付款方式',  false);
		}
		foreach ($params['aPayType'] as $key => $value) {
			if (!$value) {
				return $this->showMsg('请选择付款方式',  false);
			}
		}

		if (!isset($params['aStartDate'])) {
			return $this->showMsg('请选择开始日期',  false);
		}
		foreach ($params['aStartDate'] as $key => $value) {
			if (!$value) {
				return $this->showMsg('请选择开始日期',  false);
			}
		}

		if (!isset($params['aEndDate'])) {
			return $this->showMsg('请选择截止日期',  false);
		}
		foreach ($params['aEndDate'] as $key => $value) {
			if (!$value) {
				return $this->showMsg('请选择截止日期',  false);
			}
		}

		if (isset($params['aUseType'])) {
			foreach ($params['aUserID'] as $key => $value) {
				if ($params['aProductID'][$value] > 0 && $params['aUseType'][$value] == 2) {
					return $this->showMsg('AND只适用于指定产品全部可选',  false);
				}
			}
		}

		return $params;
	}


	/**
	 * 发送邮件参数检测
	 * @return [array/json]
	 */
	public function checkParam ()
	{
		$params = $this->getParams();
		if (!$params['ids']) {
			return $this->showMsg('请选择人员', false);
		}

		if (!isset($params['iSendEmail']) && !isset($params['iSendMsg'])) {
			return $this->showMsg('请至少勾选一个发送', false);
		}

		$params['iSendEmail'] = isset($params['iSendEmail']) ? 1 : 0;
		$params['iSendMsg'] = isset($params['iSendMsg']) ? 1 : 0;
		$params['sAccount'] = isset($params['sAccount']) ? 1 : 0;

		if (!$params['title']) {
			return $this->showMsg('请填写邮件标题', false);
		}

		if ($params['iSendEmail'] && !$params['content']) {
			return $this->showMsg('请填写邮件正文', false);
		}

		if ($params['iSendMsg'] && !$params['msg']) {
			return $this->showMsg('请填写短信内容', false);
		}

		if ($params['sAccount']) {
			$account = Yaf_G::getConf('account', 'physical');
			$params['content'] .= $account;
		}

		return $params;
	}

	/**
	 * 新增体检计划
	 * @return
	 */
	public function addplanAction ()
	{
		if ($this->isPost()) {
			$aPlan = $this->_checkClientData();
            if (empty($aPlan)) {
                return null;
            }

            $aPlan['iHRID'] = $this->enterpriseId;
            $aPlan['iStatus'] = 0;
            if ($iLastInsertID = Model_Physical_Plan::addData($aPlan)) {
                $sNextUrl = '/company/physical/plannext/id/' . $iLastInsertID;
                return $this->showMsg($sNextUrl, true);
            } else {
                return $this->showMsg('增加失败！', false);
            }
		}
	}

	/**
	 * 新增体检计划 step2
	 * @return
	 */
	public function plannextAction ()
	{
		$id = $this->getParam('id');
        $iPlanID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iPlanID)) {
            return $this->showMsg('参数有误', $iPlanID);
        }
        $aPlan = Model_Physical_Plan::getDetail($iPlanID);
        $aHasItem = [];//该产品已包含的单项
        if (empty($aPlan)) {
            return $this->showMsg('该体检计划不存在!', false);
        }

        $aPlanProduct = Model_Physical_PlanProduct::getAll([
       		'where' => [
       			'iPlanID' => $iPlanID,
				'iStatus' => Model_Physical_PlanProduct::STATUS_VALID,
       		]
       	]);
		$iProductIDs = '';
       	if ($aPlanProduct) {
       		foreach ($aPlanProduct as $k => $val) {
				if ($val['iProductID']) {
					$iProductIDs
					? $iProductIDs .= ',' . $val['iProductID']
					: $iProductIDs  = $val['iProductID'];
				}
			}

			if ($iProductIDs) {
				$aUser = Model_User::getDetail($this->enterpriseId);
				$aHasItem = Model_Product::getAllUserProduct($this->enterpriseId,1,$aUser['iChannel'],$iProductIDs);
				if($aHasItem) {
					foreach ($aHasItem as $key => $value) {
						$aProduct = Model_Product::getDetail($value['iProductID']);
						if ($aProduct) {
							$aHasStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::BASEPRODUCT);
							$aHasItem[$key]['sProductCode'] = $aProduct['sProductCode'];
							$aHasItem[$key]['sProductName'] = $aProduct['sProductName'];
							$aHasItem[$key]['iCount'] = count($aHasStore);
						}
					}
				}
			}
       	}

		$aUser = Model_User::getDetail($this->enterpriseId);
		$aHrProduct = Model_Product::getUserViewProductList($this->enterpriseId, 1,$aUser['iChannel'],$iPage,false,$iProductIDs,'iUpdateTime DESC', 10, ['id' => $id]);
        if ($aHrProduct['aList']) {
        	foreach ($aHrProduct['aList'] as $key => $value) {
        		$aProduct = Model_Product::getDetail($value['iProductID']);
				if ($aProduct) {
					$aHasStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::BASEPRODUCT);
					$aHrProduct['aList'][$key]['iCount'] = count($aHasStore);
				}
        	}
        }

        $this->assign('iPlanID', $iPlanID);
        $this->assign('aHrProduct', $aHrProduct);
        $this->assign('aHasItem', $aHasItem);
	}


	/**
     * 请求数据检测
     * @return array|bool
     */
    public function _checkClientData($type = 1)
    {
        $aParam = $this->getParams();
        if (!$aParam['sPlanName']) {
            return $this->showMsg('体检计划名称不能为空', false);
        }

        if (!$aParam['sStartDate']) {
            return $this->showMsg('体检周期开始不能为空', false);
        }

        if (!$aParam['sEndDate']) {
            return $this->showMsg('体检周期结束不能为空', false);
        }

        //验证产品名是否存在
        $aPlan = Model_Physical_Plan::getRow([
        	'where' => [
        		'iHRID' => $this->enterpriseId,
        		'sPlanName' => $aParam['sPlanName'],
        	]
        ]);

        if ($type == 2) {
        	if ($aPlan['iAutoID'] != $aParam['iAutoID']) {
        		return $this->showMsg('体检计划名已存在！', false);
        	}
        } else {
        	if ($aPlan) {
	            return $this->showMsg('体检计划名已存在！', false);
	        }
        }

        return $aParam;
    }

    /**
	 * 添加单项
	 */
    public function insertItemAction()
    {
        $iPlanID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        if (empty($iPlanID)) {
            return $this->showMsg('参数有误', false);
        }
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sItemID)) {
            return $this->showMsg('请先选择产品', false);
        }
        $aPlan = Model_Physical_Plan::getDetail($iPlanID);
        if (empty($aPlan)) {
            return $this->showMsg('该体检计划不存在!', false);
        }

        $aParam['iPlanID'] = $iPlanID;
        $aItemIDs = explode(',', $sItemID);
        foreach ($aItemIDs as $key => $value) {
        	$aParam['iProductID'] = $value;
        	$aRow = Model_Physical_PlanProduct::getRow([
        		'where' => [
        			'iPlanID' => $iPlanID,
        			'iProductID' => $value,
        			'iStatus' => Model_Physical_PlanProduct::STATUS_VALID
        		]
        	]);
        	if (!$aRow) {
        		Model_Physical_PlanProduct::addData($aParam);
        		//添加产品后对card_product表添加产品
        		Model_OrderCard::addItem($iPlanID, $value);
        	}
        }

        return $this->showMsg('添加成功！', true);
    }

    /**
	 * 删除已添加单项
	 */
    public function deleteItemAction()
    {
        $iPlanID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        if (empty($iPlanID)) {
            return $this->showMsg('参数有误', false);
        }
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        $aItemIDs = explode(',', $sItemID);
        if ($aItemIDs) {
        	foreach ($aItemIDs as $key => $value) {
        		if (!$value) {
        			unset($aItemIDs[$key]);
        		}
        	}
        }
        if (empty($aItemIDs)) {
            return $this->showMsg('请先选择产品', false);
        }

        $aPlan = Model_Physical_Plan::getDetail($iPlanID);
        if (empty($aPlan)) {
            return $this->showMsg('该体检计划不存在!', false);
        }

        $aItemIDs = explode(',', $sItemID);
        foreach ($aItemIDs as $key => $value) {
        	$bool = Model_OrderCard::checkIsUsed($iPlanID, $value);
        	if ($bool) {
        		return $this->showMsg('删除产品存在用户已经预约, 不可删除', false);
        	}
        }

        foreach ($aItemIDs as $key => $value) {
        	$aRow = Model_Physical_PlanProduct::getRow([
        		'where' => [
        			'iPlanID' => $iPlanID,
        			'iProductID' => $value,
        			'iHRID' => $this->enterpriseId,
        			'iStatus' => Model_Physical_PlanProduct::STATUS_VALID
        		]
        	]);
        	if ($aRow) {
        		$aParam['iAutoID'] = $aRow['iAutoID'];
        		$aParam['iStatus'] = Model_Physical_PlanProduct::STATUS_INVALID;
        		Model_Physical_PlanProduct::updData($aParam);

        		//删除产品后对card_product表添加产品
        		Model_OrderCard::delItem($iPlanID, $value);
        	}
        }

        return $this->showMsg('删除成功！', true);
    }

    /**
	 * 选择体检员工
	 * @return [type] [description]
	 */
	public function addplanUserAction ()
	{
		$iPlanID = intval($this->getParam('iPlanID'));
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iPlanID)) {
        	return $this->redirect('/company/physical/list');
        }
        $aPlan = Model_Physical_Plan::getDetail($iPlanID);
        if (empty($aPlan)) {
        	return $this->redirect('/company/physical/list');
        }
        
        $where = [
			'iCreateUserID' => $this->enterpriseId,
			'iStatus' => Model_Company_Company::STATUS_VALID
		];
		$iIsAll = intval($this->getParam('iIsAll'));
		if (!$iIsAll) {
			$sUserID = $this->getCustomerLastThreeMonths($this->enterpriseId);
			if ($sUserID) {
				$where['iUserID NOT IN'] = $sUserID;
			}
		}

		$this->getParam('sRealName') ? $where['sRealName'] = $this->getParam('sRealName') : '';
		if ($where['sRealName']) {
			$sIDs = Model_CustomerNew::getCustomerIDsByName($where['sRealName']);
			if ($sIDs) {
				$where['iUserID IN'] = $sIDs;
			} else {
				$where['iUserID <'] = 0;
			}
		}
		$this->getParam('iDeptID') ? $where['iDeptID'] = $this->getParam('iDeptID') : '';
		$this->getParam('iJobGradeID') ? $where['iJobGradeID'] = $this->getParam('iJobGradeID') : '';
		
		$aHasItem = [];
        $aOrder = Model_OrderCard::getAll([
       		'where' => [
       			'iPlanID' => $iPlanID,
       			'iOrderType' => Model_Physical_Product::TYPE_PRODUCT_PLAN,
				'iStatus IN' => ['-99', 1]
       		]
       	]);
       	if ($aOrder) {
       		$iUserIDs = '';
       		foreach ($aOrder as $k => $val) {
				if ($val['iUserID']) {
					$iUserIDs
					? $iUserIDs .= ',' . $val['iUserID']
					: $iUserIDs  = $val['iUserID'];
				}
			}

			if ($iUserIDs) {
				$aHasItem = Model_Company_Company::getAll(['where' => [
						'iUserID IN' => $iUserIDs,
						'iCreateUserID' => $this->enterpriseId
					]
				]);
				$aHasItem = $this->setEmployeeData($aHasItem);       
			}

			$where['iUserID NOT IN'] = $iUserIDs;
			if ($sUserID) {
				$where['iUserID NOT IN'] = $where['iUserID NOT IN'] . ',' . $iUserIDs;
			}
       	}

  		$aEmpolyee = Model_Company_Company::getList($where, $iPage);
		$aEmpolyee['aList'] = $this->setEmployeeData($aEmpolyee['aList']);

        $this->assign('iPlanID', $iPlanID);
        $this->assign('aParam', $where);
        $this->assign('aEmpolyee', $aEmpolyee);
        $this->assign('aHasItem', $aHasItem);
        $this->assign('iIsAll', $iIsAll);
	}

	/**
	 * 添加员工
	 */
    public function insertUserAction()
    {
        $iPlanID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        if (empty($iPlanID)) {
            return $this->showMsg('参数有误', false);
        }
        $aPlan = Model_Physical_Plan::getDetail($iPlanID);
        if (empty($aPlan)) {
            return $this->showMsg('该体检计划不存在!', false);
        }

        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sItemID)) {
            return $this->showMsg('请先选择员工', false);
        }

        $aItemIDs = explode(',', $sItemID);
        $iUserType = 1;
        $iOrderType = 4;
        $iCreateUserType = 2;
        foreach ($aItemIDs as $key => $value) {
        	$aCustomer = Model_CustomerNew::getDetail($value);
			$sProductAmount = 0;
			$aOrder = [
				'iOrderStatus' => 0,
				'iPlanID' => $iPlanID
			];
			$iCardID = Model_OrderCard::initCard($iOrderType, 0, $this->enterpriseId, $iCreateUserType, 0, 0, ['iUserID' => $value, 'iStatus' => '-99', 'iPlanID' => $iPlanID]);
        }

        return $this->showMsg('添加成功！', true);
    }

    /**
	 * 删除已添加员工
	 */
    public function deleteUserAction()
    {
        $iPlanID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        if (empty($iPlanID)) {
            return $this->showMsg('参数有误', false);
        }
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        $aItemIDs = explode(',', $sItemID);
        if ($aItemIDs) {
        	foreach ($aItemIDs as $key => $value) {
        		if (!$value) {
        			unset($aItemIDs[$key]);
        		}
        	}
        }
        if (empty($aItemIDs)) {
            return $this->showMsg('请先选择员工', false);
        }

        $aPlan = Model_Physical_Plan::getDetail($iPlanID);
        if (empty($aPlan)) {
            return $this->showMsg('该体检计划不存在!', false);
        }

        $aItemIDs = explode(',', $sItemID);
        foreach ($aItemIDs as $key => $value) {
        	$aRow = Model_OrderCard::getRow([
        		'where' => [
        			'iPlanID' => $iPlanID,
        			'iOrderType' => Model_Physical_Product::TYPE_PRODUCT_PLAN,
        			'iUserID' => $value,
        			'iStatus IN' => ['-99', '1']
        		]
        	]);
        	if ($aRow) {
        		$aParam['iAutoID'] = $aRow['iAutoID'];
        		$aParam['iStatus'] = 0;
        		Model_OrderCard::updData($aParam);
        	}
        }

        return $this->showMsg('删除成功！', true);
    }

	/*
	 * SET MENU
	 */
    private function setMenu($iMenu)
    {
        $aMenu = [
            0 => [
                'url' => '/company/physical/list',
                'name' => '按体检产品新增',
            ],
            1 => [
                'url' => '/company/physical/addplan',
                'name' => '按体检计划新增',
            ]
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }

    /**
     * 员工体检记录
     * @return [array]
     */
    public function recordAction ()
    {
    	$params = $this->getParams();
    	$params['sStartDate'] ? $where['sStartDate >='] = $params['sStartDate'] : '';
    	$params['sEndDate'] ? $where['sEndDate <'] = $params['sEndDate'] : '';
    	$params['sRealName'] ? $where['sRealName'] = $params['sRealName'] : '';
    	$where = [
    		'iStatus IN' => 1,
    		'iCreateUserType' => 2
    	];
    	isset($params['iStatus']) && ($params['iStatus'] != -1) ? $where1['iBookStatus'] = $params['iStatus']
    	: '';

    	if (isset($where['sRealName']) && $where['sRealName']) {
    		$row = Model_CustomerNew::getAll([
    			'where' => [
    				'sRealName LIKE' => '%' . $where['sRealName'] . '%',
    			]
    		]);
    		if (!$row) {
    			$where['iUserID'] = 0;

    			$this->assign('aProduct', []);
		    	$this->assign('aStatus', $this->aStatus);
		    	$this->assign('aParam', $params);
    			return;
    		} else {
    			foreach ($row as $key => $value) {
    				$userid[] = $value['iUserID'];
    			}
    			// $where['iUserID'] = $row['iUserID'];
    			$where['iUserID IN'] = $userid;
    		}

    		$aEmployees = Model_Company_Company::getAll([
    			'where' => [
    				'iCreateUserID' => $this->enterpriseId,
    				'iStatus >' => Model_Company_Company::STATUS_INVALID,
    				'iUserID IN' => $userid
    			]
    		]);
    		if (!$aEmployees) {
    			$this->assign('aProduct', []);
		    	$this->assign('aStatus', $this->aStatus);
		    	$this->assign('aParam', $params);
    			return;
    		}	
    	} else {
    		$aEmployees = Model_Company_Company::getAll([
    			'where' => [
    				'iCreateUserID' => $this->enterpriseId,
    				'iStatus >' => Model_Company_Company::STATUS_INVALID
    			]
    		]);
    		$aUserIDs = [];
    		foreach ($aEmployees as $key => $value) {
    			$aUserIDs[] = $value['iUserID'];
    		}

    		if (!$aEmployees) {
    			$this->assign('aProduct', []);
		    	$this->assign('aStatus', $this->aStatus);
		    	$this->assign('aParam', $params);
    			return;
    		}
    		if ($aUserIDs) {
    			$sUserIDs = implode(',', $aUserIDs);
    			$where['iUserID IN'] = $sUserIDs;
    		} else {
    			$where['iUserID'] = 0;
    		}
    	}

    	$page = isset($params['page']) ? $params['page'] : 1;

    	$aCard = [];
    	$aCardID = [];
    	$sCardID = '';
    	if (isset($where1['iBookStatus'])) {
    		$aCP = Model_OrderCardProduct::getAll(['where' => $where1]);
    		if ($aCP) {
    			foreach ($aCP as $key => $value) {
    				if ($value['iCardID']) {
    					$aCardID[] = $value['iCardID'];	
    				}    				
    			}
    			if ($aCardID) {
    				$sCardID = implode(',', $aCardID);
    				$where['iAutoID IN'] = $sCardID;

    				$aCard = Model_OrderCard::getList($where, $page, 'iUpdateTime Desc');
			    	if ($aCard['aList']) {
			    		foreach ($aCard['aList'] as $key => $value) {
			    			$aUser = Model_CustomerNew::getDetail($value['iUserID']);
			    			$aCard['aList'][$key]['sRealName'] = $aUser['sRealName'];
			    			$aCard['aList'][$key]['sMobile'] = $aUser['sMobile'];
			    			$aCard['aList'][$key]['sNotice'] = (1 == $value['iSendEMail']) ? '已发送' : '未发送';
			    			$aCard['aList'][$key]['sOperateTime'] = date('Y-m-d H:i:s', $value['iUpdateTime']);
			    			$aCard['aList'][$key]['sBookStatus'] = $this->getBookStatus($value['iAutoID']);
			    			$aCard['aList'][$key]['sPhysicalType'] = ($value['iPhysicalType'] == 1) ? '年度体检' : '入职体检'; 
			    		}
			    	}
    			} 
    		} 		
    	} else {
    		$aCard = Model_OrderCard::getList($where, $page, 'iUpdateTime Desc');
	    	if ($aCard['aList']) {
	    		foreach ($aCard['aList'] as $key => $value) {
	    			$aUser = Model_CustomerNew::getDetail($value['iUserID']);
	    			$aCard['aList'][$key]['sRealName'] = $aUser['sRealName'];
	    			$aCard['aList'][$key]['sMobile'] = $aUser['sMobile'];
	    			$aCard['aList'][$key]['sNotice'] = (1 == $value['iSendEMail']) ? '已发送' : '未发送';
	    			$aCard['aList'][$key]['sOperateTime'] = date('Y-m-d H:i:s', $value['iUpdateTime']);
	    			$aCard['aList'][$key]['sBookStatus'] = $this->getBookStatus($value['iAutoID']);
	    			$aCard['aList'][$key]['sPhysicalType'] = ($value['iPhysicalType'] == 1) ? '年度体检' : '入职体检';
	    		}
	    	}
    	}
    	
    	$this->assign('aProduct', $aCard);
    	$this->assign('aStatus', $this->aStatus);
    	$this->assign('aParam', $params);
    }

    /**
     * 展示状态字符串
     */
    public function getBookStatus ($iCardID)
    {
    	$aBookStatus = [];
    	$aCP = Model_OrderCardProduct::getAll(['where' => [
    		'iCardID' => $iCardID,
    		'iProductID >' => 0,
    		'iStatus' => 1
    	]]);
    	if ($aCP) {
    		foreach ($aCP as $key => $value) {
    			if (!in_array( $this->aStatus[$value['iBookStatus']], $aBookStatus)) {
    				$aBookStatus[] = $this->aStatus[$value['iBookStatus']];	
    			}    			
    		}
    	}

    	$sBookStatus = implode(',', $aBookStatus);
    	return $sBookStatus;
    }

    /**
     * 查看详情
     * @return [array]
     */
    public  function detailAction ()
    {
    	if ($this->isPost()) {
    		$params = $this->getParams();
    		if ($params['iUserID']) {
    			$data['iUserID'] = $params['iUserID'];
    			$data['sRealName'] = $params['sRealName'];
    			$data['iSex'] = $params['iSex'];
    			$data['iMarriage'] = $params['iMarriage'];
    			$data['sIdentityCard'] = $params['sIdentityCard'];
    			$data['sMobile'] = $params['sMobile'];
    			Model_CustomerNew::updData($data);

    			$aCompany = Model_Company_Company::getRow(['where' => [
		    		'iUserID' => $params['iUserID'],
		    		'iCreateUserID' => $this->enterpriseId
		    	]]);
		    	if ($aCompany) {
		    		$data2['iAutoID'] = $aCompany['iAutoID'];
		    		$data2['sUserName'] = $params['sUserName'];
    				$data2['sEmail'] = $params['sEmail'];	
    				Model_Company_Company::updData($data2);
		    	}
    		}

    		if ($params['id']) {
    			$aCard = Model_OrderCard::getDetail($params['id']);
		    	if (!$aCard) {
		    		$this->showMsg('体检卡号不存在', false);
		    	}

		    	// 检查产品是否已付款或者已预约 不能更改
		    	$bool = Model_OrderCard::checkIsPayOrAppoinment($aCard);
		    	if (!$bool) {
		    		return $this->showMsg('该卡已预约或已支付，不能更改', false);
		    	}

    			$card['iAutoID'] = $params['id'];
    			$card['sStartDate'] = $params['sStartDate'];
    			$card['sEndDate'] = $params['sEndDate'];
    			$card['iPhysicalType'] = $params['iPhysicalType'];
    			$card['iPaperReport'] = $params['iPaperReport'];
    			$card['iPayType'] = $params['iPayType'];
    			Model_OrderCard::updData($card);
    			return $this->showMsg('保存成功', true);
    		} else {
    			$this->showMsg('体检订单不存在', false);
    		}
    	} else {
    		$id = intval($this->getParam('id'));
    		$type = intval($this->getParam('type'));

    		$aProduct = $aCard = Model_OrderCard::getDetail($id);
	    	if (!$id || !$aCard) {
	    		$this->redirect('/physical/record');
	    	}

	    	$aProduct['iPayType'] = $aCard['iPayType'] == 2 ? 2 : 1;
	    	$aProduct['iPhysicalType'] = $aCard['iPhysicalType'] == 2 ? 2 : 1;
	    	$aProduct['sNotice'] = (1 == $aCard['iSendEMail']) ? '已发送' : '未发送';
	    	
	    	$iProductID = 0;
	    	if ($aCard['iPlanID'] > 0) {
	    		$aPlan = Model_Physical_Plan::getDetail($aCard['iPlanID']);
	    		$aHrItem = $this->getPlanProduct($aCard['iPlanID']);
	    	} else {
	    		$mocp = Model_OrderCardProduct::getRow(['where' => [
	    			'iCardID' => $aCard['iAutoID'],
	    			'iStatus' => 1
	    		]]);
	    		$aHrItem = [
	    			$mocp['iProductID'] => $mocp['sProductName']
	    		];
	    		$iProductID = $mocp['iProductID'];
	    	}
	    	$aProduct['sPlanName'] = isset($aPlan['sPlanName']) ? $aPlan['sPlanName'] : '--';

	    	$aAppointmen = [];
	    	$aCP = Model_OrderCardProduct::getAll(['where' => [
	    		'iCardID' => $id,
	    		'iProductID >' => 0,
	    		'iStatus' => 1
	    	]]);
	    	if ($aCP) {
	    		foreach ($aCP as $key => $value) {
	    			$aAppointment[$key]['sProductName'] = $value['sProductName'];
	    			$aAppointment[$key]['iBookStatus'] = $value['iBookStatus'];
	    			$aAppointment[$key]['iUseStatus'] = $this->getStatus($value['iStatus'], $value['iUseStatus']);

	    			$aStore = $value['iStoreID'] ? Model_Store::getDetail($value['iStoreID']) : [];
	    			$aAppointment[$key]['sStoreName'] = isset($aStore['sName']) ? $aStore['sName'] : '--';
	    			
	    			$aAppointment[$key]['sPhysicalDate'] = $value['iPhysicalTime']
	    								? date('Y-m-d', $value['iPhysicalTime']) : '';
	    			$aAppointment[$key]['sOrderDate'] = $value['iOrderTime'] ? date('Y-m-d', $value['iOrderTime']) : '';
	    			$aAppointment[$key]['sCreateDate'] = $value['iBookStatus'] ? ($value['iReserveTime'] ? date('Y-m-d H:i:s', $value['iReserveTime']) : '') : '';

	    			if ($aCard['iPayType'] == 1) {
	    				$product = Model_Product::getDetail($value['iProductID']);

	    				$product = Model_UserProductBase::getUserProductBase($value['iProductID'], $this->enterpriseId, 1, $this->enterprise['iChannel']);
	    				$aAppointment[$key]['sCost'] = @$product[$this->sexPrice[$aCard['iSex']]];
	    			} else {
	    				$aAppointment[$key]['sCost'] = 0;	
	    			}

					$aSupplier = Yaf_G::getConf('aHasApi', 'suppliers');
					$data['orderid'] = $value['sApiReserveOrderID'];
					$data['cardnumber'] = $value['sApiCardID'];
					$data['hospid'] = $aStore['sStoreCode'];			
					$data['iPhysicalType'] = $aCard['iPhysicalType'];
					$sData = json_encode($data);
					$supplier = Model_Type::getDetail($aStore['iSupplierID']);
					if (isset($aSupplier[$supplier['sCode']])) {
						$classname = $aSupplier[$supplier['sCode']]['classname'];
						$aAppointment[$key]['sDPUrl'] 
						= '/api/' . $classname . '/downloadreport?data=' . $sData;
					}			
		    	}	
	    	}

	    	$aEmployee = Model_CustomerNew::getDetail($aCard['iUserID']);
	    	$aCompany = Model_Company_Company::getRow(['where' => [
	    		'iUserID' => $aCard['iUserID'],
	    		'iCreateUserID' => $this->enterpriseId
	    	]]);
	    	if ($aCompany) {
	    		$aEmployee['sUserName'] = $aCompany['sUserName'];
	    		$aEmployee['sEmail'] = $aCompany['sEmail'];
	    	}
			if ($type == 2) {
				$sReturnUrl = '/company/physical/plansetvalid/stype/2/iPlanID/'.$aCard['iPlanID'];
			} else {
				$sReturnUrl = '/company/physical/record';
			}

			$this->assign('iProductID', $iProductID);
			$this->assign('aHrItem', $aHrItem);
	    	$this->assign('aProduct', $aProduct);
	    	$this->assign('aAppointment', $aAppointment);
	    	$this->assign('aEmployee', $aEmployee);
	    	$this->assign('aStatus', $this->aStatus);
	    	$this->assign('sReturnUrl', $sReturnUrl);
    	}
    }

    public function getStatus ($iStatus, $iUseStatus)
    {
    	if ($iStatus == 1) {
    		$sStatus = '可用';
    	}
    	if ($iStatus == 2) {
    		$sStatus = '不可用';
    	}
    	if ($iUseStatus == 0) {
    		$sUStatus = '未使用';
    	}
    	if ($iUseStatus == 1) {
    		$sUStatus = '已使用';
    	}

    	return $sStatus.'/'.$sUStatus;
    }

    /**
     * 体检计划列表
     * @return [array]
     */
    public function planAction ()
    {
    	$page = $this->getParam('page');
		$where = [
			'iHRID'   => $this->enterpriseId
		];

		$params = $this->getParams();
		!empty($params['sPlanName']) ? $where['sPlanName'] = $params['sPlanName'] : '';
		isset($params['iStatus']) && ($params['iStatus'] != '-1') ? $where['iStatus'] = intval($params['iStatus']) : '';

    	$aPlan = Model_Physical_Plan::getList($where, $page, 'iUpdateTime Desc');
    	if ($aPlan['aList']) {
    		foreach ($aPlan['aList'] as $key => $value) {
    			$aPlan['aList'][$key]['sPublishTime'] = date('Y-m-d H:i:s',$value['iCreateTime']);

    			switch ($value['iStatus']) {
    				case 1:
    					$sStatus = '进行中';
    					break;
    				case 2:
    					$sStatus = '已结束';
    					break;
    				default:
    					$sStatus = '未启动';
    					break;
    			}
    			$aPlan['aList'][$key]['sStatus'] = $sStatus;
    		}
    	}

    	$this->assign('aPlan', $aPlan);
    	$this->assign('aParam', $params);
    }


    /**
     * 完成进度
     * @return [array]
     */
    public function planIndexAction ()
    {
    	$iPlanID = $this->getParam('id');
    	$this->setPlanMenu(1, $iPlanID);
    }

    /**
     * 计划基本信息
     * @return [array]
     */
    public function planDetailAction ()
    {
    	$iPlanID = $this->getParam('id');
    	$aPlan = Model_Physical_Plan::getDetail($iPlanID);

    	if ($this->isPost()) {
    		$aData = $this->_checkClientData(2);    		
            if (empty($aData)) {
                return null;
            }
            if (1 == $aData['iStatus']) {
            	// Model_Physical_Plan::sendPlan($aData['iAutoID']);
            }

    		$updID = Model_Physical_Plan::updData($aData);
    		if ($updID) {
    			$this->showMsg('保存成功', true);
    		} else {
    			$this->showMsg('保存失败', true);
    		}

    	}
    	$this->setPlanMenu(1, $iPlanID);
    	$this->assign('aPlan', $aPlan);
    	$this->assign('opt', 'edit');
    	$this->assign('aStatus', $this->aPlanStatus);
    }

    /**
     * 体检产品
     * @return [arrray]
     */
    public function planItemAction ()
    {
    	$this->plannextAction();

    	$iPlanID = $this->getParam('id');
    	$this->setPlanMenu(2, $iPlanID);

    	$this->assign('opt', 'edit');
    }

    /**
     * 体检员工
     * @return [arrray]
     */
    public function planUserAction ()
    {
    	$this->addplanuserAction();

    	$iPlanID = $this->getParam('iPlanID');
    	$this->setPlanMenu(3, $iPlanID);

    	$this->assign('opt', 'edit');
    }

    /**
     * 体检有效期
     * @return [arrray]
     */
    public function planSetValidAction ()
    {
    	$this->addnextAction();

    	$iPlanID = $this->getParam('iPlanID');
    	$this->setPlanMenu(4, $iPlanID);

    	$this->assign('opt', 'edit');
    }

    /*
	 * SET MENU
	 */
    private function setPlanMenu($iMenu, $planID)
    {
        $aMenu = [
            // 0 => [
            //     'url' => '/company/physical/planindex/id/'.$planID,
            //     'name' => '完成进度',
            // ],
            1 => [
                'url' => '/company/physical/plandetail/id/'.$planID,
                'name' => '基本信息',
            ],
            2 => [
                'url' => '/company/physical/planitem/id/'.$planID,
                'name' => '体检产品',
            ],
            3 => [
                'url' => '/company/physical/planuser/iPlanID/'.$planID,
                'name' => '体检员工',
            ],
            4 => [
                'url' => '/company/physical/plansetvalid/stype/2/iPlanID/'.$planID,
                'name' => '体检有效期',
            ]
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }

    /**
     * 发送hr体检计划通知
     * @return [type] [description]
     */
    public function sendhrplanAction ()
    {
    	$iPlanID = intval($this->getParam('planId'));
    	$aPlan = Model_Physical_Plan::getDetail($iPlanID);
    	if (!$aPlan) {
    		return $this->showMsg('体检计划不存在', false);
    	}

    	$p = Model_Physical_Plan::sendPlan($iPlanID);
    	if ($p = 0) {
    		return $this->showMsg('发送存在延迟，请稍后确认', false);
    	}

    	return $this->showMsg('发送成功', true);
    }

    /**
     * HR下载体检通知页面
     * @return [type] [description]
     */
    public function dlplanAction ()
    {
    	$iPlanID = intval($this->getParam('iPlanID'));
		$content1 = Yaf_G::getConf('yearlynotice', 'physical');
		$content2 = Yaf_G::getConf('jobnotice', 'physical');
		
		$this->assign('content1', $content1);
		$this->assign('content2', $content2);
		$this->assign('iPlanID', $iPlanID);
    }

    /**
     * HR下载体检通知按钮
     * @return [type] [description]
     */
    public function downloadplanAction () {
    	$iPlanID = intval($this->getParam('iPlanID'));
    	if (!$iPlanID) {
    		return false;
    	} else {
    		$aPlan = Model_Physical_Plan::getDetail($iPlanID);
    		if ($this->enterpriseId != $aPlan['iHRID']) {
    			return false;
    		}
    		
    		$aParam = $this->getParams();
    		$where['iPlanID'] = $iPlanID;
	    	$where['iPhysicalType'] = 2 == $aParam['iType'] ? 2 : 1;

	    	$aCard = Model_OrderCard::getAll(['where' => $where]);
	    	if ($where['iPhysicalType'] == 1) {
	    		$content = Yaf_G::getConf('yearlynotice', 'physical');
	    	} else {
	    		$content = Yaf_G::getConf('jobnotice', 'physical');
	    	}

	    	$aCompany = Model_User::getDetail($this->enterpriseId);
	    	$together = '';
	    	foreach ($aCard as $key => $value) {
	    		$tmp = $content;
	    		$aUser = Model_CustomerNew::getDetail($value['iUserID']);
	    		$tmp = preg_replace('/\【员工姓名\】/', $aUser['sRealName'], $tmp);
				$tmp = preg_replace('/\【公司名称\】/', $aCompany['sRealName'], $tmp);
				$tmp = preg_replace('/\【体检卡号\】/', $value['sCardCode'], $tmp);
				$tmp = preg_replace('/\【体检开始日期\】/', $value['sStartDate'], $tmp);
				$tmp = preg_replace('/\【体检截止日期\】/', $value['sEndDate'], $tmp);
				$together .= $tmp;
	    	}

	    	$together = iconv('utf-8', 'gbk', $together);
	    	echo $together;
	    	
	    	// intval($this->aParam['iIsAll']) ? '' : $where['iBookStatus'] = 0;
			ob_start();
			header("Content-Type: application/octet-stream;charset=GBK"); 
			header("Accept-Ranges: bytes"); 
			header("Accept-Length: ".strlen($together));
			header('Content-Disposition: attachment; filename=test.doc');
			header("Pragma:no-cache"); 
			header("Expires:0"); 
			ob_end_clean();

			return false;
    	}
    }
}