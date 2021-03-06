<?php

/**
 * 生成随机红包算法一
 * Class CalculateRedPacket
 */
class CalculateRedPacket{
    private $rewardMoney; #红包金额、单位元
    private $rewardNum; #红包数量

    #执行红包生成算法
    private function splitReward($rewardMoney, $rewardNum, $max, $min)
    {
        #传入红包金额和数量，因为小数在计算过程中会出现很大误差，所以我们直接把金额放大100倍，后面的计算全部用整数进行
        $min = $min * 100;
        $max = $max * 100;
        #预留出一部分钱作为误差补偿，保证每个红包至少有一个最小值
        $this->rewardMoney = $rewardMoney * 100 - $rewardNum * $min;
        $this->rewardNum = $rewardNum;
        #计算出发出红包的平均概率值、精确到小数4位。
        $avgRand = 1 / $this->rewardNum;
        $randArr = array();
        #定义生成的数据总合sum
        $sum = 0;
        $t_count = 0;
        while ($t_count < $rewardNum) {
            #随机产出四个区间的额度
            $c = rand(1, 100);
            if ($c < 15) {
                $t = round(sqrt(mt_rand(1, 1500)));
            } else if ($c < 65) {
                $t = round(sqrt(mt_rand(1500, 6500)));
            } else if ($c < 95) {
                $t = round(sqrt(mt_rand(6500, 9500)));
            } else {
                $t = round(sqrt(mt_rand(9500, 10000)));
            }
            ++$t_count;
            $sum += $t;
            $randArr[] = $t;
        }

        #计算当前生成的随机数的平均值，保留4位小数
        $randAll = round($sum / $rewardNum, 4);

        #为将生成的随机数的平均值变成我们要的1/N，计算一下每个随机数要除以的总基数mixrand。此处可以约等处理，产生的误差后边会找齐
        #总基数 = 均值/平均概率
        $mixrand = round($randAll / $avgRand, 4);

        #对每一个随机数进行处理，并乘以总金额数来得出这个红包的金额。
        $rewardArr = array();
        foreach ($randArr as $key => $randVal) {
            #单个红包所占比例randVal
            $randVal = round($randVal / $mixrand, 4);
            #算出单个红包金额
            $single = floor($this->rewardMoney * $randVal);
            #小于最小值直接给最小值
            if ($single < $min) {
                $single += $min;
            }
            #大于最大值直接给最大值
            if ($single > $max) {
                $single = $max;
            }
            #将红包放入结果数组
            $rewardArr[] = $single;
        }

        #对比红包总数的差异、将差值放在第一个红包上
        $rewardAll = array_sum($rewardArr);
        $rewardArr[0] = $rewardMoney * 100 - ($rewardAll - $rewardArr[0]);#此处应使用真正的总金额rewardMoney，$rewardArr[0]可能小于0

        #第一个红包小于0时,做修正
        if ($rewardArr[0] < 0) {
            rsort($rewardArr);
            $this->add($rewardArr, $min);
        }

        rsort($rewardArr);
        #随机生成的最大值大于指定最大值
        if ($rewardArr[0] > $max) {
            #差额
            $diff = 0;
            foreach ($rewardArr as $k => &$v) {
                if ($v > $max) {
                    $diff += $v - $max;
                    $v = $max;
                } else {
                    break;
                }
            }
            $transfer = round($diff / ($this->rewardNum - $k + 1));
            $this->diff($diff, $rewardArr, $max, $min, $transfer, $k);
        }
        return $rewardArr;
    }

    #处理所有超过最大值的红包
    private function diff($diff, &$rewardArr, $max, $min, $transfer, $k)
    {
        #将多余的钱均摊给小于最大值的红包
        for ($i = $k; $i < $this->rewardNum; $i++) {
            #造随机值
            if ($transfer > $min * 20) {
                $aa = rand($min, $min * 20);
                if ($i % 2) {
                    $transfer += $aa;
                } else {
                    $transfer -= $aa;
                }
            }
            if ($rewardArr[$i] + $transfer > $max) continue;
            if ($diff - $transfer < 0) {
                $rewardArr[$i] += $diff;
                $diff = 0;
                break;
            }
            $rewardArr[$i] += $transfer;
            $diff -= $transfer;
        }
        if ($diff > 0) {
            $i++;
            $this->diff($diff, $rewardArr, $max, $min, $transfer, $k);
        }
    }

    #第一个红包小于0,从大红包上往下减
    private function add(&$rewardArr, $min)
    {
        foreach ($rewardArr as &$re) {
            $dev = floor($re / $min);
            if ($dev > 2) {
                $transfer = $min * floor($dev / 2);
                $re -= $transfer;
                $rewardArr[$this->rewardNum - 1] += $transfer;
            } elseif ($dev == 2) {
                $re -= $min;
                $rewardArr[$this->rewardNum - 1] += $min;
            } else {
                break;
            }
        }
        if ($rewardArr[$this->rewardNum - 1] > $min || $rewardArr[$this->rewardNum - 1] == $min) {
            return;
        } else {
            $this->add($rewardArr, $min);
        }
    }


    /**
     * @param $total
     * @param $num
     * @param $max
     * @param $min
     * @return array
     * @author deng    (2019/9/30 15:59)
     */
    private function random_red($total, $num, $max, $min)
    {
        //	总共要发的红包金额，留出一个最大值;
        $total = $total - $max;
        $result_merge = $this->splitReward($total, $num, $max - 0.01, $min);
        sort($result_merge);
        $result_merge[1] = $result_merge[1] + $result_merge[0];
        $result_merge[0] = $max * 100;
        foreach ($result_merge as &$v) {
            $v = floor($v) / 100;
        }
        return $result_merge;
    }

    /**
     * 保留2未小数，并不做四舍五入
     * @param $value
     * @return string
     * @author deng    (2019/9/27 16:27)
     */
    private function reckon($value)
    {
        return sprintf("%.2f", substr(sprintf("%.3f", $value), 0, -2));
    }

    /**
     * 生成红包
     * @param $total
     * @param $num
     * @param $min
     * @return array
     * @author deng    (2019/9/27 14:22)
     */
    public function red_packet($total, $num, $min)
    {
        if ($num == 1) {
            //  数组中只有一个时，则直接返回
            $result = [
                '0' => $total
            ];
        } else {
            $value = $this->reckon($total / $num);

            $max = $value + $this->reckon($value / 2);

            $result = $this->random_red($total, $num, $max, $min);

            if (count($result) > 1) {
                $array_sum = array_sum($result);
                if ($total > $array_sum) {
                    //  if 总和 > 数组中的和，差的值补到数组中的最后一位
                    foreach ($result as $kkk => $vvv) {
                        //  获取最大的key
                        $last_key = $kkk;
                    }

                    $diff_value = $total - $array_sum;
                    $result[$last_key] = $diff_value + $result[$last_key];
                }
            }

            //  打乱数组顺序
            shuffle($result);
        }

        return $result;
    }

}
//生成随机红包算法二
function divived($total,$num){
    $fen = $total * 100;
    // 创建一个长度等于n的红包数组
    $arr = [];
    //第一步 每个红包先塞1分
    for($i=0;$i<$num;$i++){
        array_push($arr,1);
    }
    //总金额减去已分配的n 分钱
    $fen -= $num;
    //第二步，循环遍历如果剩余金额>0 则一直分配
    $i = 0;  //从第一个红包进行分配
    //创建一个随机分配对象
    while ($fen > 1){
        $f  = random_int(0,$fen);  //创建范围[0,fen)
        if($f > ($total * 100 / $num) * 3 ){
            continue;
        }
        $arr[$i++%$num] +=  $f;
        $fen -= $f;
    }
    //判断剩余未分配的金额是否大于0,如果大于0，可以把剩下未分配金额塞到第一个红包中
    if ($fen > 0){
        $arr[0] +=  $fen;
    }
    shuffle($arr);
    return array_map(function ($v){
        return $v/100;
    },$arr);
}
/**
 * 生成平均红包算法
 * @param int $number 红包总额
 * @param int $taotl 平分红包数量
 * @param int $index 默认保留位数
 */
function average_red_packet($number,$total,$index=2) {
    // 除法取平均数
    $divide_number  = bcdiv($number, $total, $index);
    // 减法获取最后一个数
    $last_number = bcsub($number, $divide_number*($total-1), $index);
    // 拼装平分后的数据返回
    $number_str = str_repeat($divide_number.'+',$total-1).$last_number;
    return explode('+',$number_str);
}

$redis=new Redis();
$redis->connect('127.0.0.1',6379);

//红包分配
$total=100;//红包总额
$num=5;//红包个数
$min=0.5;//最小红包金额

$redpacket=new CalculateRedPacket();

$arr=$redpacket->red_packet($total,$num,$min);
//0数据库存放未领取红包
foreach ($arr as $v){
    $redis->lPush('no-1',$v);
}
$redis->expire('no-1',24*60*60);//设置24小时后过期，删除key
//1数据库存放已领取红包用户
$redis->lPush('yes-1',0);
$redis->expire('yes-1',24*60*60);//设置24小时后过期

//$arr=new CalculateRedPacket();
//$total=100; //红包总额
//$num=5; //红包数量
//$min=0.1; //单个红包最小金额
//
//echo "<pre>";
//echo "平均分配红包\n";
//var_dump(average_red_packet($total,7));
//echo "生成红包求和:".array_sum(average_red_packet($total,7))."\n";
//
//echo "<br/>";
//
//echo "随机分配红包\n";
//var_dump($arr->red_packet($total,$num,$min));
//echo "生成红包求和:".array_sum($arr->red_packet($total,$num,$min))."\n";
