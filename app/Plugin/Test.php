<?php

class Plugin_Test extends Yaf_Plugin
{

    public function routerStartup (Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        var_dump("routerStartup");
    }

    public function routerShutdown (Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        var_dump("routerShutdown");
    }

    public function dispatchLoopStartup (Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        var_dump("dispatchLoopStartup");
    }

    public function preDispatch (Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        var_dump("preDispatch");
    }

    public function postDispatch (Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        var_dump("postDispatch");
    }

    public function dispatchLoopShutdown (Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        var_dump("dispatchLoopShutdown");
    }
}