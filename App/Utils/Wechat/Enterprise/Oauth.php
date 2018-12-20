<?php

namespace App\Utils\Wechat\Enterprise;

class Oauth {
    public function __construct($config){
        $this->config = $config;
    }

    public function redirect($redirect_url = null, $state = null) {

        $redirect || $redirect = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].(empty($_SERVER['QUERY_STRING'])?'':'?'.$_SERVER['QUERY_STRING']));

        $http_query = http_build_query(array_merge($this->config['auth'], [
            'appid' => $this->config['corpid'],
            'redirect_uri' => $redirect_url,
            'response_type' => 'code',
            'state' => $state
        ]));

        // header("location: https://open.weixin.qq.com/connect/oauth2/authorize?".$http_query.'#wechat_redirect');
    }

    public function user($scope){
        if (filter_input(INPUT_GET, 'code')){
            return \Http::request('https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=ACCESS_TOKEN&code=CODE');
        } else {
            $this->config['auth'] = [
                'scope' => $scope
            ];
            $this->redirect();
        }
    }
}