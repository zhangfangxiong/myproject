<?php

/**
 * 供应商接口
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/6/22
 * Time: 下午2:32
 */
class Controller_Api_Ruici extends Controller_Api_Supplier
{
    protected $pubParams;
    protected $token;
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        $config = Yaf_G::getConf('ruici', 'supplier');
        $this->pubParams = array(
            'identityID' => $config['identityID'],
            'password' => $config['password']
        );

        $this->linkUrl = $config['linkurl'];

        $this->totken = '';

        $aTotken = Model_Kv::getDetail("ruici_token");
        $iServerUpdateTime = date('Y-m-d'). " 00:00:00";
        $iServerUpdateTime = strtotime($iServerUpdateTime);

        if (!empty($aTotken) && $aTotken['iUpdateTime'] > $iServerUpdateTime) {
            $this->token = $aTotken['sData'];
        }else {
            $params['identity'] = $config['identityID'];
            $params['password'] = $config['password'];
            $params['macAddress'] = $config['macAddr'];

            $result = $this->getContentsBySoap('CreateToken', '', $params, true, $config['tokenUrl']);
            $this->token = '';
            if(!empty($result)) {
                $result = $result['CreateTokenResult'];
                $result = json_decode($result, true);

                if($result['Success']) {
                    $this->token = $result['Info'];
                }

                Model_kv::setValue('ruici_token', $this->token);
            }
        }

        $this->pubParams['token'] = $this->token;
    }

    /*
     * 一 获取机构列表接口
     */
    public function getMedicalInstitutionListAction(){
        $result = $this->getContentsBySoap('GetMedicalInstitutionList', '', $this->pubParams);

        $this->showMsg($result['GetMedicalInstitutionListResult']);
    }

    /*
     * 二 获取机构容量接口
     */
    public function getScheduleAction(){

//        $this->pubParams['InstitutionID'] = $this->getParam('institutionID');
//        $this->pubParams['timeFrom'] = $this->getParam('timeFrom');
//        $this->pubParams['timeTo'] = $this->getParam('timeTo');
//        $this->pubParams['packageIDs'] = $this->getParam('packageIDs');
//        $this->pubParams['addInItemIDs'] = $this->getParam('addInItemIDs');


        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['InstitutionID'] = isset($params['hospid']) ? $params['hospid'] : "";
            $this->pubParams['timeFrom'] = isset($params['timeFrom']) ? $params['timeFrom'] : "";
            $this->pubParams['timeTo'] = isset($params['timeTo']) ? $params['timeTo'] : "";
        }
        $result = $this->getContentsBySoap('GetCapacityOfInstitution', '', $this->pubParams);

        $result = json_decode($result['GetCapacityOfInstitutionResult'], true);

        $data = array();
        if(!empty($result['StandardCapatity'])) {
            foreach($result['StandardCapatity'] as $r) {
                if($r['Status'] == '00') {
                    $data[] = $r['Date'];
                }
            }
        }

        $data = json_encode(['data' => $data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
    }

    /*
     * 三、	获取套餐加项包接口
     */
    public function getAddinPackageInfoAction(){
        $this->pubParams['packageID'] = $this->getParam('packageID');

        $result = $this->getContentsBySoap('GetAddinPackageInfo', '', $this->pubParams);

        $this->showMsg($result['GetAddinPackageInfoResult']);
    }

    /*
    * 四、	售卡接口
    */
    public function createVirtualCardAction(){
        $this->pubParams['packageID'] = $this->getParam('packageID');

        $result = $this->getContentsBySoap('CreateVirtualCard3', '', $this->pubParams);

        $result = json_decode($result['CreateVirtualCard3Result'], true);

        $data = array(
            'code' => 0,
            'msg' => '购卡失败',
            'cardnumber' => ''
        );
        if($result['Success'] == 'true') {
            $cardinfo = $result['Info'];
            $cardinfo = explode(':', $cardinfo);
            $cardNum = $cardinfo[1];

            $data = array(
                'code' => 1,
                'msg' => '购卡成功',
                'cardnumber' => $cardNum
            );
        }

        $data = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result['CreateVirtualCard3Result']);


    }

    /*
     * 五、	售卡接口2(本接口用不到，不用管)
     */
    public function createVirtualCard2Action(){
        $this->pubParams['CheckCardName'] = $this->getParam('CheckCardName');
        $this->pubParams['cardNo'] = $this->getParam('cardNo');
        $this->pubParams['CardPassword'] = $this->getParam('CardPassword');

        $result = $this->getContentsBySoap('CreateVirtualCard2', '', $this->pubParams);

        $this->showMsg($result['CreateVirtualCard2Result']);
    }

    /*
     * 六、	预约接口
     */
    public function orderAction(){
        $type = array(
            1 => 'CID', //身份证
            2 => 'HZ',  //护照
            3 => 'JRZ', //军官证
            4 => 'HXZ', //返乡证
            5 => 'JZ',  //驾驶证
//            6 => '港澳证',
//            7 => '其他'
        );

        $this->pubParams['virtualCardID'] = $this->getParam('virtualCardID');
        $this->pubParams['InstitutionID'] = $this->getParam('InstitutionID');
        $this->pubParams['markDate'] = $this->getParam('markDate');
        $this->pubParams['packageID'] = $this->getParam('packageID');
        $this->pubParams['userInfo'] = $this->getParam('userInfo');
        $this->pubParams['attachInsideIDs'] = $this->getParam('attachInsideIDs');
        $this->pubParams['attachOutsideIDs'] = $this->getParam('attachOutsideIDs');
        $this->pubParams['oAttachIDs'] = $this->getParam('oAttachIDs');
        $this->pubParams['PackageName'] = $this->getParam('PackageName');

        $userInfo = json_decode($this->pubParams['userInfo'], true);
        $userInfo['Sex'] -= 1;
        if(1 == intval($userInfo['isMarry'])) {
            $userInfo['isMarry'] = 2;
        }else {
            $userInfo['isMarry'] = 1;
        }
        $userInfo['CertType'] = isset($type[$userInfo['CertType']]) ? $type[$userInfo['CertType']] : "";

        $this->pubParams['userInfo'] = json_encode($userInfo, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
//        $params = $this->getParam('data');
//        $params = json_decode($params, true);
//
//        if(!empty($params)) {
//            foreach($params as $key => $value) {
//                $this->pubParams[$key] = $value;
//            }
//        }

        $result = $this->getContentsBySoap('MedicalBooking', '', $this->pubParams);
        $result = json_decode($result['MedicalBookingResult'], true);

        $data = array(
            'code' => 0,
            'msg' => '预约失败',
            'orderid' => '',
            'cardnumber' => ''
        );
        if($result['flag'] == 'success' && !empty($result['ReserID'])) {
            $data = array(
                'code' => 1,
                'msg' => '预约成功',
                'orderid' => $result['ReserID'],
                'cardnumber' => $this->pubParams['virtualCardID']
            );
        }else {
            $data['msg'] = $result['ReserID'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['MedicalBookingResult']);
    }

    /*
     * 七、	取消预约接口
     */
    public function cancelOrderAction(){

//        $this->pubParams['voucherNo'] = $this->getParam('voucherNo');


        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['voucherNo'] = isset($params['orderid']) ? $params['orderid'] : "";
        }

        $result = $this->getContentsBySoap('CancelMedicalBooking', '', $this->pubParams);
        $result = json_decode($result['CancelMedicalBookingResult'], true);

        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功'
            );
        }else {
            $data['msg'] = $result['description'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result['CancelMedicalBookingResult']);
    }

    /*
     * 八、	到检验证接口
     */
    public function validateExaminationAction(){
        $this->pubParams['voucherNo'] = $this->getParam('voucherNo');

        $result = $this->getContentsBySoap('ValidateExamination', '', $this->pubParams);
        $this->showMsg($result['ValidateExaminationResult']);
    }

    /*
     * 九、	体检报告验证接口
     */
    public function validateReportAction(){
        $this->pubParams['studyID'] = $this->getParam('studyID');

        $result = $this->getContentsBySoap('ValidateReport', '', $this->pubParams);

        $this->showMsg($result['ValidateReportResult']);
    }


    /*
     * 体检状态查询
     */
    public function checkStatusAction(){
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['voucherNo'] = isset($params['orderid']) ? $params['orderid'] : "";
        }
        $result = $this->getContentsBySoap('ValidateExamination', '', $this->pubParams);
        $result = json_decode($result['ValidateExaminationResult'], true);

        $data = array(
            'code' => 0,
            'iBookStatus' => 0
        );

        if(!empty($result)) {
            $data['code'] = 1;
            $studyID = 0;

            if(isset($result['isDj']) && 1 == intval($result['isDj'])) {
                $data['iBookStatus'] = 2;//已到检
                $studyID = $result['studyid'];
            }

            if(!empty($studyID)) {
                $this->pubParams['studyID'] = $studyID;
                $result = $this->getContentsBySoap('ValidateReport', '', $this->pubParams);
                $result = json_decode($result['ValidateReportResult'], true);

                if(!empty($result)) {
                    if(isset($result['wcbz']) && 1 == intval($result['wcbz'])) {
                        $data['iBookStatus'] = 5; //已出报告
                    }
                }
            }
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);
    }



    /*
     * 十、	下载体检报告(PDF)接口
     */
    public function downloadReportAction(){
//        $this->pubParams['studyID'] = $this->getParam('studyID');
//        $this->pubParams['virtualCard'] = $this->getParam('virtualCard');
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['voucherNo'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['virtualCard'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
        }
        $result = $this->getContentsBySoap('ValidateExamination', '', $this->pubParams);
        $result = json_decode($result['ValidateExaminationResult'], true);

        $data = array(
            'code' => 0,
            'msg' => '查询失败',
            'pdf' => ''
        );

        if(isset($result['isDj']) && 1 == intval($result['isDj'])) {
            $studyID = $result['studyid'];
            $this->pubParams['studyID'] = $studyID;

            $result = $this->getContentsBySoap('DownloadReport', '', $this->pubParams, true, '', false);
            $result = json_decode($result['DownloadReportResult'], true);
            if($result['success']) {
                $data['code'] = 1;
                $data['msg'] = "查询成功";
                $data['pdf'] = $result['content'];

                $pdf = base64_decode($result['content']);
//                $hrep = fopen(APP_PATH. '/../report_ruici.pdf', "w+");
//                fwrite($hrep, $pdf);
//                fclose($hrep);

                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename="report.pdf"');
                echo $pdf;
            }else{
                $data['code'] = 1;
                $data['msg'] = $result['content'];
            }
        }else {
            $data['code'] = 1;
            $data['msg'] = '未出报告';
        }

        echo "报告还没有生成，请联系管理员";exit;

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $result = $this->getContentsBySoap('DownloadReport', '', $this->pubParams);
//        $this->showMsg($result['DownloadReportResult']);
    }

    /*
     * 十一、	注销卡接口
     */
    public function cancelVirtualCardAction(){
        $this->pubParams['cardNo'] = $this->getParam('cardNo');

        $result = $this->getContentsBySoap('CancelVirtualCard', '', $this->pubParams);

        $this->showMsg($result['CancelVirtualCardResult']);
    }

    /*
     * 十二、	售卡并预约
     */
    public function cVCMedicalBookingAction(){
        $this->pubParams['packageIDs'] = $this->getParam('packageIDs');
        $this->pubParams['cardNo'] = $this->getParam('cardNo');
        $this->pubParams['institutionID'] = $this->getParam('institutionID');
        $this->pubParams['markDate'] = $this->getParam('markDate');
        $this->pubParams['userInfo'] = $this->getParam('userInfo');
        $this->pubParams['attachInsideIDs'] = $this->getParam('attachInsideIDs');
        $this->pubParams['attachOutsideIDs'] = $this->getParam('attachOutsideIDs');
        $this->pubParams['oAttachIDs'] = $this->getParam('oAttachIDs');
        $this->pubParams['PackageName'] = $this->getParam('PackageName');

        $result = $this->getContentsBySoap('CVCMedicalBooking2', '', $this->pubParams);

        $this->showMsg($result['CVCMedicalBooking2Result']);
    }

    /*
     * 十三、	修改预约接口
     */
    public function modifymentDateAction(){

        $this->pubParams['packageIDs'] = $this->getParam('packageIDs');
        $this->pubParams['ReserID'] = $this->getParam('ReserID');
        $this->pubParams['institutionID'] = $this->getParam('institutionID');
        $this->pubParams['markDate'] = $this->getParam('markDate');
        $this->pubParams['userInfo'] = $this->getParam('userInfo');
        $this->pubParams['attachInsideIDs'] = $this->getParam('attachInsideIDs');
        $this->pubParams['attachOutsideIDs'] = $this->getParam('attachOutsideIDs');
        $this->pubParams['oAttachIDs'] = $this->getParam('oAttachIDs');
        $this->pubParams['PackageName'] = $this->getParam('PackageName');
        $this->pubParams['virtualCardID'] = $this->getParam('virtualCardID');


//        $params = $this->getParam('data');
//        $params = json_decode($params, true);
//
//        if(!empty($params)) {
//            foreach($params as $key => $value) {
//                $this->pubParams[$key] = $value;
//            }
//        }

        $result = $this->getContentsBySoap('MedicalBooking_E', '', $this->pubParams);
        $result = json_decode($result['MedicalBooking_EResult'], true);

        $data = array(
            'code' => 0,
            'msg' => '预约失败',
            'orderid' => '',
            'cardnumber' => ''
        );
        if($result['flag'] == 'success' && !empty($result['ReserID'])) {
            $data = array(
                'code' => 1,
                'msg' => '预约成功',
                'orderid' => $result['ReserID'],
                'cardnumber' => $this->pubParams['virtualCardID']
            );
        }else {
            $data['msg'] = $result['ReserID'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['MedicalBooking_EResult']);
    }

}