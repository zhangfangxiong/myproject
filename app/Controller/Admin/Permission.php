<?php

class Controller_Admin_Permission extends Controller_Admin_Base
{

    /**
     * 权限点列表
     *
     * @return boolean
     */
    public function listAction ()
    {
        $sKey = $this->getParam('qkey');
        $iPage = intval($this->getParam('page'));
        if (isset($_GET['page'])) {
            $iPage = $_GET['page'];
        }
        $aWhere = array(
            'iStatus' => 1
        );
        if (! empty($sKey)) {
            $aWhere['sPath LIKE'] = '%' . $sKey . '%';
        }
        $aList = Model_Permission::getList($aWhere, $iPage);
        $aMenuID = array();
        foreach ($aList['aList'] as $v) {
            $aMenuID[] = $v['iMenuID'];
        }
        $aMenuList = Model_Menu::getPKIDList($aMenuID, true);
        $this->assign('aList', $aList);
        $this->assign('aMenuList', $aMenuList);
    }

    /**
     * 删除权限点
     *
     * @return boolean
     */
    public function delAction ()
    {
        $iPermissionID = intval($this->getParam('id'));
        $iRet = Model_Permission::delData($iPermissionID);
        if ($iRet == 1) {
            return $this->showMsg('权限点删除成功！', true);
        } else {
            return $this->showMsg('权限点删除失败！', false);
        }
    }

    /**
     * 编辑权限点
     *
     * @return boolean
     */
    public function editAction ()
    {
        if ($this->isPost()) {
            $aPermission = $this->_checkData();
            if (empty($aPermission)) {
                return null;
            }
            $aPermission['iPermissionID'] = intval($this->getParam('iPermissionID'));
            if (1 == Model_Permission::updData($aPermission)) {
                return $this->showMsg('权限点信息更新成功！', true);
            } else {
                return $this->showMsg('权限点信息更新失败！', false);
            }
        } else {
            $iPermissionID = intval($this->getParam('id'));
            $aPermission = Model_Permission::getDetail($iPermissionID);
            $this->assign('aPermission', $aPermission);
            $this->assign('aMenuTree', Model_Menu::getMenus());
        }
    }

    /**
     * 增加权限点
     *
     * @return NULL
     */
    public function addAction ()
    {
        if ($this->isPost()) {
            $aPermission = $this->_checkData();
            if (empty($aPermission)) {
                return null;
            }
            if (Model_Permission::addData($aPermission) > 0) {
                return $this->showMsg('权限点增加成功！', true);
            } else {
                return $this->showMsg('权限点增加失败！', false);
            }
        } else {
            $this->assign('aMenuTree', Model_Menu::getMenus());
        }
    }

    /**
     * 生成权限点
     */
    public function makeAction ()
    {
        $aMenuList = Model_Menu::getMenus();
        
        $aCtrClass = array();
        $aMenuAction = array();
        foreach ($aMenuList as $aMenu) {
            if ($aMenu['bIsLeaf']) {
                $aRoute = Yaf_G::getRoute($aMenu['sUrl']);
                $aMenuAction[$aRoute['module'] . '_' . $aRoute['controller'] . '_' . $aRoute['action']] = $aMenu['sMenuName'];
                $aCtrClass[$aRoute['module'] . '_' . $aRoute['controller']] = array(
                    'iMenuID' => $aMenu['iMenuID'],
                    'sMenuName' => $aMenu['sMenuName'],
                    'sUrl' => $aMenu['sUrl']
                );
            }
        }
        
        $aPermission = array();
        foreach ($aCtrClass as $sCtrClass => $aMenu) {
            try {
                $sCtrClass = 'Controller_' . $sCtrClass;
                if (class_exists($sCtrClass)) {
                    $oCtr = new ReflectionClass($sCtrClass);
                    $aMethod = $oCtr->getMethods();
                    foreach ($aMethod as $oMethod) {
                        $sAction = $oMethod->getName();
                        if (substr($sAction, - 6) === 'Action') {
                            $sAction = substr($sAction, 0, - 6);
                            $aRow = array(
                                $aMenu['iMenuID']
                            );
                            $aRow[] = Yaf_G::routeToUrl($sCtrClass . '_' . $sAction);
                            $sDoc = $oMethod->getDocComment();
                            $matches = null;
                            if (preg_match('/\s+\*\s+(.+)/i', $sDoc, $matches)) {
                                $aRow[] = $matches[1];
                            } elseif (isset($aMenuAction[$sCtrClass . '_' . $sAction])) {
                                $aRow[] = $aMenuAction[$sCtrClass . '_' . $sAction];
                            } else {
                                $aRow[] = $aMenu['sMenuName'] . '::' . $sAction;
                            }
                            $aPermission[] = $aRow;
                        }
                    }
                }
            } catch (Exception $e) {
                $aPermission[] = array(
                    $aMenu['iMenuID'],
                    Yaf_G::getUrl($aMenu['sUrl']),
                    $aMenu['sMenuName']
                );
            }
        }
        
        $iCnt = 0;
        foreach ($aPermission as $v) {
            $aRow = Model_Permission::getRow(array(
                'where' => array(
                    'sPath' => $v[1]
                )
            ));
            if (empty($aRow)) {
                $aRow = array(
                    'iMenuID' => $v[0],
                    'sPermissionName' => $v[2],
                    'sPath' => $v[1]
                );
                Model_Permission::addData($aRow);
                $iCnt ++;
            } else {
                $aRow['iMenuID'] = $v[0];
                $aRow['sPermissionName'] = $v[2];
                Model_Permission::updData($aRow);
                $iCnt ++;
            }
        }
        
        return $this->showMsg('本次生成权限点【' . $iCnt . '】个', true);
    }

    /**
     * 请求数据检测
     *
     * @return mixed
     */
    public function _checkData ($sType = 'add')
    {
        $sPermissionName = $this->getParam('sPermissionName');
        $sPath = $this->getParam('sPath');
        $iMenuID = $this->getParam('iMenuID');
        
        if (! Util_Validate::isLength($sPermissionName, 2, 30)) {
            return $this->showMsg('权限名长度范围为2到30个字！', false);
        }
        if (! Util_Validate::isLength($sPath, 2, 50)) {
            return $this->showMsg('权限点长度范围为2到30个字符！', false);
        }
        if (empty($iMenuID)) {
            return $this->showMsg('请选择权限点的归属模块！', false);
        }
        $iCnt = Model_Menu::getCnt(array(
            'where' => array(
                'iParentID' => $iMenuID
            )
        ));
        if ($iCnt > 0) {
            return $this->showMsg('归属模块只能选择最底层的模块！', false);
        }
        $aRow = array(
            'sPermissionName' => $sPermissionName,
            'sPath' => $sPath,
            'iMenuID' => $iMenuID
        );
        return $aRow;
    }
}