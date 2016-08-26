<?php
/**
 * User: xcy
 * Date: 2016/5/30
 * Time: 16:00
 */
class Controller_Supplier_System extends Controller_Supplier_Base 
{
	
	/**
	 * 修改密码
	 * @return [bool]
	 */
	public function chpwdAction ()
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
				'iUserID' => $this->aCurrUser['iUserID'],
				'iType' => Model_User::TYPE_SUPPLIER,
				'iStatus' => Model_User::STATUS_TYPE_NORMAL			
			];			
			$aUser = Model_User::getRow([
				'where' => $where
			]);
			if ($aUser) {
				if ($aUser['sPassword'] == md5(Yaf_G::getConf('cryptkey', 'cookie') .$sOldPwd)) {
					$where['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $sNewPwd);
					$result = Model_User::updData($where);	
				} else {
					return $this->showMsg('旧密码错误', false);
				}		
			}

			$msg = !empty($result) ? '修改密码成功' : '修改密码失败';
			$bool = !empty($result) ? true : false;
			return $this->showMsg($msg, $bool);
		}		
	}

}