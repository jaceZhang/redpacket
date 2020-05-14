<?php

$redis=new Redis();
$redis->connect('127.0.0.1',6379);
$uid=$_GET['uid'];
//首先判断该用户是否已领取该红包
$yesNum=$redis->lLen('yes-1');
if(!$yesNum){ //redis中没有查询到key,查询数据库
    //数据库操作查询当前红包该用户是否已抢，没有抢则返回红包已抢完，已抢则返回已抢金额
    echo "当前红包该用户是否已抢，没有抢则返回红包已抢完，已抢则返回已抢金额";
}else{
    $list=$redis->lRange('yes-1',0,-1);
    //判断当前用户是否在数组中
    if(in_array($uid,$list)){
        //返回用户抢的金额
        echo "数据库中的金额";
    }else{
        $len=$redis->lLen('no-1');
        if(!$len){
            echo "你手速慢了,红包已抢完!";
            exit;
        }
        $redis->lPush('yes-1',$uid);//将用户放入已抢该红包列表中
        //抢到后更新数据库
        echo "你抢的金额为:".$redis->lPop('no-1');
    }
}










