<?php
/**
 * @return
 *
 * User: chen
 * Date: 2021/5/24 15:21
 */

namespace app\services;

use app\api\model\News;
use app\ApiBaseController;
use app\api\model\sea\SeaNews;


class NewsServices extends ApiBaseController
{

    /**
     * Notes:资讯列表
     * User: chen
     * Date: 2021/5/24
     * Time: 15:39
     */
    public static function newsList($param_data)
    {
        $cate_id = $param_data['cate_id'];
        $page    = $param_data['page'] ?? 1;
        $limit   = $param_data['limit'] ?? 10;
        $search  = $param_data['search'] ?? '';
        $type    = $param_data['type'] ?? 1;

        $where[] = ['cate_id', '=', $cate_id];

        if (in_array($cate_id, [3, 4, 7])) {
            $info = News::where($where)
                ->field('id,title,content,subject,author,create_time')
                ->order('id', 'desc')->find();
        } else {

            if ($search) {
                $where[] = ['title', 'like', '%' . $search . '%'];
            }

            if ($type == 1) {
                $order = 'id desc';
            } else {
                $order = 'hits desc';
            }
            $info = News::where($where)
                ->field('id,title,content,subject,author,base_num,hits,create_time')
                ->page($page, $limit)
                ->order($order)->select()->toArray();

            foreach ($info as $k => &$v) {

                $v['reading_num'] = mt_rand(0, 200);

                if ($v['base_num'] == 0) {
                    $base_num  = mt_rand(2000, 10000);
                    $v['hits'] = $base_num + $v['hits'];
                    News::where('id', $v['id'])->update(['base_num' => $base_num]);
                } else {
                    $v['hits'] = $v['base_num'] + $v['hits'];
                }

                //匹配内容中的图片
                $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
                preg_match_all($pattern, $v['content'], $images);

                $v['images'] = $images[1];

                unset($v['content']);

            }
        }


        return $info;
    }

    /**
     * Notes:资讯详情
     * User: chen
     * Date: 2021/5/24
     * Time: 15:55
     */
    public static function newDetails($param_data)
    {

        $id = $param_data['id'];

        $where[] = ['id', '=', $id];
        $info    = News::Where($where)->field('id,title,cate_id,author,content,base_num,hits,create_time')->find();

        if ($info) {

            $info['reading_num'] = mt_rand(0, 200);
            $info['hits']        = $info['base_num'] + $info['hits'];

            News::where('id', $info['id'])->inc('hits')->update();
        }

        return $info;
    }
}