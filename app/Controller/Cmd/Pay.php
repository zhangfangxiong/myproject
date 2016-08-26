<?php
/**
 * 自媒体后台处理
 *
 *  User: pancke@qq.com
 *  Date: 2015-10-28
 *  Time: 下午4:29:42
 */

class Controller_Cmd_Pay extends Controller_Cmd_Base
{
    public function checkpayAction ()
    {
        $aList = Model_Pay::query('SELECT * FROM t_pay WHERE iStatus=0 LIMIT 100');
        $oCtl = new Controller_Payment_Weixin();
        foreach ($aList as $aRow) {
            $data = json_decode($aRow['sData'], true);
            $iPayID = $oCtl->callback($data);
            if ($iPayID > 0) {
                
            }
        }
    }
}