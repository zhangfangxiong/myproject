<?php
/**
 * User: xcy
 * Date: 2016/5/15
 * Time: 13:00
 */

class Controller_Admin_Store extends Controller_Admin_Base
{

    public $aCity = null;

    public $aRegion = null;

    public $aSupplier = null;

    public $aShopLevel = null;

    public function actionBefore()
    {
        parent::actionBefore();

        $aCitys = Model_City::getAll([
            'where' => [ 'iStatus' => Model_City::STATUS_VALID ],
            'order' => 'sPinyin ASC'
        ]); 
        if ($aCitys) {
            foreach ($aCitys as $key => $value) {
                $aCity[$value['iCityID']] =  $value['sCityName'];
            }
        }

        $iCityID = $this->getParam('iCityID');
        $where = [ 'iStatus' => Model_Region::STATUS_VALID ];
        if ($iCityID) {
            $where['iCityID'] = $iCityID;
        }
        $aRegions = Model_Region::getAll(['where' => $where]);     
        if ($aRegions) {
            foreach ($aRegions as $key => $value) {
                $aRegion[$value['iRegionID']] =  $value['sRegionName'];
            }
        }

        $aSupplier = Model_Type::getOption('supplier');
        $this->aShopLevel = Yaf_G::getConf('aShopLevel', 'store');
        // print_r($aShopLevel);die;
        $aStoreCategory = Yaf_G::getConf('aStoreCategory', 'store');
        $aStoreProperty = Yaf_G::getConf('aStoreProperty', 'store');
        $aCreditLevel = Yaf_G::getConf('aRelationLevel');
        $aRelationLevel = Yaf_G::getConf('aRelationLevel');

        $this->assign('aCity', $aCity);
        $this->assign('aRegion', $aRegion);
        $this->assign('aSupplier', $aSupplier);
        $this->assign('aShopLevel', $this->aShopLevel);
        $this->assign('aStoreCategory', $aStoreCategory);
        $this->assign('aStoreProperty', $aStoreProperty);
        $this->assign('aCreditLevel', $aCreditLevel);
        $this->assign('aRelationLevel', $aRelationLevel);
    }

    /**
     * 供应商门店列表
     */
    public function listAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [ 'iStatus >' => Model_Store::STATUS_INVALID ];
        if (!empty($aParam['sName'])) {
            $aWhere['sName LIKE'] = '%' . trim($aParam['sName']) . '%';
        }
        if (!empty($aParam['sCode'])) {
            $aWhere['sCode LIKE'] = '%' . trim($aParam['sCode']) . '%';
        }
        if (!empty($aParam['iCityID'])) {
            $aWhere['iCityID'] = intval($aParam['iCityID']);
        }
        if (!empty($aParam['iRegionID'])) {
            $aWhere['iRegionID'] = intval($aParam['iRegionID']);
        }
        if (!empty($aParam['iSupplierID'])) {
            $aWhere['iSupplierID'] = intval($aParam['iSupplierID']);
        }        

        $aList = Model_Store::getList($aWhere, $iPage);
        $this->assign('aList', $aList);
        $this->assign('aParam', $aParam);        
    }

    /**
     * 修改分数
     */
    public function updScoreAction ()
    {
        $params = $this->getParams();
        if ($params['iStoreID']) {
            Model_Store::updData($params);
        }

        return $this->redirect('list');
    }

    /**
     * 查看详情
     * @return 
     */
    public function detailAction ()
    {
        $id = intval($this->getParam('id'));
        if (!$id) {
            return $this->redirect('list');
        }

        $aStore = Model_Store::getDetail($id);
        $this->setRegion($aStore['iCityID']);

        $this->assign('sOpt', 'detail');
        $this->assign('aStore', $aStore);
        $this->setMenu(1, $id);
    }

    /**
     * 停用/启用
     * @return [type] [description]
     */
    public function stopAction ()
    {
        $id = intval($this->getParam('id'));
        if (!$id) {
            return $this->showMsg('请选择需要操作的门店', false);
        }

        $aStore = Model_Store::getDetail($id);
        if (!$aStore || !in_array($aStore['iStatus'], [1, 2])) {
            return $this->showMsg('操作的门店无效', false);
        }

        $data['iStoreID'] = $id;
        if ($aStore['iStatus'] == 1) {
            $data['iStatus'] = 2;
        }
        if ($aStore['iStatus'] == 2) {
            $data['iStatus'] = 1;
        }
        
        Model_Store::updData($data);
        return $this->showMsg('操作成功', true);
    }

    /**
     * 新增供应商门店
     */
    public function addAction ()
    {
        if ($this->isPost()) {
            $data = $this->_checkClientData(1);
            if (!$data) {
                return null;
            }

            $data['sCode'] = Model_Store::initStoreCode();
            $store = Model_Store::getStoreByName($data['sName'], $data['sShortName']);
            if ($store) {
               return $this->showMsg('门店名称已存在', false); 
            }
            $iStoreID = Model_Store::addData($data);
            if ($iStoreID) {
                return $this->showMsg('门店新增成功', true, '/admin/store/list/');
            } else {
                return $this->showMsg('新增失败', false);
            }            
        }

        $this->assign('sDelUrl', '/admin/store/detail/id/'.$id);
        $this->assign('sOpt', 'add');
    }

    /**
     * 修改供应商门店
     */
    public function editAction ()
    {
        if ($this->isPost()) {
            $data = $this->_checkClientData(2);
            if (!$data) {
                return null;
            }

            $store = Model_Store::getStoreByName($data['sName'], $data['sShortName']);          
            if ($store && 
                (   ($store['sName'] && $store['sName'] != $data['sName']) 
                    || ($store['sShortName'] && $store['sShortName'] != $data['sShortName'])
                ) && ($store['iStoreID'] != $data['iStoreID'])
            ) {
               return $this->showMsg('门店名称已存在', false); 
            }

            $iStoreID = Model_Store::updData($data);
            if ($iStoreID) {
                return $this->showMsg('门店修改成功', true, '/admin/store/detail/id/'.$data['iStoreID']);
            } else {
                return $this->showMsg('修改失败', false);
            }
        } else {
            $id = intval($this->getParam('id'));
            if (!$id) {
                return $this->redirect('list');
            }
            $aStore = Model_Store::getDetail($id);

            $this->setRegion($aStore['iCityID']);            
            $this->assign('aStore', $aStore);
        }
                
        $this->assign('sDelUrl', '/admin/store/detail/id/'.$id);
        $this->assign('sOpt', 'edit');
    }

    public function setRegion ($iCityID)
    {
        $aRegion = [];

        if ($iCityID) {
            $aRegions = Model_Region::getAll(['where' => [ 
                    'iStatus' => Model_City::STATUS_VALID,
                    'iCityID' => $iCityID
                ]
            ]);     
        }   

        if ($aRegions) {
            foreach ($aRegions as $key => $value) {
                $aRegion[$value['iRegionID']] =  $value['sRegionName'];
            }
        }

        $this->assign('aRegion', $aRegion);
    }

    /**
     * 联系人信息
     * @return
     */
    public function contactAction ()
    {
        $id = intval($this->getParam('id'));
        $page = intval($this->getParam('page'));
        if (!$id) {
            return $this->redirect('list');
        }
        
        $where = [
            'iStoreID' => $id,
            'iStatus' => Model_Contecter::STATUS_VALID,
            'iType' => Model_Contecter::TYPE_STORE
        ];
        $aList = Model_Contecter::getList($where, $page);

        $this->assign('aList', $aList);
        $this->assign('iStoreID', $id);
        $this->setMenu(2, $id);
    }

    /**
     * 新增联系人
     */
    public function addContactAction ()
    {
        if ($this->isPost()) {
            $data = $this->_checkData(1);
            if (!$data) {
                return null;
            }

            $data['iType'] = Model_Contecter::TYPE_STORE;
            $data['iStoreID'] = $data['id'];
            $iID = Model_Contecter::addData($data);
            if ($iID) {
                return $this->showMsg('/admin/store/contact/id/'.$data['id'], true);
            } else {
                return $this->showMsg('新增失败', false);
            }
        } else {
            $id = intval($this->getParam('id'));
            if (!$id) {
                return $this->redirect('/admin/store/list');
            }

            $this->assign('sDelUrl', '/admin/store/contact/id/'.$id);
        }        
    }


    /**
     * 编辑联系人
     */
    public function editContactAction ()
    {
        if ($this->isPost()) {
            $data = $this->_checkData(2);
            if (!$data) {
                return null;
            }

            $data['iType'] = Model_Contecter::TYPE_STORE;
            $iUpd = Model_Contecter::updData($data);
            if ($iUpd) {
                return $this->showMsg('/admin/store/contact/id/'.$data['id'], true);
            } else {
                return $this->showMsg('修改失败', false);
            }
        } else {
            $id = intval($this->getParam('iID'));
            if (!$id) {
                return $this->redirect('/admin/store/list');
            }

            $aContact = Model_Contecter::getDetail($id);

            $this->assign('aContact', $aContact);
            $this->assign('sDelUrl', '/admin/store/contact/id/'.$aContact['iStoreID']);
        }    
    }

    public function productAction ()
    {
        $id = intval($this->getParam('id'));
        if (!$id) {
            return $this->redirect('/admin/store/list');
        } 
        $page = intval($this->getParam('page'));        
        $where = [
            'iStoreID' => $id,
            'iStatus' => Model_ProductStore::STATUS_VALID,
            'iType' => Model_ProductStore::EXPANDPRODUCT
        ];
        
        $aExpand = Model_ProductStore::getList($where, $page);
        if ($aExpand['aList']) {
            foreach ($aExpand['aList'] as $key => $value) {
                $aProduct = Model_Product::getDetail($value['iProductID']);
                if (!$aProduct) {
                    unset($aExpand['aList'][$key]);
                    continue;
                }
                $aExpand['aList'][$key]['sProductName'] = $aProduct['sProductName'];
                $aExpand['aList'][$key]['sProductCode'] = $aProduct['sProductCode'];
            }
        }
        $this->assign('aList', $aExpand);
        $this->assign('iStoreID', $id);
        $this->setMenu(3, $id);
    }

    /**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkClientData($iType)
    {
        $aParam = $this->getParams();
        if (empty($aParam['sName'])) {
            return $this->showMsg('门店名称不能为空', false);
        }

        if (empty($aParam['sShortName'])) {
            return $this->showMsg('门店简称不能为空', false);
        }

        if (empty($aParam['iSupplierID'])) {
            return $this->showMsg('请选择门店分类', false);   
        }

        if ($aParam['sStoreCode'] == '') {
            return $this->showMsg('门店代码不能为空', false);
        }

        if ($iType == 2) {
            if (empty($aParam['iStoreID'])) {
                return $this->showMsg('非法操作！', false);
            }
            if (!Model_Store::getDetail($aParam['iStoreID'])) {
                return $this->showMsg('门店不存在！', false);
            }
        }

        $aParam['iShopLevel'] = $this->aShopLevel[$aParam['iShopLevel']];        
        return $aParam;
    }

    /**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkData($iType)
    {
        $aParam = $this->getParams(); 
        if (empty($aParam['id'])) {
            return $this->redirect('/admin/store/contact');
        }
        if (empty($aParam['sMobile']) || !Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (empty($aParam['sEmail']) || !Util_Validate::isEmail($aParam['sEmail'])) {
            return $this->showMsg('邮箱不符合规范！', false);
        }
        if (empty($aParam['sRealName'])) {
            return $this->showMsg('姓名不能为空！', false);
        }
        if (empty($aParam['sAppellation'])) {
            return $this->showMsg('称谓不能为空！', false);
        }
        if (empty($aParam['sJobPhone'])) {
            return $this->showMsg('工作电话不能为空！', false);
        }

        if ($iType == 2) {
            if (empty($aParam['iID'])) {
                return $this->showMsg('非法操作！', false);
            }
            if (!Model_Contecter::getDetail($aParam['iID'])) {
                return $this->showMsg('联系人不存在！', false);
            }
        }

        //验证邮箱
        if (!empty($aParam['sEmail'])) {
            $aContecter = Model_Contecter::getRow(['where' => [
                'iStoreID' => $aParam['id'],
                'sEmail' => $aParam['sEmail'],
                'iType' => Model_Contecter::TYPE_STORE,
                'iStatus >' => Model_Contecter::STATUS_INVALID
            ]]); 
            if (!empty($aContecter['iID']) && $aContecter['iID'] != $aParam['iID']) {
                return $this->showMsg('该邮箱已被注册！', false);
            }
        }

        if (!empty($aParam['sMobile'])) {
            $aContecter = Model_Contecter::getRow(['where' => [
                'iStoreID' => $aParam['id'],
                'sMobile' => $aParam['sMobile'],
                'iType' => Model_Contecter::TYPE_STORE,
                'iStatus >' => Model_Contecter::STATUS_INVALID
            ]]); 
            if (!empty($aContecter['iID']) && $aContecter['iID'] != $aParam['iID']) {
                return $this->showMsg('该手机已被注册！', false);
            }
        }

        return $aParam;
    }

    //headermenu
    private function setMenu($iMenu, $id)
    {
        $aMenu = [
            1 => [
                'url' => '/admin/store/detail/id/'.$id,
                'name' => '门店信息',
            ],
            2 => [
                'url' => '/admin/store/contact/id/'.$id,
                'name' => '联系人信息',
            ],
            3 => [
                'url' => '/admin/store/product/id/'.$id,
                'name' => '体检产品',
            ]            
        ];

        $aContact = Model_Store::getDetail($id);

        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
        $this->assign('sStoreName', $aContact['sName']);
    }

    public function importSupplierAction ()
    {
        if ($this->isPost()) {
            $params = $this->getParams();
            if (!$params['sFile']) {
                return $this->showMsg('文件不能为空', false);
            }

            $aFile = explode('.', $params['sFile']);
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

                $count = 0;
                for ($i = 2; $i <= $allRow; $i++) { //第1行是表头,从第2行开始读取
                    $supplier = [];
                    
                    $supplier['sTypeName'] = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                    $supplier['sUserName'] = $PHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
                    $supplier['sPassword'] = $PHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
                    if (!$supplier['sUserName']) {
                        continue;
                    }

                    if ($supplier['sTypeName']) {
                        $row = Model_Type::getRow(['where' => [
                            'sTypeName' => $supplier['sTypeName'],
                            'iStatus' => 1
                        ]]);
                        if ($row) {
                            $supplier['iSupplierID'] = $row['iTypeID'];
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }

                    if (!$supplier['sPassword']) {
                        $supplier['sPassword'] = $supplier['sUserName'];
                    }
                    $supplier['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') .$supplier['sPassword']);

                    $aSupplier = Model_User::getRow(['where' => [
                        'iType' => 4,
                        'iSupplierID' => $supplier['iSupplierID']
                    ]]);
                    if ($aSupplier) {
                        $aSupplier['sUserName'] = $supplier['sUserName'];
                        $aSupplier['sPassword'] = $supplier['sPassword'];
                        $aSupplier['iSupplierID'] = $supplier['iSupplierID'];
                        Model_User::updData($aSupplier);
                    } else {
                        $aSupplier['iType'] = 4;
                        $aSupplier['sUserName'] = $supplier['sUserName'];
                        $aSupplier['sPassword'] = $supplier['sPassword'];
                        $aSupplier['iSupplierID'] = $supplier['iSupplierID'];
                        Model_User::addData($aSupplier);
                    }

                    $count++;
                }

                return $this->showMsg('导入完成，导入成功'.$count.'条', true);
            }
        }
    }

}