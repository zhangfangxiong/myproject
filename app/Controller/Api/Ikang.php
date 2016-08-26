<?php

/**
 * 供应商接口
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/6/22
 * Time: 下午2:32
 */
class Controller_Api_Ikang extends Controller_Api_Supplier
{
    protected $hospitalCode;
    protected $pubParams;
    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        $config = Yaf_G::getConf('ikang', 'supplier');

        $this->preTag = $config['preTag'];
        $this->onlyCode = $config['onlyCode'];
        $this->version = $config['version'];
        $this->linkUrl = $config['linkurl'];

        parent::actionBefore();

        $this->pubParams = array(
            'onlycode' => $this->onlyCode,
            'signature' => $this->signature,
            'version' => $this->version
        );
    }

    /*
     * 获取体检机构排期接口
     */
    public function getScheduleAction(){
        $url = $this->linkUrl. "findServiceOrderDay";

//        $hospid = $this->getParam('hospid');
//        $this->pubParams['hospid'] = $hospid;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['hospid'] = isset($params['hospid']) ? $params['hospid'] : "";
        }

        $result = $this->getContents($url, $this->pubParams);

        $result = json_decode($result, true);

        $data = array();
        if(1 == $result['code'] && !empty($result['list'])) {
            foreach($result['list'] as $r) {
                if($r['usedNum'] < $r['maxNum']) {
                    $data[] = $r['strRegdate'];
                }
            }
        }

        $data = json_encode(['data' => $data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $this->showMsg($data);
    }

    /*
     * 预约单保存接口（不含加项）
     */
    public function orderAction(){
        $url = $this->linkUrl. "saveOrderInfo";

        $cardnumber = $this->getParam('cardnumber');        //爱康卡号
        $regdate = $this->getParam('regdate');              //必须是“yyyy-mm-dd”格式
        $packagecode = $this->getParam('packagecode');     //套餐CODE
        $hospid = $this->getParam('hospid');                //体检中心ID
        $name = $this->getParam('name');                    //体检人姓名
        $sex = intval($this->getParam('sex'));                      //体检人性别 0为女，1为男
        $married = intval($this->getParam('married'));             //体检人婚否 0为未婚，1为已婚
        $contacttel = $this->getParam('contacttel');      //体检人联系方式
        $idnumber = $this->getParam('idnumber');          //体检人证件号码
        $reportaddress = $this->getParam('reportaddress');  //报告递送地址
        $thirdnum = $this->getParam('thirdnum');            //外部预约流水号

        if(2 == $sex) {
            $sex = 0;
        }
        $married -= 1;

        $xmlInfo = array(
            'cardnumber' => $cardnumber,
            'regdate' => $regdate,
            'packagecode' => $packagecode,
            'hospid' => $hospid,
            'name' => $name,
            'sex' => $sex,
            'married' => $married,
            'contacttel' => $contacttel,
            'idnumber' => $idnumber,
            'reportaddress' => $reportaddress,
            'thirdnum' => $thirdnum
        );

//        $params = $this->getParam('data');
//        $params = json_decode($params, true);
//
//        $xmlInfo = array();
//        if(!empty($params)) {
//            $xmlInfo = $params;
//        }

        $sXmlInfo = $this->genxmlinfo($xmlInfo);

        $sXmlInfo = urlencode($sXmlInfo);
        $this->pubParams['xmlinfo'] = $sXmlInfo;

        $result = $this->getContents($url, $this->pubParams);
        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'msg' => '预约失败',
            'orderid' => '',
            'cardnumber' => ''
        );
        if(1 == intval($result['code']) ) {
            $message = $result['message'];
            $message = explode('|', $message);

            $orderid = trim($message[0]);

            $http = new Util_HttpClient();
            $code = $http->post($_SERVER['HTTP_HOST']. '/api/ikang/findExamRecordInfo', array('orderid' => $orderid));
            if ($code == 200) {
                $orderInfo = json_decode($http->content, ture);

                if(1 == intval($orderInfo['code']) && !empty($orderInfo['data'])) {
                    $cardnum = $orderInfo['data'][0]['cardid'];
                }
            }

            $data = array(
                'code' => 1,
                'msg' => '预约成功',
                'orderid' => trim($message[0]), //此处需要商量是否需要再返回一个cardnumber
                'cardnumber' => $cardnum
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result);

    }

    /*
     * 取消预约单接口
     */
    public function cancelOrderAction(){
        $url = $this->linkUrl. "cancelOrder";


//        $orderid = $this->getParam('orderid');
//        $cardnumber = $this->getParam('cardnumber');
//
//        $this->pubParams['orderid'] = $orderid;
//        $this->pubParams['cardnumber'] = $cardnumber;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber ']) ? $params['cardnumber '] : "";
        }

        $result = $this->getContents($url, $this->pubParams);
        $result = json_decode($result, true);

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
//        $this->showMsg($result);
    }

    /*
     * 预约单改期接口
     */
    public function modifymentDateAction(){
        $url = $this->linkUrl. "updateOrderDate";

//        $orderid = $this->getParam('orderid');
//        $hospid = $this->getParam('hospid');
//        $cardnumber = $this->getParam('cardnumber');
//        $regdate = $this->getParam('regdate');
//
//        $this->pubParams['orderid'] = $orderid;
//        $this->pubParams['hospid'] = $hospid;
//        $this->pubParams['cardnumber'] = $cardnumber;
//        $this->pubParams['regdate'] = $regdate;

        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
            $this->pubParams['hospid'] = isset($params['hospid']) ? $params['hospid'] : "";
            $this->pubParams['regdate'] = isset($params['regdate']) ? $params['regdate'] : "";
        }

        $result = $this->getContents($url, $this->pubParams);
        $result = json_decode($result, true);

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
//        $this->showMsg($result);
    }

    /*
     * 预约到检查询接口
     */
    public function checkStatusAction(){
        $url = $this->linkUrl. "findCheckDataBack";

//        $orderid = $this->getParam('orderid');
//        $cardnumber = $this->getParam('cardnumber');
//
//        $this->pubParams['orderid'] = $orderid;
//        $this->pubParams['cardnumber'] = $cardnumber;
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
        }

        $result = $this->getContents($url, $this->pubParams);
        $result = json_decode($result, true);

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

            if(!empty($result['list']) && 1 == intval($result['list'][0]['reportstatus'])) {
                $data['iBookStatus'] == 5;//已出报告
            }else if(!empty($result['list']) && !empty($result['list'][0]['regdate'])){
                $data['iBookStatus'] = 2;//已到检
            }
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

//        $this->showMsg($result);
    }

    /*
     * 报告查询接口(下载体检报告)
     */
    public function downloadReportAction(){
        $params = $this->getParam('data');
        $params = json_decode($params, true);

        if(!empty($params)) {
            $this->pubParams['orderid'] = isset($params['orderid']) ? $params['orderid'] : "";
            $this->pubParams['cardnumber'] = isset($params['cardnumber']) ? $params['cardnumber'] : "";
        }

        $url = $this->linkUrl. "findCheckDataBack";
        $result = $this->getContents($url, $this->pubParams);
        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'msg' => '查询失败',
            'pdf' => ''
        );
        if( !empty($result['list']) && 1 == intval($result['list'][0]['reportstatus'])) {
            $this->pubParams['workno'] = $result['list'][0]['workno'];

//        $this->pubParams['cardnumber'] = '0010900071365684';
//        $this->pubParams['workno'] = '089-2020150618001';
            $url = $this->linkUrl. "findReportInfo";
            $result = $this->getContents($url, $this->pubParams, true, false);

            $result = json_decode($result, true);

            if(isset($result['code']) && 1 == intval($result['code'])) {
                $data['code'] = 1;
                $data['msg'] = '查询成功';
                $data['pdf'] = $result['message'];

                $pdf = base64_decode($result['message']);
                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename="report.pdf"');
                echo $pdf;
            }else {
                $data['code'] = 1;
                $data['msg'] = $result['message'];
            }
        }else {
            $data['code'] = 1;
            $data['msg'] = '未出报告';
        }

        echo "报告还没有生成，请联系管理员";exit;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);

    }

    /*
     * 预约单查询
     */
    public function findExamRecordInfoAction(){
        $url = $this->linkUrl. "findExamRecordInfo";

        $orderid = $this->getParam('orderid');
        $cardnumber = $this->getParam('cardnumber');

        $this->pubParams['orderid'] = $orderid;
        $this->pubParams['cardnumber'] = $cardnumber;
        $result = $this->getContents($url, $this->pubParams);

        $result = json_decode($result, true);

        $data = array(
            'code' => 0,
            'msg' => '查询失败'
        );
        if(1 == intval($result['code']) ) {
            $data = array(
                'code' => 1,
                'msg' => '查询成功',
                'data' => $result['list']
            );
        }else {
            $data['msg'] = $result['message'];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->showMsg($data);


//        $this->showMsg($result);
    }

    /*
     * 体检人注册接口
     */
    public function registrationUserAction(){
        $url = $this->linkUrl. "registrationUser";

        $cardnumber = $this->getParam('cardnumber');        //爱康卡号
        $password = $this->getParam('password');              //密码
        $name = $this->getParam('name');                    //体检人姓名
        $sex = intval($this->getParam('sex'));                      //体检人性别 0为女，1为男
        $married = intval($this->getParam('married'));             //体检人婚否 0为未婚，1为女婚
        $contacttel = $this->getParam('contacttel');      //体检人联系方式
        $idnumber = $this->getParam('idnumber');          //体检人证件号码

        $xmlInfo = array(
            'cardnumber' => $cardnumber,
            'password' => $password,
            'name' => $name,
            'sex' => $sex,
            'married' => $married,
            'contacttel' => $contacttel,
            'idnumber' => $idnumber,
        );
        $sXmlInfo = $this->genxmlinfo($xmlInfo);
        $sXmlInfo = urlencode($sXmlInfo);

        $this->pubParams['registrationXML'] = $sXmlInfo;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);

    }

    /*
     * 根据套餐CODE查询体检中心信息
     */
    public function findHospInfoByCodeAction(){
        $url = $this->linkUrl. "findHospInfoByCode";

        $code = $this->getParam('code');

        $this->pubParams['code'] = $code;
        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 预约单保存接口（不含加项，需要配置关联卡段数据）
     */
    public function saveOrderInfoAutoAction(){
        $url = $this->linkUrl. "saveOrderInfoAuto";

        $cardnumber = $this->getParam('cardnumber');        //爱康卡号
        $regdate = $this->getParam('regdate');              //必须是“yyyy-mm-dd”格式
        $packagecode = $this->getParam('packagecode');     //套餐CODE
        $hospid = $this->getParam('hospid');                //体检中心ID
        $name = $this->getParam('name');                    //体检人姓名
        $sex = intval($this->getParam('sex'));                      //体检人性别 0为女，1为男
        $married = intval($this->getParam('married'));             //体检人婚否 0为未婚，1为女婚
        $contacttel = $this->getParam('contacttel');      //体检人联系方式
        $idnumber = $this->getParam('idnumber');          //体检人证件号码
        $reportaddress = $this->getParam('reportaddress');  //报告递送地址
        $thirdnum = $this->getParam('thirdnum');            //外部预约流水号

        $xmlInfo = array(
            'cardnumber' => $cardnumber,
            'regdate' => $regdate,
            'packagecode' => $packagecode,
            'hospid' => $hospid,
            'name' => $name,
            'sex' => $sex,
            'married' => $married,
            'contacttel' => $contacttel,
            'idnumber' => $idnumber,
            'reportaddress' => $reportaddress,
            'thirdnum' => $thirdnum
        );
        $sXmlInfo = $this->genxmlinfo($xmlInfo);
        $sXmlInfo = urlencode($sXmlInfo);
        $this->pubParams['xmlinfo'] = $sXmlInfo;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);

    }

    /*
     * 预约单改期接口（根据第三方卡号改期）
     */
    public function updateOrderDateByOthercardAction(){
        $url = $this->linkUrl. "updateOrderDateByOthercard";

        $othercard = $this->getParam('othercard');
        $hospid = $this->getParam('hospid');
        $regdate = $this->getParam('regdate');

        $this->pubParams['othercard'] = $othercard;
        $this->pubParams['hospid'] = $hospid;
        $this->pubParams['regdate'] = $regdate;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 绑卡接口
     */
    public function bindCardInfoAction(){
        $url = $this->linkUrl. "bindCardInfo";

        $cardnumber = $this->getParam('cardnumber');        //爱康卡号
        $packagecode = $this->getParam('packagecode');     //套餐CODE
        $name = $this->getParam('name');                    //体检人姓名
        $sex = intval($this->getParam('sex'));                      //体检人性别 0为女，1为男
        $married = intval($this->getParam('married'));             //体检人婚否 0为未婚，1为女婚
        $contacttel = $this->getParam('contacttel');      //体检人联系方式
        $idnumber = $this->getParam('idnumber');          //体检人证件号码

        $xmlInfo = array(
            'cardnumber' => $cardnumber,
            'packagecode' => $packagecode,
            'name' => $name,
            'sex' => $sex,
            'married' => $married,
            'contacttel' => $contacttel,
            'idnumber' => $idnumber,
        );
        $sXmlInfo = $this->genxmlinfo($xmlInfo);
        $sXmlInfo = urlencode($sXmlInfo);
        $this->pubParams['xmlinfo'] = $sXmlInfo;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 预约到检查询接口
     */
    public function findCheckDataBackForPageAction(){
        $url = $this->linkUrl. "findCheckDataBackForPage";

        $orderid = $this->getParam('orderid');
        $beginTime = $this->getParam('beginTime');
        $endTime = $this->getParam('endTime');
        $pageNumber = intval($this->getParam('pageNumber'));
        $pageSize = intval($this->getParam('pageSize'));

        $pageNumber = max($pageNumber, 1);
        $pageSize = max($pageSize, 10);

        $this->pubParams['orderid'] = $orderid;
        $this->pubParams['beginTime'] = $beginTime;
        $this->pubParams['endTime'] = $endTime;
        $this->pubParams['pageNumber'] = $pageNumber;
        $this->pubParams['pageSize'] = $pageSize;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 体检报告结构化数据查询接口
     */
    public function getResultInfosAction(){
        $url = $this->linkUrl. "getResultInfos";

        $workno = $this->getParam('workno');
        $beginTime = $this->getParam('beginTime');
        $endTime = $this->getParam('endTime');
        $pageNumber = intval($this->getParam('pageNumber'));
        $pageSize = intval($this->getParam('pageSize'));

        $pageNumber = max($pageNumber, 1);
        $pageSize = max($pageSize, 10);

        $this->pubParams['workno'] = $workno;
        $this->pubParams['beginTime'] = $beginTime;
        $this->pubParams['endTime'] = $endTime;
        $this->pubParams['pageNumber'] = $pageNumber;
        $this->pubParams['pageSize'] = $pageSize;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 预约单保存接口（含加项）
     */
    public function saveOrderInfoPlusAction(){
        $url = $this->linkUrl. "saveOrderInfoPlus";

        $cardnumber = $this->getParam('cardnumber');        //爱康卡号
        $regdate = $this->getParam('regdate');              //必须是“yyyy-mm-dd”格式
        $packagecode = $this->getParam('packagecode');     //套餐CODE
        $hospid = $this->getParam('hospid');                //体检中心ID
        $name = $this->getParam('name');                    //体检人姓名
        $sex = intval($this->getParam('sex'));                      //体检人性别 0为女，1为男
        $married = intval($this->getParam('married'));             //体检人婚否 0为未婚，1为女婚
        $contacttel = $this->getParam('contacttel');      //体检人联系方式
        $idnumber = $this->getParam('idnumber');          //体检人证件号码
        $reportaddress = $this->getParam('reportaddress');  //报告递送地址
        $thirdnum = $this->getParam('thirdnum');            //外部预约流水号
        $addpackages = $this->getParam('addpackages');            //加项包可有多个,用','隔开

        $xmlInfo = array(
            'cardnumber' => $cardnumber,
            'regdate' => $regdate,
            'packagecode' => $packagecode,
            'hospid' => $hospid,
            'name' => $name,
            'sex' => $sex,
            'married' => $married,
            'contacttel' => $contacttel,
            'idnumber' => $idnumber,
            'reportaddress' => $reportaddress,
            'thirdnum' => $thirdnum,
            'addpackages' => explode(',', $addpackages)
        );
        $sXmlInfo = $this->genxmlinfo($xmlInfo, array('addpackages' => 'addpackage'));

        $sXmlInfo = urlencode($sXmlInfo);
        $this->pubParams['xmlinfo'] = $sXmlInfo;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 体检报告结构化数据查询接口（含诊断）
     */
    public function getResultInfoPlusAction(){
        $url = $this->linkUrl. "getResultInfoPlus";

        $workno = $this->getParam('workno');
        $thirdCardNo = $this->getParam('thirdCardNo');
        $beginTime = $this->getParam('beginTime');
        $endTime = $this->getParam('endTime');
        $pageNumber = intval($this->getParam('pageNumber'));
        $pageSize = intval($this->getParam('pageSize'));

        $this->pubParams['workno'] = $workno;
        $this->pubParams['thirdCardNo'] = $thirdCardNo;
        $this->pubParams['beginTime'] = $beginTime;
        $this->pubParams['endTime'] = $endTime;
        $this->pubParams['pageNumber'] = $pageNumber;
        $this->pubParams['pageSize'] = $pageSize;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }

    /*
     * 电子报告查询接口
     */
    public function findCustReportInfoAction(){
        $url = $this->linkUrl. "findCustReportInfo";

        $idnumber = $this->getParam('idnumber');
        $checkdate = $this->getParam('checkdate');

        $this->pubParams['idnumber'] = $idnumber;
        $this->pubParams['checkdate'] = $checkdate;

        $result = $this->getContents($url, $this->pubParams);

        $this->showMsg($result);
    }
}