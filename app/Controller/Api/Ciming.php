<?php

/**
 * 供应商接口
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/6/22
 * Time: 下午2:32
 */
class Controller_Api_Ciming extends Controller_Api_Supplier
{
    protected $userName;
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        $config = Yaf_G::getConf('ciming', 'supplier');
        $this->linkUrl = $config['linkurl'];

        $signCode = $config['user']. $config['password'];

        parent::actionBefore($signCode);

        $this->userName = $config['user'];
    }

    /*
     * 2.1.1	生产订单(购买)
     */
    public function createCodeAction(){
        $areaMap = array(
            1 => '0001',
            24=> '0002',
            12=> '0003',
            9=> '0004',
            5=> '0005',
            52=> '0006',
            20=> '0008',
            10=> '0015',
            13=> '0020',
            18=> '0021',
            4=> '0022',
            53=> '0023',
            2=> '0028',
            7=> '0033',
            34=> '0034',
            11=> '0037'
        );

        $xmlArray = array(
            'function' => 'CODE',
            'orderId' => $this->getParam('orderId'),
            'customerName' => $this->getParam('customerName'),
            'customerIdentityType' => $this->getParam('customerIdentityType'),
            'customerIdentityNo' => $this->getParam('customerIdentityNo'),
            'customerGender' => intval($this->getParam('customerGender')),
            'phoneNo' => $this->getParam('phoneNo'),
            'customerBirthday' => $this->getParam('customerBirthday'),
            'medicalStatus' => intval($this->getParam('medicalStatus')),
            'hasAuthorized' => $this->getParam('hasAuthorized'),
            'VIP' => $this->getParam('VIP'),
            'checkcity' => intval($this->getParam('checkcity'))
        );

        if(isset($areaMap[$xmlArray['checkcity']])) {
            $xmlArray['checkcity'] = $areaMap[$xmlArray['checkcity']];
        }else {
            $data = array(
                'code' => 0,
                'msg' => '城市不存在',
                'cardnumber' => ''
            );

            $this->showMsg($data);
        }

        if(1 == $xmlArray['medicalStatus']) {
            $xmlArray['medicalStatus'] = 2;
        }else if(2 == $xmlArray['medicalStatus']){
            $xmlArray['medicalStatus'] = 1;
        }

        $xmlinfo = $this->genxmlinfo($xmlArray, '', 'bookinfo');
        $xmlinfo = '<?xml version="1.0" encoding="UTF-8"?>'. $xmlinfo;

        $params = array(
            'arg0' => $xmlinfo,
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('createCode', '', $params);
        $result = json_decode($result['return'], true);

        $data = array(
            'code' => 0,
            'msg' => '购卡失败',
            'cardnumber' => ''
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '购卡成功',
                'cardnumber' => $result['hospitalOrderId']
            );
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.2	预约申请
    */
    public function orderAction(){

        $xmlArray = array(
            'function' => 'BOOK',
            'orderId' => $this->getParam('orderId'),
            'hospitalOrderId' => $this->getParam('hospitalOrderId'),
            'hospitalSubId' => $this->getParam('hospitalSubId'),
            'medicalPackage' => $this->getParam('medicalPackage'),
            'appointmentTime' => date("YmdHis", strtotime($this->getParam('appointmentTime'))),
            'VIP' => $this->getParam('VIP'),
        );

//        $data = $this->getParam('data');
//        $data = json_decode($data, true);
//
//        $xmlArray = array();
//        if(!empty($data)) {
//            $xmlArray = $data;
//        }

        $xmlinfo = $this->genxmlinfo($xmlArray, '', 'bookinfo');
        $xmlinfo = '<?xml version="1.0" encoding="UTF-8"?>'. $xmlinfo;

        $params = array(
            'arg0' => $xmlinfo,
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('bookServer', '', $params);
        $result = json_decode($result['return'], true);

        $data = array(
            'code' => 0,
            'msg' => '预约失败',
            'orderid' => '',
            'cardnumber' => ''
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '预约成功',
                'orderid' => $xmlArray['orderId'],
                'cardnumber' => $result['hospitalOrderId']
            );
        }else {
            $data['msg'] = $result['status'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.3	取消订单(退订)
    */
    public function cancellationOfOrderAction(){
        $orderId = $this->getParam('orderId');

        $params = array(
            'arg0' => $orderId,
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('cancellationOfOrder', '', $params);

        $this->showMsg($result['return']);
    }

    /*
    * 2.1.4	取消预约
    */
    public function cancelOrderAction(){

//        $orderId = $this->getParam('orderId');

        $data = $this->getParam('data');
        $data = json_decode($data, true);

        $params = array(
//            'arg0' => $orderId,
            'arg0' => $data['orderid'],
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('cancellationOfBook', '', $params);
        $result = json_decode($result['return'], true);

        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功'
            );
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.5	客户申请改期
    */
    public function modifymentDateAction(){

//        $xmlArray = array(
//            'function' => $this->getParam('function'),
//            'orderId' => $this->getParam('orderId'),
//            'hospitalOrderId' => $this->getParam('hospitalOrderId'),
//            'appointmentTime' => date("YmdHis", strtotime($this->getParam('appointmentTime')))
//        );

        $data = $this->getParam('data');
        $data = json_decode($data, true);

        $xmlArray = array();
        if(!empty($data)) {
            $xmlArray = array(
                'function' => "BOOKUPDATE",
                'orderId' => $data['orderid'],//预约返回的
                'hospitalOrderId' => $data['cardnumber'],//卡号
                'appointmentTime' => date("YmdHis", strtotime($data['regdate']))
            );
        }

        $xmlinfo = $this->genxmlinfo($xmlArray, '', 'bookinfo');
        $xmlinfo = '<?xml version="1.0" encoding="UTF-8"?>'. $xmlinfo;

        $params = array(
            'arg0' => $xmlinfo,
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('updateBook', '', $params);
        $result = json_decode($result['return'], true);

        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功',
                'orderid' => $xmlArray['orderId'],
                'cardnumber' => $xmlArray['hospitalOrderId']
            );
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.6 体检预约可用人数
    */
    public function getScheduleAction(){
        /*
        $hospitalSubId = $this->getParam('hospitalSubId');
        */

        $data = $this->getParam('data');
        $data = json_decode($data, true);

        $params = array(
//            'arg0' => $hospitalSubId,
            'arg0' => $data['hospid'],
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('bookNum', '', $params);

        $result = json_decode($result['return'], true);
        $data = array();
        if(!empty($result['results'])) {
            foreach($result['results'] as $r) {
                if(intval($r['canOrder']) > 0) {
                    $data[] = date('Y-m-d', strtotime($r['date']));
                }
            }
        }

        $data = json_encode(['data' => $data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);
//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.7	查询客户体检状态
    */
    public function checkStatusAction(){
//        $orderid = $this->getParam('orderid');
        $data = $this->getParam('data');
        $data = json_decode($data, true);

        $params = array(
            'arg0' => $data['orderid'],
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('checkStatus', '', $params);
        $result = json_decode($result['return'], true);
        $data = array(
            'code' => 0,
            'msg' => '查询失败'
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '查询成功',
                'iBookStatus' => 0
            );

            if(1010 == intval($result['checkstatus'])) {
                $data['iBookStatus'] = 2;//已到检
            }

            if(1020 == intval($result['checkstatus'])) {
                $data['iBookStatus'] = 5;//已出报告
            }
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);

//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.8	查询体检格式化数据
    */
//    public function checkResultAction(){
//        $starttime = $this->getParam('starttime');
//        $endtime = $this->getParam('endtime');
//        if(!empty($starttime)) {
//            $starttime = date("Y-m-d", strtotime($starttime));
//        }
//        if(!empty($endtime)) {
//            $endtime = date("Y-m-d", strtotime($endtime));
//        }
//        $params = array(
//            'arg0' => $starttime,
//            'arg1' => $endtime,
//            'arg2' => $this->userName,
//            'arg3' => $this->signature
//        );
//
//        $result = $this->getContentsBySoap('checkResult', '', $params);
//
//        $this->showMsg($result['return']);
//    }

    /*
   * 2.1.9	查询体检格式化数据
   */
    public function downloadReportAction(){
//        $orderid = $this->getParam('orderid');
        $data = $this->getParam('data');
        $data = json_decode($data, true);

        $params = array(
            'arg0' => $data['orderid'],
            'arg1' => $this->userName,
            'arg2' => $this->signature
        );

        $result = $this->getContentsBySoap('checkResult', '', $params, true, '', false);
        $result = json_decode($result['return'], true);

        $data = array(
            'code' => 0,
            'msg' => '查询失败',
            'pdf' => ''
        );
        if(200 == intval($result['status']) ) {
            $data = array(
                'code' => 1,
                'msg' => '查询成功',
                'pdf' => $result['result']
            );

            $pdf = base64_decode($result['result']);
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="report.pdf"');
            echo $pdf;
        }

        echo "报告还没有生成，请联系管理员";exit;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['return']);
    }

    /*
    * 2.1.10	查询套餐
    */
//    public function checkOrderGrpAction(){
//        $comid = $this->getParam('comid');
//
//        $params = array(
//            'arg0' => $comid,
//            'arg1' => $this->userName,
//            'arg2' => $this->signature
//        );
//
//        $result = $this->getContentsBySoap('checkOrderGrp', '', $params);
//
//        $this->showMsg($result['return']);
//    }

    /*
    * 2.1.11	查询项目
    */
//    public function checkModuleAction(){
//        $comid = $this->getParam('comid');
//
//        $params = array(
//            'arg0' => $comid,
//            'arg1' => $this->userName,
//            'arg2' => $this->signature
//        );
//
//        $result = $this->getContentsBySoap('checkModule', '', $params);
//
//        $this->showMsg($result['return']);
//    }
}