<?php

class Model_Message extends Model_Base
{

    const TABLE_NAME = 't_message';
    
    /**
     * 取得Class信息
     * @param unknown $sClass
     * @param string $sField
     * @throws Yaf_Exception
     * @return unknown
     */
    public static function getClass($sClass, $sField = null)
    {
    	if (empty($sClass)) {
    		$aRet = self::query("SELECT * FROM t_message_setting WHERE iStatus=1 LIMIT 1", 'row');
    		$sClass = $aRet['sClass'];
    	} else {
    		$aRet = self::query("SELECT * FROM t_message_setting WHERE sClass='$sClass' AND iStatus=1 LIMIT 1", 'row');
    		if (empty($aRet)) {
    			throw new Yaf_Exception(__CLASS__ . '::' . __FUNCTION__ . " $sClass not exists!");
    		}
    	}
    
    	if (empty($sField)) {
    		return $aRet;
    	} else {
    		return $aRet[$sField];
    	}
    }
    
    /**
     * 取得文章的作者
     * @param unknown $sClass
     * @return Ambigous <Ambigous, number, multitype:multitype:, multitype:unknown >
     */
    public static function getAuthor($sClass, $iUserID)
    {
    	$iUserID = intval($iUserID);
    	$aClass = self::getClass($sClass);
    	$sModel = $aClass['sUserModel'];
    	return $sModel::getAvatarAndName($iUserID);
    }
    
    /**
     * 取得文章的作者
     * @param unknown $sClass
     * @return Ambigous <Ambigous, number, multitype:multitype:, multitype:unknown >
     */
    public static function getAuthors($sClass)
    {
    	$aClass = self::getClass($sClass);
    	if (empty($aClass['sReplyAuthorIDS'])) {
    		return array();
    	}
    	
    	$sModel = $aClass['sUserModel'];
    	return $sModel::getAuthors($aClass['sReplyAuthorIDS']);
    }
    
    
    
    /**
     * 新增数据
     *
     * @param array $aData
     * @return int/false
     */
    public static function addData ($aData)
    {
    	if (! isset($aData['iStatus'])) {
    		$aClass = self::getClass($aData['sClass']);
    		if ($aClass['iIsReview']) {
    			$aData['iStatus'] = 2;
    		} else {
            	$aData['iStatus'] = self::STATUS_VALID;
    		}
        }
        
    	$aAuthor = self::getAuthor($aData['sClass'], $aData['iUserID']);
    	$aData['sUserName'] = $aAuthor['sName'];
    	$aData['sAvatar'] = $aAuthor['sAvatar'];
    	return parent::addData($aData);
    }
    
    /**
     * 获取主键数据
     *
     * @param int $iPKID            
     * @return array/null
     */
    public static function getDetail ($iPKID) 
    {
    	$aRow = parent::getDetail($iPKID);
    	if (empty($aRow)) {
    		return $aRow;
    	}
    	
    	$aClass = self::getClass($aRow['sClass']);
    	$sModel = $aClass['sTargetModel'];
    	$aRow['aTarget'] = $sModel::getDetail($aRow['iTargetID']);
    	$aRow['sTargetTitle'] = empty($aRow['aTarget']) ? '' : $aRow['aTarget'][$aClass['sTargetField']];
    	
    	return $aRow;
    }

    /**
     * 按分页取文章数据
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
    	$aClass = self::getClass($aParam['sClass']);
    	//$aParam['iStatus'] = self::STATUS_VALID;
    	$sWhere = self::_buildWhere($aParam);
    	$iPage = max($iPage, 1);
    	$sOrder = 'ORDER BY ' . $sOrder;
    	$sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
    
    	$sSQL = 'SELECT * FROM ' . self::TABLE_NAME . $sWhere . ' ' . $sOrder . ' ' . $sLimit;
    	//         echo $sSQL;exit;
    	$aRet['aList'] = self::getOrm()->query($sSQL);
    	$sModel = $aClass['sTargetModel'];
    	foreach ($aRet['aList'] as &$v) {
    		$v['aTarget'] = $sModel::getDetail($v['iTargetID']);
    		$v['sTargetTitle'] = empty($v['aTarget']) ? '' : $v['aTarget'][$aClass['sTargetField']];
    	}
    	
    	if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
    		$aRet['iTotal'] = count($aRet['aList']);
    		$aRet['aPager'] = null;
    	} else {
    		unset($aParam['limit'], $aParam['order']);
    		$sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . $sWhere;
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
        if (isset($aParam['iMessageID']) && ! empty($aParam['iMessageID'])) {
            $aWhere[] = 'iMessageID=' . $aParam['iMessageID'];
        }
        if (isset($aParam['iMessageID NOT IN']) && ! empty($aParam['iMessageID NOT IN'])) {
            $iMessageID = implode(',', $aParam['iMessageID NOT IN']);
            $aWhere[] = 'iMessageID NOT IN (' . $iMessageID . ')';
        }
        if (isset($aParam['iReplyID']) && ! empty($aParam['iReplyID'])) {
        	$aWhere[] = 'iReplyID=' . $aParam['iReplyID'];
        }
        if (isset($aParam['iTargetID']) && ! empty($aParam['iTargetID'])) {
        	$aWhere[] = 'iTargetID=' . $aParam['iTargetID'];
        }
        if (! empty($aParam['sKeyword'])) {
            $aWhere[] = 'sContent LIKE \'%' . addslashes($aParam['sKeyword']) . '%\'';
        }
        if (! empty($aParam['sClass'])) {
            $aWhere[] = 'sClass="' . addslashes($aParam['sClass']) . '"';
        }
        if (isset($aParam['iStatus']) && $aParam['iStatus'] != - 1 && $aParam['iStatus'] !== '') {
            $aWhere[] = 'iStatus=' . $aParam['iStatus'];
        } else {
        	$aWhere[] = 'iStatus!=0';
        }
        // 如果有作者ID就不用作者名
        if (! empty($aParam['iUserID']) && $aParam['iUserID'] > 0) {
            $aWhere[] = 'iUserID=' . $aParam['iUserID'];
        } elseif (! empty($aParam['sUserName'])) {
            $aWhere[] = 'sUserName="' . addslashes($aParam['sUserName']) . '"';
        }
        // 时间换算
        if (! empty($aParam['iStartTime'])) {
            $aWhere[] = 'iCreateTime >=' . strtotime($aParam['iCreateTime']);
        }
        if (! empty($aParam['iEndTime'])) {
            $aWhere[] = 'iCreateTime <=' . strtotime($aParam['iCreateTime']);
        }
        if (empty($aWhere)) {
            $sWhere = '';
        } else {
            $sWhere = ' WHERE ' . join(' AND ', $aWhere);
        }
        
        return $sWhere;
    }
}