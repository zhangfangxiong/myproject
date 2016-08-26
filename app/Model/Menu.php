<?php

/**
 * 
 * @author xiejinci
 *
 */
class Model_Menu extends Model_Base
{

    const TABLE_NAME = 't_menu';

    public static function getMenus ($iAdminID = 0)
    {
        $aTree = self::getTree($iAdminID);
        $aList = array();
        self::_toList($aTree, $aList);
        return $aList;
    }

    private static function _toList ($aTree, &$aList)
    {
        foreach ($aTree as $aMenu) {
            $aMenu['bIsLeaf'] = empty($aMenu['aChild']) ? 1 : 0;
            $aChild = $aMenu['aChild'];
            unset($aMenu['aChild']);
            $aList[] = $aMenu;
            if ($aMenu['bIsLeaf'] == 0) {
                self::_toList($aChild, $aList);
            }
        }
    }

    public static function getTree ($iAdminID = 0)
    {
        $aWhere = array(
            'iStatus' => 1
        );
        $aList = self::getAll(array(
            'where' => $aWhere,
            'order' => 'iMenuID ASC'
        ));
        
        // 权限判断
        if ($iAdminID > 0) {
            $aMenuPermission = Model_Permission::getMenuPermissions($iAdminID);
        }
        
        // 是否查找当前菜单
        $bFind = 0;
        if ($iAdminID > 0) {
            $sUri = Yaf_G::getUrl();
        } else {
            $sUri = null;
        }
        
        // 排序及整理
        $aParentID = array();
        $aOrder = array();
        $aData = array();
        foreach ($aList as $aMenu) {
            if ($iAdminID > 0) {
                if ($aMenuPermission != - 1 && ! isset($aMenuPermission[$aMenu['iMenuID']])) {
                    continue;
                }
                $aMenu['iCurr'] = Yaf_G::getUrl($aMenu['sUrl']) == $sUri ? 1 : 0;
                if (! $bFind && $aMenu['iCurr'] == 1) {
                    $bFind = 1;
                }
            }
            $aParentID[] = $aMenu['iParentID'];
            $aOrder[] = $aMenu['iOrder'];
            unset($aMenu['iOrder'], $aMenu['iStatus'], $aMenu['iCreateTime'], $aMenu['iUpdateTime']);
            $aData[] = $aMenu;
        }
        unset($aList);
        array_multisort($aParentID, SORT_NUMERIC, SORT_ASC, $aOrder, SORT_NUMERIC, SORT_ASC, $aData);
        
        // 如果没有匹配的菜单，则从Cookie中获取
        if ($iAdminID > 0) {
            if (! $bFind) {
                $sUri = Util_Cookie::get('menu');
            } else {
                Util_Cookie::set('menu', $sUri);
            }
        }
        
        // 整理父节点
        $aParent = [];
        foreach ($aData as $aMenu) {
            if (! $bFind) {
                $aMenu['iCurr'] = Yaf_G::getUrl($aMenu['sUrl']) == $sUri ? 1 : 0;
            }
            $aParent[$aMenu['iParentID']][] = $aMenu;
        }
        unset($aMenu);
        
        return self::_buildTree($aParent, 0, 0, '');
    }

    private static function _buildTree ($aParent, $iParentID, $iLevel, $sPath)
    {
        $aTree = array();
        if (isset($aParent[$iParentID])) {
            foreach ($aParent[$iParentID] as $aMenu) {
                $aMenu['iLevel'] = $iLevel;
                $aMenu['sPath'] = $sPath;
                $aMenu['aChild'] = self::_buildTree($aParent, $aMenu['iMenuID'], $iLevel + 1, $sPath . ' menup' . $aMenu['iMenuID']);
                if (! empty($aMenu)) {
                    foreach ($aMenu['aChild'] as $v) {
                        if ($v['iCurr'] == 1) {
                            $aMenu['iCurr'] = 1;
                            break;
                        }
                    }
                }
                $aTree[] = $aMenu;
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
     *
     * @param unknown $aFMenu            
     * @param unknown $iDirect            
     */
    public static function changeOrder ($aFMenu, $iDirect)
    {
        $aParam = array(
            'where' => array(
                'iStatus' => 1,
                'iParentID' => $aFMenu['iParentID'],
                'iMenuID !=' => $aFMenu['iMenuID']
            )
        );
        if ($iDirect == 0) {
            $iAdd = - 1;
            $aParam['where']['iOrder <='] = $aFMenu['iOrder'];
            $aParam['order'] = 'iOrder DESC';
        } else {
            $iAdd = 1;
            $aParam['where']['iOrder >='] = $aFMenu['iOrder'];
            $aParam['order'] = 'iOrder ASC';
        }
        $aList = self::getAll($aParam);
        if (empty($aList)) {
            return 0;
        }
        
        $aTMenu = $aList[0];
        if ($aTMenu['iOrder'] == $aFMenu['iOrder']) {
            $iOrder = $aFMenu['iOrder'] + $iAdd;
            self::updData(array(
                'iMenuID' => $aFMenu['iMenuID'],
                'iOrder' => $iOrder
            ));
            foreach ($aList as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                $iOrder += $iAdd;
                self::updData(array(
                    'iMenuID' => $v['iMenuID'],
                    'iOrder' => $iOrder
                ));
            }
        } else {
            self::updData([
                'iMenuID' => $aFMenu['iMenuID'],
                'iOrder' => $aTMenu['iOrder']
            ]);
            self::updData([
                'iMenuID' => $aTMenu['iMenuID'],
                'iOrder' => $aFMenu['iOrder']
            ]);
        }
        return count($aList);
    }
}