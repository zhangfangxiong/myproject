<?php

/**
 * 分类维护
 */
class Controller_Admin_Category extends Controller_Admin_Base
{

	const  ERROR = '不能为空';

	public $category = null;

	public $cateParam = [
		'sCateName' => [
			'need' => true,
			'type' => 0,
			'desc' => '分类名称'
		],
		'iParentID' => [
			'need' => false,
			'type' => 1,
			'desc' => '从属大类'
		],
		'sRemark' => [
			'need' => false,
			'type' => 0,
			'desc' => '检查意义'
		]
	];

	public function actionBefore () 
	{
		parent::actionBefore();
		
		$where = [
			'iParentID' => 0,
    		'iStatus'   => Model_Product_Category::STATUS_VALID        		
    	];
    	
    	$aCategory = Model_Product_Category::getAll(['where' => $where]);
    	foreach ($aCategory as $key => $value) {
    		$this->category[$value['iAutoID']] = $value['sCateName'];
    	}

    	$this->assign('category', $this->category);
	}

	/**
	 * 分类列表
	 * @return [array]
	 */
    public function listAction ()
    {
    	$where = [
    		'iStatus' => Model_Product_Category::STATUS_VALID        		
    	];

        $parentId = $this->getParam('type') ? intval($this->getParam('type')) : 0;
        $page = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if ($parentId) {
        	$where['iParentID >'] = 0;        	
        } else {
        	$where['iParentID'] = 0;
        }

        $aCategory = Model_Product_Category::getList($where, $page);
        if ($aCategory['aList']) {
        	foreach ($aCategory['aList'] as $key => $value) {
        		if ($value['iParentID']) {
        			$aCategory['aList'][$key]['sBelong'] = $this->category[$value['iParentID']];
        			$aCategory['aList'][$key]['iCount'] = Model_Item::getCnt(['where' => [
	        			'iCat' => $value['iAutoID'],
	        			'iParentCat' => $value['iParentID'],
	        			'iStatus' => Model_Item::STATUS_VALID
	        		]]);	
        		} else {
        			$aCategory['aList'][$key]['iCount'] = Model_Item::getCnt(['where' => [
	        			'iParentCat' => $value['iAutoID'],
	        			'iStatus' => Model_Item::STATUS_VALID
	        		]]);	
        		}        		
        	}
        }

        $this->setMenu($parentId);
        $this->assign('aCategory', $aCategory);
    }

    /*
	 * excelmenu
	 */
    private function setMenu($iMenu)
    {
        $aMenu = [
            0 => [
                'url' => '/admin/category/list',
                'name' => '大类维护',
            ],
            1 => [
                'url' => '/admin/category/list/type/1',
                'name' => '分类维护',
            ]            
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }


    /**
     * 新增类别
     */
    public function addAction ()
    {
    	$parentId = $this->getParam('type')	? intval($this->getParam('type')) : 0;
    	if ($this->isPost()) {    		
    		$aCate = $this->checkParam(1, $parentId);
    		if (empty($aCate)) {
				return;
			}

			$where = [
				'sCateName' => $aCate['sCateName'],
				'iStatus' => Model_Product_Category::STATUS_VALID
			];
			if ($parentId) {
	        	$where['iParentID >'] = 0;        	
	        } else {
	        	$where['iParentID'] = 0;
	        }

			$row  = Model_Product_Category::getRow(['where' => $where]);
			if ($row) {
				$msg = '类别已经存在';
			} else {
				$addID = Model_Product_Category::addData($aCate);
				$msg   = $addID ? '新增成功' : '新增失败';			
			}

			return $this->showMsg($msg, true);
    	} else {

    	}

    	$this->assign('menu', $parentId);
    }


    public function editAction()
    {
    	$parentId = $this->getParam('type')	? intval($this->getParam('type')) : 0;
    	if ($this->isPost()) {
    		$aCate = $this->checkParam(2, $parentId);
			if (empty($aCate)) {
				return;
			}

			$where = [
				'sCateName' => $aCate['sCateName'],
				'iStatus' => Model_Product_Category::STATUS_VALID
			];
			if ($parentId) {
	        	$where['iParentID >'] = 0;        	
	        } else {
	        	$where['iParentID'] = 0;
	        }

			$row  = Model_Product_Category::getRow(['where' => $where]);
			if ($row && $row['iAutoID'] != $aCate['iAutoID']) {
				$bool = false;
				$msg  = '类别已经存在';
			} else {
				$update = Model_Product_Category::updData($aCate);
				$msg    = $update ? '修改成功' : '修改失败';	
				$bool   = $update ? true : false;		
			}
			
			return $this->showMsg($msg, $bool);
    	} else {
    		$iCateID = $this->getParam('id') ? intval($this->getParam('id')) : 0;
			if (!$iCateID) {
				return $this->showMsg('类别ID'.self::ERROR, false);
			}

			$aCate = Model_Product_Category::getDetail($iCateID);
			if (isset($aCate['iStatus']) && $aCate['iStatus'] != Model_Product_Category::STATUS_VALID) {
				$aCate = [];
			}

			$this->assign('aCate', $aCate);
    	}

    	$this->assign('menu', $parentId);
    }

    /**
     * 删除
     * @return [bool]
     */
    public function delAction ()
    {
    	$id = intval($this->getParam('id'));
    	$type = intval($this->getParam('type'));
    	if (!$id) {
    		return $this->showMsg('类别ID'.self::ERROR, false);
    	}

    	if (!$type) {
    		$aCate = Model_Product_Category::getRow([
	    		'where' => [
	    			'iStatus' => Model_Product_Category::STATUS_VALID,
	    			'iParentID' => $id
	    		]
	    	]);
	    	if ($aCate) {
	    		return $this->showMsg('不能删除大类,下面还有分类', false);
	    	}
    	}

    	$where  = [
    		'iAutoID' => $id,
    		'iStatus' => Model_Product_Category::STATUS_INVALID,
    	];

    	$update = Model_Product_Category::updData($where);
    	$msg    = $update ? '修改成功' : '修改失败';	
		$bool   = $update ? true : false;

		return $this->showMsg($msg, $bool);
    }


    /**
	 * 检测参数
	 * @param  [int] type (1＝新增,2=修改)
	 * @param  [int] parentId (1＝大类,2=分类)
	 * @return [array]
	 */
	private function checkParam($type = 1, $parentId = 0)
	{
		$params = $this->getParams();
		if ($type == 2) {
			if (isset($params['iAutoID']) && intval($params['iAutoID'])) {
				$aCate['iAutoID'] = $params['iAutoID'];
			} else {
				return $this->showMsg('类别ID'.self::ERROR, false);
			}
		}

		foreach ($this->cateParam as $key => $value) {
			if ($value['need']) {
				if (!isset($params[$key])) {
					return $this->showMsg($this->cateParam[$key]['desc'].self::ERROR, false);
				}
			} else {
				if ($parentId && !isset($params[$key])) {
					return $this->showMsg($this->cateParam[$key]['desc'].self::ERROR, false);
				}
			}		
		}

		foreach ($params as $key => $value) {
			if (isset($this->cateParam[$key])) {
				$aCate[$key] = $value;
				if ($this->cateParam[$key]['need'] && !$aCate[$key]) {
					return $this->showMsg($this->cateParam[$key]['desc'].self::ERROR, false);
				}
			}
		}

		return $aCate;
	}

	public function getSubCateAction ()
	{
		$iCateID = $this->getParam('iCateID');
        $aData = Model_Product_Category::getAll([
        	'where' => [
        		'iParentID' => $iCateID, 
        		'iStatus' => Model_Product_Category::STATUS_VALID
        	]
        ]);

        return $this->showMsg($aData, true);
	}

	public function getRemarkAction ()
	{
		$iCateID = $this->getParam('iCateID');
        $aData = Model_Product_Category::getDetail($iCateID);

        return $this->showMsg($aData, true);
	}
}