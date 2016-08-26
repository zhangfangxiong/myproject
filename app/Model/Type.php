<?php

/**
 *
 * @author xiejinci
 *
 */
class Model_Type extends Model_Base
{

    const TABLE_NAME = 't_type';

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
            $aRet = self::query("SELECT * FROM t_type_setting WHERE iStatus=1 LIMIT 1", 'row');
            $sClass = $aRet['sClass'];
        } else {
            $aRet = self::query("SELECT * FROM t_type_setting WHERE sClass='$sClass' AND iStatus=1 LIMIT 1", 'row');
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
     * 取得Autocomplete
     *
     * @param unknown $sClass
     * @return Ambigous <number, multitype:multitype:, multitype:unknown >
     */
    public static function getAutocomplete ($sClass, $sKey)
    {
        $where = array(
            'sClass' => $sClass,
            'iStatus' => 1
        );
        if (! empty($sKey)) {
            $where['sTypeName LIKE'] = '%' . $sKey . '%';
        }
        $aList = self::getAll(array(
            'where' => $where,
            'order' => 'iOrder',
            'limit' => 20
        ));
        
        foreach($aList as &$v) {
            unset($v['iParentID'],$v['sRemark'],$v['iOrder'],$v['iStatus'],$v['iCreateTime'],$v['iUpdateTime'],$v['sClass']);
        }
        
        return $aList;
    }
    
    /**
     * 取得类似Option的Key/Value
     *
     * @param unknown $sClass            
     * @return Ambigous <number, multitype:multitype:, multitype:unknown >
     */
    public static function getOption ($sClass)
    {
        return self::getPair(array(
            'where' => array(
                'sClass' => $sClass,
                'iStatus' => 1
            ),
            'order' => 'iOrder'
        ), 'iTypeID', 'sTypeName');
    }

    /**
     * 根据类型名称返回信息
     * @param unknown $sClass
     * @param unknown $sName
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getTypeByName ($sClass, $sName)
    {
        return self::getRow(array(
            'sClass' => $sClass,
            'sTypeName' => $sName
        ));
    }

    /**
     * 取得某个类别的所有类型
     *
     * @param string $sClass            
     * @return multitype:
     */
    public static function getTypes ($sClass = '')
    {
        $aTree = self::getTree($sClass);
        $aList = array();
        self::_toList($aTree, $aList);
        return $aList;
    }

    private static function _toList ($aTree, &$aList)
    {
        foreach ($aTree as $aType) {
            $aType['bIsLeaf'] = empty($aType['aChild']) ? 1 : 0;
            $aChild = $aType['aChild'];
            unset($aType['aChild']);
            $aList[] = $aType;
            if ($aType['bIsLeaf'] == 0) {
                self::_toList($aChild, $aList);
            }
        }
    }

    /**
     * 取得树型数据
     *
     * @param string $sClass            
     * @return multitype:unknown
     */
    public static function getTree ($sClass = '')
    {
        $aWhere = array(
            'iStatus' => 1
        );
        if (! empty($sClass)) {
            $aWhere['sClass'] = $sClass;
        }
        $aList = self::getAll(array(
            'where' => $aWhere,
            'order' => 'iTypeID ASC'
        ));

        // 排序及整理
        $aParentID = array();
        $aOrder = array();
        $aData = array();
        foreach ($aList as $aType) {
            $aParentID[] = $aType['iParentID'];
            $aOrder[] = $aType['iOrder'];
            unset($aType['iOrder'], $aType['iStatus'], $aType['iCreateTime'], $aType['iUpdateTime']);
            $aData[] = $aType;
        }
        unset($aList);
        array_multisort($aParentID, SORT_NUMERIC, SORT_ASC, $aOrder, SORT_NUMERIC, SORT_ASC, $aData);

        // 整理父节点
        $aParent = [];
        foreach ($aData as $aType) {
            $aParent[$aType['iParentID']][] = $aType;
        }
        unset($aType);

        return self::_buildTree($aParent, 0, 0, '');
    }

    private static function _buildTree ($aParent, $iParentID, $iLevel, $sPath)
    {
        $aTree = array();
        if (isset($aParent[$iParentID])) {
            foreach ($aParent[$iParentID] as $aType) {
                $aType['iLevel'] = $iLevel;
                $aType['sPath'] = $sPath;
                $aType['aChild'] = self::_buildTree($aParent, $aType['iTypeID'], $iLevel + 1, $sPath . ' typep' . $aType['iTypeID']);
                $aTree[] = $aType;
            }
        }
        return $aTree;
    }

    /**
     * 取得下一个Order
     *
     * @param unknown $iParentID
     * @return number
     */
    public static function getNextOrder ($iParentID)
    {
        $iOrder = self::getOne(array(
            'where' => array(
                'iParentID' => $iParentID,
                'iStatus' => 1
            ),
            'order' => 'iOrder DESC'
        ), 'iOrder');
        if ($iOrder) {
            $iOrder += 1;
        } else {
            $iOrder = 0;
        }
        return $iOrder;
    }

    /**
     * 改变顺序
     *
     * @param unknown $aFType
     * @param unknown $iDirect
     */
    public static function changeOrder ($aFType, $iDirect)
    {
        $aParam = array(
            'where' => array(
                'iStatus' => 1,
                'iParentID' => $aFType['iParentID'],
                'iTypeID !=' => $aFType['iTypeID']
            )
        );
        if ($iDirect == 0) {
            $iAdd = - 1;
            $aParam['where']['iOrder <='] = $aFType['iOrder'];
            $aParam['order'] = 'iOrder DESC';
        } else {
            $iAdd = 1;
            $aParam['where']['iOrder >='] = $aFType['iOrder'];
            $aParam['order'] = 'iOrder ASC';
        }
        $aList = self::getAll($aParam);
        if (empty($aList)) {
            return 0;
        }

        $aTType = $aList[0];
        if ($aTType['iOrder'] == $aFType['iOrder']) {
            $iOrder = $aFType['iOrder'] + $iAdd;
            self::updData(array(
                'iTypeID' => $aFType['iTypeID'],
                'iOrder' => $iOrder
            ));
            foreach ($aList as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                $iOrder += $iAdd;
                self::updData(array(
                    'iTypeID' => $v['iTypeID'],
                    'iOrder' => $iOrder
                ));
            }
        } else {
            self::updData([
                'iTypeID' => $aFType['iTypeID'],
                'iOrder' => $aTType['iOrder']
            ]);
            self::updData([
                'iTypeID' => $aTType['iTypeID'],
                'iOrder' => $aFType['iOrder']
            ]);
        }
        return count($aList);
    }
}