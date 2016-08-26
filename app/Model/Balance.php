<?php

class Model_Balance extends Model_Base
{
    const TABLE_NAME = 't_balance';

    /**
     * 按分页取预给信息数据
     * @param unknown $aParam
     * @param unknown $iPage
     * @param string $sOrder
     * @param number $iPageSize
     * @param string $sUrl
     * @param unknown $aArg
     * @return Ambigous <NULL, boolean, string, multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getPageList ($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
    	$sWhere = self::_buildWhere($aParam);
    	$iPage = max($iPage, 1);
    	$sOrder = 'ORDER BY ' . $sOrder;
    	$sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
    
    	$sSQL = 'SELECT b.*,s.sTypeName as sSupplierName FROM ' . self::TABLE_NAME . ' AS b
    			 LEFT JOIN t_type AS s ON s.iTypeID=b.iSupplierID
    			' . $sWhere . ' ' . $sOrder . ' ' . $sLimit;
    	$aRet['aList'] = self::getOrm()->query($sSQL);
    	if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
    		$aRet['iTotal'] = count($aRet['aList']);
    		$aRet['aPager'] = null;
    	} else {
    		unset($aParam['limit'], $aParam['order']);
    		$sCntSQL = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME . ' AS b
    			 LEFT JOIN t_type AS s ON s.iTypeID=b.iSupplierID' . $sWhere;
    		$ret = self::getOrm()->query($sCntSQL);
    		$aRet['iTotal'] = $ret[0]['total'];
    		$aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam); // update by cjj 2015-02-13 分页增加query 参数
    	}
    
    	return $aRet;
    }
    
    /**
     * 构建搜索条件
     * @param unknown $aParam
     */
    public static function _buildWhere($aParam)
    {
    	$aWhere = array();
    	if (isset($aParam['iAutoID']) && ! empty($aParam['iAutoID'])) {
    		$aWhere[] = 'b.iAutoID=' . $aParam['iAutoID'];
    	}
    	if (isset($aParam['iAutoID NOT IN']) && ! empty($aParam['iAutoID NOT IN'])) {
    		$iAutoID = implode(',', $aParam['iAutoID NOT IN']);
    		$aWhere[] = 'b.iAutoID NOT IN (' . $iAutoID . ')';
    	}
    	if (! empty($aParam['iSupplierID'])) {
    		$aWhere[] = 'b.iSupplierID=' . intval($aParam['iSupplierID']) . '';
    	}
    	if (! empty($aParam['iStoreID'])) {
    		$aWhere[] = 'b.iStoreID=' . intval($aParam['iStoreID']) . '';
    	}
    	if (! empty($aParam['sBalanceCode'])) {
    		$aWhere[] = 'b.sBalanceCode="' . addslashes($aParam['sBalanceCode']) . '"';
    	}
    	if (! empty($aParam['iStatus'])) {
    		$aWhere[] = 'b.iStatus=' . intval($aParam['iStatus']) . '';
    	}
    	if (empty($aWhere)) {
    		$sWhere = '';
    	} else {
    		$sWhere = ' WHERE ' . join(' AND ', $aWhere);
    	}
    
    	return $sWhere;
    }

     /*
     * 生成订单编号（自动生成的）
     */
    public static function initBalanceCode()
    {
        //生成规则未定
        $sProductCode = 'G'.Util_Tools::passwdGen(10,1);
        if(self::getItemByCode($sProductCode)) {
            self::initBalanceCode();
        }
        return $sProductCode;
    }

    public static function getItemByCode($sCode, $iStatus = null)
    {
        $aWhere = array(
            'sBalanceCode' => $sCode,
        );
        if ($iStatus === null) {
            $aWhere['iStatus >'] = 0;
        } else {
            $aWhere['iStatus'] = $iStatus;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }
}