<?php

/**
 * 供应商接口
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/6/22
 * Time: 下午2:32
 */
class Controller_Api_Renai extends Controller_Api_Supplier
{
    protected $hospitalCode;
    protected $pubParams;
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        $config = Yaf_G::getConf('renai', 'supplier');

        $this->preTag = $config['preTag'];
        $this->onlyCode = $config['onlyCode'];
        $this->version = $config['version'];
        $this->linkUrl = $config['linkurl'];

        parent::actionBefore();

        $this->pubParams = array(
            'onlyCode' => $this->onlyCode,
            'onlycode' => $this->onlyCode,
            'signature' => $this->signature,
            'version' => $this->version
        );
    }

    /*
     * 获取体检机构排期接口
     */
    public function getScheduleAction(){
//        $hospid = $this->getParam('hospid');
//        $this->pubParams['hospitalID'] = $hospid;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['hospitalID'] = isset($params['hospid']) ? $params['hospid'] : "";
        }

        $result = $this->getContentsBySoap('getPhysicalTestSchedual', 'iSchedualWebService', $this->pubParams);
        $result = json_decode($result['out'], true);
        $data = array();
        if(1 == $result['code'] && !empty($result['list'])) {
            foreach($result['list'] as $r) {
                $data[] = date('Y-m-d', strtotime($r['appointedDate']));
            }
        }

        $data = json_encode(['data' => $data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);
//        $this->showMsg($result['out']);
    }

    /*
     * 预约单保存接口（不含加项）
     */
    public function orderAction(){

        $cardnumber = $this->getParam('cardnumber');        //爱康卡号
        $regdate = $this->getParam('regdate');              //必须是“yyyy-mm-dd”格式
        $packagecode = $this->getParam('packagecode');     //套餐CODE
        $adittion = $this->getParam('adittion');            //
        $additioncode = $this->getParam('additioncode');            //
        $hospid = $this->getParam('hospid');                //体检中心ID
        $name = $this->getParam('name');                    //体检人姓名
        $sex = $this->getParam('sex');                      //体检人性别 0为女，1为男
        $married = intval($this->getParam('married'));             //体检人婚否 0为未婚，1为女婚
        $contacttel = $this->getParam('contacttel');      //体检人联系方式
        $idnumber = $this->getParam('idnumber');          //体检人证件号码
        $reportaddress = $this->getParam('reportaddress');  //报告递送地址
        $thirdnum = $this->getParam('thirdnum');            //外部预约流水号

        if(2 == $sex) {
            $sex = 0;
        }
        $married -= 1;

        $this->pubParams['cardnumber'] = $cardnumber;
        $this->pubParams['regdate'] = $regdate. "T00:00:00";
        $this->pubParams['packagecode'] = $packagecode;
        $this->pubParams['adittion'] = $adittion;
        $this->pubParams['additioncode'] = $additioncode;
        $this->pubParams['hospid'] = $hospid;
        $this->pubParams['name'] = $name;
        $this->pubParams['sex'] = $sex;
        $this->pubParams['married'] = $married;
        $this->pubParams['contacttel'] = $contacttel;
        $this->pubParams['idnumber'] = $idnumber;
        $this->pubParams['reportaddress'] = $reportaddress;
        $this->pubParams['thirdnum'] = $thirdnum;

//        $params = $this->getParam('data');
//        $params = json_decode($params, true);
//
//        if(!empty($params)) {
//            foreach($params as $key => $value) {
//                $this->pubParams[$key] = $value;
//            }
//        }

        $result = $this->getContentsBySoap('saveOrderInfo', 'iOrderWebService', $this->pubParams);
        $result = json_decode($result['out'], true);

        $data = array(
            'code' => 0,
            'msg' => '预约失败',
            'orderid' => '',
            'cardnumber' => ''
        );
        if(1 == intval($result['code']) ) {
            $message = $result['message'];
            $message = explode('|', $message);
            $data = array(
                'code' => 1,
                'msg' => '预约成功',
                'orderid' => trim($message[0]),
                'cardnumber' => $cardnumber
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result['out']);
    }

    /*
     * 取消预约单接口
     */
    public function cancelOrderAction(){

//        $orderid = $this->getParam('orderid');
//        $cardnumber = $this->getParam('cardnumber');
//        $hospid = $this->getParam('hospid');
//
//        $this->pubParams['orderid'] = $orderid;
//        $this->pubParams['cardnumber'] = $cardnumber;
//        $this->pubParams['hospid'] = $hospid;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
            $this->pubParams['hospid'] = isset($params['hospid']) ? $params['hospid'] : "";
        }

        $result = $this->getContentsBySoap('updateOrderStatus', 'iOrderWebService', $this->pubParams);
        $result = json_decode($result['out'], true);

        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(1 == intval($result['code']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功'
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);
//        $this->showMsg($result['out']);
    }

    /*
     * 预约单改期接口
     */
    public function modifymentDateAction(){

//        $orderid = $this->getParam('orderid');
//        $cardnumber = $this->getParam('cardnumber');
//        $hospid = $this->getParam('hospid');
//        $regdate = $this->getParam('regdate');
//
//        $this->pubParams['orderid'] = $orderid;
//        $this->pubParams['cardnumber'] = $cardnumber;
//        $this->pubParams['regdate'] = $regdate;
//        $this->pubParams['hospid'] = $hospid;


        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
            $this->pubParams['hospid'] = isset($params['hospid']) ? $params['hospid'] : "";
            $this->pubParams['regdate'] = isset($params['regdate']) ? $params['regdate'] : "";
        }
        $result = $this->getContentsBySoap('updateOrderDate', 'iOrderWebService', $this->pubParams);
        $result = json_decode($result['out'], true);

        $data = array(
            'code' => 0,
            'msg' => '更新失败'
        );
        if(1 == intval($result['code']) ) {
            $data = array(
                'code' => 1,
                'msg' => '更新成功',
                'orderid' => $this->pubParams['orderid'],
                'cardnumber' => $this->pubParams['cardnumber']
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['out']);
    }

    /*
     * 预约到检查询接口
     */
    public function checkStatusAction(){
//        $orderid = $this->getParam('orderid');
//        $cardnumber = $this->getParam('cardnumber');
//        $hospid = $this->getParam('hospid');
//
//        $this->pubParams['orderid'] = $orderid;
//        $this->pubParams['cardnumber'] = $cardnumber;
//        $this->pubParams['hospid'] = $hospid;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
            $this->pubParams['hospid'] = isset($params['hospid']) ? $params['hospid'] : "";
        }

        $result = $this->getContentsBySoap('getArriveHosInfo', 'iOrderWebService', $this->pubParams);
        $result = json_decode($result['out'], true);

        $data = array(
            'code' => 0,
            'msg' => '查询失败',
            'iBookStatus' => 0
        );
        if(1 == intval($result['code']) ) {
            $data = array(
                'code' => 1,
                'msg' => '查询成功',
                'iBookStatus' => 0
            );

            if("该订单用户已经开始检查" == $result['message']){
                $data['iBookStatus'] = 2;//已到检
            }else if("该订单用户已经检查完毕" == $result['message']){
                $data['iBookStatus'] = 5;//已出报告
            }

        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['out']);
    }

    /*
     * 报告查询接口
     */
    public function downloadReportAction(){
//        $workno = $this->getParam('workno');
//        $cardnumber = $this->getParam('cardnumber');
//        $hospid = $this->getParam('hospid');
//
//        $this->pubParams['workno'] = $workno;
//        $this->pubParams['cardnumber'] = $cardnumber;
//        $this->pubParams['hospid'] = $hospid;
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
            $this->pubParams['hospid'] = isset($params['hospid']) ? $params['hospid'] : "";
        }


        $data = array(
            'code' => 0,
            'msg' => '查询失败',
            'pdf' => ''
        );

        $result = $this->getContentsBySoap('getReportsInfo', 'iOrderWebService', $this->pubParams, true, '', false);
//echo $result['out'];exit;

        //var_dump($result);exit;
        $result = json_decode($result['out'], true);
        if(1 == intval($result['code'])) {
            $data = array(
                'code' => 1,
                'msg' => '查询成功',
//                'pdf' => $result['message']
            );
            $content = $result['list'];

            $html = '<!DOCTYPE html>
                        <html lang="en">
                            <head>
                            <meta http-equiv="content-type" content="text/html;charset=utf-8">
                                <title>体检卡</title>
                                <style>
                                table td{border:1px solid #417daf;border-bottom:0px;padding:0;margin:0;line-height: 30px;text-indent:8px;}
                                .title{font-size:22px;font-weight:bold;}
                                table{width:100%;margin-top:20px;}
                                body{padding:20px 10px;font-family:"宋体";}
                                .lefttitle{text-align: center;background-color:#89c6c7;}
                                .toptitle{font-weight: bold;}
                                table td.blank1{border-right:none;border-bottom:none;}
                                table td.blank0{border-left:none;border-bottom:none;}
                                table td.blank{border-right:none;border-bottom:none;border-top:none;}
                                table td.blank2{border-left:none;border-bottom:none;border-top:none;}
                                table td.bdrnone{border-right:none;}
                                table td.bdbtm{border-bottom:1px solid #417daf;}
                                </style>
                            </head>
                            <body>';
            if(!empty($content)) {
                $pdfContent = array();
                foreach ($content as $c) {
                    if(!isset($pdfContent[$c['hospitalKsmc']])) {
                        $pdfContent[$c['hospitalKsmc']] = array();
                    }

                    $pdfContent[$c['hospitalKsmc']][] = array(
                        'name' => $c['reportXmmc'],
                        'value' => $c['reportJg']
                    );
                }
                $sex = ( 1== intval($content[0]['personSex'])) ? "男" : "女";
                $marrigage = ( 1== intval($content[0]['personMarried'])) ? "已婚" : "未婚";

                $html .='<table cellpadding="0" cellspacing="0">
                            <tr>
                                <td colspan="4">卡号：'.$content[0]['cardnumber']. '</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: center;" class="title">仁爱健康体检体检报告书</td>
                            </tr>
                            <tr>
                                <td width="25%" class="lefttitle bdrnone">姓名：</td>
                                <td width="30%" class="bdrnone">'. $content[0]['personName']. '</td>
                                <td class="blank1"></td>
                                <td class="blank0"></td>
                            </tr>
                            <tr>
                                <td class="lefttitle bdrnone">性别：</td>
                                <td class="bdrnone">'. $sex. '</td>
                                <td class="blank"></td>
                                <td class="blank2"></td>
                            </tr>
                            <tr>
                                <td class="lefttitle bdrnone">检查日期：</td>
                                <td class="bdrnone">'. $content[0]['reportJcrq']. '</td>
                                <td class="blank"></td>
                                <td class="blank2"></td>
                            </tr>
                            <tr>
                                <td class="lefttitle bdrnone">年龄：</td>
                                <td class="bdrnone">'. $content[0]['personAge']. '岁</td>
                                <td class="blank"></td>
                                <td class="blank2"></td>
                            </tr>
                            <tr>
                                <td class="lefttitle bdrnone">证件号：</td>
                                <td colspan="3"  class="bdbtm">'. $content[0]['personIdcardnum']. '</td>
                            </tr>
                        </table>';

                foreach($pdfContent as $key => $items) {
                    $html .= '<table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td colspan="2" class="title">'. $key. '</td>
                                </tr>
                                <tr>
                                    <td class="lefttitle toptitle bdrnone">项目名称：</td>
                                    <td class="lefttitle toptitle">检查结果：</td>
                                </tr>';

                    foreach($items as $item) {
                        $html .='<tr>
                                    <td class="bdrnone">'. $item['name']. '</td>
                                    <td>'. $item['value']. '</td>
                                </tr>';
                    }


                    $html .= '<tr><td colspan="4" style="text-align: right;padding-right:8px" class="bdbtm">检查医生：'. $content['0']['reportZjys']. '</td></tr></table>';
                }

            }

            $html .="</body></html>";
//echo $html;exit;
            $hrep = fopen(APP_PATH. '/../report.html', "w+");
            fwrite($hrep, $html);
            fclose($hrep);

            exec("/usr/local/bin/wkhtmltopdf ". APP_PATH. "/../report.html ". APP_PATH. "/../report.pdf");

            $pdf = file_get_contents(APP_PATH. "/../report.pdf");
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="report.pdf"');
            echo $pdf;

        }else {
            $data['code'] = 1;
            $data['msg'] = $result['message'];
        }

        echo "报告还没有生成，请联系管理员";exit;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result['out']);
    }

    /*
     * 预约单查询
     */
    public function findExamRecordInfoAction(){
        $orderid = $this->getParam('orderid');
        $hospid = $this->getParam('hospid');

        $this->pubParams['orderid'] = $orderid;
        $this->pubParams['hospid'] = $hospid;

        $result = $this->getContentsBySoap('getOrderDeatilList', 'iOrderWebService', $this->pubParams);

        $this->showMsg($result['out']);
    }

    /*
     * 追加加项
     */
    public function updatePackagecodeAdditionAction(){
        $orderid = $this->getParam('orderid');
        $cardnumber = $this->getParam('cardnumber');
        $additioncode = $this->getParam('additioncode');
        $hospid = $this->getParam('hospid');

        $this->pubParams['orderid'] = $orderid;
        $this->pubParams['cardnumber'] = $cardnumber;
        $this->pubParams['additioncode'] = $additioncode;
        $this->pubParams['hospid'] = $hospid;

        $result = $this->getContentsBySoap('updatePackagecodeAddition', 'iOrderWebService', $this->pubParams);

        $this->showMsg($result['out']);
    }


    /*
     * 到检状态查询接口
     */
    public function findCheckDataBackForPageAction(){
        $regdatestart = $this->getParam('regdatestart');
        $regdatend = $this->getParam('regdatend');
        $hospid = $this->getParam('hospid');

        $this->pubParams['regdatestart'] = $regdatestart;
        $this->pubParams['regdatend'] = $regdatend;
        $this->pubParams['hospid'] = $hospid;

        $result = $this->getContentsBySoap('getTocheckStatusInfo', 'iOrderWebService', $this->pubParams);

        $this->showMsg($result['out']);
    }

}