<?php

/**
 * 安心体检
 * User: xuchuyuan
 * Date: 16/6/21 10:00
 */
class Controller_Index_Web extends Controller_Index_Base
{

	/**
     * 执行动作之前
     */
    public function actionBefore()
    {
        parent::actionBefore();
        $this->_frame = 'pcbasic.phtml';
        $this->_assignUrl();
    }

    protected function _assignUrl()
    {
        $this->assign('sDetailUrl', '/index/web/detail/');
        $this->assign('sGetRegionUrl', '/index/store/getregion/');
        $this->assign('sStoreUrl', '/index/store/store/');
        $this->assign('sGetStoreUrl', '/index/store/getstore/');
        $this->assign('sStoreDetailUrl', '/index/web/stdetail/id/');
        $this->assign('sCartlistUrl', '/index/web/cartlist/');
        $this->assign('sAddCartUrl', '/index/web/addcart/');
        $this->assign('sDeleteCartUrl', '/index/web/deletecart/');
        
        $this->assign('sBalanceUrl', '/index/balance/balance/');
        $this->assign('sBalanceValidateUrl', '/index/balance/balancevalidate/');
        $this->assign('sBalancePostUrl', '/index/balance/balancepost/');
        $this->assign('sPayUrl', '/index/balance/pay/');
        $this->assign('sPayPostUrl', '/index/balance/paypost/');
    }

	/**
	 * 安心体检列表
	 * @return
	 */
	public function listAction ()
	{
		//购物车信息
        $aChart = Model_Cart::getCart($this->iCurrUserID);
        $this->assign('aChart', $aChart);

        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aData = $this->getCanViewProduct($iPage);
        if ($aData['aList']) {
        	foreach ($aData['aList'] as $key => $value) {
                list($aData['aList'][$key]['iStoreNum'], $aData['aList'][$key]['iCardNum']) 
                = $this->getStoreAndPersonNumber($value['iProductID']);
        	}
        }

        $this->assign('sTitle', '产品列表');
        $this->assign('iCartIcon', 1);
        $this->assign('aData', $aData);
	}

	public function detailAction ()
	{
		$aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->showMsg('参数不全', false);
        }

        $aData = Model_UserProductBase::getUserProductBase($aParam['id'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID'],true);
        if (empty($aData)) {
            return $this->showMsg('产品不存在', false);
        }

        list($aData['iStoreNum'], $aData['iCardNum']) = $this->getStoreAndPersonNumber($aData['iProductID']);
        
        //获取该产品包含的单项
        $aItem = Model_ProductItem::getProductItems($aData['iProductID'], Model_ProductItem::EXPANDPRODUCT, null, true);
        if (!empty($aItem)) {
            $sItem = implode(',', $aItem);
            $aItemCat = Model_Item::setGroupByCategory($sItem, true);
        } else {
            $aItemCat = [];
        }

        //购物车信息
        $aChart = Model_Cart::getCart($this->iCurrUserID);
        $aCitys = Model_City::getPair([
            'where' => ['iStatus' => Model_City::STATUS_VALID],
            'order' => 'sPinyin ASC' 
        ], 'iCityID', 'sCityName');
        
        $iChannelType = 2;
        $aSupplier = Model_Store::getStoreSupplier($aData['iProductID'], $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannelID']);
        $sSupplier = implode(',', $aSupplier);

        $this->assign('aSupplier', $aSupplier);
        $this->assign('sSupplier', $sSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aItemCat', $aItemCat);
        $this->assign('aChart', $aChart);
        $this->assign('sTitle', '产品详情');
        $this->assign('aData', $aData);
        $this->assign('iCartIcon', 1);
        $this->assign('iCartBur', 1);
        $this->assign('iCartFoot', 1);
        $this->assign('hassnotoremenu', 1);
        $this->assign('iProductID', $aData['iProductID']);
        $this->assign('sProductCode', $aParam['id']);
    }

    /**
     * 门店产品列表
     * @return [array]
     */
    public function stdetailAction ()
    {
    	$id = intval($this->getParam('id'));
    	$aStore = Model_Store::getDetail($id);
    	if (!$id || !$aStore) {
    		return $this->redirect('/index/web/list');
    	}

    	$page = $this->getParam('page');

    	$where = [
    		'iStoreID' => $id,
    		'iType' => Model_ProductStore::EXPANDPRODUCT,
    		'iSex' => 1
    	];
    	$aData = Model_ProductStore::getList($where, $page, 'iUpdateTime Desc');

    	$aRegions = Model_Region::getAll(['where' => [
            'iStatus' => Model_Region::STATUS_VALID
        ]]);
        foreach ($aRegions as $k => $v) {
            $aRegion[$v['iRegionID']] = $v['sRegionName'];
        }
        $this->assign('aRegion', $aRegion);
    	$this->assign('aStore', $aStore);
    	$this->assign('aData', $aData);
    }

    //购物车列表
    public function cartlistAction()
    {
        $aCart = Model_Cart::getCart($this->iCurrUserID);
        if (!empty($aCart)) {
            foreach ($aCart as $key => $value) {
                $aDetail = Model_UserProductBase::getUserProductBase($key, $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID']);
                if (empty($aDetail) || $aDetail['iStatus'] != 1) {//如果不存在该产品或已下架，删除该条购物车
                    unset($aCart[$key]);
                    Model_Cart::deleteCart($key, $this->iCurrUserID);
                } else {
                    $aCart[$key]['detail'] = $aDetail;
                }
            }
        }
        
        $this->assign('aSex',Yaf_G::getConf('aSex', 'product'));
        $this->assign('aCart', $aCart);
        $this->assign('sTitle', '购物车');
    }

    //加入购物车
    public function addCartAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->showMsg('参数不全', false);
        }
        $iProductID = $aParam['id'];

        $aData = Model_UserProductBase::getUserProductBase($iProductID, $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID']);
        if (empty($aData)) {
            return $this->showMsg('产品不存在', false);
        }
        if ($aCart = Model_Cart::addCart($iProductID, $this->iCurrUserID)) {
            return $this->showMsg($aCart, true);
        } else {
            return $this->showMsg('加入失败', false);
        }
    }

    //删除购物车
    public function deleteCartAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['id'])) {
            return $this->showMsg('参数不全', false);
        }
        $iProductID = $aParam['id'];
        if (Model_Cart::deleteCart($iProductID, $this->iCurrUserID)) {
            return $this->showMsg('删除成功', true);
        } else {
            return $this->showMsg('删除失败', false);
        }
    }

    /**
     * 计划产品详情页
     * @return [type] [description]
     */
    public function planproductdetailAction ()
    {
        $aParam = $this->getParams();
        if (empty($aParam['pid'])) {
            return $this->showMsg('参数不全', false);
        }

        $aCard = Model_OrderCard::getDetail($aParam['id']);
        //$aCard['iCompanyID']
        $aData = Model_UserProductBase::getUserProductBase($aParam['pid'], $this->aUser['iCreateUserID'], 2, $this->aUser['iChannelID']);
        if (empty($aData)) {
            return $this->showMsg('产品不存在', false);
        }

        list($aData['iStoreNum'], $aData['iCardNum']) = $this->getStoreAndPersonNumber($aParam['id']);
        
        //获取该产品包含的单项
        $aItem = Model_ProductItem::getProductItems($aData['iProductID'], Model_ProductItem::EXPANDPRODUCT, null, true);
        if (!empty($aItem)) {
            $sItem = implode(',', $aItem);
            $aItemCat = Model_Item::setGroupByCategory($sItem, true);
        } else {
            $aItemCat = [];
        }

        //购物车信息
        $aChart = Model_Cart::getCart($this->iCurrUserID);
        $aCitys = Model_City::getPair(['where' => ['iStatus' => Model_City::STATUS_VALID]], 'iCityID', 'sCityName');
        
        $iChannelType = 2;
        $aSupplier = Model_Store::getStoreSupplier($aData['iProductID'], $this->aUser['iCreateUserID'], $iChannelType, $this->aUser['iChannelID']);
        $sSupplier = implode(',', $aSupplier);

        $this->assign('aSupplier', $aSupplier);
        $this->assign('sSupplier', $sSupplier);
        $this->assign('aCitys', $aCitys);
        $this->assign('aItemCat', $aItemCat);
        $this->assign('aChart', $aChart);
        $this->assign('sTitle', '产品详情');
        $this->assign('aData', $aData);
        $this->assign('iCartIcon', 1);
        $this->assign('iCartBur', 1);
        $this->assign('iCartFoot', 1);
        $this->assign('hassnotoremenu', 1);
        $this->assign('iProductID', $aData['iProductID']);
        $this->assign('sProductCode', $aParam['id']);

        $sAptUrl = '/order/buythird/id/' . $aParam['id'] . '/pid/' . $aParam['pid'];
        $this->assign('sAptUrl', $sAptUrl);
    }

    //获取上一月，下一月的可预约日期（ajax）
    public function getReserveDateAction()
    {
        $aParam = $this->getParams();
        if (empty($aParam['sSupplierCode']) || empty($aParam['sStoreCode']) || empty($aParam['sdate'])) {
            return $this->showMsg('参数不全', false);
        }
        $aReserDateList = Model_Supplier::getReserveTimeByCode($aParam['sSupplierCode'], $aParam['sdate'],$aParam['sStoreCode'], $aParam['iPhysicalType']);
        return $this->showMsg($aReserDateList, true);
    }

    /**
     * 确认登陆 修改状态
     * @return
     */
    public function confirmLoginAction ()
    {
        $iUserID = intval($this->getParam('iCustomerID'));
        $aUser = Model_CustomerNew::getDetail($iUserID);
        if (!$iUserID || !$aUser) {
            return $this->showMsg('无此用户', false);
        }

        $aUser['iLoginStatus'] = 1;
        Model_CustomerNew::updData($aUser);

        return  $this->showMsg('确认成功', true);
    }
}