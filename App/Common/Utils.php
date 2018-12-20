<?php

namespace App\Common;

class Utils {
    static function money_number_2_cn($num) {
        $convert_cn = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
        $repair_number = array('零仟零佰零拾零','萬萬','零仟','零佰','零拾');
        $unit_cn = array("拾","佰","仟","萬","億");
        $exp_cn = array("","萬","億");
        $max_len = 12;
         
        $len = strlen($num);
        if($len > $max_len) {
            return 'outnumber';
        }
        $num = str_pad($num,12,'-',STR_PAD_LEFT);
        $exp_num = array();
        $k = 0;
        for($i=12;$i>0;$i--){
            if($i%4 == 0) {
                $k++;
            }
            $exp_num[$k][] = substr($num,$i-1,1);
        }
        $str = '';
        foreach($exp_num as $key=>$nums) {
            if(array_sum($nums)){
                $str = array_shift($exp_cn) . $str;
            }

            foreach($nums as $nk=>$nv) {
                if($nv == '-') {continue;}
                if($nk == 0) {
                    $str = $convert_cn[$nv] . $str;
                } else {
                    $str = $convert_cn[$nv].$unit_cn[$nk-1] . $str;
                }
            }
        }
        $str = str_replace($repair_number,array('萬','億','-'),$str);
        $str = preg_replace("/-{2,}/","",$str);
        $str = str_replace(array('零','-'),array('','零'),$str);
        return $str;
    }
    static function number_2_cn($num) {
        $convert_cn = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
        $repair_number = array('零仟零佰零拾零','萬萬','零仟','零佰','零拾');
        $unit_cn = array("拾","佰","仟","萬","億");
        $exp_cn = array("","萬","億");
        $max_len = 12;

        $len = strlen($num);
        if($len > $max_len) {
            return 'outnumber';
        }
        $num = str_pad($num,12,'-',STR_PAD_LEFT);
        $exp_num = array();
        $k = 0;
        for($i=12;$i>0;$i--){
            if($i%4 == 0) {
                $k++;
            }
            $exp_num[$k][] = substr($num,$i-1,1);
        }
        $str = '';
        foreach($exp_num as $key=>$nums) {
            if ($key<3 || array_sum($nums)){
                $str = array_shift($exp_cn) . $str;
            }
            foreach($nums as $nk=>$nv) {
                if ($nv == '-'){
                    if (($key*4+$nk)>8) continue;
                    else $str = '　'.$convert_cn[0].' '.$unit_cn[$nk-1] . $str;
                }elseif($nk == 0) {
                    $str = '　'.$convert_cn[$nv] .' '. $str;
                } else {
                    $str = '　'.$convert_cn[$nv].' '.$unit_cn[$nk-1] . $str;
                }
            }
        }
        $str = str_replace($repair_number,array('萬','億','零仟','零佰','零拾'),$str);
        $str = preg_replace("/-{2,}/","",$str);
        $str = str_replace(array('零','-'),array('零','零'),$str);
        return $str;
    }
    //补充一个中文转数字的
    function cn_2_num($str){
        $convert_cn = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
        $skip_words = array("拾","佰","仟");
        $str = str_replace($skip_words,"",$str);
        $len = mb_strlen($str,'utf-8');
        $num = 0;
        $k = '';
        for($i=0;$i<$len;$i++) {
        $cn = mb_substr($str,$i,1,'utf-8');
        if($cn == '億') {
        $num = $num + intval($k)*100000000;
        $k = '';
        } elseif($cn == '萬') {
        $num = $num + intval($k)*10000;
        $k = '';
        } else {
        $k = $k . array_search($cn,$convert_cn);
        }
        }
        if($k) {
        $num = $num + intval($k);
        }    
        return $num;
    }

    // [1,2,3], [1,2] => [1,2]
    public function arrayWhiteList (array $array, $whitelist) {
        if (is_null($whitelist)) $whitelist = [];
        return array_intersect_key($array, array_flip($whitelist));
    }
    
    // [1,2,3], [1,2] => [3]
    public function arrayBlackList (array $array, $blacklist) {
        if (is_null($blacklist)) $blacklist = [];
        return array_diff_key($array, array_flip($blacklist));
    }
    /**
     * [['name' => 'zhangsan'], ['name' => 'lisi']]
     * [['key' => 'name', 'value' => 'zhangsan'], ['key' => 'name', 'value' => 'zhangsan']]
     */
    public function array2DbItem (array $array, array $fields) {
        // $dataItems = [];
        // array_walk($array, function($value, $key) use ( &$dataItems, $fields ) {
        //     $dataItems[] = array_combine($fields, [ $key, $value ]);
        // });

        $dataItems = array_map(function ($key, $value) use ($fields) {
            return array_combine($fields, [ $key, is_array($value) ? serialize($value): $value ]);
        }, array_keys($array), array_values($array));

        return $dataItems;
    }
}