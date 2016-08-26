<?php

class Model_ProductItem extends Model_Base
{
    const TABLE_NAME = 't_product_item';

    const BASEPRODUCT = 1;//基础产品
    const EXPANDPRODUCT = 2;//拓展产品
    const ADDTION = 3;//加项

    //产品或加项包含的所有单项
    public static function getProductItems($iProductID, $iType, $iStatus = null,$getpair = false)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iType' => $iType
        );
        if ($iStatus === null) {
            $aWhere['iStatus >'] = 0;
        } else {
            $aWhere['iStatus'] = $iStatus;
        }
        if ($getpair) {
            return self::getPair(array(
                'where' => $aWhere
            ),'iAutoID','iItemID');
        }
        return self::getAll(array(
            'where' => $aWhere
        ));
    }


    //判断产品或加项是否含有某单项
    public static function getDataByItemID($iProductID,$iItemID, $iType)
    {
        $aWhere = array(
            'iProductID' => $iProductID,
            'iType' => $iType,
            'iItemID' => $iItemID,
            'iStatus' => 1
        );
        return self::getRow(array(
            'where' => $aWhere
        ));
    }

    public static function getPItemByIDs($aParam) 
    {
        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE iStatus=' . self::STATUS_VALID;
        $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . ' WHERE iStatus=' . self::STATUS_VALID;

        if (!empty($aParam['iProductID'])) {
            $sSQL .= ' AND iProductID IN ' . '('.$aParam['iProductID'].')';
            $sCntSQL .= ' AND iProductID IN ' . '('.$aParam['iProductID'].')';
        }

        if (!empty($aParam['sItemID'])) {
            $sSQL .= ' AND iItemID IN ' . '('.$aParam['sItemID'].')';
            $sCntSQL .= ' AND iItemID IN ' . '('.$aParam['sItemID'].')';
        }

        if (!empty($aParam['sType'])) {
            $sSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
            $sCntSQL .= ' AND iType IN ' . '('.$aParam['sType'].')';
        }
        
        return self::query($sSQL);
    }

    //获取所有包含某指定单项的产品或加项
    public static function getDataByItems($aItemIDs,$iType,$iStatus = null)
    {
        $aWhere = array(
            'iItemID IN' => $aItemIDs,
            'iType' => $iType,
        );
        if ($iStatus===null) {
            $aWhere['iStatus >'] = 0;
        } else {
            $aWhere['iStatus'] = $iStatus;
        }
        return self::getAll(array(
            'where' => $aWhere
        ));
    }

    /**
     * 根据单项匹配基础产品
     * @param $sItemID
     * @param $Limit
     */
    public static function matchProductByItem($sItemID,$Limit=5)
    {
        if (is_array($sItemID)) {
            $sItemID = implode(',',$sItemID);
        }
        $sSql = 'select p.iProductID, p.sProductName, count(*) as iCnt from '.self::TABLE_NAME.' i, t_product p where i.iProductID=p.iProductID AND p.iType='.Model_Product::TYPE_BASE.' AND i.iType='.self::BASEPRODUCT.' AND p.iStatus>0 AND i.iStatus>0 AND i.iItemID in ('.$sItemID.') group by i.iProductID order by iCnt desc limit '.$Limit;
        return self::query($sSql);
    }
}