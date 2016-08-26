<?php

/**
 * 供应商接口
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/6/22
 * Time: 下午2:32
 */
class Controller_Api_Meinian extends Controller_Api_Supplier
{
    protected $pubParams;
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        $iPhysicalType = intval($this->getParam('iPhysicalType'));

        if(1 == $iPhysicalType) {//普通体检,年度体检
            $config = Yaf_G::getConf('meinian_niandu', 'supplier');
        }else {//入职体检
            $config = Yaf_G::getConf('meinian_ruzhi', 'supplier');
        }

        $this->linkUrl = $config['linkurl'];

        $this->pubParams = array(
            'appKey' => $config['appKey'],
            'timestamp' => date("YmdHis")
        );

        parent::actionBefore($config['password']. $config['appKey']. $this->pubParams['timestamp']);

        $this->pubParams['sign'] = $this->signature;
    }

    /*
     * 客户预约接口
     */
    public function orderAction(){
        $url = $this->linkUrl. "Reservation/PostCreate";


        $birthday = $this->getParam('customerBirthday');
        if(!empty($birthday)) {
            $birthday = date("Ymd", strtotime($birthday));
        }

        $mentDate = $this->getParam('appointmentDate');
        if(!empty($mentDate)) {
            $mentDate = date("Ymd", strtotime($mentDate));
        }

        $this->pubParams['customerName'] = $this->getParam('customerName');
        $this->pubParams['customerIdentityNo'] = $this->getParam('customerIdentityNo');
        $this->pubParams['customerGender'] = intval($this->getParam('customerGender'));
        $this->pubParams['customerBirthday'] = $birthday;
        $this->pubParams['customerMedicalStatus'] = intval($this->getParam('customerMedicalStatus'));
        $this->pubParams['customerMobilePhone'] = $this->getParam('customerMobilePhone');
        $this->pubParams['appointmentHospitalCode'] = $this->getParam('appointmentHospitalCode');
        $this->pubParams['appointmentPackageCode'] = $this->getParam('appointmentPackageCode');
        $this->pubParams['appointmentDate'] = $mentDate;
        $this->pubParams['outOrderCode'] = $this->getParam('outOrderCode');
        $this->pubParams['hasAuthorized'] = $this->getParam('hasAuthorized');
        $this->pubParams['Dept1'] = $this->getParam('Dept1');
        $this->pubParams['AddItems'] = $this->getParam('AddItems');

        if(1 == $this->pubParams['customerMedicalStatus']) {
            $this->pubParams['customerMedicalStatus'] = 2;
        }else {
            $this->pubParams['customerMedicalStatus'] = 1;
        }

//        $params = $this->getParam('data');
//        $params = json_decode($params, true);
//
//        if(!empty($params)) {
//            foreach($params as $key => $value) {
//                $this->pubParams[$key] = $value;
//            }
//        }

        $result = $this->getContents($url, $this->pubParams, false);
        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'msg' => '预约失败',
            'orderid' => '',
            'cardnumber' => ''
        );
        if(200 == intval($result['state']) ) {
            $data = array(
                'code' => 1,
                'msg' => '预约成功',
                'orderid' => $result['result']['serviceNumber'],
                'cardnumber' => ''
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result);
    }

    /*
    * 预约改期
    */
    public function modifymentDateAction(){
        $url = $this->linkUrl. "Reservation/PostModifyAppointmentDate";

//        $serviceNumber = $this->getParam('serviceNumber');
//        $appointmentDate = $this->getParam('appointmentDate');
//        if(!empty($appointmentDate)) {
//            $appointmentDate = date("Ymd", strtotime($appointmentDate));
//        }
//
//        $this->pubParams['serviceNumber'] = $serviceNumber;
//        $this->pubParams['appointmentDate'] = $appointmentDate;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['serviceNumber'] = !empty($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['appointmentDate'] = !empty($params['regdate']) ? date("Ymd", strtotime($params['regdate'])) : "";
        }

        $result = $this->getContents($url, $this->pubParams, false);
        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(200 == intval($result['state']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功',
                'orderid' => $this->pubParams['serviceNumber'],
                'cardnumber' => ''
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result);
    }

    /*
    * 取消预约
    */
    public function cancelOrderAction(){
        $url = $this->linkUrl. "Reservation/PostCancel";

//        $this->pubParams['serviceNumber'] = $this->getParam('serviceNumber');

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['serviceNumber'] = isset($params['orderid']) ? $params['orderid'] : "";
        }

        $result = $this->getContents($url, $this->pubParams, false);
        $result = json_decode($result, true);
        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(200 == intval($result['state']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功'
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result);
    }

    /*
    * 获取门店排期
    */
    public function getScheduleAction(){
        $url = $this->linkUrl. "CheckUnit/PostScheduleByCode";

        /*
        $this->pubParams['code'] = $this->getParam('code');

        $beginDate = $this->getParam('beginDate');
        if(!empty($beginDate)) {
            $beginDate = date("Ymd", strtotime($beginDate));
        }
        $endDate = $this->getParam('endDate');
        if(!empty($endDate)) {
            $endDate = date("Ymd", strtotime($endDate));
        }
        $this->pubParams['beginDate'] = $beginDate;
        $this->pubParams['endDate'] = $endDate;
        */

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['code'] = isset($params['hospid']) ? $params['hospid'] : "";
            $this->pubParams['beginDate'] = isset($params['timeFrom']) ? date("Ymd", strtotime($params['timeFrom'])) : "";
            $this->pubParams['endDate'] = isset($params['timeTo']) ? date("Ymd", strtotime($params['timeTo'])) : "";
        }

        $result = $this->getContents($url, $this->pubParams, false);

        $result = json_decode($result, true);
        $data = array();
        if(200 == $result['state'] && !empty($result['result'])) {
            foreach($result['result'] as $r) {
                if(intval($r['personCount']) > 0) {
                    $data[] = date('Y-m-d', strtotime($r['scheduleDate']));
                }
            }
        }

        $data = json_encode(['data' => $data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);

//        $this->showMsg($result);
    }

    /*
    * 是否到检
    */
    public function isCheckedAction(){
        $url = $this->linkUrl. "Reservation/PostQueryIsChecked";

        $this->pubParams['serviceNumber'] = $this->getParam('serviceNumber');

        $result = $this->getContents($url, $this->pubParams, false);

        $this->showMsg($result);
    }

    /*
    * 是否已出报告
    */
    public function hasReportAction(){
        $url = $this->linkUrl. "Reservation/PostQueryIsCreateReport";

        $this->pubParams['serviceNumber'] = $this->getParam('serviceNumber');

        $result = $this->getContents($url, $this->pubParams, false);

        $this->showMsg($result);
    }

    /*
     * 体检状态查询
     */
    public function checkStatusAction(){
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['serviceNumber'] = isset($params['orderid']) ? $params['orderid'] : "";
        }

        $url = $this->linkUrl. "Reservation/PostQueryIsCreateReport";
        $result = $this->getContents($url, $this->pubParams, false);
        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'iBookStatus' => 0
        );

        $iState = $result['state'];
        if(200 == $result['state'] && !empty($result['result']) && $result['result']['isCreateReport']) {
            $data['iBookStatus'] = 5;//已出报告
        }else {
            $url = $this->linkUrl. "Reservation/PostQueryIsChecked";
            $result = $this->getContents($url, $this->pubParams, false);

            $result = json_decode($result, true);
            $iState = $result['state'];
            if(200 == $result['state'] && !empty($result['result']) && $result['result']['isChecked']) {
                $data['iBookStatus'] = 2;//已到检
            }
        }

        if(200 == $iState) {
            $data['code'] = 1;
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);
    }

    /*
    * 获取体检报告
    */
    public function downloadReportAction(){
//        $type = intval($this->getParam("type"));
//        if(1 == $type) {  //pdf格式报告
//            $url = $this->linkUrl. "Report/PostQueryReport";
//        }else{  //json格式报告
//            $url = $this->linkUrl. "Report/PostQueryReportJson";
//        }
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['serviceNumber'] = isset($params['orderid']) ? $params['orderid'] : "";
        }

        $url = $this->linkUrl. "Report/PostQueryReport";
        $result = $this->getContents($url, $this->pubParams, false, false);
        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'msg' => '查询失败',
            'pdf' => ''
        );

        if(200 == $result['state'] ) {
            $data = array(
                'code' => 1,
                'msg' => '查询成功',
                'pdf' => $result['result']['PDFBase64String']
            );

            $pdf = base64_decode($result['result']['PDFBase64String']);
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="report.pdf"');
            echo $pdf;
        }else {
            $data['msg'] = $result['message'];
        }

        echo "报告还没有生成，请联系管理员";exit;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);
    }

    /*
    * 设置体检报告获取权限
    */
    public function setReportAuthorizationAction(){
        $url = $this->linkUrl. "Report/PostReprotAuthorization";

        $this->pubParams['serviceNumber'] = $this->getParam('serviceNumber');
        $this->pubParams['isAuthorization'] = $this->getParam('isAuthorization'); //true or false

        $result = $this->getContents($url, $this->pubParams, false);

        $this->showMsg($result);
    }
}