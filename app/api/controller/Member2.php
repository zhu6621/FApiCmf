<?php


namespace app\api\controller;


use app\api\controller\common\Base;
use app\service\member\Member2Service;
use think\facade\Db;
use think\facade\Request;

class Member2 extends Base
{
    public $public_action = [];


    /**
     * 实名认证信息
     * Created by PhpStorm.
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: 小朱
     * Date: 2023/3/18
     * Time: 11:07
     */
    public function verifyInfo()
    {

        $member_id = $this->member_id;

        $result = Member2Service::verifyInfo($member_id);
        return ajaxReturn($result);

    }

    /**
     * 活跃好友列表
     * Created by PhpStorm.
     * @return null
     * User: 小朱
     * Date: 2023/3/23
     * Time: 15:28
     */
    public function activeFriend(){
        $member_id = $this->member_id;

        $result = Member2Service::activeFriend($member_id);
        return ajaxReturn($result);
    }
}