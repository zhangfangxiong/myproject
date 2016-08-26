<?php

/**
 * 供应商接口
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/6/22
 * Time: 下午2:32
 */
class Controller_Api_Supplier extends Yaf_Controller
{

    protected $preTag = "";
    protected $onlyCode = "";
    protected $version = "";
    protected $linkUrl = "";
    protected $signature = "";

    /**
     * 执行动作之前
     */
    public function actionBefore($signCode = "")
    {
        parent::actionBefore();

        $this->signature = $this->genSignature($signCode);
    }

    public function genSignature($signCode = ""){
        if(!empty($signCode)) {
            return md5($signCode);
        }else {
            return md5($this->preTag. $this->onlyCode. '$_'. date('Y-m-d'));
        }
    }

    /*
     * 生成xml字符串
     * param $info， 数组(最多支持二维数据)
     */
    public function genxmlinfo($info, $specialedKey = array(), $xmlRoot = ""){
        $sXmlInfo = "";
        if(empty($xmlRoot)) {
            $xmlRoot = "info";
        }

        $sXmlInfo = "<". $xmlRoot. ">";

        if(!empty($info)) {
            foreach($info as $key => $value) {
                if(is_array($value)) {
                    $sXmlInfo .= "<". $key. ">";

                    if(!empty($specialedKey) && isset($specialedKey[$key])) {
                        $theKey = $specialedKey[$key];
                        foreach($value as $secondKey => $secondValue) {
                            $sXmlInfo .= "<". $theKey. ">". $secondValue. "</". $theKey. ">";
                        }
                    }else {
                        foreach($value as $secondKey => $secondValue) {
                            $sXmlInfo .= "<". $secondKey. ">". $secondValue. "</". $secondKey. ">";
                        }
                    }
                    $sXmlInfo .= "</". $key. ">";
                }else {

                    $sXmlInfo .= "<". $key. ">". $value. "</". $key. ">";
                }
            }
        }
        $sXmlInfo .= "</". $xmlRoot. ">";

        return $sXmlInfo;
    }

    public function getContents($url, $params = array(), $isget = true, $log = true){
        $http = new Util_HttpClient();

        $sParams = "";
        if(!empty($params)) {
            foreach($params as $key => $value) {
                $sParams .= '&'. $key. '='. $value;
            }
            $sParams = ltrim($sParams, '&');
        }
        if(!empty($sParams)) {
            $url .= "?". $sParams;
        }

        if($isget) {
            $code = $http->get($url);
        }else {
            $code = $http->post($url, $params);
        }

        if ($code == 200) {
            if($log) {
                $this->log($url, json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $http->content);
            }

            return $http->content;
        }else {
            $result = array(
                'code' => 0,
                'message' => $code
            );

            if($log) {
                $this->log($url, json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $code);
            }

            return json_encode($result, JSON_UNESCAPED_UNICDE | JSON_UNESCAPED_SLASHES);
        }
    }

    /*
     * $actionName  方法名
     * $wsdl wsdl文件名
     * $params 参数
     * $withWSDL  是否用wsdl文件
     * $linkurl  指定链接地址，默认就是$this->linkUrl
     */
    public function getContentsBySoap($actionName, $wsdl = '', $params = array(), $withWSDL = true, $linkurl = '', $log = true){
        require_once LIB_PATH. '/Sms/sdk/nusoap.php';

        if(!empty($linkurl)) {
            $this->linkUrl = $linkurl;
        }

        if($withWSDL) {
            $url = $this->linkUrl;
            if(!empty($wsdl)) {
                $url .= $wsdl;
            }

            $client = new nusoap_client($url. "?wsdl", 'wsdl');
        }else {
            $client = new nusoap_client($this->linkUrl);
        }
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;
        $client->xml_encoding = 'UTF-8';

        $content = $client->call($actionName, $params);

        if($log) {
            $this->log($url, json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $content);
        }
        return $content;
    }

    public function showMsg ($jData)
    {
        $response = $this->getResponse();
        $response->appendBody($jData);
        $this->autoRender(false);

        return false;
    }

    public function log($sUrl, $sRequest, $sResponse){
        if(is_array($sResponse)) {
            $sResponse = json_encode($sResponse, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }

        $data = array(
            'sControllerName' => $this->_name,
            'sUrl' => $sUrl,
            'sRequest' => $sRequest,
            'sResponse' => $sResponse,
        );

        Model_Interfacelog::addData($data);
    }
}