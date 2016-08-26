<?php

class Model_Company_Department extends Model_Base
{
	
	const TABLE_NAME = 't_company_department';

	public static function checkExist ($enterpriseId, $dept)
	{
		$param = [
			'where' => [
				'iEnterpriseID' => intval($enterpriseId),
				'sDeptName' => trim($dept['sDeptName']),
				'iStatus'   => self::STATUS_VALID
			]
		];
		
		return self::getRow($param);
	}

	/**
	 * 新增部门model
	 */
	public static function addDept ($dept)
	{
		if ($dept) {
			$dept['iStatus'] = self::STATUS_VALID;
			return self::addData($dept);
		}

		return 0;
	}

	/**
	 * 修改部门model
	 */
	public static function updDept ($dept)
	{
		if ($dept) {
			$dept['iStatus'] = self::STATUS_VALID;
			return self::updData($dept);
		}

		return 0;
	}

	/**
	 * 取得所有部门的ID => Name数组
	 * @param int $iType
	 * @param null $iStatus ==null时为启用和启用的都返回
	 * @return int
	 */
	public static function getPairDepartment ($iEnterpriseID, $iStatus=1)
	{
		$aParam['iEnterpriseID'] = $iEnterpriseID;
		$aParam['iStatus'] = $iStatus;
		return self::getPair($aParam, 'iAutoID', 'sDeptName');
	}

	public static function getTree ($aWhere = [])
    {
    	if (isset($aWhere['iParentID'])) unset($aWhere['iParentID']);
    	if (isset($aWhere['iAutoID'])) unset($aWhere['iAutoID']);
        
        $aList = self::getAll(array(
            'where' => $aWhere,
            'order' => 'iAutoID ASC'
        ));        
        
        // 排序及整理
        $aParentID = array();
        $aOrder = array();
        $aData = array();
        foreach ($aList as $aMenu) {
            $aParentID[] = $aMenu['iParentID'];
            $aOrder[] = 0;
            unset($aMenu['iStatus'], $aMenu['iCreateTime'], $aMenu['iUpdateTime']);
            $aData[] = $aMenu;
        }
        unset($aList);
        array_multisort($aParentID, SORT_NUMERIC, SORT_ASC, $aOrder, SORT_NUMERIC, SORT_ASC, $aData);

        // 整理父节点
        $aParent = [];
        foreach ($aData as $aMenu) {            
            $aParent[$aMenu['iParentID']][] = $aMenu;
        }
        unset($aMenu);

        return self::_buildTree($aParent, 0, 0);
    }

    private static function _buildTree ($aParent, $iParentID, $iLevel)
    {
        $aTree = array();
        if (isset($aParent[$iParentID])) {        	
            foreach ($aParent[$iParentID] as $aMenu) {             	
            	$aMenu['iLevel'] = $iLevel;      
                $aMenu['aChild'] = self::_buildTree($aParent, $aMenu['iAutoID'], $iLevel + 1);
                $aTree[] = $aMenu;
            }
        }

        return $aTree;
    }

    //根据部门id获取所有子孙部门id
    public static function getAllSubDeptIDs ($iDeptID)
    {
        $array[] = $iDeptID;
    	do {
            $ids = '';
            $temp = self::getAll(['where' => ['iParentID' => $iDeptID, 'iStatus' => self::STATUS_VALID]]);
            foreach ($temp as $v) {
                $array[] = $v['iAutoID'];
                $ids .= ',' . $v['iAutoID'];
            }
            $ids = substr($ids, 1, strlen($ids));
            $iDeptID = $ids;
        } while (!empty($temp));
        
        $ids = implode(',', $array);

    	return $ids;
    }
}