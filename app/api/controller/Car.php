<?php


namespace app\api\controller;


use app\api\controller\common\Base;
use app\service\game\GameService;
use think\facade\Db;
use think\facade\Request;

class Game extends Base
{
    public $public_action = [];


    /**
     * 汽车配置
     * Created by PhpStorm.
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: 小朱
     * Date: 2024/3/27
     * Time: 10:00
     */
    public function taskConfig()
    {

        $result = GameService::taskConfig();
        return ajaxReturn($result);

    }
}