<?php

class Model_Addtion extends Model_Base
{
    const TABLE_NAME = 't_addtion';


    //生成产品编号（自动生成的）
    public static function initAddtionCode()
    {
        //生成规则未定？
        //p-46634562
        $sProductCode = 'A'.Util_Tools::passwdGen(8,1);
        if(self::getAddtionByCode($sProductCode)) {
            self::initAddtionCode();
        }
        return $sProductCode;
    }

    //根据产品编号获取产品
    public static function getAddtionByCode($sProductCode,$iStatus = null)
    {
        $aWhere = array(
            'sCode' => $sProductCode,
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

    public static function getPageList($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20)
    {
        $iPage = max($iPage, 1);
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';

        if (!empty($aParam['sType'])) {
            $sSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
            $sCntSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
        }
        //不选产品已包含的单项
        if (!empty($aParam['sAddtionID'])) {
            $sSQL .= ' AND iAddtionID NOT IN ' . '('.$aParam['sAddtionID'].')';
            $sCntSQL .= ' AND iAddtionID NOT IN ' . '('.$aParam['sAddtionID'].')';
        }

        if (!empty($aParam['sKeyword'])) {
            $sSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword'])."%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
            $sCntSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword']) . "%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
        }   

        if (!empty($aParam['sProductID'])) {
            $sSQL .= ' OR iAddtionID IN ' . '('.$aParam['sProductID'].')';
            $sCntSQL .= ' OR iAddtionID IN ' . '('.$aParam['sProductID'].')';
        }     

        $sSQL .= ' Order by ' . $sOrder;
        $sSQL .= ' Limit ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;
        $aRet['aList'] = self::getOrm()->query($sSQL);
        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            unset($aParam['limit'], $aParam['order']);
            $ret = self::getOrm()->query($sCntSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', self::_getNewsPageParam($aParam));
        }
        return $aRet;
    }

    private static function _getNewsPageParam(&$aParam)
    {
        $pageParam = array(
            'sType' => isset($aParam['sCategory']) ? $aParam['sType'] : '',
            'sKeyword' => isset($aParam['sKeyword']) ? $aParam['sKeyword'] : '',
            'id' => isset($aParam['id']) ? $aParam['id'] : '',
        );
        return $pageParam;
    }

    public static function getAllAddition($aParam) {
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';

        if (!empty($aParam['sType'])) {
            $sSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
            $sCntSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
        }
        if (!empty($aParam['sKeyword'])) {
            $sSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword'])."%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
            $sCntSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword']) . "%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
        }  

        return self::query($sSQL);
    }

    //产品包含的所有加项
    public static function getProductAddtions($iProductID, $iStatus = null)
    {
        $sSql = "select a.* from ". self::TABLE_NAME. " AS a left join t_product_addtion pa on a.iAddtionID = pa.iAddtionID where pa.iProductID = $iProductID and pa.iType = 2";

        if ($iStatus === null) {
            $sSql .= ' and pa.iStatus > 0';
        } else {
            $sSql .= ' and pa.iStatus = '. $iStatus;
        }

        return self::query($sSql);
    }
}