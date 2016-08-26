<?php

class Model_Physical_Product extends Model_Base
{

    const TABLE_NAME = 't_physical_product';

    const TYPE_PRODUCT = 3;

    const TYPE_PRODUCT_PLAN = 4;

    const STATUS_UNCONFIRM = -99; //未确认的状态

    const STATUS_DELETE = 5; //已作废删除的员工

    /**
     * 
     * @param unknown $aParam
     */
    public static function getStat2 ($aParam)
    {
    	$aWhere = array('p.iStatus>0');
    	if (! empty($aParam['sStartDate'])) {
    		$aWhere[] = 'p.sStartDate>="' . $aParam['sStartDate'] . '"';
    	}
    	if (! empty($aParam['sEndDate'])) {
    		$aWhere[] = 'p.sStartDate<="' . $aParam['sEndDate'] . '"';
    	}
    	
        $sWhere = ' WHERE ' . join(' AND ', $aWhere);
    	$sSQL = 'SELECT c.sRealName as sCoName, COUNT(*) iUserCnt, SUM(p.sCost) sCost FROM ' . self::TABLE_NAME . ' AS p
    			 LEFT JOIN t_user AS c ON p.iHRID=c.iUserID
    			' . $sWhere . ' GROUP BY iHRID';
    	return self::query($sSQL);
    }
    

    /**
     * 供应商数据汇总
     * @param unknown $aParam
     */
    public static function getStat3 ($aParam)
    {
    	$aWhere = array('p.iStatus>0');
    	if (! empty($aParam['sStartDate'])) {
    		$aWhere[] = 'p.iOrderTime>="' . strtotime($aParam['sStartDate']) . '"';
    	}
    	if (! empty($aParam['sEndDate'])) {
    		$aWhere[] = 'p.iOrderTime<="' . strtotime($aParam['sEndDate']) . '"';
    	}
    	 
    	$sWhere = ' WHERE ' . join(' AND ', $aWhere);
    	$sSQL = 'SELECT s.iSupplierID, t.sTypeName as sSupplierName, COUNT(*) iUserCnt, SUM(p.sCost) sCost FROM ' . self::TABLE_NAME . ' AS p
    			 LEFT JOIN t_store AS s ON p.iStoreID=s.iStoreID
    		     LEFT JOIN t_type AS t ON t.iTypeID=s.iSupplierID
    			' . $sWhere . ' GROUP BY s.iSupplierID';
    	$aList = self::query($sSQL);
    	
    	$sWhere .= ' AND p.iBalance=1';
    	$sSQL = 'SELECT s.iSupplierID, SUM(p.sCost) sCost FROM ' . self::TABLE_NAME . ' AS p
    			 LEFT JOIN t_store AS s ON p.iStoreID=s.iStoreID
    			' . $sWhere . ' GROUP BY s.iSupplierID';
    	$aBalance = self::query($sSQL, 'pair');
    	foreach ($aList as $k => $v) {
    		$aList[$k]['bCost'] = isset($aBalance[$v['iSupplierID']]) ? $aBalance[$v['iSupplierID']] : 0;
    	}
    	
    	return $aList; 
    }
    
    /**
     * 供应商数据汇总
     * @param unknown $aParam
     */
    public static function getStat4 ($aParam)
    {
    	$aWhere = array('p.iStatus>0');
    	if (! empty($aParam['sStartDate'])) {
    		$aWhere[] = 'p.iOrderTime>="' . strtotime($aParam['sStartDate']) . '"';
    	}
    	if (! empty($aParam['sEndDate'])) {
    		$aWhere[] = 'p.iOrderTime<="' . strtotime($aParam['sEndDate']) . '"';
    	}
    
    	$sWhere = ' WHERE ' . join(' AND ', $aWhere);
    	$sSQL = 'SELECT p.iStoreID, s.sName as sStoreName, COUNT(*) iUserCnt, SUM(p.sCost) sCost FROM ' . self::TABLE_NAME . ' AS p
    			 LEFT JOIN t_store AS s ON p.iStoreID=s.iStoreID
    			' . $sWhere . ' GROUP BY p.iStoreID';
    	$aList = self::query($sSQL);
    	 
    	$sWhere .= ' AND p.iBalance=1';
    	$sSQL = 'SELECT p.iStoreID, SUM(p.sCost) sCost FROM ' . self::TABLE_NAME . ' AS p
    			' . $sWhere . ' GROUP BY p.iStoreID';
    	$aBalance = self::query($sSQL, 'pair');
    	foreach ($aList as $k => $v) {
    		$aList[$k]['bCost'] = isset($aBalance[$v['iStoreID']]) ? $aBalance[$v['iStoreID']] : 0;
    	}
    	 
    	return $aList;
    }
    

    /**
     * 体检数据核对
     * @param unknown $aParam
     * @param unknown $iPage
     * @param string $sOrder
     * @param number $iPageSize
     * @param string $sUrl
     * @param unknown $aArg
     * @return Ambigous <NULL, boolean, string, multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getCheckList ($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
    	$sWhere = self::_buildWhere($aParam);
    	$iPage = max($iPage, 1);
    	$sOrder = 'ORDER BY ' . $sOrder;
    	$sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
    
    	$sSQL = 'SELECT p.*,pr.sProductName,s.sName as sStroeName,
    	                u.sUserName,u.sMobile,u.sEmail,u.sRealName,
    	                c.sUserName as sCoCode,c.sRealName as sCoName,
    	                pp.sPlanName FROM ' . self::TABLE_NAME . ' AS p
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN t_product AS pr ON p.iProductID=pr.iProductID
    			 LEFT JOIN t_user AS c ON p.iHRID=c.iUserID
    			 LEFT JOIN t_physical_plan AS pp ON pp.iAutoID=p.iPlanID 
    			 LEFT JOIN t_user AS u ON s.iUserID=u.iUserID AND u.iType=3
    			' . $sWhere . ' ' . $sOrder . ' ' . $sLimit;
    	$aRet['aList'] = self::getOrm()->query($sSQL);
    	if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
    		$aRet['iTotal'] = count($aRet['aList']);
    		$aRet['aPager'] = null;
    	} else {
    		unset($aParam['limit'], $aParam['order']);
    		$sCntSQL = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME . ' AS p
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN t_user AS u ON s.iUserID=u.iUserID AND u.iType=3 ' . $sWhere;
    		$ret = self::getOrm()->query($sCntSQL);
    		$aRet['iTotal'] = $ret[0]['total'];
    		$aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam);
    	}
    
    	return $aRet;
    }
    
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
    public static function getStat1 ($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
    	$sWhere = self::_buildWhere($aParam);
    	$iPage = max($iPage, 1);
    	$sOrder = 'ORDER BY ' . $sOrder;
    	$sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
    
    	$sSQL = 'SELECT p.*,pr.sProductName,s.sName as sStroeName,u.sRealName as sUserName,c.sRealName as sCoName FROM ' . self::TABLE_NAME . ' AS p 
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN t_product AS pr ON p.iProductID=pr.iProductID
    			 LEFT JOIN t_user AS c ON p.iHRID=c.iUserID
    			 LEFT JOIN t_user AS u ON s.iUserID=u.iUserID AND u.iType=3
    			' . $sWhere . ' ' . $sOrder . ' ' . $sLimit;
    	$aRet['aList'] = self::getOrm()->query($sSQL);
    	if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
    		$aRet['iTotal'] = count($aRet['aList']);
    		$aRet['aPager'] = null;
    	} else {
    		unset($aParam['limit'], $aParam['order']);
    		$sCntSQL = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME . ' AS p 
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN t_user AS u ON s.iUserID=u.iUserID AND u.iType=3 ' . $sWhere;
    		$ret = self::getOrm()->query($sCntSQL);
    		$aRet['iTotal'] = $ret[0]['total'];
    		$aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam);
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
            $aWhere[] = 'p.iAutoID=' . $aParam['iAutoID'];
        }
        if (isset($aParam['iAutoID NOT IN']) && ! empty($aParam['iAutoID NOT IN'])) {
            $iAutoID = implode(',', $aParam['iAutoID NOT IN']);
            $aWhere[] = 'p.iAutoID NOT IN (' . $iAutoID . ')';
        }
        if (! empty($aParam['iUserID'])) {
            $aWhere[] = 'p.iUserID=' . intval($aParam['iUserID']) . '';
        }
        if (! empty($aParam['sCoCode'])) {
        	$aWhere[] = 'c.sUserName="' . addslashes($aParam['sCoCode']) . '"';
        }
        if (! empty($aParam['sRealName'])) {
            $aWhere[] = 'u.sRealName="' . addslashes($aParam['sRealName']) . '"';
        }
        if (! empty($aParam['sUserCode'])) {
            $aWhere[] = 'u.sUserName="' . addslashes($aParam['sUserCode']) . '"';
        }
        
        if (! empty($aParam['sIdentityCard'])) {
            $aWhere[] = 'u.sIdentityCard="' . addslashes($aParam['sIdentityCard']) . '"';
        }
        if (! empty($aParam['iPreStatus'])) {
            $aWhere[] = 'p.iPreStatus=' . intval($aParam['iPreStatus']) . '';
        }
        if (isset($aParam['sPhysicalNumber']) && $aParam['sPhysicalNumber'] != -1) {
        	$aWhere[] = 'p.sPhysicalNumber="' . addslashes($aParam['sPhysicalNumber']) . '"';
        }
        if (isset($aParam['iSendMsg']) && $aParam['iSendMsg'] != -1) {
        	$aWhere[] = 'p.iSendMsg=' . intval($aParam['iSendMsg']) . '';
        }
        if (isset($aParam['iSendEMail']) && $aParam['iSendEMail'] != -1) {
        	$aWhere[] = 'p.iSendEMail=' . intval($aParam['iSendEMail']) . '';
        }
        if (! empty($aParam['sOptStartDate'])) {
        	$aWhere[] = 'p.iUpdateTime>=' . strtotime($aParam['sOptStartDate']) . '';
        }
        if (! empty($aParam['sOptEndDate'])) {
        	$aWhere[] = 'p.iUpdateTime<=' . strtotime($aParam['sOptEndDate']) . '';
        }
        if (! empty($aParam['iStatus'])) {
            $aWhere[] = 'p.iStatus=' . intval($aParam['iStatus']) . '';
        }
        if (! empty($aParam['iPhysicalType'])) {
            $aWhere[] = 'p.iPhysicalType=' . intval($aParam['iPhysicalType']) . '';
        }
        if (! empty($aParam['iPhysicalTime'])) {
            $aWhere[] = 'p.iPhysicalTime="' . $aParam['iPhysicalTime'] . '"';
        }
        if (! empty($aParam['iStoreID'])) {
            $aWhere[] = 'p.iStoreID=' . intval($aParam['iStoreID']) . '';
        }
        if (! empty($aParam['sStoreName'])) {
            $aWhere[] = 's.sName="' . addslashes($aParam['sStoreName']) . '"';
        }
        if (! empty($aParam['iPlatID'])) {
            $aWhere[] = 'p.iPlatID=' . intval($aParam['iPlatID']) . '';
        }
        if (empty($aWhere)) {
            $sWhere = '';
        } else {
            $sWhere = ' WHERE ' . join(' AND ', $aWhere);
        }
        
        return $sWhere;
    }

	/**
	 * 生成卡号（自动生成的虚拟卡号,实物卡在card表中）
	 * @return string
	 */
    public static function initPhysicalNumber()
    {
        //生成规则未定
		$sPrefix = 'p';
        $sProductCode = $sPrefix.Util_Tools::passwdGen(20,1);
        if(self::getCardByCode($sProductCode)) {
            self::initPhysicalNumber();
        }
        return $sProductCode;
    }

    public static function getCardByCode($sCode, $iStatus = null)
    {
        $aWhere = array(
            'sPhysicalNumber' => $sCode,
        );
        if ($iStatus===null) {
            $aWhere['iStatus >'] = 0;
        } else {
            $aWhere['iStatus'] = $iStatus;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

	/**
	 * 更新订单中产品的支付状态(该方法未使用缓存，以后加缓存的时候要注意)
	 * @param $iOrderID 订单ID
	 * @param $iProductID 产品ID
	 * @param $iPayType 付款方式(1=公司付款,2=个人付款)
	 * @param $iPayChannel 付款渠道(0=无,1=支付宝,2=微信支付,3=银联支付)
	 * @param $sPayOrderID 第三方付款订单ID
	 * @param $iPayMoney 个人付款金额
	 * @param $iPayStatus 付款状态(0=未付款,1=已付款)
	 */
	public static function updPayStatusByOrderID($iOrderID,$iProductID,$iPayType,$iPayChannel,$sPayOrderID,$iPayMoney,$iPayStatus)
	{
		$sSql = "UPDATE ".self::TABLE_NAME." SET iPayType=".$iPayType.",iPayChannel=".$iPayChannel.",sPayOrderID='".$sPayOrderID."',iPayMoney='".$iPayMoney."',iPayStatus=".$iPayStatus." WHERE iOrderID = ".$iOrderID." AND iProductID =".$iProductID;
		return self::query($sSql);
	}

	/**
	 * 获取用户的所有体检卡（已付款的体检卡）
	 */
	public static function getUserCard($iUserID)
	{
		$aWhere = array(
			'iType' => self::TYPE_PRODUCT,
			'iUserID' => $iUserID,
			'iStatus' => 1,
		);
		return self::getAll(array(
			'where' => $aWhere
		));
	}
}