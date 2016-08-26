<?php

class Model_Physical_Plan extends Model_Base
{

    const TABLE_NAME = 't_physical_plan';

    /**
	 * 发送体检计划启动通知
	 * @param string $value [description]
	 */
	public static function sendPlan ($iPlanID)
	{
		$content = Yaf_G::getConf('planmail', 'physical');
		$msg  = Yaf_G::getConf('planmsg', 'physical');

		$aPlan = Model_Physical_Plan::getDetail($iPlanID);
		$aCompany = Model_User::getDetail($aPlan['iHRID']);
		$iCnt = Model_OrderCard::getCnt(['where' => ['iPlanID' => $iPlanID]]);
		
		$msg = preg_replace('/\【公司名称\】/', $aCompany['sRealName'], $msg);
		$msg = preg_replace('/\【体检计划\】/', $aPlan['sPlanName'], $msg);
		$msg = preg_replace('/\【账号\】/', $aCompany['sUserName'], $msg);
		$msg = preg_replace('/\【密码\】/', $aCompany['sUserName'], $msg);
		$smsRes = Sms_Joying::sendBatch($aCompany['sMobile'], $msg);
		// $smsRes = Sms_Joying::sendBatch(13127732668, $msg);
		
		$content = preg_replace('/\【公司名称\】/', $aCompany['sRealName'], $content);
		$content = preg_replace('/\【体检计划\】/', $aPlan['sPlanName'], $content);
		$content = preg_replace('/\【体检开始日期\】/', $aPlan['sStartDate'], $content);
		$content = preg_replace('/\【体检截止日期\】/', $aPlan['sEndDate'], $content);
		$content = preg_replace('/\【体检人数\】/', $iCnt, $content);		
		$mailRes = Util_Mail::send($aCompany['sEmail'], 'HR年度体检计划启动通知', $content);
		if ($smsRes > 0 || $mailRes == 1) {
			return 1;
		}

		return 0;
	}
}