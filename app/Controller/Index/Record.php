<?php
/**
 * 个人体检记录
 * Date: 16/6/13
 * Time: 17:00
 */

class Controller_Index_Record extends Controller_Index_Base
{
	public $aStatus = [
		'0' => '未预约',
		'1' => '已预约',
		'2' => '已体检',
		'3' => '已退订',
		'4' => '作废',
		'5' => '已出报告',
    '6' => '预约失败'
	];

  public function _assignUrl ()
  {
      $this->assign('sAppointmentDetailUrl', '/wx/appointment/detail/');
      $this->assign('sAjaxCheckCardUrl', '/wx/ajax/ajaxcheckcard/');
      $this->assign('sVerifyCheckUrl', '/wx/ajax/ajaxcheckcardverify/');
      $this->assign('sCardlistUrl', '/wx/appointment/cardlist/');
      $this->assign('sAddCardUrl', '/wx/appointment/addcard/');
      $this->assign('sReserveStoreUrl', '/wx/appointment/reservestore/');
      $this->assign('sReserveCommitUrl', '/wx/appointment/reservecommit/');
      $this->assign('sGetStoreListUrl', '/wx/getstoreList/');
      $this->assign('sUserInfoEditUrl', '/wx/appointment/userInfoEdit/');
      $this->assign('sMapUrl', '/wx/appointment/map/');
      $this->assign('sReserveDetailUrl', '/wx/appointment/reservedetail/');
      $this->assign('sReserveCancelUrl', '/wx/appointment/reservecancel/');
      $this->assign('sStoreListUrl', '/wx/appointment/storelist/');
      $this->assign('sUpgradeDetailUrl', '/wx/appointment/upgradedetail/');
  }

	public function actionBefore ()
	{
		parent::actionBefore();
		$this->_frame = 'pcmenu.phtml';

    if (!$this->aUser) {
        return $this->redirect('/index/account/cdlogin');
    }

    $aCustomer = Model_CustomerNew::getDetail($this->aUser['iUserID']);
    if ($aCustomer['iLoginStatus'] == 0) {
        $this->assign('hasLoged', 0);
    } else {
        $this->assign('hasLoged', 1);
    }

    $this->assign('aStatus', $this->aStatus);
    $this->_assignUrl();    
	}

	/**
	 * 个人体检记录列表
	 * @return
	 */
	public function listAction()
	{	
      $params = $this->getParams();
      $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
      list($aTree, $aCard['iTotal'], $aCard['aPager']) = Model_OrderCard::getTree($this->iCurrUserID, $iPage, $params);
      $aCard['aList'] = $aTree;
      $this->assign('aTree', $aTree);
      $this->assign('aColor', array(
          '',
          'success',
          'warning',
          'danger',
          'info',
          'active'
      ));

      if (!empty($aCard['aList'])) {
          //组装需要的数据
          foreach ($aCard['aList'] as $key => &$value) {
              $aAttribute = Yaf_G::getConf('aAttribute', 'product');
              $value['sAttribute'] = !empty($aAttribute[$value['iPhysicalType']]) ? $aAttribute[$value['iPhysicalType']] : '';
          }
      }

      $this->assign('aCard', $aCard);
      $t = $this->getParam('t');
      if ($t) {
          return $this->show404($aCard, true);
      }
      $this->assign('aPayType', Yaf_G::getConf('paytype', 'physical'));
      $this->assign('aOrderType', Yaf_G::getConf('aOrderType', 'order'));
      $this->assign('aSex', Yaf_G::getConf('aSex', 'product'));
      $this->assign('aCardUseType', Yaf_G::getConf('aUseType', 'card'));
      $this->assign('sTitle', '体验卡列表');
      $this->assign('iCartFoot', 1);
      $this->assign('iCodeType', Util_Verify::TYPE_CARD_ADD_IMAGE_WX);
    	$this->assign('aParam', $params);
	}

    /**
     * 体检卡查看详情
     * @return 
     */
    public function detailAction () {
        $iCardId = $this->getParam('id');
        if (!$iCardId) {
            return $this->redirect('/index/record/list/');
        }
        $aCard = Model_OrderCard::getDetail($iCardId);
        if (!$aCard || $aCard['iUserID'] != $this->aUser['iUserID']) {
            return $this->redirect('/index/record/list/');
        }

        $aStore = Model_Store::getDetail($aCard['iStoreID']);
        $aProduct = Model_UserProductBase::getUserProductBase($aCard['iProductID'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID']);
        $aEmployee = Model_Company_Employee::getDetail($aCard['iUserID']);

        $this->assign('aCard', $aCard);
        $this->assign('aStore', $aStore);
        $this->assign('aProduct', $aProduct);
        $this->assign('aEmployee', $aEmployee);
    }
}