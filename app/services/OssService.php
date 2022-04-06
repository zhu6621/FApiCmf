<?php
/**
 * oss快捷方式
 * @package app\services
 */
namespace app\services;

use OSS\OssClient;
use OSS\Core\OssException;
use think\facade\Log;

class OssService
{

    /**
     * Oss 初始化参数
     * @param  string $accessId   aliyun access id
     * @param  string $accessKey  aliyun access key
     * @param  string $endPoint   aliyun endPoint
     * @return void
     * @author sam
     */
    public function __construct()
    {
        $this->accessKeyId     = config('oss.accessKeyId') ;
        $this->accessKeySecret = config('oss.accessKeySecret');
        $this->endPoint        = config('oss.endPoint');
    }

    /**
     * 获取oss客户端对象
     * @return OssClient
     * @author sam
     */
    public function getClient() {
        Log::write($this->endPoint);
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endPoint, false);
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    public function isResponseOk($resultData)
    {
        $info = $resultData['info'];
        $status = $info['http_code'];
        if ((int)(intval($status) / 100) == 2 && (int)(intval($status)) !== 203) {
            return true;
        }
        return false;

    }

    /*---------------------------以下是oss object操作方法----------------------------------------------*/
    /**
     * 上传内存中的内容
     *
     * @param  string  $bucket       bucket名称
     * @param  string  $ossFileName  objcet名称
     * @param  string  $content      上传的内容
     * @param  array   $options      kay-value选项
     * @return bool
     * @author sam
     */
    public function putObject($bucket, $ossFileName, $content, $options = NULL) {
        $res =  $this->getClient()->putObject($bucket, $ossFileName, $content, $options);
        return $this->isResponseOk($res);
    }

    /**
     * 拷贝一个在OSS上已经存在的object成另外一个object
     *
     * @param  string $fromBucket 源bucket名称
     * @param  string $fromObject 源object名称
     * @param  string $toBucket   目标bucket名称
     * @param  string $toObject   目标object名称
     * @param  array  $options    选项
     * @return void
     * @throws OssException
     * @author sam
     */
    public function copyObject($fromBucket, $fromOssFileName, $toBucket, $toOssFileName, $options = NULL) {
        $res = $this->getClient()->copyObject($fromBucket, $fromOssFileName, $toBucket, $toOssFileName, $options);
        return $this->isResponseOk($res);
    }

    /**
     * 获取Object的Meta信息
     *
     * @param  string $bucket      bucket名称
     * @param  string $ossFileName oss相对文件名
     * @param  string $options     具体参考SDK文档
     * @return array
     * @author sam
     */
    public function getObjectMeta($bucket, $ossFileName, $options = NULL) {
        return $this->getClient()->getObjectMeta($bucket, $ossFileName, $options);
    }

    /**
     * 删除某个Object
     *
     * @param  string $bucket      bucket名称
     * @param  string $ossFileName oss相对文件名
     * @param  array  $options      选项
     * @return bool
     * @author sam
     */
    public function deleteObject($bucket, $ossFileName, $options = NULL) {
        $res = $this->getClient()->deleteObject($bucket, $ossFileName, $options);
        return $this->isResponseOk($res);
    }

    /**
     * 获取bucket下的object列表
     * @param (string, 必选) $bucket     资源仓名字
     * @param (string, 可选) $prefix     限定返回的Object key必须以prefix作为前缀,其中 prefix，marker用来实现分页显示效果，参数的长度必须小于256字节。
     * @param (string, 可选) $maxKeys    用于限定此次返回object的最大数，如果不设定，默认为100。
     * @param (string, 可选) $delimiter  用于对Object名字进行分组的字符
     * @param (string, 可选) $marker     用户设定结果从marker之后按字母排序的第一个开始返回
     * @return array
     * @author sam
     */
    public function listObjects($bucket, $prefix = '', $max = 1000, $delimiter = '', $marker = '') {
        $options = array(
            'max-keys'  => $max, //max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
            'prefix'    => $prefix, //限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
            'delimiter' => $delimiter, //是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
            'marker'    => $marker, //用户设定结果从marker之后按字母排序的第一个开始返回。
        );
        $res         = (array) $this->getClient()->listObjects($bucket, $options)->getObjectList();
        $fileListArr = array();
        foreach ($res as $key => $obj) {
            $fileListArr[] = $obj->getKey();
        }
        return $fileListArr;
    }

    /**
     * 删除同一个Bucket中的多个Object
     *
     * @param  string $bucket          bucket名称
     * @param  array  $ossFileNameArr  oss相对文件名列表
     * @param  array  $options         选项
     * @return bool
     * @author sam
     */
    public function deleteObjects($bucket, $ossFileNameArr, $options = null) {
        $res = $this->getClient()->deleteObjects($bucket, $ossFileNameArr, $options);
        return $this->isResponseOk($res);
    }

    /**
     * 获得Object内容
     *
     * @param  string $bucket       bucket名称
     * @param  string $ossFileName  oss相对文件名
     * @param  array  $options      该参数中必须设置ALIOSS::OSS_FILE_DOWNLOAD，ALIOSS::OSS_RANGE可选，可以根据实际情况设置；如果不设置，默认会下载全部内容
     * @return string
     * @author sam
     */
    public function getObject($bucket, $ossFileName, $options = NULL) {
        return $this->getClient()->getObject($bucket, $ossFileName, $options);
    }

    /**
     * 检测Object是否存在
     * 通过获取Object的Meta信息来判断Object是否存在， 用户需要自行解析ResponseCore判断object是否存在
     *
     * @param  string $bucket       bucket名称
     * @param  string $ossFileName  oss相对文件名
     * @param  array  $options      选项
     * @return bool
     * @author sam
     */
    public function doesObjectExist($bucket, $ossFileName, $options = NULL) {
        return $this->getClient()->doesObjectExist($bucket, $ossFileName, $options);
    }

    /**
     * 支持生成get和put签名, 用户可以生成一个具有一定有效期的
     * 签名过的url
     *
     * @param  string $bucket       bucket名称
     * @param  string $ossFileName  oss相对文件名
     * @param  int    $timeout      超时时间，单位秒
     * @param  string $method       访问方法，默认GET,支持GET|POST
     * @param  array  $options      Key-Value数组
     * @return string
     * @throws OssException
     * @author sam
     */
    public function signUrl($bucket, $ossFileName, $timeout = 3600, $method = 'GET', $options = NULL) {
        $options = empty($options) ? array('response-content-type' => 'application/octet-stream') : $options;
        $result = $this->getClient()->signUrl($bucket, $ossFileName, $timeout, $method, $options);
        $result = str_replace('-internal.aliyuncs.com', '.aliyuncs.com', $result);
        return $result;
    }

    /*---------------------------以下是上传文件操作方法----------------------------------------------*/
    /**
     * multipart上传统一封装，从初始化到完成multipart，以及出错后中止动作，支持断点续传
     *
     * @param  string $bucket          bucket名称
     * @param  string $ossFileName     oss相对文件名
     * @param  string $localFileName   需要上传的本地文件的路径
     * @param  array  $options         Key-Value数组
     * @return void
     * @throws OssException
     * @author sam
     */
    public function multiuploadFile($bucket, $ossFileName, $localFileName, $options = null) {
        $res = $this->getClient()->multiuploadFile($bucket, $ossFileName, $localFileName, $options);
        return $this->isResponseOk($res);
    }

    /**
     * 把本地的$localFileName上传到指定$bucket, 命名为$ossFileName
     * @param  string  $bucketName     bucket名字
     * @param  string  $localFileName  本地文件完整名
     * @param  string  $ossFileName    oss相对文件名
     * @return boolean
     * @author sam
     */
    public function uploadFile($bucketName, $ossFileName, $localFileName) {
        $res = $this->getClient()->uploadFile($bucketName, $ossFileName, $localFileName);
        return $this->isResponseOk($res);
    }

}