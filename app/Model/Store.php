<?php

class Model_Store extends Model_Base
{
    const TABLE_NAME = 't_store';

    const INPORTROWSUPPERNAME = '供应商名称';//导入的格式，第一行必须叫门店名称
    const INPORTROWCITY = '城市';//导入的格式，第一行必须叫门店名称
    const INPORTROWNAME = '门店名称';//导入的格式，第一行必须叫门店名称
    const INPORTROWSEX = '性别';//导入的格式，第二行必须叫性别
    const INPORTROWCODE = '产品代码';//导入的格式，第三行必须叫产品代码
    const INPORTROWPRICE1 = '供应商结算价';//导入的格式，第三行必须叫供应商结算价
    const INPORTROWPRICE2 = '渠道结算价';//导入的格式，第三行必须叫渠道结算价

    //生成门店编号（自动生成的）
    public static function initStoreCode()
    {
        //生成规则未定？
        //p-46634562
        $sProductCode = 'S'.Util_Tools::passwdGen(8,1);
        if(self::getStoreByCode($sProductCode)) {
            self::initStoreCode();
        }
        return $sProductCode;
    }

    //根据门店编号获取产品
    public static function getStoreByStoreName($sName,$iStatus = null)
    {
        $aWhere = array(
            'sName' => $sName,
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

    //根据门店编号获取产品
    public static function getStoreByCode($sCode,$iStatus = null)
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

    //获取门店所包含的所有分类
    public static function getSupplierByStores($aStoreIDs)
    {
        if (!is_array($aStoreIDs)) {
            $aStoreIDs = explode(',',$aStoreIDs);
        }
        $aWhere['iStoreID IN'] = $aStoreIDs;
        $aWhere['iStatus'] = 1;
        $aParam['where'] = $aWhere;
        $aParam['group'] = 'iSupplierID';
        return self::getPair($aParam,'iStoreID','iSupplierID');
    }

    public static function getData($aParam,$sOrder = 'iUpdateTime DESC')
    {
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';
        if (!empty($aParam['sCity'])) {
            $sSQL .= ' AND iCityID IN ' . '('.$aParam['sCity'].')';
        }

        if (!empty($aParam['sSupplier'])) {
            $sSQL .= ' AND iSupplierID IN ' . '('.$aParam['sSupplier'].')';
        }

        $sSQL .= ' Order by ' . $sOrder;
        $aData = self::getOrm()->query($sSQL);
        return $aData;
    }

    public static function getPageList($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20)
    {
        $iPage = max($iPage, 1);
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus>0';

        if (!empty($aParam['sCity'])) {
            $sSQL .= ' AND iCityID IN ' . '('.$aParam['sCity'].')';
            $sCntSQL .= ' AND iCityID IN ' . '('.$aParam['sCity'].')';
        }

        if (!empty($aParam['sSupplier'])) {
            $sSQL .= ' AND iSupplierID IN ' . '('.$aParam['sSupplier'].')';
            $sCntSQL .= ' AND iSupplierID IN ' . '('.$aParam['sSupplier'].')';
        }

        if (!empty($aParam['sType'])) {
            $sSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
            $sCntSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
        }

        //不选产品已包含的门店
        if (!empty($aParam['sStoreID'])) {
            $sSQL .= ' AND iStoreID NOT IN ' . '('.$aParam['sStoreID'].')';
            $sCntSQL .= ' AND iStoreID NOT IN ' . '('.$aParam['sStoreID'].')';
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
            'sCity' => isset($aParam['sCity']) ? $aParam['sCity'] : '',
            'sSupplier' => isset($aParam['sSupplier']) ? $aParam['sSupplier'] : '',
            'sType' => isset($aParam['sType']) ? $aParam['sType'] : '',
            'sKeyword' => isset($aParam['sKeyword']) ? $aParam['sKeyword'] : '',
            'iStoreID' => isset($aParam['iStoreID']) ? $aParam['iStoreID'] : '',
            'id' => isset($aParam['id']) ? $aParam['id'] : '',
        );
        return $pageParam;
    }

    public static function getAllStore($aParam) 
    {
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus=' . self::STATUS_VALID;
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus=' . self::STATUS_VALID;

        if (!empty($aParam['sCity'])) {
            $sSQL .= ' AND iCityID IN ' . '('.$aParam['sCity'].')';
            $sCntSQL .= ' AND iCityID IN ' . '('.$aParam['sCity'].')';
        }

        if (!empty($aParam['sSupplier'])) {
            $sSQL .= ' AND iSupplierID IN ' . '('.$aParam['sSupplier'].')';
            $sCntSQL .= ' AND iSupplierID IN ' . '('.$aParam['sSupplier'].')';
        }

        if (!empty($aParam['sType'])) {
            $sSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
            $sCntSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
        }
        
        return self::query($sSQL);
    }

    //根据真实名或简称名获取供应商
    public static function getStoreByName($sName, $sShortName)
    {
        $where1 = array(
            'sName' => $sName,
            'iStatus' => self::STATUS_VALID
        );

        $where2 = array(
            'sShortName' => $sShortName,
            'iStatus' => self::STATUS_VALID
        );

        $row1 = self::getRow(array(
            'where' => $where1
        ));

        $row2 = self::getRow(array(
            'where' => $where2
        ));

        return $row1 ? $row1 : $row2;
    }

    /**
     * 获取产品所有门店
     * @return [array]
     */
    public static function getAllProductStore ($iProductID, $iChannelType, $iCreateUserID, $iChannelID, $aParam = [])
    {
        $aWhere = ['iStatus >' => Model_Store::STATUS_INVALID];
        if ($aParam['iCityID'] > 0) {
            $aWhere['iCityID'] = intval($aParam['iCityID']);
        }
        if (!empty($aParam['iRegionID'])) {
            $aWhere['iRegionID'] = intval($aParam['iRegionID']);
        }
        if (!empty($aParam['iSupplierID'])) {
            $aWhere['iSupplierID'] = intval($aParam['iSupplierID']);
        }
        $aStore = Model_Store::getPair($aWhere, 'iStoreID', 'sName');
        if (empty($aStore)) {
            return [];
        }

        $aStoreParam['iStoreID IN'] = array_keys($aStore);
        $aData = Model_UserProductStore::getUserProductStore($iProductID, $iCreateUserID, $iChannelType, $iChannelID, $aStoreParam);
        if (!empty($aData)) {
            //组装需要数据
            foreach ($aData as $key => $value) {
                $aStores[] = Model_Store::getDetail($value['iStoreID']);
            }
        }

        return $aStores;
    }

    /**
     * 获取产品门店
     * @return [array]
     */
    public static function getProductStore ($iProductID, $iChannelType, $iCreateUserID, $iChannelID, $iPage, $aParam = [])
    {
        $aWhere = ['iStatus >' => Model_Store::STATUS_INVALID];
        if ($aParam['iCityID'] > 0) {
            $aWhere['iCityID'] = intval($aParam['iCityID']);
        }
        if (!empty($aParam['iRegionID'])) {
            $aWhere['iRegionID'] = intval($aParam['iRegionID']);
        }
        if (!empty($aParam['iSupplierID'])) {
            $aWhere['iSupplierID'] = intval($aParam['iSupplierID']);
        }
        $aStore = Model_Store::getPair($aWhere, 'iStoreID', 'sName');
        if (empty($aStore)) {
            return [];
        }

        $aStoreParam['iStoreID IN'] = array_keys($aStore);
        $aData = Model_UserProductStore::getUserProductStoreList($iProductID, $iCreateUserID, $iChannelType, $iChannelID, $iPage, $aStoreParam);
        if (!empty($aData['aList'])) {
            //组装需要数据
            foreach ($aData['aList'] as $key => $value) {
                $aStores['aList'][] = Model_Store::getDetail($value['iStoreID']);
            }
            $aStores['aPager'] = $aData['aPager'];
        }

        return $aStores;
    }

    /**
     * 获取门店包含的供应商
     * @param  [type] $iProductID    [description]
     * @param  [type] $iCreateUserID [description]
     * @param  [type] $iChannelType  [description]
     * @param  [type] $iChannelID    [description]
     * @return [type]                [description]
     */
    public static function getStoreSupplier ($iProductID, $iCreateUserID, $iChannelType, $iChannelID)
    {        
        $iChannelType = 2;
        $aSupplierID = $aSupplier = [];
        $aStore = Model_UserProductStore::getUserProductStore($iProductID, $iCreateUserID, $iChannelType, $iChannelID);
        if ($aStore) {
            foreach ($aStore as $key => $value) {
                $store = Model_Store::getDetail($value['iStoreID']);
                if ($store['iSupplierID'] && !in_array($store['iSupplierID'], $aSupplierID)) {
                    $aSupplierID[] = $store['iSupplierID'];
                }
            }

            if ($aSupplierID) {
                $aSuppliers = Model_Type::getListByPKIDs($aSupplierID);
                foreach ($aSuppliers as $key => $value) {
                    $aSupplier[$value['iTypeID']] = $value['sTypeName'];
                }
            }
        }       

        return $aSupplier;
    }
}