<?php

/**
 * Admin后台_加项包管理
 * User: xuchuyuan
 * Date: 16/4/26 18:00
 */
class Controller_Admin_Itemap extends Controller_Admin_ItemBase
{

	public $sex = [
		'男' => 1,
		'女未婚' => 2,
		'女已婚' => 3,
	];

	public $sexType = [
		1 => '男',
		2 => '女未婚',
		3 => '女已婚',
	];

	public function actionAfter ()
	{
		parent::actionAfter();

		$this->assign('sInsertItemUrl', '/admin/itemap/insertitem/');
		$this->assign('sDeleteItemUrl', '/admin/itemap/deleteitem/');
		$this->assign('aSex', $this->sexType);
	}

	/**
	 * 加项列表
	 * @return [array]
	 */
    public function listAction ()
    {
		$page  = $this->getParam('page') ? intval($this->getParam('page')) : 1;
		$aParam = $this->getParams();
		$aParam['iCanAdd'] = 1;
		if (!empty($aParam['aParentCat'])) {
            $aParam['aParentCat'] = array_keys($aParam['aParentCat']);
            $aParam[ 'iParentCat IN' ] = $aParam['aParentCat'];
        }
        if (!empty($aParam['aCat'])) {
            $aParam['aCat'] = array_keys($aParam['aCat']);
            $aParam[ 'iCat IN' ] = $aParam['aCat'];
        }
        if (!empty($aParam['aCanMan'])) {
            $aParam['aCanMan'] = array_keys($aParam['aCanMan']);
            $aParam[ 'iCanMan IN' ] = $aParam['aCanMan'];
        }
		if (!empty($aParam['aCanWomanNoMarry'])) {
            $aParam['aCanWomanNoMarry'] = array_keys($aParam['aCanWomanNoMarry']);
            $aParam[ 'iCanWomanNoMarry IN' ] = $aParam['aCanWomanNoMarry'];
        }
        if (!empty($aParam['aCanWomanMarry'])) {
            $aParam['aCanWomanMarry'] = array_keys($aParam['aCanWomanMarry']);
            $aParam[ 'iCanWomanMarry IN' ] = $aParam['aCanWomanMarry'];
        }      
        if (!empty($aParam['sKeyword'])) {
        	$aParam['sWhere'] = "(sName LIKE '%".addslashes($aParam['sKeyword'])."%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
        } else {
        	unset($aParam['sKeyword']);
        }
        if (!empty($aParam['page'])) {
        	unset($aParam['page']);
        }
        $aList = Model_Item::getList($aParam, $page, 'iUpdateTime DESC');

		$this->assign('aList', $aList);
		$this->assign('aParam', $aParam);
		$this->assign('iPage',  $page);
		$this->setMenu(1);
	}


	/**
	 * 组合加项列表
	 * @return 
	 */
	public function groupAction ()
	{
		$aParam['sKeyword'] = $this->getParam('sKeyword');
		if ($this->isPost()) {			
			if ($aParam['sKeyword']) {
				$aItem = Model_Item::getAll([
					'where' => [
						'sName LIKE' => '%' . $aParam['sKeyword'] . '%',
						'iStatus' => Model_Item::STATUS_VALID,
					]
				]);
				if ($aItem) {
					$aExistItemID = [];
					foreach ($aItem as $key => $value) {
						$aExistItemID[] = $value['iItemID'];
					}					
					if ($aExistItemID) {
						$sExistItemID = implode(',', $aExistItemID);
						$aProductItem = Model_ProductItem::getAll([
							'where' => [
								'iType' => Model_ProductItem::ADDTION,
								'iStatus' => Model_ProductItem::STATUS_VALID,
								'iItemID IN' => $sExistItemID,
							]
						]);						
						if ($aProductItem) {
							$aProductID = [];
							foreach ($aProductItem as $k => $val) {
								if (!in_array($val['iProductID'], $aProductID)) {
									$aProductID[] = $val['iProductID'];	
								}								
							}							
							if ($aProductID) {
								$aParam['sProductID'] = implode(',', $aProductID);
							}
						}						
					}					
				}				
			}	
		}

		$aParam['sType'] = '2';
		$page = $this->getParam('iPage');
		
		$aGroup = Model_Addtion::getPageList($aParam, $page);
		if ($aGroup['aList']) {
			foreach ($aGroup['aList'] as $key => $value) {
				$aAddition = Model_ProductItem::getAll([
					'where' => [
						'iProductID' => $value['iAddtionID'],
						'iType' => Model_ProductItem::ADDTION,
						'iStatus' => Model_ProductItem::STATUS_VALID,
					]
				]);

				$sName = '';
				if ($aAddition) {
					foreach ($aAddition as $k => $val) {
						if ($val['iItemID']) {
							$sName 
							? $sName .= ',' . $this->itemMap[$val['iItemID']] 
							: $sName  = $this->itemMap[$val['iItemID']];
						}
					}					
				}

				$aGroup['aList'][$key]['sItems'] = $sName;
			}
		}

		$this->assign('aList', $aGroup);	

		$this->assign('aParam', $aParam);

		$this->setMenu(2);
	}



	/**
	 * 新增加项
	 */
	public function addGroupAction ()
	{	
		if ($this->_request->isPost()) {
            $aAddition = $this->_checkClientData(1);
            if (empty($aAddition)) {
                return null;
            }

            $aAddition['iType'] = 2;
            $aAddition['iCreateUserID'] = $this->aCurrUser['iUserID'];
            $aAddition['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if ($iLastInsertID = Model_Addtion::addData($aAddition)) {
                $sNextUrl = '/admin/itemap/next/id/' . $iLastInsertID;
                return $this->showMsg($sNextUrl, true);
            } else {
                return $this->showMsg('增加失败！', false);
            }
        }
	}

	/**
	 * 新增第二步
	 */
	public function nextAction () 
	{
		$id = $this->getParam('id');
		$iPageSize = 5;
        $iAddtionID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
        $aParam['aCategory'] = $this->getParam('aCategory') ? array_keys($this->getParam('aCategory')) : [];
        $aParam['aParentCat'] = $this->getParam('aParentCat') ? array_keys($this->getParam('aParentCat')) : [];
        $aParam['sKeyword'] = $this->getParam('sKeyword') ? addslashes($this->getParam('sKeyword')) : '';
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iAddtionID)) {
            return $this->showMsg('参数有误', $iAddtionID);
        }
        $aAddition = Model_Addtion::getDetail($iAddtionID);
        $aHasItem = [];//该产品已包含的单项
        if (empty($aAddition)) {
            return $this->showMsg('该产品不存在!', false);
        }

       	$aProductItem = Model_ProductItem::getAll([
       		'where' => [
       			'iProductID' => $iAddtionID,
				'iType' => Model_ProductItem::ADDTION,
				'iStatus' => Model_ProductItem::STATUS_VALID,
       		]
       	]);
       	if ($aProductItem) {
       		$iItemIDs = '';
       		foreach ($aProductItem as $k => $val) {
				if ($val['iItemID']) {
					$iItemIDs 
					? $iItemIDs .= ',' . $val['iItemID'] 
					: $iItemIDs  = $val['iItemID'];
				}
			}	

			if ($iItemIDs) {
				$aHasItem = Model_Item::getAll(['where' => [
						'iCanAdd' => 1,
						'iItemID IN' => $iItemIDs,
						'iStatus' => Model_Item::STATUS_VALID
					]
				]);

				$aParam['sItemID'] = $iItemIDs;
			}
       	}

       	$aParam['sCanadd'] = 1;
        $aParam['sCategory'] = implode(',', $aParam['aCategory']);
        $aParam['sParentCat'] = implode(',', $aParam['aParentCat']);
        $aParam['id'] = $iAddtionID;//分页的时候要带上
        $aItem = Model_Item::getPageList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        
        //添加之后刷新，分页bug特殊处理
        if ((ceil($aItem['iTotal'] / $iPageSize)) != 0 && $iPage > (ceil($aItem['iTotal'] / $iPageSize))) {
            $aItem = Model_Item::getPageList($aParam, 1, 'iUpdateTime DESC', $iPageSize);
        }

        $this->assign('iProductID', $iAddtionID);
        $this->assign('aParam', $aParam);
        $this->assign('aItem', $aItem);
        $this->assign('aHasItem', $aHasItem);
        $this->assign('aAddition', $aAddition);
	}

	/**
	 * 添加单项
	 */
    public function insertItemAction()
    {
        $iProductID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', false);
        }
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sItemID)) {
            return $this->showMsg('请先选择单项', false);
        }
        $aProduct = Model_Addtion::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }        
        
        $aParam['iProductID'] = $iProductID;
        $aParam['iType'] = Model_ProductItem::ADDTION;
        $aParam['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];

        $aItemIDs = explode(',', $sItemID);
        foreach ($aItemIDs as $key => $value) {
        	$aParam['iItemID'] = $value;
        	$aRow = Model_ProductItem::getRow([
        		'where' => [
        			'iProductID' => $iProductID,
        			'iItemID' => $value,
        			'iType'   => Model_ProductItem::ADDTION,
        			'iStatus' => Model_ProductItem::STATUS_VALID
        		]
        	]);
        	if (!$aRow) {
        		$aParam['iCreateUserID'] = $this->aCurrUser['iUserID'];
        		Model_ProductItem::addData($aParam);
        	} else {
        		$aParam['iAutoID'] = $aRow['iAutoID'];
        		Model_ProductItem::updData($aParam);
        	}
        }

        return $this->showMsg('添加成功！', true);
    }

    /**
	 * 删除已添加单项
	 */
    public function deleteItemAction()
    {
        $iProductID = $this->getParam('pid') ? intval($this->getParam('pid')) : 0;
        if (empty($iProductID)) {
            return $this->showMsg('参数有误', false);
        }
        $sItemID = $this->getParam('id') ? $this->getParam('id') : '';
        if (empty($sItemID)) {
            return $this->showMsg('请先选择单项', false);
        }
        $aProduct = Model_Addtion::getDetail($iProductID);
        if (empty($aProduct)) {
            return $this->showMsg('该产品不存在!', false);
        }
        	
        $aParam['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
        $aItemIDs = explode(',', $sItemID);
        foreach ($aItemIDs as $key => $value) {
        	$aRow = Model_ProductItem::getRow([
        		'where' => [
        			'iProductID' => $iProductID,
        			'iItemID' => $value,
        			'iType'   => Model_ProductItem::ADDTION,
        			'iStatus' => Model_ProductItem::STATUS_VALID
        		]
        	]);
        	if ($aRow) {
        		$aParam['iAutoID'] = $aRow['iAutoID'];
        		$aParam['iStatus'] = Model_ProductItem::STATUS_INVALID;
        		Model_ProductItem::updData($aParam);
        	}
        }

        return $this->showMsg('删除成功！', true);
    }

    public function editGroupAction ()
    {
    	return $this->nextAction();    	
    }

    /**
     * ajax保存
     * @return [bool]
     */
    public function ajaxEditAction ()
    {
    	$data = [
    		'iAddtionID' => 0,
    		'sName'   => '',
    		'sRemark' => ''
    	];

    	$params = $this->getParams();
    	foreach ($data as $key => $value) {
    		if (!isset($params[$key])) {
    			return $this->showMsg('请传入数据', false);
    		}
    		if (!intval($params['iAddtionID'])) {
    			return $this->showMsg('请选择单项', false);
    		}

    		$data[$key] = $params[$key];
    	}

   		$upd = Model_Addtion::updData($data);
		$msg = $upd ? '保存成功' : '保存失败';
		$bool = $upd ? true : false;

		return $this->showMsg($msg, $bool);
    }



	/**
	 * 编辑价格
	 * @return [array]
	 */
	public function editPriceAction ()
	{
		$aStore = [];
    	$iProductID = intval($this->getParam('id'));
    	
    	$type = $this->getParam('type') ? intval($this->getParam('type')) : 4;
    	if (!in_array($type, [4, 5])) {
    		$type = 4;
    	}

    	if ($iProductID) {
    		if ($this->isPost()) {
	 			$aParam['aSupplier'] = $this->getParam('aSupplier') 
				? array_keys($this->getParam('aSupplier')) : [];
				$aParam['sSupplier'] = implode(',', $aParam['aSupplier']);

				$aParam['aCity'] = $this->getParam('aCity') 
				? array_keys($this->getParam('aCity')) : [];
				$aParam['sCity'] = implode(',', $aParam['aCity']);

				$allStore = Model_Store::getAllStore($aParam);
				if ($allStore) {
					foreach ($allStore as $key => $value) {
						$aStoreID[$key] = $value['iStoreID'];
					}
					$param['sStoreID'] = implode(',', $aStoreID);
					$param['sType']    = $type;
					$param['iProductID'] = $iProductID;

				}
				$aStore = Model_ProductStore::getPStoreByIDs($param);
				
				$this->assign('aParam', $aParam);
	    	} else {    		
    			$aStore = Model_ProductStore::getAll([
	    			'where' => [
	    				'iProductID' => $iProductID,
						'iStatus' => Model_Store::STATUS_VALID,
						'iType'   => $type,
					]
	    		]);	    		
	    	}

	    	if ($aStore) {
	    		foreach ($aStore as $key => $value) {
	    			$row = Model_Store::getDetail($value['iStoreID']);
	    			if ($row) {
	    				$aStore[$key]['sSupplier'] = $this->supplier[$row['iSupplierID']];	
	    				$aStore[$key]['sCity'] = $this->city[$row['iCityID']];
	    			}
	    			
	    		}
	    	}
    	}    	
    	$this->assign('aList', $aStore);
    	$this->assign('type', $type);
	}

	/**
     * ajax保存数据
     * @return [bool]
     */
    public function savedAction ()
    {
    	$data = [
    		'iAutoID' => 0,
    		'sCode'   => '',
    		'sCostPrice' => 0,
    		'sMarketPrice' => 0,
    		'sChannelPrice' => 0,
    	];

    	$params = $this->getParams();
    	foreach ($data as $key => $value) {
    		if (!isset($params[$key])) {
    			return $this->showMsg('请传入数据', false);
    		}
    		if (!intval($params['iAutoID'])) {
    			return $this->showMsg('请选择单项', false);
    		}

    		$data[$key] = $params[$key];
    	}

   		$upd = Model_ProductStore::updData($data);
		$msg = $upd ? '保存成功' : '保存失败';
		$bool = $upd ? true : false;

		return $this->showMsg($msg, $bool);
    }


	public function excelimportAction ()
	{
		$params = $this->getParams();
 		if (!$params['id'] || !$params['file']) {
 			return $this->showMsg('文件和加项包不能为空', false);
 		}
 		if (empty($params['type']) || !in_array($params['type'], ['4', '5'])) {
 			$type = 4;
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
				$supplier = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
				$city = $PHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
				if (empty($this->supplierName[$supplier]) || empty($this->cityName[$city])) {
					continue;
				}
				
				$aStore = Model_Store::getAll([
					'where' => [
						'iSupplierID' => $this->supplierName[$supplier],
						'iCityID' => $this->cityName[$city],
						'iStatus' => Model_Store::STATUS_VALID,
					]
				]);
				if ($aStore) {
					foreach ($aStore as $key => $value) {
						$sex = $PHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
						if (!$this->sex[$sex]) {
							continue;
						}

						$store = [];
						$store['iType'] = $type;
						$store['iProductID'] = $id;						
						$store['iStoreID']   = $value['iStoreID'];
						$store['iSex'] = $this->sex[$sex];
						$row = Model_ProductStore::getRow(['where' => $store]);						
						if ($value['iStoreID'] && $row) {
							continue;
						}
						$store['iCreateUserID'] = $this->aCurrUser['iUserID'];
						$store['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];					
						$store['sCode']         = $PHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
						$store['sCostPrice']    = $PHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
						$store['sMarketPrice']  = $PHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
						$store['sChannelPrice'] = $PHPExcel->getActiveSheet()->getCell("E".$i)->getValue();

						Model_ProductStore::addData($store);
					}
				}
			}			

			return $this->showMsg('导入完成', true);
		}
	}

	/**
	 * 服务加项管理
	 * @return [type] [description]
	 */
	public function serviceAction ()
    {
    	$aParam['sKeyword'] = $this->getParam('sKeyword');
		$aParam['sType'] = '3';
		$page = $this->getParam('iPage');

		$aGroup = Model_Addtion::getPageList($aParam, $page);

		$this->assign('aList', $aGroup);
		
		if(isset($aWhere['sKeyword'])) {
			$aParam['sKeyword'] =  $aWhere['sKeyword'];	
		}		
		$this->assign('aParam', $aParam);

    	$this->setMenu(3);
    }

    /**
     * 新建服务加项
     * @return [type] [description]
     */
    public function addsvAction ()
    {
    	if ($this->_request->isPost()) {
            $aAddition = $this->_checkClientData(1, 3);
            if (empty($aAddition)) {
                return null;
            }

            $aAddition['iType'] = 3;
            $aAddition['iCreateUserID'] = $this->aCurrUser['iUserID'];
            $aAddition['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if ($iLastInsertID = Model_Addtion::addData($aAddition)) {
                return $this->showMsg('增加成功！', true);
            } else {
                return $this->showMsg('增加失败！', false);
            }
        }
    }

    /**
     * 新建服务加项
     * @return [type] [description]
     */
    public function editsvAction ()
    {
    	$id = $this->getParam('id');
    	if ($this->_request->isPost()) {
    		$aAddition = $this->_checkClientData(2, 3);
            if (empty($aAddition)) {
                return null;
            }
            
            $aAddition['iType'] = 3;
            $aAddition['iCreateUserID'] = $this->aCurrUser['iUserID'];
            $aAddition['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];

            $upd = Model_Addtion::updData($aAddition);
            if ($upd) {
            	return $this->showMsg('修改成功！', true);
            } else {
                return $this->showMsg('修改失败！', false);
            }

    	} else {
    		$aAdd = Model_Addtion::getDetail($id);
    		if (!$aAdd || $aAdd['iStatus'] < 1) {
    			$aAdd = [];
    		}
    		$this->assign('aAdd', $aAdd);
    	}
    }

	//headermenu
    private function setMenu($iMenu)
    {
        $aMenu = [
            1 => [
                'url' => '/admin/itemap/list',
                'name' => '单项加项',
            ],
            2 => [
                'url' => '/admin/itemap/group',
                'name' => '组合加项',
            ],
            3 => [
                'url' => '/admin/itemap/service',
                'name' => '服务加项',
            ]            
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }

    public function downDemoAction ()
	{
		$filepath = '/data/wwwroot/xcy/51joying/doc/itemgroupdemo.xls';
		$filename = '组合加项管理导入模板.xls';

		Util_File::download($filepath, $filename);
	}


	/**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * * @param int $type 1:单项加项2:组合单项3:服务加项
     * @return array|bool
     */
    public function _checkClientData($iType, $type = 2)
    {
        $aParam = $this->getParams();
        if (!Util_Validate::isLength($aParam['sName'], 1, 50)) {
            return $this->showMsg('组合加项名称为1到50个字！', false);
        }

        //验证产品名是否存在
        $aAddtion = Model_Addtion::getRow([
        	'where' => [
        		'sName' => $aParam['sName'],
        		'iType' => $type,
        		'iStatus' => Model_Addtion::STATUS_VALID,
        	]
        ]);
        if (!empty($aAddtion['iAddtionID']) && $aAddtion['iAddtionID'] != $aParam['iAddtionID']) {
            return $this->showMsg('该产品名已存在！', false);
        }

        return $aParam;
    }
}