<?php

class Model_Item extends Model_Base
{
    const TABLE_NAME = 't_item';
    const INPORTROWNAME = '单项名称';//导入的格式，第一行必须叫单项名称

    public static function getPageList($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20)
    {
        $iPage = max($iPage, 1);
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';        
        if (!empty($aParam['sParentCat'])) {
            $sSQL .= ' AND iParentCat IN ' . '('.$aParam['sParentCat'].')';
            $sCntSQL .= ' AND iParentCat IN ' . '('.$aParam['sParentCat'].')';
        }

        if (!empty($aParam['sCat'])) {
            $sSQL .= ' AND iCat IN ' . '('.$aParam['sCat'].')';
            $sCntSQL .= ' AND iCat IN ' . '('.$aParam['sCat'].')';
        }

        if (!empty($aParam['sCategory'])) {
            $sSQL .= ' AND iCat IN ' . '('.$aParam['sCategory'].')';
            $sCntSQL .= ' AND iCat IN ' . '('.$aParam['sCategory'].')';
        }

        if (!empty($aParam['sCanMan']) && $aParam['sCanMan'] != '-1') {
            $sSQL .= ' AND iCanMan IN ' . '('.$aParam['sCanMan'].')';
            $sCntSQL .= ' AND iCanMan IN ' . '('.$aParam['sCanMan'].')';
        }

        if (!empty($aParam['sCanWomanNoMarry']) && $aParam['sCanWomanNoMarry'] != '-1') {
            $sSQL .= ' AND iCanWomanNoMarry IN ' . '('.$aParam['sCanWomanNoMarry'].')';
            $sCntSQL .= ' AND iCanWomanNoMarry IN ' . '('.$aParam['sCanWomanNoMarry'].')';
        }

        if (!empty($aParam['sCanWomanMarry']) && $aParam['sCanWomanMarry'] != '-1') {
            $sSQL .= ' AND iCanWomanMarry IN ' . '('.$aParam['sCanWomanMarry'].')';
            $sCntSQL .= ' AND iCanWomanMarry IN ' . '('.$aParam['sCanWomanMarry'].')';
        }

        if (!empty($aParam['sCanadd']) && $aParam['sCanadd'] != '-1') {
            $sSQL .= ' AND iCanadd IN ' . '('.$aParam['sCanadd'].')';
            $sCntSQL .= ' AND iCanadd IN ' . '('.$aParam['sCanadd'].')';
        }

        //不选产品已包含的单项
        if (!empty($aParam['sItemID'])) {
            $sSQL .= ' AND iItemID NOT IN ' . '('.$aParam['sItemID'].')';
            $sCntSQL .= ' AND iItemID NOT IN ' . '('.$aParam['sItemID'].')';
        }

        if (isset($aParam['sKeyword']) && !empty($aParam['sKeyword'])) {
            $sSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword'])."%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
            $sCntSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword']) . "%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
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
            'sParentCat' => isset($aParam['sParentCat']) ? $aParam['sParentCat'] : '',
            'sCat' => isset($aParam['sCat']) ? $aParam['sCat'] : '',
            'sCanMan' => isset($aParam['sCanMan']) ? $aParam['sCanMan'] : '',
            'sCanWomanNoMarry' => isset($aParam['sCanWomanNoMarry']) ? $aParam['sCanWomanNoMarry'] : '',
            'sCanWomanMarry' => isset($aParam['sCanWomanMarry']) ? $aParam['sCanWomanMarry'] : '',
            'sCanadd' => isset($aParam['sCanadd']) ? $aParam['sCanadd'] : '',
            'sKeyword' => isset($aParam['sKeyword']) ? $aParam['sKeyword'] : '',
        );
        return $pageParam;
    }

    /*
     * 生成产品编号（自动生成的）
     */
    public static function initItemCode()
    {
        //生成规则未定？
        //p-46634562
        $sProductCode = 'I'.Util_Tools::passwdGen(8,1);
        if(self::getItemByCode($sProductCode)) {
            self::initItemCode();
        }
        return $sProductCode;
    }

    public static function getItemByCode($sCode, $iStatus = null)
    {
        $aWhere = array(
            'sCode' => $sCode,
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

    public static function getAllItem($aParam)
    {
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus > 0';
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus > 0';

        if (isset($aParam['sKeyword']) && !empty($aParam['sKeyword'])) {
            $sSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword'])."%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
            $sCntSQL .= " AND (sName LIKE '%".addslashes($aParam['sKeyword']) . "%' OR sCode LIKE '%".addslashes($aParam['sKeyword'])."%')";
        }

        return self::query($sSQL);
    }

    /**
     * 单项按类别分组
     * @param [type] $itemIds [description]
     */
    public static function setGroupByCategory ($itemIds,$fDetail = false)
    {
        $aItems = self::getListByPKIDs($itemIds);
        $aGroup = [];
        $aGroupDetail = [];
        if ($aItems) {
            foreach ($aItems as $key => $value) {
                $aGroup[$value['iParentCat']][$value['iCat']][] = $value['iItemID'];
                if ($fDetail) {
                    $aCat1 = Model_Product_Category::getDetail($value['iParentCat']);
                    $aCat2 = Model_Product_Category::getDetail($value['iCat']);
                    $aGroupDetail[$aCat1['sCateName']][$aCat2['sCateName']]['sRemark'] 
                    = $value['sRemark'] ? $value['sRemark'] : $aCat1['sRemark'];
                    $aGroupDetail[$aCat1['sCateName']][$aCat2['sCateName']]['aItemNames'][] = $value['sName'];
                    $aGroupDetail[$aCat1['sCateName']][$aCat2['sCateName']]['iCanMan'] = $value['iCanMan'];
                    $aGroupDetail[$aCat1['sCateName']][$aCat2['sCateName']]['iCanWomanMarry'] = $value['iCanWomanMarry'];
                    $aGroupDetail[$aCat1['sCateName']][$aCat2['sCateName']]['iCanWomanNoMarry'] = $value['iCanWomanNoMarry'];
                }
            }
        }
        return $fDetail? $aGroupDetail : $aGroup;
    }

    //产品或加项包含的所有单项
    public static function getProductItemsByID($iProductID, $iStatus = null)
    {
        $sSql = "select i.* from ". self::TABLE_NAME. " AS i left join t_product_item pi on i.iItemID = pi.iItemID where pi.iProductID = $iProductID and pi.iType = 2";

        if ($iStatus === null) {
            $sSql .= ' and pi.iStatus > 0';
        } else {
            $sSql .= ' and pi.iStatus = '. $iStatus;
        }

        return self::query($sSql);
    }

}