<?php
/**
 * 文件转移
 *  
 *  User: pancke@qq.com
 *  Date: 2015-10-28
 *  Time: 下午4:29:42
 */

class Controller_Cmd_File extends Controller_Cmd_Base
{
    public function moveAction ()
    {
        $oStorage = new File_Storage();
        $sHost = $this->getParam('host');
        $iCnt = Model_File::query('SELECT COUNT(*) FROM t_file', 'one');
        for ($i = 0; $i < $iCnt; $i += 200) {
            $aList = Model_File::query("SELECT * FROM t_file LIMIT $i,200");
            foreach ($aList as $aRow) {
                $sFileKey = $aRow['sKey'] . '.' . $aRow['sExt'];
                $sUrl = $sHost . '/view/' . $sFileKey;
                $sContent = file_get_contents($sUrl);
                $sDestFile = $oStorage->directSaveFile($aRow['sKey'], $sContent);
                
                echo "$sUrl => $sDestFile\n";
            }
        }
    }
}