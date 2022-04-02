<?php

namespace app\api\controller;

use app\ApiBaseController;
use think\App;
use app\services\NewsServices;

class News extends ApiBaseController
{

    protected $public_action = ['newsList', 'h5NewDetails','newDetails'];
    protected $no_encrypt_action = ['h5newdetails']; //不加密即可访问
    protected $is_method_filter = true;

    /**
     * Notes:资讯列表
     * User: chen
     * Date: 2021/5/24
     * Time: 15:42
     *
     */
    public function newsList()
    {
        //接收的数据
        $param_data = $this->param_data;

        $userInfo = NewsServices::newsList($param_data);

        $this->jsonEncrypt(200, '请求成功', $userInfo);

    }

    /**
     * Notes:资讯详情
     * User: chen
     * Date: 2021/5/24
     * Time: 15:42
     */
    public function newDetails()
    {
        //接收的数据
        $param_data = $this->param_data;

        $advisoryInfo = NewsServices::newDetails($param_data);

        $this->jsonEncrypt(200, '请求成功', $advisoryInfo);

    }

    /**
     * H5资讯详情
     * Created by PhpStorm.
     * User: xiao zhu
     * Date: 2021/6/25
     * Time: 16:14
     */
    public function h5NewDetails()
    {
        //接收的数据
        $param_data = $this->param_data;

        $advisoryInfo = NewsServices::newDetails($param_data);

        $this->json_new(200, '请求成功', $advisoryInfo);

    }

}