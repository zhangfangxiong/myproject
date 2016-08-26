<?php

class Controller_Admin_Front extends Controller_Admin_Base
{

    /**
     * (non-PHPdoc)
     *
     * @see Yaf_Controller::__call()
     */
    public function __call ($sMethod, $aArg)
    {
        $sClass = str_replace('Action', '', $sMethod);
        $aClass = Model_Front::getClass($sClass);
        if (empty($aClass)) {
            parent::__call($sMethod, $aArg);
            return false;
        }
        $this->listAction($sClass);
        $this->setViewScript('/Admin/Front/list.phtml');
    }

    /**
     * 删除菜单
     *
     * @return boolean
     */
    public function delAction ()
    {
        $iAutoID = $this->getParam('id');
        $aFront = Model_Front::getDetail($iAutoID);
        $sClassName = Model_Front::getClass($aFront['sClass'], 'title');
        $iRet = Model_Front::delData($iAutoID);
        if ($iRet == 1) {
            return $this->showMsg($sClassName . '删除成功！', true);
        } else {
            return $this->showMsg($sClassName . '删除失败！', false);
        }
    }

    /**
     * 菜单列表
     */
    public function listAction ($sClass = '')
    {
        if (empty($sClass)) {
            $sClass = $this->getParam('class', '');
        }
        
        $aClass = Model_Front::getClass($sClass);
        $aList = Model_Front::getFronts($sClass);
        $this->assign('aList', $aList);
        $this->assign('aClass', $aClass);
        $this->assign('sClass', $sClass);
    }

    /**
     * 编辑菜单
     */
    public function editAction ()
    {
        if ($this->isPost()) {

            $aFront = $this->_checkData('update');
            if (empty($aFront)) {
                return null;
            }
            
            $aClass = Model_Front::getClass($aFront['sClass']);
            $aFront['iAutoID'] = intval($this->getParam('iAutoID'));
            $aOldType = Model_Front::getDetail($aFront['iAutoID']);
            if (empty($aOldType)) {
                return $this->showMsg($aClass['sName'] . '不存在！', false);
            }
            if (1 == Model_Front::updData($aFront)) {
                return $this->showMsg($aClass['sName'] . '信息更新成功！', true);
            } else {
                return $this->showMsg($aClass['sName'] . '信息更新失败！', false);
            }
        } else {
            $iAutoID = intval($this->getParam('id'));
            $aFront = Model_Front::getDetail($iAutoID);
            $sClass = $aFront['sClass'];
            $aClass = Model_Front::getClass($sClass);
            $this->assign('aFront', $aFront);
            $this->assign('aClass', $aClass);
            $this->assign('sClass', $sClass);
        }
    }

    /**
     * 增加菜单
     */
    public function addAction ()
    {
        if ($this->isPost()) {
            $aFront = $this->_checkData();
            if (empty($aFront)) {
                return null;
            }
            
            $aClass = Model_Front::getClass($aFront['sClass']);
            $aFront['iOrder'] = Model_Front::getNextOrder($aFront['sClass'], $aFront['iCityID']);
            if (Model_Front::addData($aFront) > 0) {
                return $this->showMsg($aClass['sName'] . '增加成功！', true);
            } else {
                return $this->showMsg($aClass['sName'] . '增加失败！', false);
            }
        } else {
            $sClass = $this->getParam('class', '');
            $aClass = Model_Front::getClass($sClass);    
            
            $this->assign('aClass', $aClass);
            $this->assign('sClass', $sClass);
        }
    }

    /**
     * 菜单移动
     *
     * @return boolean
     */
    public function moveAction ()
    {
        $iAutoID = $this->getParam('id');
        $iDirect = $this->getParam('direct');
        $aFront = Model_Front::getDetail($iAutoID);
        $iCnt = Model_Front::changeOrder($aFront, $iDirect);
        return $this->showMsg($iCnt, true);
    }

    /**
     * 请求数据检测
     *
     * @return mixed
     */
    public function _checkData ($sType = 'add')
    {
        $sClass = $this->getParam('sClass', '');
        $aClass = Model_Front::getClass($sClass);
        $aRow = array();
        $aRow['sClass'] = $aClass['sClass'];
        $aRow['sSourceID'] = $this->getParam('sSourceID', '');
        if ($aClass['iCity'] == 1) {
            $aRow['iCityID'] = $this->iCurrCityID;
        } else {
            $aRow['iCityID'] = 0;
        }
        if ($aClass['sTitle']) {
            $aRow['sTitle'] = $this->getParam('sTitle');
            if (! Util_Validate::isLength($aRow['sTitle'], 2, 50)) {
                return $this->showMsg('标题长度范围为2到50个字！', false);
            }
        }
        if ($aClass['sImage']) {
            $aRow['sImage'] = $this->getParam('sImage');
            if (empty($aRow['sImage'])) {
                return $this->showMsg('请上传一张图片！', false);
            }
        }
        if ($aClass['sUrl']) {
            $aRow['sUrl'] = $this->getParam('sUrl');
            if (! Util_Validate::isUrl($aRow['sUrl'])) {
                return $this->showMsg('链接地址错误！', false);
            }
        }
        if ($aClass['sDiy1']) {
            $aRow['sDiy1'] = $this->getParam('sDiy1');
        }
        if ($aClass['sDiy2']) {
            $aRow['sDiy2'] = $this->getParam('sDiy2');
        }
        if ($aClass['sDiy3']) {
            $aRow['sDiy3'] = $this->getParam('sDiy3');
        }
        if ($aClass['sColor']) {
            $aRow['sColor'] = $this->getParam('sColor');
        }
        if ($aClass['sRemark']) {
            $aRow['sRemark'] = $this->getParam('sRemark');
        }

        return $aRow;
    }
}