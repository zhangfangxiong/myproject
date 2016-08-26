<?php

class Model_Product extends Model_Base
{
    const TABLE_NAME = 't_product';
    const TYPE_BASE = 1;
    const TYPE_EXPAND = 2;

    /**
     * 取得基础产品列表
     */
    public static function getBaseList()
    {
        return self::getAll(array(
            'where' => array(
                'iStatus>' => 0,
                'iParentID' => 0,
                'iType' => self::TYPE_BASE
            )
        ));
    }

    /**
     * 获取某基础产品衍生的拓展产品
     */
    public static function getExplandData($aProductID)
    {
        return self::getAll(array(
            'where' => array(
                'iStatus>' => 0,
                'iParentID IN' => $aProductID,
                'iType' => self::TYPE_EXPAND
            )
        ));
    }

    /**
     * 合并产品基本信息(价格统一)
     * @param $aProduct
     * @param $aUser
     * @param int $iChannelType 1:公司渠道，2：个人渠道
     */
    public static function mergeBase(&$aProduct, $aUser, $iChannelType)
    {
        $aUserProductBase = Model_UserProductBase::getUserProductBase($aProduct['iProductID'], $aUser['iCreateUserID'], $iChannelType, $aUser['iChannel']);
        $aProduct = array_merge($aProduct, $aUserProductBase);
    }

    /**
     * 生成产品编号（自动生成的）
     * @param string $prefix 前缀，基础产品为p,拓展产品为ep
     * @return string
     */
    public static function initProductCode($prefix = 0)
    {
        // 生成规则未定？
        // p-46634562
        $prefix = $prefix == 0 ? 'p' : 'ep';
        $sProductCode = $prefix . Util_Tools::passwdGen(8, 1);
        if (self::getProductByCode($sProductCode)) {
            self::initProductCode();
        }
        return $sProductCode;
    }

    // 根据产品编号获取产品
    public static function getProductByCode($sProductCode, $iStatus = null)
    {
        $aWhere = array(
            'sProductCode' => $sProductCode
        );
        if ($iStatus === null) {
            $aWhere ['iStatus >'] = 0;
        } else {
            $aWhere ['iStatus'] = $iStatus;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    // 根据产品名称获取产品
    public static function getProductByName($sProductName, $iStatus = null)
    {
        $aWhere = array(
            'sProductName' => $sProductName
        );
        if ($iStatus === null) {
            $aWhere ['iStatus >'] = 0;
        } else {
            $aWhere ['iStatus'] = $iStatus;
        }
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    /**
     * 获取用户可见列表
     * @param $iUserID
     * @param $iChannelType
     * @param $iChannelID
     * @param array $sProductID 逗号隔开
     * @param bool $iIsCard 是否体检卡
     * @return array|mixed
     */
    public static function getAllUserProduct($iUserID, $iChannelType, $iChannelID, $sProductID = '',$iIsCard = false,$sKey = '')
    {
        $sSQL = '
        SELECT c.sRemark,c.sProductCode,c.sImage,c.sProductName,c.iPCard,a.iAutoID,a.iProductID,a.iType,a.iChannelID,a.iViewRange,a.iStatus,b.iUserID,d.sAlias,d.iUserCanPrice,IFNULL(IFNULL(d.iManPrice,a.iManPrice),c.iManPrice) AS iManPrice,IFNULL(IFNULL(d.iWomanPrice1,a.iWomanPrice1),c.iWomanPrice1) AS iWomanPrice1,IFNULL(IFNULL(d.iWomanPrice2,a.iWomanPrice2),c.iWomanPrice2) AS iWomanPrice2
FROM `t_product_channel` a
LEFT JOIN `t_product_channel_viewlist` b ON a.iAutoID = b.iProductChannelID AND a.iViewRange = b.iViewRange AND b.iUserID = ' . $iUserID . ' AND b.iStatus = 1
LEFT JOIN `t_product` c ON c.iProductID = a.iProductID  AND c.iStatus = 1
LEFT JOIN `t_user_product_base` d ON d.iType=a.iType AND d.iChannelID=a.iChannelID AND d.iProductID=a.iProductID AND d.iUserID = ' . $iUserID . ' AND d.iStatus = 1
WHERE
	a.iType = ' . $iChannelType . ' AND a.iChannelID = ' . $iChannelID . ' AND a.iStatus = 1
AND (
	a.iViewRange = 0
	OR (a.iViewRange = 1 AND a.iAutoID LIKE ( SELECT iProductChannelID FROM t_product_channel_viewlist WHERE iProductChannelID = a.iAutoID AND iViewRange = 1 AND iStatus = 1 AND iUserID = ' . $iUserID . ' ))
	OR (a.iViewRange = 2 AND ' . $iUserID . ' NOT IN (SELECT iUserID FROM t_product_channel_viewlist WHERE iProductChannelID = a.iAutoID AND iStatus = 1 AND iViewRange = 2))
)';
        if ($iChannelType == 1) {
            $sSQL .= ' AND c.iCanCompany =1';
        } elseif ($iChannelType == 2) {
            $sSQL .= ' AND c.iCanIndividual =1';
        }

        if (!empty ($sProductID)) {
            $sSQL .= ' AND c.iProductID IN (' . $sProductID . ')';
        }

        if (!empty ($iIsCard)) {
            $sSQL .= ' AND c.iPCard >0';
        }

        $aData = self::query($sSQL);
        $aTmp = [];

        foreach ($aData as $key => &$value) {
            //价格特殊处理
            if ($value['iManPrice'] == $value['iWomanPrice1'] && $value['iManPrice'] == $value['iWomanPrice2'])
            {
                $value['iPrice'] = $value['iManPrice'];
            } else {
                $aPrice = array($value['iManPrice'],$value['iWomanPrice1'],$value['iWomanPrice2']);
                sort($aPrice);
                $value['iPrice'] = $aPrice[0].'-'.$aPrice[2];
            }

            if ($sKey) {
                $aTmp[$value[$sKey]] = $value;
            }
        }
        if ($sKey) {
            return $aTmp;
        }

        return $aData;
    }




    //获取用户能看到的产品列表
    public static function getUserViewProductList($iUserID, $iChannelType, $iChannelID, $iPage,$iIsCard = false,$sProductID = '', $sOrder = 'iUpdateTime DESC', $iPageSize = 10, $aParam)
    {
        $iPage = max(1, $iPage);
        $sSQL = '
SELECT c.sRemark,c.sProductCode,c.sImage,c.sProductName,c.iPCard,a.iAutoID,a.iProductID,a.iType,a.iChannelID,a.iViewRange,a.iStatus,b.iUserID,d.sAlias,d.iUserCanPrice,IFNULL(IFNULL(d.iManPrice,a.iManPrice),c.iManPrice) AS iManPrice,IFNULL(IFNULL(d.iWomanPrice1,a.iWomanPrice1),c.iWomanPrice1) AS iWomanPrice1,IFNULL(IFNULL(d.iWomanPrice2,a.iWomanPrice2),c.iWomanPrice2) AS iWomanPrice2
FROM `t_product_channel` a
LEFT JOIN `t_product_channel_viewlist` b ON a.iAutoID = b.iProductChannelID AND a.iViewRange = b.iViewRange AND b.iUserID = ' . $iUserID . ' AND b.iStatus = 1
LEFT JOIN `t_product` c ON c.iProductID = a.iProductID AND c.iStatus = 1
LEFT JOIN `t_user_product_base` d ON d.iType=a.iType AND d.iChannelID=a.iChannelID AND d.iProductID=a.iProductID AND d.iUserID = ' . $iUserID . ' AND d.iStatus = 1
WHERE
	a.iType = ' . $iChannelType . ' AND a.iChannelID = ' . $iChannelID . ' AND a.iStatus = 1
AND (
	a.iViewRange = 0
	OR (a.iViewRange = 1 AND a.iAutoID LIKE ( SELECT iProductChannelID FROM t_product_channel_viewlist WHERE iProductChannelID = a.iAutoID AND iViewRange = 1 AND iStatus = 1 AND iUserID = ' . $iUserID . ' ))
	OR (a.iViewRange = 2 AND ' . $iUserID . ' NOT IN (SELECT iUserID FROM t_product_channel_viewlist WHERE iProductChannelID = a.iAutoID AND iStatus = 1 AND iViewRange = 2))
)';
        $sCntSQL = '
         SELECT COUNT(*) AS total FROM `t_product_channel` a
LEFT JOIN `t_product_channel_viewlist` b ON a.iAutoID = b.iProductChannelID AND a.iViewRange = b.iViewRange AND b.iUserID = ' . $iUserID . ' AND b.iStatus = 1
LEFT JOIN `t_product` c ON c.iProductID = a.iProductID  AND c.iStatus = 1
LEFT JOIN `t_user_product_base` d ON d.iType=a.iType AND d.iChannelID=a.iChannelID AND d.iProductID=a.iProductID AND d.iUserID = ' . $iUserID . ' AND d.iStatus = 1
WHERE
	a.iType = ' . $iChannelType . ' AND a.iChannelID = ' . $iChannelID . ' AND a.iStatus = 1
AND (
	a.iViewRange = 0
	OR (a.iViewRange = 1 AND a.iAutoID LIKE ( SELECT iProductChannelID FROM t_product_channel_viewlist WHERE iProductChannelID = a.iAutoID AND iViewRange = 1 AND iStatus = 1 AND iUserID = ' . $iUserID . ' ))
	OR (a.iViewRange = 2 AND ' . $iUserID . ' NOT IN (SELECT iUserID FROM t_product_channel_viewlist WHERE iProductChannelID = a.iAutoID AND iStatus = 1 AND iViewRange = 2))
)';
        if (!empty ($sProductID)) {
            $sSQL .= ' AND c.iProductID NOT IN (' . $sProductID . ')';
            $sCntSQL .= ' AND c.iProductID NOT IN (' . $sProductID . ')';
        }

        if ($iChannelType == 1) {
            $sSQL .= ' AND c.iCanCompany =1';
            $sCntSQL .= ' AND c.iCanCompany =1';
        } elseif ($iChannelType == 2) {
            $sSQL .= ' AND c.iCanIndividual =1';
            $sCntSQL .= ' AND c.iCanIndividual =1';
        }

        if (!empty ($iIsCard)) {
            $sSQL .= ' AND c.iPCard >0';
        }

        $sSQL .= ' Order by c.' . $sOrder;
        $sSQL .= ' Limit ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
        $aData = self::getOrm()->query($sSQL);

        foreach ($aData as $key => &$value) {
            //价格特殊处理
            if ($value['iManPrice'] == $value['iWomanPrice1'] && $value['iManPrice'] == $value['iWomanPrice2'])
            {
                $value['iPrice'] = $value['iManPrice'];
            } else {
                $aPrice = array($value['iManPrice'],$value['iWomanPrice1'],$value['iWomanPrice2']);
                sort($aPrice);
                $value['iPrice'] = $aPrice[0].'-'.$aPrice[2];
            }
        }
        $aRet ['aList'] = $aData;
        if ($iPage == 1 && count($aRet ['aList']) < $iPageSize) {
            $aRet ['iTotal'] = count($aRet ['aList']);
            $aRet ['aPager'] = null;
        } else {
            $ret = self::getOrm()->query($sCntSQL);
            $aRet ['iTotal'] = $ret [0] ['total'];
            $aRet ['aPager'] = Util_Page::getPage($aRet ['iTotal'], $iPage, $iPageSize, '', self::_getNewsPageParam($aParam));
        }
        return $aRet;
    }

    public static function getPageList($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20)
    {
        $iPage = max($iPage, 1);
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE 1';
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE 1';

        if (isset ($aParam ['sKeyword']) && !empty ($aParam ['sKeyword'])) {
            $sSQL .= " AND (sProductName LIKE '%" . addslashes($aParam ['sKeyword']) . "%' OR sProductCode LIKE '%" . addslashes($aParam ['sKeyword']) . "%')";
            $sCntSQL .= " AND (sProductName LIKE '%" . addslashes($aParam ['sKeyword']) . "%' OR sProductCode LIKE '%" . addslashes($aParam ['sKeyword']) . "%')";
        }
        if (!empty ($aParam ['sProductID'])) {
            $sSQL .= ' AND iProductID IN (' . $aParam ['sProductID'] . ')';
            $sCntSQL .= ' AND iProductID IN (' . $aParam ['sProductID'] . ')';
        }
        if (!empty($aParam['iType'])) {
            $sSQL .= ' AND iType=' . $aParam['iType'];
            $sCntSQL .= ' AND iType=' . $aParam['iType'];
        }

        $sSQL .= ' Order by ' . $sOrder;
        $sSQL .= ' Limit ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
        $aRet ['aList'] = self::getOrm()->query($sSQL);
        if ($iPage == 1 && count($aRet ['aList']) < $iPageSize) {
            $aRet ['iTotal'] = count($aRet ['aList']);
            $aRet ['aPager'] = null;
        } else {
            unset ($aParam ['limit'], $aParam ['order']);
            $ret = self::getOrm()->query($sCntSQL);
            $aRet ['iTotal'] = $ret [0] ['total'];
            $aRet ['aPager'] = Util_Page::getPage($aRet ['iTotal'], $iPage, $iPageSize, '', self::_getNewsPageParam($aParam));
        }
        return $aRet;
    }

    private static function _getNewsPageParam(&$aParam)
    {
        $pageParam = array(
            'sKeyword' => isset ($aParam ['sKeyword']) ? $aParam ['sKeyword'] : '',
            'id' => isset ($aParam ['id']) ? $aParam ['id'] : ''
        );
        return $pageParam;
    }

    /**
     * 获取某产品的某渠道已添加客户(不分页)
     * @param $iProductID
     * @param $iType
     * @param string $iChannelID
     * @return array|mixed
     */
    public static function getUserViewlist($iProductID, $iType, $iChannelID)
    {
        $sSql = 'SELECT c.sRealName,c.sUserName,b.iAutoID,b.iUserID FROM ' . Model_ProductChannel::TABLE_NAME . ' a,' . Model_ProductViewRange::TABLE_NAME . ' b,' . Model_User::TABLE_NAME . ' c WHERE a.iProductID=' . $iProductID . ' AND a.iType=' . $iType . ' AND a.iChannelID=' . $iChannelID . ' AND a.iAutoID=b.iProductChannelID AND a.iStatus=1 AND b.iStatus=1 AND b.iUserID=c.iUserID AND a.iViewRange = b.iViewRange ORDER BY b.iCreateTime DESC';
        return self::query($sSql);
    }

    /**
     * 获取产品可视客户（分页）
     * @param $iProductID
     * @param $aParam
     * @return array
     */
    public static function getViewList($iProductID, $iType, $iChannel, $aParam = array())
    {
        $aData = [];
        $aProduct = self::getDetail($iProductID);
        if (empty($aProduct)) {
            return [];
        }
        if ($iType == Model_ProductChannel::TYPE_COMPANY && empty($aProduct['iCanCompany'])) {
            return [];
        } elseif ($iType == Model_ProductChannel::TYPE_INDIVIDUAL && empty($aProduct['iCanIndividual'])) {
            return [];
        }
        $aChannel = Model_ProductChannel::getData($iProductID, $iType, $iChannel);
        if (empty($aChannel)) {
            return [];
        }
        $aUserParam['iType'] = 2;

        if (!empty($aParam['sKeyword'])) {
            $aUserParam['sWhere'] = '(sUserName="' . $aParam['sKeyword'] . '" OR sRealName LIKE "%' . $aParam['sKeyword'] . '%")';
        }
        $iPage = !empty($aParam['page']) ? $aParam['page'] : 1;
        $iPageSize = !empty($aParam['pagesize']) ? $aParam['pagesize'] : 20;
        if (empty($aChannel['iViewRange'])) {//全部可视
            $aUserParam['iStatus >'] = 0;
            $aUserParam['iChannel'] = $iChannel;
            $aData = Model_User::getList($aUserParam, $iPage, 'iCreateTime Desc', $iPageSize);
        } elseif ($aChannel['iViewRange'] == 1) {//部分可视
            $aCanViewUserParam['iProductChannelID'] = $aChannel['iAutoID'];
            $aCanViewUserParam['iViewRange'] = $aChannel['iViewRange'];
            $aCanViewUserParam['iStatus'] = 1;
            $aData = Model_ProductViewRange::getList($aCanViewUserParam, $iPage, 'iCreateTime Desc', $iPageSize);
            if (!empty($aData['aList'])) {
                //组装客户数据
                $iUserIDs = [];
                foreach ($aData['aList'] as $key => $value) {
                    $iUserIDs[] = $value['iUserID'];
                }
                $aUserParam['where']['iStatus >'] = 0;
                $aUserParam['where']['iUserID IN'] = $iUserIDs;
                $aCanViewUser = Model_User::getAll($aUserParam, true);
                foreach ($aData['aList'] as $key => $value) {
                    if (!empty($aCanViewUser[$value['iUserID']])) {
                        $aData['aList'][$key]['sRealName'] = $aCanViewUser[$value['iUserID']]['sRealName'];
                        $aData['aList'][$key]['sUserName'] = $aCanViewUser[$value['iUserID']]['sUserName'];
                    } else {
                        unset($aData['aList'][$key]);
                    }
                }
            }
        } elseif ($aChannel['iViewRange'] == 2) {//部分不可视
            $aUserParam['iStatus >'] = 0;
            $aUserParam['iChannel'] = $iChannel;

            //获取黑名单中用户
            $aNoUserParam['iProductChannelID'] = $aChannel['iAutoID'];
            $aNoUserParam['iViewRange'] = $aChannel['iViewRange'];
            $aNoUserParam['iStatus'] = 1;
            $aNoUser = Model_ProductViewRange::getPair($aNoUserParam, 'iAutoID', 'iUserID');
            if (!empty($aNoUser)) {
                $aUserParam['iUserID NOT IN'] = array_values($aNoUser);
            }
            $aData = Model_User::getList($aUserParam, $iPage, 'iCreateTime Desc', $iPageSize);
        }
        return $aData;
    }
}