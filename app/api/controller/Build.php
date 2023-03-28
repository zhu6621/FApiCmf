<?php


namespace app\api\controller;

use app\api\controller\common\Base;
use app\common\model\member\MemberInfo;
use app\service\build\BuildService;
use app\service\build\HighLevelRewardService;
use app\service\build\HonourRewardService;
use think\facade\Db;


class Build extends Base
{
    public $public_action = ['beseBuildInfoList'];


    /**
     * 合成物列表
     * Created by PhpStorm.
     * @return null
     * User: 小朱
     * Date: 2023/3/6
     * Time: 14:35
     */
    function list()
    {
        $type = input('post.type', 0);

        $result = BuildService::list($type);
        return ajaxReturn($result);
    }


    /**
     * 用户可交易的合成物列表
     * Created by PhpStorm.
     * @return null
     * User: 小朱
     * Date: 2023/3/8
     * Time: 17:06
     */
    function tradeList()
    {

        $member_id = $this->member_id;

        $result = BuildService::tradeList($member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取用户各个位置里的建筑物
     * date: 2023/3/10 10:18
     * @Auther: Red
     */
    function getposition()
    {
        $result = BuildService::getPosition($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 合成/移动建筑物
     * date: 2023/3/10 13:53
     * @Auther: Red
     */
    function composeBuild()
    {
        $one = input('post.one');
        $two = input('post.two');
        $result = BuildService::composeBuild($this->member_id, $one, $two);
        return ajaxReturn($result);
    }

    /**
     * 购买建筑物
     * date: 2023/3/10 16:21
     * @Auther: Red
     */
    function buyBuild()
    {
        $result = BuildService::buyBuild($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 领取银币和奖券的
     * date: 2023/3/10 17:06
     * @Auther: Red
     */
    function receiveCoins()
    {
        if (_limit_frequency('limit_frequency_receiveCoins_', $this->member_id, 2)) return ajaxReturn([], '操作频繁，请稍后~', ERROR1);
        $result = BuildService::receiveCoins($this->member_id);
        return ajaxReturn($result);
    }

    function receiveCoinsCopy()
    {
        if (_limit_frequency('limit_frequency_receiveCoinsCopy_', $this->member_id, 230)) return ajaxReturn([], '操作频繁，请稍后~', ERROR1);
        $result = BuildService::receiveCoins($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 回收建筑物
     * date: 2023/3/10 19:40
     * @Auther: Red
     */
    function recycled()
    {
        $seat = input('post.seat');
        $result = BuildService::recycled($this->member_id, $seat);
        return ajaxReturn($result);
    }

    /**
     * 获取基础合成物的图鉴列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/11 11:00
     * @Auther: Red
     */
    function baseHandbookList()
    {
        $result = BuildService::baseHandbookList($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 领取基础的图鉴奖励
     * date: 2023/3/11 13:45
     * @Auther: Red
     */
    function receiveBaseHandbook()
    {
        $buildId = input('post.build_id');
        $result = BuildService::receiveBaseHandbook($this->member_id, $buildId);
        return ajaxReturn($result);
    }

    /**
     * 获取购买建筑物信息
     * date: 2023/3/11 16:22
     * @Auther: Red
     */
    function buyInfo()
    {
        $result = BuildService::buyInfo($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取基础建筑物信息列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/11 17:17
     * @Auther: Red
     */
    function beseBuildInfoList()
    {
        $result = BuildService::beseBuildInfoList();
        return ajaxReturn($result);
    }

    /**
     * 获取进阶合成物的图鉴列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/11 17:51
     * @Auther: Red
     */
    function advancedHandbookList()
    {
        $result = BuildService::advancedHandbookList($this->member_id);
        return ajaxReturn($result);
    }

    /*
     * 一键出售（37级以下的建筑物）
     * Created by PhpStorm.
     * @return null
     * User: 小朱
     * Date: 2023/3/11
     * Time: 17:09
     */
    public function onekeySell()
    {
        $build_id = input('post.build_id', 0);
        $num = intval(input('post.num', 0));
        $result = BuildService::onekeySell($build_id, $num, $this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 领取进阶的图鉴奖励
     * date: 2023/3/11 19:56
     * @Auther: Red
     */
    function receiveAdvancedHandbook()
    {
        $buildId = input('post.build_id');
        $result = BuildService::receiveAdvancedHandbook($this->member_id, $buildId);
        return ajaxReturn($result);
    }

    /*
     * 普通建筑物列表（37级以下的建筑物）
     * Created by PhpStorm.
     * @return null
     * User: 小朱
     * Date: 2023/3/11
     * Time: 18:12
     */
    public function plainList()
    {
        $result = BuildService::plainList($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 我的背包
     * Created by PhpStorm.
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: 小朱
     * Date: 2023/3/12
     * Time: 10:01
     */
    function myBackpack()
    {
        $member_id = $this->member_id;
        $result = BuildService::myBackpack($member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取自动合成和自动购买状态
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/12 18:32
     * @Auther: Red
     */
    function getAutomaticInformation()
    {
        $memberInfo = MemberInfo::getMemberInfo($this->member_id, 'is_auto_purchase,is_auto_synthesis');
        $result['result'] = $memberInfo;
        $result['code'] = SUCCESS;
        $result['message'] = '获取成功';
        return ajaxReturn($result);
    }

    /**
     * 获取宝箱列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/12 19:48
     * @Auther: Red
     */
    function getBoxList()
    {
        $result = BuildService::getBoxList($this->member_id);
        return ajaxReturn($result);
    }

    /**
     *开宝箱
     * date: 2023/3/12 19:57
     * @Auther: Red
     */
    function drawBoxLottery()
    {
        $boxId = input('post.box_id');
        $result = BuildService::drawBoxLottery($this->member_id, $boxId);
        return ajaxReturn($result);
    }

    /**
     * 获取纪念册列表信息
     * date: 2023/3/12 21:20
     * @Auther: Red
     */
    function getAutographBookList()
    {
        $result = BuildService::getAutographBookList($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 领取纪念册奖励
     * date: 2023/3/12 22:02
     * @Auther: Red
     */
    function drawAutographBook()
    {
        $bookId = input('post.book_id');
        $result = BuildService::drawAutographBook($this->member_id, $bookId);
        return ajaxReturn($result);
    }

    /**
     * 获取的我长城
     * date: 2023/3/13 10:09
     * @Auther: Red
     */
    function getMyGreatWall()
    {
        $orderBy = input('post.order_by', 1);
        $result = BuildService::getMyGreatWall($this->member_id, $orderBy);
        return ajaxReturn($result);
    }

    /**
     * 获取用户当前宝箱
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/15 17:19
     * @Auther: Red
     */
    function newBox()
    {
        $result = BuildService::newBox($this->member_id);
        return ajaxReturn($result);
    }

    /*
     * 我的建筑物总信息
     * Created by PhpStorm.
     * @return null
     * User: 小朱
     * Date: 2023/3/15
     * Time: 16:42
     */
    public function buildInfo()
    {
        $result = BuildService::buildInfo($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取离线银币的一条数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/17 17:33
     * @Auther: Red
     */
    function getOfflineCoin()
    {
        $result = BuildService::getOfflineCoin($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 更新离线银币状态
     * @throws \think\db\exception\DbException
     * date: 2023/3/17 17:34
     * @Auther: Red
     */
    function updateOfflineCoin()
    {
        $id = input('post.id');
        $result = BuildService::updateOfflineCoin($this->member_id, $id);
        return ajaxReturn($result);
    }

    /**
     * 升阶操作
     * date: 2023/3/17 19:32
     * @Auther: Red
     */
    function ascendingOrder()
    {
        $buildId = input('post.build_id');
        $useProbability = input('post.use_probability');
        $useKeep = input('post.use_keep');
        $result = BuildService::ascendingOrder($this->member_id, $buildId, $useProbability, $useKeep);
        return ajaxReturn($result);
    }

    /**
     * 获取用户的道具列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/17 21:33
     * @Auther: Red
     */
    function getMemberPropsList()
    {
        $result = BuildService::getMemberPropsList($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取高阶奖励的排行榜
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/20 9:41
     * @Auther: Red
     */
    function getHighLevelList()
    {
        $result = HighLevelRewardService::getHighLevelList();
        return ajaxReturn($result);
    }

    /**
     * 获取高阶瓜分信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/20 9:52
     * @Auther: Red
     */
    function getHighLevelInfo()
    {
        $result = HighLevelRewardService::getHighLevelInfo($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取用户的高阶奖励记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/20 10:01
     * @Auther: Red
     */
    function getMemberHighLevelLog()
    {
        $page = input('post.page', 1);
        $rows = input('post.rows', 10);
        $result = HighLevelRewardService::getMemberHighLevelLog($this->member_id, $page, $rows);
        return ajaxReturn($result);
    }

    /**
     * 获取阶级里的人数信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/20 11:20
     * @Auther: Red
     */
    function getEachLevelInfo()
    {
        $id = input('post.id');
        $level = input('post.level');
        $result = HighLevelRewardService::getEachLevelInfo($id, $level);
        return ajaxReturn($result);
    }

    /**
     * 获取用户的道具仓库
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/20 14:44
     * @Auther: Red
     */
    function getMemberPropsProgress()
    {
        $result = BuildService::getMemberPropsProgress($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取荣耀池数据信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/21 15:33
     * @Auther: Red
     */
    function getHonourInfo()
    {
        $result = HonourRewardService::getHonourInfo($this->member_id);
        return ajaxReturn($result);
    }

    /**
     * 获取荣耀池等级的详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * date: 2023/3/21 20:33
     * @Auther: Red
     */
    function getHonourInfoDetails()
    {
        $id = input('post.id');
        $page = input('post.page', 1);
        $rows = input('post.rows', 10);
        $result = HonourRewardService::getHonourInfoDetails($id, $page, $rows);
        return ajaxReturn($result);
    }

    /**
     * 获取钥匙数量
     * date: 2023/3/23 20:07
     * @Auther: Red
     */
    function getKeyCount()
    {
        $result['code'] = SUCCESS;
        $result['message'] = '获取成功';
        $num = Db::name('member_info')->where(['member_id' => $this->member_id])->value('key_count');
        $result['result'] = $num > 0 ? $num : 0;
        return ajaxReturn($result);
    }
}