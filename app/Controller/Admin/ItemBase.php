<?php

/**
 * Admin后台_单项管理
 * User: xuchuyuan
 * Date: 16/4/24 18:00
 */
class Controller_Admin_ItemBase extends Controller_Admin_Base
{

	const ERROR = '不能为空';

	public $category  = null;
	
	public $parentCat = null;

	public $subCat = null;

	public $supplier = null;

	public $supplierName = null;

	public $store = null;

	public $addition = null;

	public $city = null;

	public $cityName = null;

	public $item = null;

	public $itemMap = null;

	public function actionBefore ()
	{
		parent::actionBefore();

		$aCate = Model_Product_Category::getAll([
			'where' => [
				'iStatus' => Model_Product_Category::STATUS_VALID
			]
		]);
		if ($aCate) {
			foreach ($aCate as $key => $value) {
				if (0 == $value['iParentID']) {
					$this->parentCat[$value['iAutoID']] = $value['sCateName'];
				}
				if (0 < $value['iParentID']) {
					$this->subCat[$value['iAutoID']] = $value['sCateName'];
					$this->category[$value['iParentID']][$value['iAutoID']] = $value['sCateName'];
				}				
			}
		}

		$aSupplier = Model_Type::getOption('supplier');
		if ($aSupplier) {
			foreach ($aSupplier as $key => $value) {
				$this->supplier[$key] = $value;
				$this->supplierName[$value] = $key;
			}
		}
		$aCity = Model_City::getAll([
			'where' => [
				'iStatus' => Model_City::STATUS_VALID
			],
			'order' => 'sPinyin ASC'
		]);
		if ($aCity) {
			foreach ($aCity as $key => $value) {
				$this->city[$value['iCityID']] = $value['sCityName'];
				$this->cityName[$value['sCityName']] = $value['iCityID'];
			}
		}

		$aItem = Model_Item::getAll([
			'where' => [
				'iStatus' => Model_Item::STATUS_VALID
			]
		]);
		if ($aItem) {
			foreach ($aItem as $key => $value) {
				$this->item[$value['sName']] = $value['iItemID'];
				$this->itemMap[$value['iItemID']] = $value['sName'];
			}
		}

		$aStore = Model_Store::getAll([
			'where' => [
				'iStatus' => Model_Store::STATUS_VALID
			]
		]);
		if ($aStore) {
			foreach ($aStore as $key => $value) {
				$this->store[$value['iStoreID']] = $value['sName'];
			}
		}

		$aAddition = Model_Addtion::getAll([
			'where' => [
				'iStatus' => Model_Addtion::STATUS_VALID
			]
		]);
		if ($aAddition) {
			foreach ($aAddition as $key => $value) {
				$this->addition[$value['iAddtionID']] = $value['sName'];
			}
		}

		$this->assign('category', $this->category);
		$this->assign('parentCat', $this->parentCat);
		$this->assign('subCat', $this->subCat);
		$this->assign('supplier', $this->supplier);
		$this->assign('store', $this->store);
		$this->assign('addition', $this->addition);
		$this->assign('city', $this->city);
	}

	protected function _assignUrl()
    {
        $this->assign('productcss',1);
    }

    public function actionAfter()
    {
        parent::actionAfter();
        $this->_assignUrl();
    }
}