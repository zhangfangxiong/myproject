<?php

/**
 * Admin后台_单项管理
 * User: xuchuyuan
 * Date: 16/4/24 18:00
 */
class Controller_Admin_Item extends Controller_Admin_ItemBase
{
	
	public $itemParam = [
		'sName' => [
			'need' => true,
			'type' => 'strval',
			'desc' => '单项名'
		],
		'iParentCat' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '体检大类'
		],
		'iCat' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '单项分类'
		],
		'sMark' => [
			'need' => false,
			'type' => 'strval',
			'desc' => '备注'
		],
		'iCanAdd' => [
			'need' => true,
			'type' => 'intval',
			'desc' => '单项加项'
		],
		'iCanMan' => [
			'need' => false,
			'type' => 'intval',
			'desc' => '男性是否可用'
		],
		'iCanWomanNoMarry' => [
			'need' => false,
			'type' => 'intval',
			'desc' => '未婚女性是否可用'
		],
		'iCanWomanMarry' => [
			'need' => false,
			'type' => 'intval',
			'desc' => '已婚女性是否可用'
		],
	];

	/**
	 * 单项列表
	 * @return [array]
	 */
    public function listAction ()
    {
		$page  = $this->getParam('page') ? intval($this->getParam('page')) : 1;

		$aParam = $this->getParams();
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
        if (!empty($aParam['aCanAdd'])) {
            $aParam['aCanAdd'] = array_keys($aParam['aCanAdd']);
            $aParam[ 'iCanAdd IN' ] = $aParam['aCanAdd'];
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
		foreach ($aList['aList'] as $key => $value) {
			$aList['aList'][$key]['sCost'] = 0;
			$aStore = Model_ProductStore::getAll([
				'where' => [
					'iProductID' => $value['iItemID'],
					'iType' => 1,
					'iStatus' => Model_ProductStore::STATUS_VALID
				]
			]);
			$sort = array(  
			        'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sCostPrice',//排序字段  
			);
			$arrSort = array();  
			if ($aStore) {
				foreach($aStore as $uniqid => $row){  
				    foreach($row as $k => $value){  
				        $arrSort[$k][$uniqid] = $value;  
				    }  
				}			
				   
				array_multisort($arrSort[$sort['field']], constant($sort['direction']), $aStore);
				$aList['aList'][$key]['sCost'] = $aStore[0]['sCostPrice'];
			}
			
		}

		$this->assign('aList', $aList);
		$this->assign('aParam', $aParam);
		$this->assign('iPage',  $page);
    }


    public function addAction ()
    {
    	if ($this->isPost()) {
			$aItem = $this->checkData();
			if (empty($aItem)) {
				return;
			}

			$aItem['sCode'] = Model_Item::initItemCode();	
			$addID = Model_Item::addData($aItem);
			$msg   = $addID ? '新增成功' : '新增失败';	
			$bool  = $addID ? true : false;					

			return $this->showMsg($msg, $bool);
		}

		$url = '/admin/item/list';
		$this->assign('sUrl', $url);
    }

    public function editAction ()
    {
    	if ($this->isPost()) {
			$aItem = $this->checkData(2);
			if (empty($aItem)) {
				return;
			}

			$update = Model_Item::updData($aItem);
			$msg    = $update ? '修改成功' : '修改失败';
			$bool = $update ? true : false;
			
			return $this->showMsg($msg, $bool);
		} else {
			$iItemID = intval($this->getParam('id'));
			if (!$iItemID) {
				return $this->showMsg('ID'.self::ERROR, false);
			}

			$aItem = Model_Item::getDetail($iItemID);
			if (isset($aItem['iStatus']) && $aItem['iStatus'] != Model_Item::STATUS_VALID) {
				$aItem = [];
			}

			$type = intval($this->getParam('type'));
			if (1 == $type) {
				$url = '/admin/itemap/list';
			} else {
				$url = '/admin/item/list';
			}

			$this->assign('sUrl', $url);
			$this->assign('aItem', $aItem);

			$this->setCate($aItem['iParentCat']);
		}
    }

    /**
     * 获取当前大类的子类
     * @param 
     */
    public function setCate($catId)
    {
    	$aSubCate = Model_Product_Category::getAll(['where' => [
    		'iParentID' => $catId,
    		'iStatus' => Model_Product_Category::STATUS_VALID
    	]]);

    	$subCat = [];
    	if ($aSubCate) {
    		foreach ($aSubCate as $key => $value) {
    			$subCat[$value['iAutoID']] = $value['sCateName'];
    		}
    	}
    	$this->assign('subCat', $subCat);
    }


    public function checkData($type = 1)
    {
		$params = $this->getParams();

		if ($type == 2) {
			if (isset($params['iItemID']) && intval($params['iItemID'])) {
				$aItem['iItemID'] = $params['iItemID'];
			} else {
				return $this->showMsg('ID'.self::ERROR, false);
			}
		}

		foreach ($this->itemParam as $key => $value) {
			if ($value['need']) {
				if (!isset($params[$key])) {
					return $this->showMsg($this->itemParam[$key]['desc'].self::ERROR, false);
				}
			}		
		}

		foreach ($params as $key => $value) {
			if (isset($this->itemParam[$key])) {
				$aItem[$key] = $this->itemParam[$key]['type']($value);
				
				if ($this->itemParam[$key]['need'] && !$aItem[$key]) {
					return $this->showMsg($this->itemParam[$key]['desc'].self::ERROR, false);
				}
			}
		}

		$row = Model_Item::getRow(['where' => [
			'sName' => $params['sName'],
			'iStatus' => Model_Item::STATUS_VALID 
		]]);
		
		if ($row) {
			if ($type == 1 || ($type == 2 && $params['iItemID'] != $row['iItemID'])) {
				return $this->showMsg('单项名已存在', false);
			}	
		}

		return $aItem;
    }

    /**
     * 编辑单项价格
     * @return [array]
     */
    public function editPriceAction ()
    {
    	$aStore = [];
    	$iProductID = intval($this->getParam('id'));
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
					$param['sType'] = '1';
					$param['iProductID'] = $iProductID;

				}
				$aStore = Model_ProductStore::getPStoreByIDs($param);
				
				$this->assign('aParam', $aParam);
	    	} else {    		
    			$aStore = Model_ProductStore::getAll([
	    			'where' => [
	    				'iProductID' => $iProductID,
						'iStatus' => Model_Store::STATUS_VALID,
						'iType'   => 1,
					]
	    		]);
	    	}
    	}    	

    	$this->assign('aList', $aStore);
    }


    /**
     * ajax保存数据
     * @return [bool]
     */
    public function savedAction ()
    {
    	$data = [
    		'iAutoID' => 0,
    		'sCode' => '',
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


    /**
     * 导入价格
     * @return
     */
    public function importAction ()
    {
    	if ($this->isPost()) {
    		$aParam['sType'] = '1';

 			$aParam['aSupplier'] = $this->getParam('aSupplier') 
			? array_keys($this->getParam('aSupplier')) : [];
			$aParam['sSupplier'] = implode(',', $aParam['aSupplier']);

			$aParam['aCity'] = $this->getParam('aCity') 
			? array_keys($this->getParam('aCity')) : [];
			$aParam['sCity'] = implode(',', $aParam['aCity']);

			$iStore = $this->getParam('iStore');
			if($iStore == 'on') {
				$aParam['iStore'] = 1;
			}

			$aStore = Model_Store::getAllStore($aParam);
			
			$this->assign('aParam', $aParam);
    	} else {
    		$aStore = Model_Store::getAll([
    			'where' => [
					'iStatus' => Model_Store::STATUS_VALID,
					'iType'   => 1
				]
    		]);   		
    	}

    	$aIds = [];
		if($aStore) {
			foreach ($aStore as $key => $value) {
				$aIds[] = $value['iStoreID'];
			}
		}
		$sIds = implode(',', $aIds);

		$this->assign('aList', $aStore);
		$this->assign('sIds', $sIds);
    }

    /**
     * 导入价格
     * @return
     */
    public function excelImportAction ()
    {
    	$params = $this->getParams();
 		if (!$params['ids'] || !$params['file']) {
 			return $this->showMsg('文件和门店不能为空', false);
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
			$aIds = explode(',', $params['ids']);
			foreach ($aIds as $key => $value) {
				for ($i = 2; $i <= $allRow; $i++) { //第1行是表头,从第2行开始读取
					$sItemName = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
					if (empty($this->item[$sItemName])) {
						continue;
					}

					$store = [];
					$store['iProductID'] = $this->item[$sItemName];
					$store['iType'] = 1;
					$store['iStoreID'] = $value;

					$row = Model_ProductStore::getRow(['where' => $store]);
					if ($store['iStoreID'] && $row) {
						$store['iAutoID'] = $row['iAutoID'];
						$store['iCreateUserID'] = $this->aCurrUser['iUserID'];
						$store['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];				
						$store['sCode'] = $PHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
						$store['sCostPrice']    = $PHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
						$store['sMarketPrice']  = $PHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
						$store['sChannelPrice'] = $PHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
						Model_ProductStore::updData($store);					
						continue;
					}

					$store['iCreateUserID'] = $this->aCurrUser['iUserID'];
					$store['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];				
					$store['sCode'] = $PHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
					$store['sCostPrice']    = $PHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
					$store['sMarketPrice']  = $PHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
					$store['sChannelPrice'] = $PHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
					Model_ProductStore::addData($store);					
				}
			}

			return $this->showMsg('导入完成', true);
		}
    }

    /**
     * 单项名称列表
     * @return [type] [description]
     */
    public function nameListAction ()
    {
    	$page  = $this->getParam('page') ? intval($this->getParam('page')) : 1;
		
		$aParam['aParentCat'] = $this->getParam('aParentCat') 
			? array_keys($this->getParam('aParentCat')) : [];
		$aParam['sParentCat'] = implode(',', $aParam['aParentCat']);

		$aParam['aCat'] = $this->getParam('aCat') 
			? array_keys($this->getParam('aCat')) : [];
		$aParam['sCat'] = implode(',', $aParam['aCat']);
		$aParam['sKeyword'] = $this->getParam('sKeyword');

		$aList = Model_Item::getPageList($aParam, $page);
		foreach ($aList['aList'] as $key => $value) {
			$allName = Model_Itemname::getAll(['where' => [
				'iItemID' => $value['iItemID'],
				'iStatus' => Model_Itemname::STATUS_VALID
			]]);
			if ($allName) {				
				foreach ($allName as $k => $val) {
					$aList['aList'][$key]['sSupplierSetName'][$val['iSupplierID']] = $val['sItemName'];
				}				
			} else {
				foreach ($this->supplier as $kk => $vv) {
					$aList['aList'][$key]['sSupplierSetName'][$kk] = $value['sName'];
				}
			}
		}
		
		$this->assign('aList', $aList);
		$this->assign('aParam', $aParam);
		$this->assign('iPage',  $page);
		$this->assign('aSupplier', $this->supplier);
    }

    public function savenameAction ()
    {
    	$params = $this->getParams();
    	if (!$params['itemId']) {
    		return $this->showMsg('没有单项ID', false);
    	}
    	if (!$params['array']) {
    		return $this->showMsg('没有修改名称', false);
    	}
    	if ($params['array']) {    		
    		foreach ($params['array'] as $key => $value) {
    			if (trim($value) && $this->supplier[$key]) {
    				$row = Model_Itemname::getRow(['where' => [
	    				'iItemID' => $params['itemId'],
	    				'iSupplierID' => $key,
	    			]]);

	    			if (!$row) {
	    				$data['iItemID'] = $params['itemId'];
    					$data['iSupplierID'] = $key;
    					$data['sItemName'] = $value;
    					Model_Itemname::addData($data);
	    			} else {
	    				$data['iAutoID'] = $row['iAutoID'];
	    				$data['iItemID'] = $params['itemId'];
    					$data['iSupplierID'] = $key;
    					$data['sItemName'] = $value;
	    				Model_Itemname::updData($data);
	    			}
    			}
    		}
    	}
    	return $this->showMsg('修改名称成功', true);
    }

    /**
     * 导入名称
     * @return
     */
    public function excelnameAction ()
    {
    	$params = $this->getParams();
 		if (!$params['file']) {
 			return $this->showMsg('文件不能为空', false);
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
			$maxCol = $currentSheet->getHighestColumn();
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($maxCol); 			
			for ($col = 1; $col <= $highestColumnIndex-1; $col++) {
				$supplier[$col] = $PHPExcel->getActiveSheet()->getCellByColumnAndRow($col, 1)->getValue();
			}
			
			for ($i = 2; $i <= $allRow; $i++) { //第1行是表头,从第2行开始读取
				$data = [];
				$sItemName = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
				if (empty($this->item[$sItemName])) {
					continue;
				}
				for ($k = 0; $k <= $highestColumnIndex-1; $k++) {
					if ($k == 0) {
						$sStandard = $PHPExcel->getActiveSheet()->getCellByColumnAndRow($k, $i)->getValue();
						if ($this->item[$sStandard]) {
							$data['iItemID'] = $this->item[$sStandard];
							$data['sName'] = $sStandard;							
						} else {
							continue;
						}
					} else {
						if (!$this->supplierName[$supplier[$k]]) {
							continue;
						}
						$data['iSupplierID'] = $this->supplierName[$supplier[$k]];
						$data['sItemName'] = $PHPExcel->getActiveSheet()->getCellByColumnAndRow($k, $i)->getValue();
						$row = Model_Itemname::getRow(['where' => [
							'iItemID' => $data['iItemID'],
							'iSupplierID' => $this->supplierName[$supplier[$k]],
							'iStatus' => Model_Itemname::STATUS_VALID
						]]);
						if (!$row) {
							Model_Itemname::addData($data);	
						}					
					}					
				}	
			}
			
			return $this->showMsg('导入完成', true);
		}
    }

    public function downDemoAction ()
	{
		$filepath = '/data/wwwroot/xcy/51joying/doc/itemprice.xls';
		$filename = '单项价格导入模板.xls';

		$type = $this->getParam('type');
		if ($type == 2) {
			$filepath = '/data/wwwroot/xcy/51joying/doc/itemnamedemo.xls';
			$filename = '导入单项名称.xls';
		}

		Util_File::download($filepath, $filename);
	}

}