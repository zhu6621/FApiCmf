<?php


namespace app\api\controller;


use app\api\controller\common\Base;
use app\service\wallet\WalletService;
use think\facade\Db;
use think\facade\Request;

class Wallet extends Base
{
    public $public_action = [];


    /**
     * 用户资产余额
     * Created by PhpStorm.
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: 小朱
     * Date: 2023/3/8
     * Time: 10:50
     */
    public function balance()
    {

        $currency_id = input('post.currency_id', 0);
        $member_id   = $this->member_id;

        $result = WalletService::balance($currency_id, $member_id);
        return ajaxReturn($result);
    }
}