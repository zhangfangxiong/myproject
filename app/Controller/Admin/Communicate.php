<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/14
 * Time: 9:50
 */
class Controller_Admin_Communicate extends Controller_Admin_Base
{
    /**
     * 修改
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $aCommunicate = $this->_checkData(2);
            if (empty($aCommunicate)) {
                return null;
            }
            $aCommunicate['iTime'] = strtotime($aCommunicate['iTime']);
            $aCommunicate['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_Communicate::updData($aCommunicate) > 0) {
                return $this->showMsg('沟通记录编辑成功！', true);
            } else {
                return $this->showMsg('沟通记录编辑失败！', false);
            }
        } else {
            $iID = $this->getParam('id');
            if (empty($iID)) {
                return $this->showMsg('参数有误！', false);
            }
            $aCommunicate = Model_Communicate::getDetail($iID);
            if (empty($aCommunicate)) {
                return $this->showMsg('该沟通记录不存在！', false);
            }
            $this->assign('iUserID', $aCommunicate['iUserID']);
            $this->assign('aCommunicate', $aCommunicate);
            $aCommunicateLevel = Yaf_G::getConf('aCommunicateLevel');
            $aCommunicateResult = Yaf_G::getConf('aCommunicateResult');
            $this->assign('aCommunicateLevel',$aCommunicateLevel);
            $this->assign('aCommunicateResult',$aCommunicateResult);
        }
    }

    /**
     * 添加
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $aCommunicate = $this->_checkData(1);
            if (empty($aCommunicate)) {
                return null;
            }
            $aCommunicate['iTime'] = strtotime($aCommunicate['iTime']);
            $aCommunicate['iCreateUserID'] = $aCommunicate['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_Communicate::addData($aCommunicate) > 0) {
                return $this->showMsg('增加成功！', true);
            } else {
                return $this->showMsg('增加失败！', false);
            }
        } else {
            $iUserID = intval($this->getParam('id'));
            if (empty($iUserID)) {
                return $this->showMsg('参数有误！', false);
            }
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该用户不存在！', false);
            }
            $this->assign('iUserID',$iUserID);
            $aCommunicateLevel = Yaf_G::getConf('aCommunicateLevel');
            $aCommunicateResult = Yaf_G::getConf('aCommunicateResult');
            $this->assign('aCommunicateLevel',$aCommunicateLevel);
            $this->assign('aCommunicateResult',$aCommunicateResult);
        }
    }

    /**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkData($iType)
    {
        $aParam = $this->getParams();
        if (! Util_Validate::isCLength($aParam['sRealName'], 1, 10))
        {
            return $this->showMsg('姓名为1到10个字！', false);
        }
        if (!Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (!Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if ($iType==1) {
            if (empty($aParam['iUserID'])) {
                return $this->showMsg('非法操作！', false);
            }
            if (!Model_User::getDetail($aParam['iUserID'])) {
                return $this->showMsg('用户不存在！', false);
            }
        }
        return $aParam;
    }

}