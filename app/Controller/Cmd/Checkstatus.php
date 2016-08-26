<?php
/**
 * 自媒体后台处理
 *
 *  User: Chasel
 *  Date: 2016-08-09
 *  Time: 下午4:29:42
 */

class Controller_Cmd_Checkstatus extends Controller_Cmd_Base
{
    public function indexAction ()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3000M');


//        $count = Model_OrderCardProduct::getCnt(['where' => ['iBookStatus' => 1]]);
        $count = Model_OrderCardProduct::getCnt(['where' => ['iAutoID' => 1149]]);
        if ($count > 0) {
            $iPagesize = 1000;
            $iPage = ceil($count / $iPagesize);

            $suppliers = Yaf_G::getConf("aHasApi", "suppliers");
            $supplierCodes = array_keys($suppliers);

            for ($i = 0; $i < $iPage; $i++) {
                $start = $i * $iPagesize;
                $sSQL = "SELECT ocp.*, store.sStoreCode, sup.sCode FROM ". Model_OrderCardProduct::TABLE_NAME. " as ocp".
                    " left join ". Model_Store::TABLE_NAME. " as store on ocp.iStoreID = store.iStoreID".
                    " left join ".Model_Type::TABLE_NAME. " as sup on store.iSupplierID = sup.iTypeID".
                    " WHERE ocp.iBookStatus = 1 and ocp.iAutoID = 1149 Limit $start,$iPagesize";


                $ocps = Model_OrderCardProduct::query($sSQL);
                if(!empty($ocps)) {
                    foreach($ocps as $ocp) {
                        if(in_array($ocp['sCode'], $supplierCodes)) {
                            $className = $suppliers[$ocp['sCode']]['classname'];

                            $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $className . '/checkStatus/';

                            $aParam['orderid'] = $ocp['sApiReserveOrderID'];
                            $aParam['cardnumber'] = $ocp['sApiCardID'];
                            $aParam['hospid'] = $ocp['sStoreCode'];

                            $card = Model_OrderCard::getDetail($ocp['iCardID']);
                            $iPhysicalType = !empty($card) ? $card['iPhysicalType'] : 2;

                            $sUrl .= "?data=" . json_encode($aParam). "&iPhysicalType=$iPhysicalType";

                            $aData = self::curl($sUrl);
                            $aData = json_decode($aData, true);

                            if(!empty($aData) && !empty($aData['code']) && !empty($aData['iBookStatus'])) {
                                $updData = array(
                                    'iAutoID' => $ocp['iAutoID'],
                                    'iBookStatus' => $aData['iBookStatus']
                                );

                                Model_OrderCardProduct::updData($updData);
                            }else {
                                continue;
                            }
                        }else {
                            continue;
                        }
                    }
                }
            }

        }

        echo 'Finish log', PHP_EOL;
    }

    public static function curl($sUrl, $bPost = false, $aData = array(), $host = '', $header = [], $isRedirect = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($host)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $host);//设置host
        }
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($bPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if ($bPost && !empty($aData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);
        }
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        if ($isRedirect) {
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $content = curl_exec($ch);
        $response = curl_getinfo($ch);
        curl_close($ch);
        return $content;
    }
}