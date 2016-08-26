<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/14
 * Time: 9:50
 */
class Controller_Admin_Contecter extends Controller_Admin_Base
{
    /**
     * 修改联系人
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $aContecter = $this->_checkData(2);
            if (empty($aContecter)) {
                return null;
            }
            $aContecter['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_Contecter::updData($aContecter) > 0) {
                return $this->showMsg('联系人编辑成功！', true);
            } else {
                return $this->showMsg('联系人编辑失败！', false);
            }
        } else {
            $iID = $this->getParam('id');
            if (empty($iID)) {
                return $this->showMsg('参数有误！', false);
            }
            $aContecter = Model_Contecter::getDetail($iID);
            if (empty($aContecter)) {
                return $this->showMsg('该联系人不存在！', false);
            }
            $this->assign('iUserID', $aContecter['iUserID']);
            $this->assign('aContecter', $aContecter);
        }
    }

    /**
     * 添加联系人
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkData(1);
            if (empty($aUser)) {
                return null;
            }
            $aUser['iCreateUserID'] = $aUser['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_Contecter::addData($aUser) > 0) {
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
        if (! Util_Validate::isCLength($aParam['sAppellation'], 1, 10))
        {
            return $this->showMsg('称谓为1到10个字！', false);
        }
        if (! Util_Validate::isCLength($aParam['sJobPhone'], 3, 20))
        {
            return $this->showMsg('工作电话为3到20个字！', false);
        }
        if (!Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (!Util_Validate::isEmail($aParam['sEmail'])) {
            return $this->showMsg('邮箱不符合规范！', false);
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