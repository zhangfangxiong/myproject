<?php

class Controller_Cmd_Cron extends Controller_Cmd_Base
{

    private $_mainAction = '/cmd/cron/main';

    public function stopAction ()
    {
        Util_Cron::stopMain($this->_mainAction);
        Util_Cron::stopSub();
    }

    public function startAction ()
    {
        Util_Cron::stopMain($this->_mainAction);
        Util_Cron::stopSub();
        Util_Cron::startMain($this->_mainAction);
    }

    public function restartAction ()
    {
        $this->startAction();
    }

    public function mainAction ()
    {
        Util_Cron::startSub();
    }
}