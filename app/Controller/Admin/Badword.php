<?php

class Controller_Admin_Badword extends Controller_Admin_Base
{

    /**
     * 敏感词删除
     */
    public function delAction ()
    {
        $iBadwordID = intval($this->getParam('id'));
        $iRet = Model_Badword::delData($iBadwordID);
        if ($iRet == 1) {
            return $this->showMsg('敏感词删除成功！', true);
        } else {
            return $this->showMsg('敏感词删除失败！', false);
        }
    }

    /**
     * 敏感词列表
     */
    public function listAction ()
    {
        $iPage = intval($this->getParam('page'));
        if (isset($_GET['page'])) {
            $iPage = $_GET['page'];
        }
        $aWhere = array(
            'iStatus' => 1
        );
        
        $aParam = $this->getParams();
        if (! empty($aParam['sWord'])) {
            $aWhere['sWord LIKE'] = '%' . $aParam['sWord'] . '%';
        }
        
        $aList = Model_Badword::getList($aWhere, $iPage);
        $this->assign('aList', $aList);
        $this->assign('aParam', $aParam);
    }

    /**
     * 敏感词修改
     */
    public function editAction ()
    {
        if ($this->_request->isPost()) {            
            $aBadword = $this->_checkData('update');
            if (empty($aBadword)) {
                return null;
            }
            $aBadword['iAutoID'] = intval($this->getParam('iAutoID'));
            $aOldBadword = Model_Badword::getDetail($aBadword['iAutoID']);
            if (empty($aOldBadword)) {
                return $this->showMsg('敏感词不存在！', false);
            }
            if ($aOldBadword['sWord'] != $aBadword['sWord']) {
                if (Model_Badword::getBadwordByWord($aBadword['sWord'])) {
                    return $this->showMsg('敏感词已经存在！', false);
                }
            }
            if (1 == Model_Badword::updData($aBadword)) {
                return $this->showMsg('敏感词信息更新成功！', true);
            } else {
                return $this->showMsg('敏感词信息更新失败！', false);
            }
        } else {
            $iBadwordID = intval($this->getParam('id'));
            $aBadword = Model_Badword::getDetail($iBadwordID);
            $this->assign('aBadword', $aBadword);
        }
    }

    /**
     * 增加敏感词
     */
    public function addAction ()
    {
        if ($this->_request->isPost()) {
            $aBadword = $this->_checkData('add');
            if (empty($aBadword)) {
                return null;
            }
            if (Model_Badword::getBadwordByWord($aBadword['sWord'])) {
                return $this->showMsg('敏感词已经存在！', false);
            }
            if (Model_Badword::addData($aBadword) > 0) {
                return $this->showMsg('敏感词增加成功！', true);
            } else {
                return $this->showMsg('敏感词增加失败！', false);
            }
        }
    }

    /**
     * 请求数据检测
     *
     * @return mixed
     */
    public function _checkData ($sType = 'add')
    {
        $sWord = $this->getParam('sWord');
        $iUpdateTime = time();
        
        $aRow = array(
            'sWord' => $sWord,
            'iUpdateTime' => $iUpdateTime
        );
        
        return $aRow;
    }
}