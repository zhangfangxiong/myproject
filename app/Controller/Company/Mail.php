<?php

/**
 * 企业后台_发送账号
 * User: xuchuyuan
 * Date: 16/4/13 14:00
 */
class Controller_Company_Mail extends Controller_Company_Base
{

	const ERROR = '不能为空';

	public $loginUrl = null;

	public function actionBefore ()
	{
		parent::actionBefore();

		$this->loginUrl = Yaf_G::getConf('loginUrl');
	}

	/**
	 * 设置发送邮件内容
	 */
	public function setMailAction ()
	{
		if ($this->isPost()) {
			$aMail['title'] = $this->getParam('title');
			$aMail['sendto'] = $this->getParam('sendto');
			$aMail['content'] = $this->getParam('content');
			if (!empty($aMail['title']) && !empty($aMail['sendto']) && !empty($aMail['content'])) {
				$result = Util_Mail::send($aMail['sendto'], $aMail['title'], $aMail['content']);
				if ($result == 1) {
					return $this->showMsg('邮件发送成功', true);
			 	} else {
			 		return $this->showMsg('邮件发送失败', false);
			 	}
			} else {
				return $this->showMsg('邮件内容不完整', false);
			}	
		} else {
			$mail = Model_Kv::getValue(self::MAIL_KEY.$this->enterpriseId);
			if (!$mail) {
				$mail = Yaf_G::getConf('mail', 'employee');
			}
			$employeeId = intval($this->getParam('employeeId'));
			if ($employeeId) {
				$aEmployee = Model_CustomerNew::getDetail($employeeId);
				$row = Model_Company_Company::checkIsExist($aEmployee['iUserID'], $this->enterpriseId);
				$aEmployee['sEmail'] = $row['sEmail'];
				$aEmployee['iStatus'] = $row['iStatus'];
				if ($aEmployee && $aEmployee['iStatus'] == Model_Company_Employee::STATUS_VALID) {
					$aMail['title']    = $this->enterprise['sCoName'].'-弹性福利平台员工账号';
					$aMail['sendto']   = $aEmployee['sEmail'];
					
					$mail = preg_replace('/\[收件人\]/', $aEmployee['sRealName'], $mail);
					$mail = preg_replace('/\[登录地址\]/', $this->loginUrl, $mail);
					$mail = preg_replace('/\[账号\]/', $aEmployee['sUserName'], $mail);
					$aMail['content'] = preg_replace('/\[密码\]/', $aEmployee['sUserName'], $mail);

					// $employee['iAutoID'] = $row['iAutoID'];
					$row['iSendStatus'] = 1;
					Model_Company_Company::updData($row);
				}

				$aMail['employeeId'] = $employeeId;
				$this->assign('aMail', $aMail);
			}
		}		
	}

	/**
	 * 设置批量发送邮件
	 */
	public function setMultiMailAction ()
	{
		$mail = Model_Kv::getValue(self::MAIL_KEY.$this->enterpriseId);
		if (!$mail) {
			$mail = Yaf_G::getConf('mail', 'employee');
		}
		
		$where = [
			'iCreateUserID' => $this->enterpriseId,
			'iStatus >' => Model_Company_Company::STATUS_INVALID
		];
		$this->getParam('iLevelID') ? $where['iJobGradeID'] = intval($this->getParam('iLevelID')) : '';
		$this->getParam('iDeptID') ? $where['iDeptID']      = intval($this->getParam('iDeptID')) : '';
		
		$iLoginSendStatus = $this->getParam('iSendLoginStatus');
		if ($iLoginSendStatus == 1) {
			$where['iSendStatus'] = 0;
		}
		if ($iLoginSendStatus == 2) {
			$where['iLoginStatus'] = 0;
		}

		$this->getParam('iSendStatus') 
		? $where['iSendStatus'] = intval($this->getParam('iSendStatus')) : '';

		$aList = Model_Company_Company::getAll(['where' => $where]);
		$aList = $this->setEmployeeData($aList);

		$sIDs = '';
		$aSendIDs = [];
		if ($aList) {
			foreach ($aList as $key => $value) {
				$aSendIDs[] = $value['iUserID'];
			}
		}

		if ($aSendIDs)	$sIDs = implode(',', $aSendIDs);
		$this->assign('aParams', $this->getParams());
		$this->assign('aList', $aList);
		$this->assign('sIDs', $sIDs);
		$this->assign('sMail', $mail);	
	}


	/**
	 * 批量发送邮件
	 * @return [type] [description]
	 */
	public function sendMultiMailAction ()
	{
		$employeeIds = strval($this->getParam('employeeIds'));
		if ($employeeIds) {
			$aEmployeeID = explode(',', $employeeIds);
			
			foreach ($aEmployeeID as $key => $value) {
				if (intval($value) < 1) {
					unset($aEmployeeID[$key]);
				}
			}
			if (!$aEmployeeID) {
				return $this->showMsg('员工ID'.self::ERROR, false);
			}
			
			$aEmployees = Model_CustomerNew::getListByPKIDs($aEmployeeID);
			if ($aEmployees) {
				foreach ($aEmployees as $key => &$value) {
					$row = Model_Company_Company::checkIsExist($value['iUserID'], $this->enterpriseId);
					if (!$row) {
						unset($aEmployees[$key]);
						continue;
					}
					$value['sUserName'] = $row['sUserName'];
					$value['sEmail'] = $row['sEmail'];
					$value['iStatus'] = $row['iStatus'];
					$value['iCompanyAutoID'] = $row['iAutoID'];
				}
			}
			if ($aEmployees) {
				foreach ($aEmployees as $key => $value) {
					$emp = [];
					$mail = Model_Kv::getValue(self::MAIL_KEY.$this->enterpriseId);
					if (!$mail) {
						$mail = Yaf_G::getConf('sMail');
					}
					if ($value['iStatus'] == Model_Company_Company::STATUS_VALID 
						&& !empty($value['sRealName']) && !empty($value['sEmail'])
						&& !empty($value['sUserName'])) {
						$mail  = preg_replace('/\[收件人\]/', $value['sRealName'], $mail);
						$mail  = preg_replace('/\[登录地址\]/', $this->loginUrl, $mail);
						$mail  = preg_replace('/\[账号\]/', $value['sUserName'], $mail);
						$content = preg_replace('/\[初始密码\]/', $value['sUserName'], $mail);
						$mailRes = Util_Mail::send($value['sEmail'], '弹性福利平台员工账号', $content);

						$emp['iAutoID'] = $value['iCompanyAutoID'];
						$emp['iSendStatus'] = 1;
						Model_Company_Company::updData($emp);
					}
				}

				return $this->showMsg('邮件批量发送成功', true); 
			} else {
				return $this->showMsg('员工ID'.self::ERROR, false);
			}

			
		} else {
			return $this->showMsg('员工ID'.self::ERROR, false);
		}
	}
}