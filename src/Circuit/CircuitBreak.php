<?php
/**
 * Created by PhpStorm.
 * User: Sixstar-Peter
 * Date: 2019/6/15
 * Time: 22:23
 */

namespace Six\Rpc\Client;


use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Redis\Redis;

class CircuitBreak
{

    const  FAILKEY = 'circuit';//记录服务失败次数的key
    const  OpenBreaker='circuit_open';
    const  FAILCOUNT = 3; //允许失败的次数
    const  SuccessCount = 3; //成功多少次之后熔断器关闭
    const  StateOpen = 1;//熔断器开启的状态
    const  StateClose = 2;//关
    const  StateHalfOpen = 3;//半开
    const  OpenTime=5; //多久时间切换到半开状态
    /**
     * @Inject("redis.pool")
     * @var \Swoft\Redis\Pool
     */
    public $redis;

    public function __construct()
    {
        //$this->redis=Redis::connection();
    }

    /**
     * 记录服务失败次数
     * @param $address
     * @return float
     */
    public function add($address,$count=null)
    {
        if($count!=null){
         return Redis::zAdd(self::FAILKEY, [$count, $address]);
        }
        return Redis::zIncrBy(self::FAILKEY, 1, $address);
    }

    /**
     * 开启服务熔断,并且设置当前服务半开启的时间
     * @param $address
     * @return int
     */
    public  function OpenBreaker($address){
         return  Redis::zAdd(self::OpenBreaker,[(time()+self::OpenTime)=>$address]);
    }

    /**
     * 获取服务状态
     * @param $address
     * @return float
     */
    public function getState($address)
    {
        $score = Redis::zScore(self::FAILKEY, $address);
        var_dump($score."成绩");
        if ($score >= self::FAILCOUNT) return self::StateOpen; //返回开启状态
        if ($score<0) return self::StateHalfOpen; //返回半开启状态
        return self::StateClose; //返回的是关闭状态
    }

}