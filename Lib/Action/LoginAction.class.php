<?php

class LoginAction extends CommonAction
{

    function _initialize() {}

    /**
     +----------------------------------------------------------
     * 初始化登录，获取token
     +----------------------------------------------------------
     */
    private function getToken() {
        $this->clearData();
        $connection = new TwitterAPI(CONSUMER_KEY, CONSUMER_SECRET);
        $request_token = $connection->getRequestToken(OAUTH_CALLBACK);
        if ($connection->http_code == 200) {
            $token = $request_token['oauth_token'];
            session('oauth_token', $token);
            session('oauth_token_secret', $request_token['oauth_token_secret']);
            return $connection->getAuthorizeURL($token);
        }
        else {
            session(null);
            $this->toError('Login', '无法获取Token，HTTP编码不是200');
            return false;
        }
    }


    /**
     +----------------------------------------------------------
     * 直接登录
     +----------------------------------------------------------
     */
    public function directOAuth() {
        $url = $this->getToken();
        header("Location:$url");
    }

    public function d() {
        $this->directOAuth();
    }

    /**
     +----------------------------------------------------------
     * 代理登录
     +----------------------------------------------------------
     */
    public function proxifyOAuth() {
        $url = $this->getToken();
        $html = processCurl($url, false, setCurlHeader(false));
        if (isMobile()) {
            $match = '/name="authenticity_token".*value="(\w+)"(?:.|\n)+name="oauth_token".*value="(\w+)"/i';
            if(preg_match($match, $html, $out)) {
                $authenticity_token = $out[1];
                $oauth_token = $out[2];
                if(isset($_GET['error']) && $_GET['error'] == 'password_is_wrong') {
                    $this->assign('isWrongPassword', true);
                }
                $this->assign('authenticity_token', $authenticity_token);
                $this->assign('oauth_token', $oauth_token);
                $this->showTpl('login');
                return;
            }
        }
        $replacement = array(
            'https://api.twitter.com/oauth/authenticate' => AUTHORIZE_URL,
            'https://api.twitter.com/oauth/authorize'    => AUTHORIZE_URL,
            );
        echo strtr($html,$replacement);
    }

    public function p() {
        $this->proxifyOAuth();
    }

    /**
     +----------------------------------------------------------
     * 代理认证
     +----------------------------------------------------------
     */
    public function authorize() {
        $url = 'https://api.twitter.com/oauth/authorize';
        $html = processCurl($url, http_build_query($_POST), setCurlHeader(false));
        if (preg_match('/'.strtr(OAUTH_CALLBACK, array('/' => '\/')).'[^"]+/i', $html, $out)) {
            header('Location:'.$out[0]);
        }
        else {
            $replacement = array(
                'https://api.twitter.com/oauth/authenticate' => AUTHORIZE_URL,
                'https://api.twitter.com/oauth/authorize'    => AUTHORIZE_URL,
                );
            echo strtr($html,$replacement);
        }
    }

    /**
     +----------------------------------------------------------
     * 回调操作
     +----------------------------------------------------------
     */
    public function callBack() {
        if (isset($_REQUEST['denied'])) {
            session(null);
            header('Location:'.__APP__.'/Login?error=password_is_wrong');
        }
        elseif (isset($_REQUEST['oauth_token']) && $_REQUEST['oauth_token'] === session('oauth_token')) {       
            $connection = new TwitterAPI(CONSUMER_KEY, CONSUMER_SECRET, session('oauth_token'), session('oauth_token_secret'));
            $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
            if ($connection->http_code == 200) {
                session('login_status', 'verified');
                session('oauth_token', $access_token['oauth_token']);
                session('oauth_token_secret', $access_token['oauth_token_secret']);
                session('user_id', $access_token['user_id']);
                session('screen_name', $access_token['screen_name']);

                tCookie('login_status', 'verified');
                tCookie('oauth_token', $access_token['oauth_token']);
                tCookie('oauth_token_secret', $access_token['oauth_token_secret']);
                tCookie('user_id', $access_token['user_id']);
                tCookie('screen_name', $access_token['screen_name']);
                $this->doRedirect();
            }
            else {
                session(null);
                $this->toError('Login', '无法验证Token，HTTP编码不是200');   
            }
        }
        else {
            session(null);
            $this->toError('Login', '非法回调，回调参数与Session不符');
        }
    }

    /**
     +----------------------------------------------------------
     * 登出
     +----------------------------------------------------------
     */
    public function logout() {
        $this->clearData();
        header('Location:'.__APP__);
    }

    /**
     +----------------------------------------------------------
     * 空函数
     +----------------------------------------------------------
     */
    public function _empty(){
        $this->proxifyOAuth();
    }
}
?>