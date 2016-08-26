<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/21
 * Time: 9:50
 */
class Controller_Admin_Product extends Controller_Admin_ItemBase
{
    const PAGESIZE = 20;

    /**
     * 编辑或查看的头部信息
     * @param $iEditHeaderIndex 当前的索引值
     * @param $iHeaderType 1：查看 2：编辑
     */
    protected function _editHeader($iEditHeaderIndex, $iHeaderType)
    {
        $aEditHeaderInfo = [
            1 => [
                'editurl' => '/admin/product/editbaseinfo/',
                'viewurl' => '/admin/product/viewbaseinfo/',
                'name' => '基本信息'
            ],
            2 => [
                'editurl' => '/admin/product/edititem/',
                'viewurl' => '/admin/product/viewitem/',
                'name' => '体检单项'
            ],
            3 => [
                'editurl' => '/admin/product/editstore/',
                'viewurl' => '/admin/product/viewstore/',
                'name' => '供应商门店'
            ],
            4 => [
                'editurl' => '/admin/product/editupgrade/',
                'viewurl' => '/admin/product/viewupgrade/',
                'name' => '升级产品'
            ],
            5 => [
                'editurl' => '/admin/product/editaddtion/',
                'viewurl' => '/admin/product/viewaddtion/',
                'name' => '加项包'
            ],
            6 => [
                'editurl' => '/admin/product/editexpand/',
                'viewurl' => '/admin/product/sonlist/',
                'name' => '拓展产品'
            ]
        ];
        $this->assign('aEditHeaderInfo', $aEditHeaderInfo);
        $this->assign('iEditHeaderIndex', $iEditHeaderIndex);
        $this->assign('iHeaderType', $iHeaderType);
    }

    protected function _assignUrl()
    {
        $this->assign('sEpListUrl', '/admin/eproduct/list/');
        $this->assign('sListUrl', '/admin/product/list/');
        $this->assign('sAddBaseInfoUrl', '/admin/product/addbaseinfo/');
        $this->assign('sEditBaseInfoUrl', '/admin/product/editbaseinfo/');
        $this->assign('sViewBaseInfoUrl', '/admin/product/viewbaseinfo/');
        $this->assign('sEpEditBaseInfoUrl', '/admin/eproduct/editbaseinfo/');
        $this->assign('sEpViewBaseInfoUrl', '/admin/eproduct/viewbaseinfo/');
        $this->assign('sAddItemUrl', '/admin/product/additem/');
        $this->assign('sEditItemUrl', '/admin/product/edititem/');
        $this->assign('sViewItemUrl', '/admin/product/viewitem/');
        $this->assign('sInsertItemUrl', '/admin/product/insertitem/');
        $this->assign('sDeleteItemUrl', '/admin/product/deleteitem/');
        $this->assign('sAddStoreUrl', '/admin/product/addstore/');
        $this->assign('sEditStoreUrl', '/admin/product/editstore/');
        $this->assign('sViewStoreUrl', '/admin/product/viewstore/');
        $this->assign('sInsertStoreUrl', '/admin/product/insertstore/');
        $this->assign('sDeleteStoreUrl', '/admin/product/deletestore/');
        $this->assign('sAddUpgradeUrl', '/admin/product/addupgrade/');
        $this->assign('sEditUpgradeUrl', '/admin/product/editupgrade/');
        $this->assign('sViewUpgradeUrl', '/admin/product/viewupgrade/');
        $this->assign('sInsertUpgradeUrl', '/admin/product/insertupgrade/');
        $this->assign('sDeleteUpgradeUrl', '/admin/product/deleteupgrade/');
        $this->assign('sAddAddtionUrl', '/admin/product/addaddtion/');
        $this->assign('sEditAddtionUrl', '/admin/product/editaddtion/');
        $this->assign('sViewAddtionUrl', '/admin/product/viewaddtion/');
        $this->assign('sInsertAddtionUrl', '/admin/product/insertaddtion/');
        $this->assign('sDeleteAddtionUrl', '/admin/product/deleteaddtion/');
//todo
        $this->assign('sAddExpandUrl', '/admin/product/addexpand/');
        $this->assign('sInsertExpandUrl', '/admin/product/insertexpand/');
        $this->assign('sDeleteExpandUrl', '/admin/product/deleteexpand/');
    }

    public function actionAfter()
    {
        parent::actionAfter();
        $this->_assignUrl();
        $this->assign('productcss', 1);
    }

    //产品列表
    public function listAction()
    {
        $iPageSize = self::PAGESIZE;
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();

        if (!empty($aParam['aCity'])) {
            $aParam['aCity'] = array_keys($aParam['aCity']);
            $aParam['sCity'] = implode(',', $aParam['aCity']);
        }
        if (!empty($aParam['aSupplier'])) {
            $aParam['aSupplier'] = array_keys($aParam['aSupplier']);
            $aParam['sSupplier'] = implode(',', $aParam['aSupplier']);
        }
        $iSelectStore = (empty($aParam['aCity']) && empty($aParam['sSupplier'])) ? 0 : 1;//是否要关联门店，不带条件就不关联门店
        $sProductIDs = '';//满足门店条件的产品
        if ($iSelectStore) {
            $aTmp = Model_Store::getData($aParam);//满足条件的门店
            $aStoreIDs = [];
            if (!empty($aTmp)) {
                foreach ($aTmp as $key => $value) {
                    $aStoreIDs[] = $value['iStoreID'];
                }
                $aProduct = Model_ProductStore::getDataByStores($aStoreIDs, Model_ProductStore::BASEPRODUCT);
                if (!empty($aProduct)) {
                    foreach ($aProduct as $key => $value) {
                        $aProductIDs[] = $value['iProductID'];
                    }
                    $sProductIDs = implode(',', $aProductIDs);
                }
            } else {
                $sProductIDs = '-1';//如果关联门店，且没有满足条件的产品，这个设为一个永远不可能的条件
            }
        }
        if (!empty($sProductIDs)) {
            $aParam['sProductID'] = $sProductIDs;
        }
        $aParam['iType'] = Model_Product::TYPE_BASE;
        $aData = Model_Product::getPageList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        if (!empty($aData['aList'])) {
            //整合需要的数据
            foreach ($aData['aList'] as $key => $value) {
                $aHasStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::BASEPRODUCT);
                $aData['aList'][$key]['iStoreNum'] = count($aHasStore);
                $aData['aList'][$key]['iCityNum'] = 0;
                $aData['aList'][$key]['iSupplierNum'] = 0;
                $aData['aList'][$key]['iExpandNum'] = 0;
                //统计城市和供应商数目
                if (!empty($aHasStore)) {
                    foreach ($aHasStore as $k => $val) {
                        $aProductData = Model_Store::getDetail($val['iStoreID']);
                        $aHasStore[$key]['aStore'] = $aProductData;
                        //按城市分组
                        $aCityTmp[$aProductData['iCityID']][] = 1;
                        //按供应商分组
                        $aSupplierTmp[$aProductData['iSupplierID']][] = 1;
                    }
                    $aData['aList'][$key]['iCityNum'] = count($aCityTmp);
                    $aData['aList'][$key]['iSupplierNum'] = count($aSupplierTmp);
                }
                //统计拓展产品数目
                $aSonProduct = Model_Product::getExplandData($value['iProductID']);
                $aData['aList'][$key]['iExpandNum'] = count($aSonProduct);
            }
        }
        $aWhere['iStatus >'] = 0;
        $aSupplier = Model_Type::getOption('supplier');
        $aCity = Model_City::getPair($aWhere, 'iCityID', 'sCityName');
        $this->assign('iType', Model_Product::TYPE_BASE);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aCity', $aCity);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
    }

    //填写基本信息
    public function addBaseInfoAction()
    {
        if ($this->_request->isPost()) {
            $aProduct = $this->_checkClientData(1);
            if (empty($aProduct)) {
                return null;
            }
            $aProduct['sProductCode'] = Model_Product::initProductCode();
            $aProduct['iType'] = Model_Product::TYPE_BASE;
            $aProduct['iCreateUserID'] = $aProduct['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            $aProduct['iStatus'] = 1;
            if ($iLastInsertID = Model_Product::addData($aProduct)) {
                $sNextUrl = '/admin/product/additem/id/' . $iLastInsertID;
                return $this->showMsg('添加成功!', true, $sNextUrl);
            } else {
                return $this->showMsg('增加失败！', false);
            }
        } else {
            $aStatus = Yaf_G::getConf('aStatus', 'product');
            $aProductType = Yaf_G::getConf('aType', 'product');
            $aSpecialCheck = Yaf_G::getConf('aSpecial', 'product');
            $this->assign('aStatus', $aStatus);
            $this->assign('aProductType', $aProductType);
            $this->assign('aSpecialCheck', $aSpecialCheck);
        }
    }

    //编辑基本信息
    public function editBaseInfoAction()
    {
        if ($this->_request->isPost()) {
            $aProduct = $this->_checkClientData(2);
            if (empty($aProduct)) {
                return null;
            }
            $aProduct['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_Product::updData($aProduct) > 0) {
                return $this->showMsg('修改成功！', true);
            } else {
                return $this->showMsg('修改失败！', false);
            }
        } else {
            $iProductID = intval($this->getParam('id'));
            if (empty($iProductID)) {
                return $this->showMsg('参数有误！', false);
            }
            $aProduct = Model_Product::getDetail($iProductID);
            if (empty($aProduct)) {
                return $this->showMsg('产品不存在！', false);
            }
            $aStatus = Yaf_G::getConf('aStatus', 'product');
            $aProductType = Yaf_G::getConf('aType', 'product');
            $aSpecialCheck = Yaf_G::getConf('aSpecial', 'product');
            $this->assign('aStatus', $aStatus);
            $this->assign('aProductType', $aProductType);
            $this->assign('aSpecialCheck', $aSpecialCheck);
            //munu相关的赋值
            $this->_editHeader(1, 2);
            $this->assign('id', $iProductID);
            $this->assign('aProduct', $aProduct);
        }
    }

    //查看基本信息
    public function viewBaseInfoAction()
    {
        $iProductID = intval($this->getParam('id'));
        if (empty($iProductID)) {
            return $this->showMsg('参数有误！', false);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('产品不存在！', false);
        }
        $aStatus = Yaf_G::getConf('aStatus', 'product');
        $aProductType = Yaf_G::getConf('aType', 'product');
        $aSpecialCheck = Yaf_G::getConf('aSpecial', 'product');
        $this->assign('aProduct', $aProduct);
        $this->assign('aStatus', $aStatus);
        $this->assign('aProductType', $aProductType);
        $this->assign('aSpecialCheck', $aSpecialCheck);
        //munu相关的赋值
        $this->_editHeader(1, 1);
        $this->assign('id', $iProductID);
        $this->assign('aProduct', $aProduct);
    }

    //单项共用
    private function _item()
    {
        $iPageSize = self::PAGESIZE;
        $iProductID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        $aParam['aCategory'] = $this->getParam('aCategory') ? array_keys($this->getParam('aCategory')) : [];
        $aParam['sKeyword'] = $this->getParam('sKeyword') ? addslashes(trim($this->getParam('sKeyword'))) : '';
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', $iProductID);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }
        $aHasItem = Model_ProductItem::getProductItems($iProductID, Model_ProductItem::BASEPRODUCT,1);//该产品已包含的单项
        if (!empty($aHasItem)) {
            //整合需要的数据
            $aHasItemID = [];
            foreach ($aHasItem as $key => $value) {
                $aHasItemID[] = $value['iItemID'];
                $aProductData = Model_Item::getDetail($value['iItemID']);
                $aHasItem[$key]['aItem'] = $aProductData;
            }
            $aParam['iItemID NOT IN'] = $aHasItemID;
        }
        if (!empty($aParam['aCategory'])) {
            $aParam['iCat IN'] = $aParam['aCategory'];
        }

        if (!empty($aParam['sKeyword'])) {
            $aParam['sWhere'] = '(sCode="' . $aParam['sKeyword'] . '" OR sName LIKE "%' . $aParam['sKeyword'] . '%")';
        }
        $aParam['iStatus'] = 1;
        $aItem = Model_Item::getList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        //添加之后刷新，分页bug特殊处理
        if ((ceil($aItem['iTotal'] / $iPageSize)) != 0 && $iPage > (ceil($aItem['iTotal'] / $iPageSize))) {
            $aItem = Model_Item::getList($aParam, 1, 'iUpdateTime DESC', $iPageSize);
        }
        //获取所有小项
        $aCatParam['where'] = [
            'iParentID >' => 0,
            'iStatus' => 1,
        ];
        $aCategory = Model_Product_Category::getPair($aCatParam, 'iAutoID', 'sCateName');
        $sNextUrl = '/admin/product/addstore/id/' . $iProductID;
        $this->assign('aCategory', $aCategory);
        $this->assign('iProductID', $iProductID);
        $this->assign('aParam', $aParam);
        $this->assign('aItem', $aItem);
        $this->assign('aHasItem', $aHasItem);
        $this->assign('sNextUrl', $sNextUrl);
        $this->assign('aProduct', $aProduct);
        return true;
    }

    //选择单项
    public function addItemAction()
    {
        $result = $this->_item();
        if (empty($result)) {
            return null;
        }
    }

    //编辑单项
    public function editItemAction()
    {
        $result = $this->_item();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(2, 2);
        $this->assign('id', intval($this->getParam('id')));
    }

    //查看单项
    public function viewItemAction()
    {
        $result = $this->_item();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(2, 1);
        $this->assign('id', intval($this->getParam('id')));
    }

    //添加单项
    public function insertItemAction()
    {
        $iProductID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', false);
        }
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sItemID)) {
            return $this->showMsg('请先选择单项', false);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }

        $aParam['iProductID'] = $iProductID;
        $aParam['iCreateUserID'] = $aParam['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
        $aParam['iType'] = Model_ProductItem::BASEPRODUCT;//基础产品
        $aParam['iStatus'] = 1;
        if ($iCommitType) {//批量
            $aItemID = explode(',', $sItemID);
            $sSuccessNum = 0;
            $sTotalNum = count($aItemID);
            foreach ($aItemID as $key => $value) {
                //判断是否存在该单项
                if (Model_ProductItem::getDataByItemID($iProductID, $value, Model_ProductItem::BASEPRODUCT)) {
                    continue;
                }
                $aParam['iItemID'] = $value;
                if (Model_ProductItem::addData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功添加' . $sSuccessNum . '个单项，失败' . ($sTotalNum - $sSuccessNum) . '个单项';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iItemID'] = intval($sItemID);
            //判断是否存在该单项
            if (Model_ProductItem::getDataByItemID($iProductID, $aParam['iItemID'], Model_ProductItem::BASEPRODUCT)) {
                return $this->showMsg('已添加过该单项！', false);
            }
            if (Model_ProductItem::addData($aParam)) {
                return $this->showMsg('添加成功！', true);
            } else {
                return $this->showMsg('添加失败！', false);
            }
        }
    }

    //删除已添加单项
    public function deleteItemAction()
    {
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sItemID)) {
            return $this->showMsg('请先选择单项', false);
        }
        $aParam['iStatus'] = 0;
        if ($iCommitType) {//批量
            $aItemID = explode(',', $sItemID);
            $sSuccessNum = 0;
            $sTotalNum = count($aItemID);
            foreach ($aItemID as $key => $value) {
                //判断是否存在该单项
                if (!Model_ProductItem::getDetail($value)) {
                    continue;
                }
                $aParam['iAutoID'] = $value;
                if (Model_ProductItem::updData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功删除' . $sSuccessNum . '个单项，失败' . ($sTotalNum - $sSuccessNum) . '个单项';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iAutoID'] = intval($sItemID);
            //判断是否存在该单项
            if (Model_ProductItem::updData($aParam)) {
                return $this->showMsg('删除成功！', true);
            } else {
                return $this->showMsg('删除失败！', false);
            }
        }
    }

    //门店共用
    private function _store()
    {
        $iPageSize = self::PAGESIZE;
        $iProductID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        $aParam['aCity'] = $this->getParam('aCity') ? array_keys($this->getParam('aCity')) : [];
        $aParam['aSupplier'] = $this->getParam('aSupplier') ? array_keys($this->getParam('aSupplier')) : [];
        $aParam['aType'] = $this->getParam('aType') ? array_keys($this->getParam('aType')) : [];
        $aParam['sKeyword'] = $this->getParam('sKeyword') ? addslashes(trim($this->getParam('sKeyword'))) : '';
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', $iProductID);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }
        $aWhere['iStatus >'] = 0;
        $aSupplier = Model_Type::getOption('supplier');
        $aCity = Model_City::getPair($aWhere, 'iCityID', 'sCityName');
        $aType = Yaf_G::getConf('aStoreType', 'product');
        $sTips = '';

        $iHasData = $this->getParam('hasdata') ? intval($this->getParam('hasdata')) : 0;
        if ($iHasData) {
            $aHasParam['iStatus'] = 1;
            $aHasParam['aHasCity'] = $this->getParam('aHasCity') ? array_keys($this->getParam('aHasCity')) : [];
            $aHasParam['aHasSupplier'] = $this->getParam('aHasSupplier') ? array_keys($this->getParam('aHasSupplier')) : [];
            $aHasParam['aHasType'] = $this->getParam('aHasType') ? array_keys($this->getParam('aHasType')) : [];
            $aHasParam['sHasKeyword'] = $this->getParam('sHasKeyword') ? addslashes(trim($this->getParam('sHasKeyword'))) : '';
            if (!empty($aHasParam['aHasCity'])) {
                $aHasParam['iCityID IN'] = $aHasParam['aHasCity'];
            }
            if (!empty($aHasParam['aHasSupplier'])) {
                $aHasParam['iSupplierID IN'] = $aHasParam['aHasSupplier'];
            }
            if (!empty($aHasParam['aHasType'])) {
                $aHasParam['iType IN'] = $aHasParam['aHasType'];
            }
            if (!empty($aHasParam['sHasKeyword'])) {
                $aHasParam['sWhere'] = '(sCode="' . $aHasParam['sHasKeyword'] . '" OR sName LIKE "%' . $aHasParam['sHasKeyword'] . '%")';
            }
            $aHasStore = Model_Store::getPair($aHasParam,'iStoreID','sName');
            if (!empty($aHasStore)) {
                $aHasStoreParam['iStoreID IN'] = array_keys($aHasStore);
            } else {
                $aHasStoreParam['iStoreID IN'] = 999999;//搞个永远搜索不到的数字
            }
            $this->assign('aHasParam', $aHasParam);
        }
        $aHasStoreParam['iStatus >'] = 0;
        $aHasStoreParam['iProductID'] = $iProductID;
        $aHasStoreParam['iType'] = Model_ProductStore::BASEPRODUCT;
        $aHasStore = Model_ProductStore::getAll($aHasStoreParam);//该产品已包含的门店

        if (!empty($aHasStore)) {
            $aParam['sStoreID'] = '';
            $aCityTmp = [];
            $aSupplierTmp = [];
            //整合需要的数据
            foreach ($aHasStore as $key => $value) {
                $aStoreIDs[] = $value['iStoreID'];
                $aProductData = Model_Store::getDetail($value['iStoreID']);
                $aHasStore[$key]['aStore'] = $aProductData;
                //按城市分组
                $aCityTmp[$aProductData['iCityID']][] = 1;
                //按供应商分组
                $aSupplierTmp[$aProductData['iSupplierID']][] = 1;
            }
            $aParam['iStoreID NOT IN'] = $aStoreIDs;
            //生成tips
            if (!empty($aCityTmp)) {
                $sTips = '共' . count($aCityTmp) . '个城市:';
                foreach ($aCityTmp as $key => $value) {
                    $sTips .= $aCity[$key] . count($value) . '个,';
                }
                $sTips = trim($sTips, ',') . "<br>";
            }
            if (!empty($aSupplierTmp)) {
                $sTips .= '共' . count($aSupplierTmp) . '个供应商:';
                foreach ($aSupplierTmp as $key => $value) {
                    $sTips .= $aSupplier[$key] . count($value) . '个,';
                }
                $sTips = trim($sTips, ',');
            }
        }
        $aParam['iStatus'] = 1;
        $aParam['aCity'] = $this->getParam('aCity') ? array_keys($this->getParam('aCity')) : [];
        $aParam['aSupplier'] = $this->getParam('aSupplier') ? array_keys($this->getParam('aSupplier')) : [];
        $aParam['aType'] = $this->getParam('aType') ? array_keys($this->getParam('aType')) : [];
        $aParam['sKeyword'] = $this->getParam('sKeyword') ? addslashes(trim($this->getParam('sKeyword'))) : '';
        if (!empty($aParam['aCity'])) {
            $aParam['iCityID IN'] = $aParam['aCity'];
        }
        if (!empty($aParam['aSupplier'])) {
            $aParam['iSupplierID IN'] = $aParam['aSupplier'];
        }
        if (!empty($aParam['aType'])) {
            $aParam['iType IN'] = $aParam['aType'];
        }
        if (!empty($aParam['sKeyword'])) {
            $aParam['sWhere'] = '(sCode="' . $aParam['sKeyword'] . '" OR sName LIKE "%' . $aParam['sKeyword'] . '%")';
        }
        $aStore = Model_Store::getList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        //添加之后刷新，分页bug特殊处理
        if ((ceil($aStore['iTotal'] / $iPageSize)) != 0 && $iPage > (ceil($aStore['iTotal'] / $iPageSize))) {
            $aStore = Model_Store::getList($aParam, 1, 'iUpdateTime DESC', $iPageSize);
        }
        $sNextUrl = '/admin/product/addupgrade/id/' . $iProductID;
        $this->assign('aType', $aType);
        $this->assign('aCity', $aCity);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('iProductID', $iProductID);
        $this->assign('aParam', $aParam);
        $this->assign('aStore', $aStore);
        $this->assign('aHasStore', $aHasStore);
        $this->assign('sTips', $sTips);
        $this->assign('sNextUrl', $sNextUrl);
        $this->assign('aProduct', $aProduct);
        return true;
    }

    //选择门店
    public function addStoreAction()
    {
        $result = $this->_store();
        if (empty($result)) {
            return null;
        }
    }

    //编辑门店
    public function editStoreAction()
    {
        $result = $this->_store();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(3, 2);
        $this->assign('id', intval($this->getParam('id')));
    }

    //查看门店
    public function viewStoreAction()
    {
        $result = $this->_store();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(3, 1);
        $this->assign('id', intval($this->getParam('id')));
    }

    //添加门店
    public function insertStoreAction()
    {
        $iProductID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', false);
        }
        $sStoreID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sStoreID)) {
            return $this->showMsg('请先选择门店', false);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }

        $aParam['iProductID'] = $iProductID;
        $aParam['iCreateUserID'] = $aParam['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
        $aParam['iType'] = Model_ProductStore::BASEPRODUCT;//基础产品
        $aParam['iStatus'] = 1;
        if ($iCommitType) {//批量
            $aStoreID = explode(',', $sStoreID);
            $sSuccessNum = 0;
            $sTotalNum = count($aStoreID);
            foreach ($aStoreID as $key => $value) {
                //判断是否存在该门店
                if (Model_ProductStore::getDataByStoreID($iProductID, $value, Model_ProductStore::BASEPRODUCT)) {
                    continue;
                }
                $aParam['iStoreID'] = $value;
                if (Model_ProductStore::addData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功添加' . $sSuccessNum . '个门店，失败' . ($sTotalNum - $sSuccessNum) . '个门店';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iStoreID'] = intval($sStoreID);
            //判断是否存在该门店
            if (Model_ProductStore::getDataByStoreID($iProductID, $aParam['iStoreID'], Model_ProductStore::BASEPRODUCT)) {
                return $this->showMsg('已添加过该门店！', false);
            }
            if (Model_ProductStore::addData($aParam)) {
                return $this->showMsg('添加成功！', true);
            } else {
                return $this->showMsg('添加失败！', false);
            }
        }
    }

    //删除已添加门店
    public function deleteStoreAction()
    {
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        $sStoreID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sStoreID)) {
            return $this->showMsg('请先选择门店', false);
        }
        $aParam['iStatus'] = 0;
        if ($iCommitType) {//批量
            $aStoreID = explode(',', $sStoreID);
            $sSuccessNum = 0;
            $sTotalNum = count($aStoreID);
            foreach ($aStoreID as $key => $value) {
                //判断是否存在该门店
                if (!Model_ProductStore::getDetail($value)) {
                    continue;
                }
                $aParam['iAutoID'] = $value;
                if (Model_ProductStore::updData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功删除' . $sSuccessNum . '个门店，失败' . ($sTotalNum - $sSuccessNum) . '个门店';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iAutoID'] = intval($sStoreID);
            //判断是否存在该门店
            if (Model_ProductStore::updData($aParam)) {
                return $this->showMsg('删除成功！', true);
            } else {
                return $this->showMsg('删除失败！', false);
            }
        }
    }

    //升级产品共用
    private function _upgrade()
    {
        $iPageSize = self::PAGESIZE;
        $iProductID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        $aParam['sKeyword'] = $this->getParam('sKeyword') ? addslashes(trim($this->getParam('sKeyword'))) : '';
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', $iProductID);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }
        $aHasUpgrade = Model_ProductUpgrade::getProductUpgrades($iProductID, Model_ProductUpgrade::BASEPRODUCT);//该产品已包含的升级产品
        $aParam['iType'] = Model_Product::TYPE_BASE;
        $aParam['iStatus'] = 1;
        $aParam['iManPrice >'] = $aProduct['iManPrice'];
        $aParam['iWomanPrice1 >'] = $aProduct['iWomanPrice1'];
        $aParam['iWomanPrice2 >'] = $aProduct['iWomanPrice2'];
        if (!empty($aParam['sKeyword'])) {
            $aParam['sWhere'] = '(sProductCode="' . $aParam['sKeyword'] . '" OR sProductName LIKE "%' . $aParam['sKeyword'] . '%")';
        }
        if (!empty($aHasUpgrade)) {
            $aHasUpgradeIDs = [];
            foreach ($aHasUpgrade as $key => $value) {
                $aHasUpgradeIDs[] = $value['iUpgradeID'];
                $aHasProductDetail = Model_Product::getDetail($value['iUpgradeID']);
                $aHasUpgrade[$key]['sProductName'] = $aHasProductDetail['sProductName'];
                $aHasUpgrade[$key]['sProductCode'] = $aHasProductDetail['sProductCode'];
            }
            $aParam['iProductID NOT IN'] = $aHasUpgradeIDs;
        }
        $aUpgrade = Model_Product::getList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        //添加之后刷新，分页bug特殊处理
        if ((ceil($aUpgrade['iTotal'] / $iPageSize)) != 0 && $iPage > (ceil($aUpgrade['iTotal'] / $iPageSize))) {
            $aUpgrade = Model_Product::getList($aParam, 1, 'iUpdateTime DESC', $iPageSize);
        }
        //整合需要的数据
        if (!empty($aUpgrade['aList'])) {
            foreach ($aUpgrade['aList'] as $key => $value) {
                $aStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::BASEPRODUCT);
                $aUpgrade['aList'][$key]['iStoreNum'] = count($aStore);
            }
        }
        if (!empty($aHasUpgrade)) {
            foreach ($aHasUpgrade as $key => $value) {
                $aStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::BASEPRODUCT);
                $aHasUpgrade[$key]['iStoreNum'] = count($aStore);
            }
        }
        $aStatus = Yaf_G::getConf('aStatus', 'product');
        $sNextUrl = '/admin/product/addaddtion/id/' . $iProductID;
        $this->assign('iProductID', $iProductID);
        $this->assign('aParam', $aParam);
        $this->assign('aUpgrade', $aUpgrade);
        $this->assign('aHasUpgrade', $aHasUpgrade);
        $this->assign('aStatus', $aStatus);
        $this->assign('sNextUrl', $sNextUrl);
        $this->assign('aProduct', $aProduct);
        return true;
    }

    //选择升级产品
    public function addUpgradeAction()
    {
        $result = $this->_upgrade();
        if (empty($result)) {
            return null;
        }
    }

    //编辑升级产品
    public function editUpgradeAction()
    {
        $result = $this->_upgrade();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(4, 2);
        $this->assign('id', intval($this->getParam('id')));
    }

    //查看升级产品
    public function viewUpgradeAction()
    {
        $result = $this->_upgrade();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(4, 1);
        $this->assign('id', intval($this->getParam('id')));
    }

    //添加升级产品
    public function insertUpgradeAction()
    {
        $iProductID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', false);
        }
        $sUpgradeID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sUpgradeID)) {
            return $this->showMsg('请先选择升级产品', false);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }

        $aParam['iProductID'] = $iProductID;
        $aParam['iCreateUserID'] = $aParam['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
        $aParam['iType'] = Model_ProductUpgrade::BASEPRODUCT;//基础产品
        $aParam['iStatus'] = 1;
        if ($iCommitType) {//批量
            $aUpgradeID = explode(',', $sUpgradeID);
            $sSuccessNum = 0;
            $sTotalNum = count($aUpgradeID);
            foreach ($aUpgradeID as $key => $value) {
                //判断是否存在该升级产品
                if (Model_ProductUpgrade::getDataByUpgradeID($iProductID, $value, Model_ProductUpgrade::BASEPRODUCT)) {
                    continue;
                }
                $aParam['iUpgradeID'] = $value;
                if (Model_ProductUpgrade::addData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功添加' . $sSuccessNum . '个升级产品，失败' . ($sTotalNum - $sSuccessNum) . '个升级产品';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iUpgradeID'] = intval($sUpgradeID);
            //判断是否存在该升级产品
            if (Model_ProductUpgrade::getDataByUpgradeID($iProductID, $aParam['iUpgradeID'], Model_ProductUpgrade::BASEPRODUCT)) {
                return $this->showMsg('已添加过该升级产品！', false);
            }
            if (Model_ProductUpgrade::addData($aParam)) {
                return $this->showMsg('添加成功！', true);
            } else {
                return $this->showMsg('添加失败！', false);
            }
        }
    }

    //删除已添加升级产品
    public function deleteUpgradeAction()
    {
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        $sUpgradeID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sUpgradeID)) {
            return $this->showMsg('请先选择升级产品', false);
        }

        $aParam['iStatus'] = 0;
        if ($iCommitType) {//批量
            $aUpgradeID = explode(',', $sUpgradeID);
            $sSuccessNum = 0;
            $sTotalNum = count($aUpgradeID);
            foreach ($aUpgradeID as $key => $value) {
                //判断是否存在该升级产品
                if (!Model_ProductUpgrade::getDetail($value)) {
                    continue;
                }
                $aParam['iAutoID'] = $value;
                if (Model_ProductUpgrade::updData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功删除' . $sSuccessNum . '个升级产品，失败' . ($sTotalNum - $sSuccessNum) . '个升级产品';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iAutoID'] = intval($sUpgradeID);
            //判断是否存在该升级产品
            if (Model_ProductUpgrade::updData($aParam)) {
                return $this->showMsg('删除成功！', true);
            } else {
                return $this->showMsg('删除失败！', false);
            }
        }
    }

    //加项包共用
    private function _addtion()
    {
        $iPageSize = self::PAGESIZE;
        $iProductID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        $aParam['aType'] = $this->getParam('aType') ? array_keys($this->getParam('aType')) : [];
        $aParam['sKeyword'] = $this->getParam('sKeyword') ? addslashes(trim($this->getParam('sKeyword'))) : '';
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', $iProductID);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        $sTips = '';
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }
        $aType = Yaf_G::getConf('aAddtionType', 'product');

        $aHasAddtion = Model_ProductAddtion::getProductAddtions($iProductID, Model_ProductAddtion::BASEPRODUCT);//该产品已包含的加项包
        if (!empty($aHasAddtion)) {
            $aHasAddtionIDs = [];
            //整合需要的数据和生成tips
            $aTypeTmp = [];
            foreach ($aHasAddtion as $key => $value) {
                $aHasAddtionIDs[] = $value['iAddtionID'];
                $aHasProductDetail = Model_Addtion::getDetail($value['iAddtionID']);
                $aHasAddtion[$key]['sName'] = $aHasProductDetail['sName'];
                $aHasAddtion[$key]['iType'] = $aHasProductDetail['iType'];
                $aHasAddtion[$key]['sCode'] = $aHasProductDetail['sCode'];
                $aHasAddtion[$key]['sPrice'] = $aHasProductDetail['sPrice'];
                $aTypeTmp[$aHasProductDetail['iType']][] = 1;
            }

            if (!empty($aTypeTmp)) {
                foreach ($aTypeTmp as $key => $value) {
                    $sTips .= $aType[$key] . "(<strong class='red'>" . count($value) . "</strong>个)&nbsp&nbsp";
                }
            }

            $aParam['iAddtionID NOT IN'] = $aHasAddtionIDs;
        }
        if (!empty($aParam['aType'])) {
            $aParam['iType IN'] = $aParam['aType'];
        }
        if (!empty($aParam['sKeyword'])) {
            $aParam['sWhere'] = '(sCode="' . $aParam['sKeyword'] . '" OR sName LIKE "%' . $aParam['sKeyword'] . '%")';
        }
        $aParam['iStatus'] = 1;
        $aAddtion = Model_Addtion::getList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        //添加之后刷新，分页bug特殊处理
        if ((ceil($aAddtion['iTotal'] / $iPageSize)) != 0 && $iPage > (ceil($aAddtion['iTotal'] / $iPageSize))) {
            $aAddtion = Model_Addtion::getList($aParam, 1, 'iUpdateTime DESC', $iPageSize);
        }

        $sNextUrl = '/admin/product/list/';
        $this->assign('aType', $aType);
        $this->assign('iProductID', $iProductID);
        $this->assign('aParam', $aParam);
        $this->assign('aAddtion', $aAddtion);
        $this->assign('aHasAddtion', $aHasAddtion);
        $this->assign('sTips', $sTips);
        $this->assign('sNextUrl', $sNextUrl);
        $this->assign('aProduct', $aProduct);
        return true;
    }

    //选择加项包
    public function addAddtionAction()
    {
        $result = $this->_addtion();
        if (empty($result)) {
            return null;
        }
    }

    //编辑加项包
    public function editAddtionAction()
    {
        $result = $this->_addtion();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(5, 2);
        $this->assign('id', intval($this->getParam('id')));
    }

    //查看加项包
    public function viewAddtionAction()
    {
        $result = $this->_addtion();
        if (empty($result)) {
            return null;
        }
        //munu相关的赋值
        $this->_editHeader(5, 1);
        $this->assign('id', intval($this->getParam('id')));
    }

    //拓展产品
    public function sonListAction()
    {
        $iPageSize = self::PAGESIZE;
        $iProductID = intval($this->getParam('id'));
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aProductIDs = [];
        if (empty($iProductID)) {
            return $this->showMsg('参数有误！', false);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('产品不存在！', false);
        }

        if (!empty($aParam['aStatus'])) {
            $aParam['aStatus'] = array_keys($aParam['aStatus']);
            $aParam['iStatus IN'] = $aParam['aStatus'];
        }

        if (!empty($aParam['aSupplier'])) {
            $aParam['aSupplier'] = array_keys($aParam['aSupplier']);
            $aStoreParam['where']['iSupplierID IN'] = $aParam['aSupplier'];
        }

        if (!empty($aParam['aChannel'])) {
            $aParam['aChannel'] = array_keys($aParam['aChannel']);
            $aChannelParam['iChannel IN'] = $aParam['aChannel'];
            $aChannelParam['iProductID'] = $iProductID;
            $aChannelParam['iStatus'] = 1;
        }

        if (!empty($aStoreParam)) {
            $aTmp = Model_Store::getAll($aStoreParam);//满足条件的门店
            $aStoreIDs = [];
            if (!empty($aTmp)) {
                foreach ($aTmp as $key => $value) {
                    $aStoreIDs[] = $value['iStoreID'];
                }
                $aProductStore = Model_ProductStore::getDataByStores($aStoreIDs, Model_ProductStore::EXPANDPRODUCT);
                if (!empty($aProductStore)) {
                    foreach ($aProductStore as $key => $value) {
                        $aProductIDs[] = $value['iProductID'];
                    }
                }
            }
            $aParam['iProductID IN'] = $aProductIDs;
        }

        if (!empty($aChannelParam)) {
            $aProductChannel = Model_ProductChannel::getProductByChannel($aParam['aChannel']);
            $aTmp = [];
            if (!empty($aProductChannel)) {
                foreach ($aProductChannel as $key => $value) {
                    $aProductParam['iParentID'] = $iProductID;
                    $aChannelParam['iStatus>'] =0;
                    $aChannelParam['iProductID'] =$value['iProductID'];
                    if(Model_Product::getRow($aChannelParam))
                    {
                        $aTmp[] = $value['iProductID'];
                    }
                }
            }
            if (!empty($aParam['iProductID IN'])) {
                $aParam['iProductID IN'] = array_intersect($aParam['iProductID IN'],$aTmp);
            } else {
                $aParam['iProductID IN'] = $aTmp;
            }
        }


        $aParam['iParentID'] = $iProductID;
        $aParam['iType'] = Model_Product::TYPE_EXPAND;
        if (!empty($aParam['sKeyword'])) {
            $aParam['sWhere'] = '(sProductCode="' . $aParam['sKeyword'] . '" OR sProductName LIKE "%' . $aParam['sKeyword'] . '%")';
        }

        if (isset($aParam['iProductID IN']) && empty($aParam['iProductID IN'])) {
            $aData['aList'] = [];
        } else {
            $aData = Model_Product::getList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        }

        if (!empty($aData['aList'])) {
            //整合需要的数据

            //基础产品单项数目
            $aProductItem = Model_ProductItem::getProductItems($iProductID, Model_ProductItem::BASEPRODUCT,1,true);
            foreach ($aData['aList'] as $key => $value) {
                $aHasStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::EXPANDPRODUCT);
                $aData['aList'][$key]['iStoreNum'] = count($aHasStore);
                $aData['aList'][$key]['iCityNum'] = 0;
                $aData['aList'][$key]['iSupplierNum'] = 0;
                $aData['aList'][$key]['iComChannelNum'] = 0;
                $aData['aList'][$key]['iComUserNum'] = 0;
                $aData['aList'][$key]['iIndivChannelNum'] = 0;
                $aData['aList'][$key]['iIndivUserNum'] = 0;
                $aData['aList'][$key]['iItemChange'] = '';
                //统计单项变化
                $aSonItem = Model_ProductItem::getProductItems($value['iProductID'], Model_ProductItem::EXPANDPRODUCT,1,true);
                //单项交集
                $aItemIntersect = array_intersect($aProductItem,$aSonItem);
                //单项差集
                $aItemDiff = array_diff($aProductItem,$aSonItem);
                $aItemDiff1 = array_diff($aSonItem,$aProductItem);
                $sItemChange = '';
                if (!empty($aItemDiff)) {
                    $sItemChange .= '减少'.count($aItemDiff).'个';
                }
                if (!empty($aItemDiff1)) {
                    if (!empty($sItemChange)) {
                        $sItemChange .= ',';
                    }
                    $sItemChange .= '增加'.count($aItemDiff1).'个';
                }
                if (empty($aItemDiff) && empty($aItemDiff1)) {
                    $sItemChange = '无变化';
                }
                $aData['aList'][$key]['iItemChange'] = $sItemChange;
                //统计城市和供应商数目
                if (!empty($aHasStore)) {
                    foreach ($aHasStore as $k => $val) {
                        $aProductData = Model_Store::getDetail($val['iStoreID']);
                        $aHasStore[$key]['aStore'] = $aProductData;
                        //按城市分组
                        $aCityTmp[$aProductData['iCityID']][] = 1;
                        //按供应商分组
                        $aSupplierTmp[$aProductData['iSupplierID']][] = 1;
                    }
                    $aData['aList'][$key]['iCityNum'] = count($aCityTmp);
                    $aData['aList'][$key]['iSupplierNum'] = count($aSupplierTmp);
                }
                //统计渠道和客户数量
                if (!empty($value['iCanCompany'])) {
                    $aChannel = Model_ProductChannel::getChannelInfoByProductID($value['iProductID'], Model_ProductChannel::TYPE_COMPANY);
                    $aData['aList'][$key]['iComChannelNum'] = count($aChannel);
                    if (!empty($aChannel)) {
                        $iNumTmp = 0;
                        $aViewList = [];
                        foreach ($aChannel as $k => $val) {
                            //统计渠道所有支持数目
                            if ($val['iViewRange'] == 0 || $val['iViewRange'] == 2) {//全部和不可见要统计渠道所有支持数目
                                $aUserParam['where']['iStatus >'] = 0;
                                $aUserParam['where']['iChannel'] = $val['iChannelID'];
                                $aChannelUser = Model_User::getCnt($aUserParam);
                                $iNumTmp += $aChannelUser;
                                if ($val['iViewRange'] == 2) {
                                    $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_COMPANY, $val['iChannelID']);
                                    $iNumTmp = $iNumTmp - count($aViewList);
                                }
                            } else {
                                $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_COMPANY, $val['iChannelID']);
                                if (!empty($aViewList)) {
                                    $iNumTmp += count($aViewList);
                                }
                            }
                        }
                        $aData['aList'][$key]['iComUserNum'] = $iNumTmp;
                    }
                }
                if (!empty($value['iCanIndividual'])) {
                    $aChannel = Model_ProductChannel::getChannelInfoByProductID($value['iProductID'], Model_ProductChannel::TYPE_INDIVIDUAL);
                    $aData['aList'][$key]['iIndivChannelNum'] = count($aChannel);
                    if (!empty($aChannel)) {
                        $iNumTmp = 0;
                        $aViewList = [];
                        foreach ($aChannel as $k => $val) {
                            //统计渠道所有支持数目
                            if ($val['iViewRange'] == 0 || $val['iViewRange'] == 2) {//全部和不可见要统计渠道所有支持数目
                                $aUserParam['where']['iStatus >'] = 0;
                                $aUserParam['where']['iChannel'] = $val['iChannelID'];
                                $aChannelUser = Model_User::getCnt($aUserParam);
                                $iNumTmp += $aChannelUser;
                                if ($val['iViewRange'] == 2) {
                                    $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_INDIVIDUAL, $val['iChannelID']);
                                    $iNumTmp = $iNumTmp - count($aViewList);
                                }
                            } else {
                                $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_INDIVIDUAL, $val['iChannelID']);
                                if (!empty($aViewList)) {
                                    $iNumTmp += count($aViewList);
                                }
                            }
                        }
                        $aData['aList'][$key]['iIndivUserNum'] = $iNumTmp;
                    }
                }
            }
        }

        $aChannel = Yaf_G::getConf('aChannel');
        $aStatus = Yaf_G::getConf('aStatus', 'product');
        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aChannel', $aChannel);
        $this->assign('aStatus', $aStatus);
        $this->assign('aParam', $aParam);
        $this->assign('aData', $aData);

        //munu相关的赋值
        $this->_editHeader(6, 1);
        $this->assign('id', intval($iProductID));
        $this->assign('aProduct', $aProduct);
    }

    //添加加项包
    public function insertAddtionAction()
    {
        $iProductID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', false);
        }
        $sAddtionID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sAddtionID)) {
            return $this->showMsg('请先选择加项包', false);
        }
        $aProduct = Model_Product::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }

        $aParam['iProductID'] = $iProductID;
        $aParam['iCreateUserID'] = $aParam['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
        $aParam['iType'] = Model_ProductAddtion::BASEPRODUCT;//基础产品
        $aParam['iStatus'] = 1;
        if ($iCommitType) {//批量
            $aAddtionID = explode(',', $sAddtionID);
            $sSuccessNum = 0;
            $sTotalNum = count($aAddtionID);
            foreach ($aAddtionID as $key => $value) {
                //判断是否存在该加项包
                if (Model_ProductAddtion::getDataByAddtionID($iProductID, $value, Model_ProductAddtion::BASEPRODUCT)) {
                    continue;
                }
                $aParam['iAddtionID'] = $value;
                if (Model_ProductAddtion::addData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功添加' . $sSuccessNum . '个加项包，失败' . ($sTotalNum - $sSuccessNum) . '个加项包';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iAddtionID'] = intval($sAddtionID);
            //判断是否存在该加项包
            if (Model_ProductAddtion::getDataByAddtionID($iProductID, $aParam['iAddtionID'], Model_ProductAddtion::BASEPRODUCT)) {
                return $this->showMsg('已添加过该加项包！', false);
            }
            if (Model_ProductAddtion::addData($aParam)) {
                return $this->showMsg('添加成功！', true);
            } else {
                return $this->showMsg('添加失败！', false);
            }
        }
    }

    //删除已添加加项包
    public function deleteAddtionAction()
    {
        $iCommitType = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        $sAddtionID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sAddtionID)) {
            return $this->showMsg('请先选择加项包', false);
        }
        $aParam['iStatus'] = 0;
        if ($iCommitType) {//批量
            $aAddtionID = explode(',', $sAddtionID);
            $sSuccessNum = 0;
            $sTotalNum = count($aAddtionID);
            foreach ($aAddtionID as $key => $value) {
                //判断是否存在该加项包
                if (!Model_ProductAddtion::getDetail($value)) {
                    continue;
                }
                $aParam['iAutoID'] = $value;
                if (Model_ProductAddtion::updData($aParam)) {
                    $sSuccessNum++;
                }
            }
            $sMsg = '成功删除' . $sSuccessNum . '个加项包，失败' . ($sTotalNum - $sSuccessNum) . '个加项包';
            return $this->showMsg($sMsg, true);
        } else {
            $aParam['iAutoID'] = intval($sAddtionID);
            //判断是否存在该加项包
            if (Model_ProductAddtion::updData($aParam)) {
                return $this->showMsg('删除成功！', true);
            } else {
                return $this->showMsg('删除失败！', false);
            }
        }
    }

    /**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkClientData($iType)
    {

        $aParam = $this->getParams();
        if (!Util_Validate::isLength($aParam['sProductName'], 1, 50)) {
            return $this->showMsg('产品名称为1到50个字！', false);
        }
        if (empty($aParam['sImage'])) {
            return $this->showMsg('产品图片不能为空！', false);
        }

        //验证产品名是否存在
        $aProduct = Model_Product::getProductByName($aParam['sProductName']);
        if (!empty($aProduct['iProductID']) && $aProduct['iProductID'] != $aParam['iProductID']) {
            return $this->showMsg('该产品名已存在！', false);
        }
        return $aParam;
    }

    public function downDemoAction()
    {
        $filepath = '/data/wwwroot/xcy/51joying/doc/itemdemo.xls';
        $filename = '单项导入模板.xls';

        Util_File::download($filepath, $filename);
    }

    /**
     * 导入单项
     * @return [type] [description]
     */
    public function importItemAction ()
    {
        $params = $this->getParams();
        if (!$params['id'] || !$params['file']) {
            return $this->showMsg('文件和加项包不能为空', false);
        }
        if (empty($params['type']) || !in_array($params['type'], ['1', '2'])) {
            $type = 1;
        } else {
            $type = $params['type'];
        }

        $aFile = explode('.', $params['file']);
        $oFile = new File_Storage();
        $ret = $oFile->getFile($aFile[0], $aFile[1]);
        if ($ret) {
            $PHPExcel  = new PHPExcel();
            $PHPReader = new PHPExcel_Reader_Excel2007();
            
            $file_path = $oFile->getDestFile($aFile[0]);
            if(!$PHPReader->canRead($file_path)){
                $PHPReader = new PHPExcel_Reader_Excel5();
                if(!$PHPReader->canRead($file_path)){
                    return $this->showMsg('Excel文件处理错误', false);                
                }
            }
            $PHPExcel = $PHPReader->load($file_path);
            $currentSheet = $PHPExcel->getSheet(0);
            $allRow = $currentSheet->getHighestRow();
            $id = $params['id'];
            for ($i = 2; $i <= $allRow; $i++) { //第1行是表头,从第2行开始读取
                $item = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                if (empty($this->item[$item])) {
                    continue;
                }

                $where = [
                    'iProductID' => $id,
                    'iItemID' => $this->item[$item],
                    'iType' => $type,
                    'iStatus' => Model_ProductItem::STATUS_VALID
                ];
                $row = Model_ProductItem::getRow(['where' => $where]);
                if ($row) {
                    continue;
                }

                $where ['iCreateUserID'] = $this->aCurrUser['iUserID'];
                $where ['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
                Model_ProductItem::addData($where);
            }           

            return $this->showMsg('导入完成', true);
        }
    }
}