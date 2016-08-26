<?php

/**
 * 企业后台_系统设置
 * User: xuchuyuan
 * Date: 16/4/18 10:00
 */
class Controller_Company_System extends Controller_Company_Base
{

	const LOGO_KEY = 'LOGO_KEY_';

	const PAGE_KEY = 'PAGE_KEY_';

	const ALLOW_KEY = 'IS_ALLOW_KEY_';


	public function actionBefore ()
	{
		parent::actionBefore();

		//公司Logo获取
		$sLogo = Model_Kv::getValue(self::LOGO_KEY.$this->enterpriseId);
		$this->assign('sLogo', $sLogo);

		//登录页面图片获取
		$sLogin = Model_Kv::getValue(self::PAGE_KEY.$this->enterpriseId);
		$this->assign('sLogin', $sLogin);

		//邮件格式获取
		$sMail = Model_Kv::getValue(self::MAIL_KEY.$this->enterpriseId);
		if (!$sMail) {
			$sMail = Yaf_G::getConf('mail', 'employee');
		}
		$this->assign('sMail', $sMail);
	}

	public function indexAction ()
	{
		if ($this->_request->isPost()) {
			$param = $this->getParams();
			if (empty($param['sLogoKey']) && empty($param['sPageKey']) && empty($param['sMail'])) {
				return $this->showMsg('请选择上传内容', false);
			}
			
			if (!empty($param['sLogoKey'])) {
				Model_Kv::setValue(self::LOGO_KEY.$this->enterpriseId, $param['sLogoKey']);
			}

			if (!empty($param['sPageKey'])) {
				Model_Kv::setValue(self::PAGE_KEY.$this->enterpriseId, $param['sPageKey']);
			}

			if (!empty($param['sMail'])) {
				Model_Kv::setValue(self::MAIL_KEY.$this->enterpriseId, $param['sMail']);
			}

			return $this->showMsg('设置成功', true);
		} 
	}


	/**
	 * 修改密码
	 * @return [bool]
	 */
	public function resetPwdAction ()
	{
		if ($this->_request->isPost()) {
			$sOldPwd = strval($this->getParam('sOldPwd'));
			$sNewPwd = strval($this->getParam('sNewPwd'));
			$sConfirmPwd = strval($this->getParam('sConfirmPwd'));

			if (!$sOldPwd || !$sNewPwd || !$sConfirmPwd) {
				return $this->showMsg('请输入密码', false);
			}
			if ($sConfirmPwd != $sNewPwd) {
				return $this->showMsg('新旧密码不一致,请重新输入', false);
			}
			
			$where = [
				'iUserID' => $this->enterpriseId,
				'iType' => Model_User::TYPE_HR,
				'iStatus' => Model_User::STATUS_TYPE_NORMAL			
			];			
			$aUser = Model_User::getRow([
				'where' => $where
			]);
			if ($aUser) {
				if ($aUser['sPassword'] == md5(Yaf_G::getConf('cryptkey', 'cookie') .$sOldPwd)) {
					$where['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $sNewPwd);
					$result = Model_User::updData($where);	
				}				
			}

			$msg = !empty($result) ? '修改密码成功' : '修改密码失败';
			$bool = !empty($result) ? true : false;
			return $this->showMsg($msg, $bool);
		}		
	}


	/**
	 * 修改邮箱Index
	 * @return [array]
	 */
	public function mailIndexAction ()
	{
		if ($this->_request->isPost()) {
			$param = $this->getParams();
			if ($param['iType'] == 2) {
				$iAllow = intval($this->getParam('iAllow'));
				$sMail = strval($this->getParam('sReceiveMail'));
				if (!$sMail) {
					return $this->showMsg('请输入员工订单收件箱', false);
				}
				
				$aAllow = [
					'iAllow' => $iAllow,
					'sReceiveMail' => $sMail
				];

				Model_Kv::setValue(self::ALLOW_KEY.$this->enterpriseId, $aAllow);

				return $this->showMsg('设置成功', true);
			}

			if ($param['iType'] == 1) {
				$sPassword = strval($this->getParam('sPassword'));	
				$sMail = strval($this->getParam('sMail'));
				if (!$sPassword || !$sMail) {
					return $this->showMsg('请输入密码或者邮箱', false);
				}

				$where = [
					'iUserID' => $this->enterpriseId,
					'sPassword' => md5(Yaf_G::getConf('cryptkey', 'cookie') . $sPassword),
					'iType' => Model_User::TYPE_HR,
					'iStatus' => Model_User::STATUS_TYPE_NORMAL			
				];
				$aUser = Model_User::getRow([
					'where' => $where
				]);
				if ($aUser) {
					$where['sEmail'] = $sMail;	
					$aSet = Model_User::getAll(['where' => [
						'sEmail' => $sMail,
						'iType' => Model_User::TYPE_HR,
						'iStatus' => Model_User::STATUS_TYPE_NORMAL
					]]);
					if ($aSet) {
						return $this->showMsg('邮箱已存在!', false);
					}
					$result = Model_User::updData($where);
				} else {
					return $this->showMsg('密码不正确!', false);
				}

				return $this->showMsg('修改邮箱成功!', true);
			}
			
			return $this->showMsg($msg, $bool);

		} else {
			$where = [
				'iUserID' => $this->enterpriseId,
				'iType' => Model_User::TYPE_HR,
				'iStatus' => Model_User::STATUS_TYPE_NORMAL			
			];
			$aUser = Model_User::getRow([
				'where' => $where
			]);
			$sMail = isset($aUser['sEmail']) ? $aUser['sEmail'] : '';

			$aValue = Model_Kv::getValue(self::ALLOW_KEY.$this->enterpriseId, true);

			$this->assign('sMail', $sMail);
			$this->assign('aValue', $aValue);
		}		
	}

}