<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Index extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        die('Welcome');
        return $this->view->fetch();
    }
}
