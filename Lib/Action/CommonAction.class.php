<?php

class CommonAction extends Action
{
    /**
     +----------------------------------------------------------
     * 初始化页面
     +----------------------------------------------------------
     */
    function _initialize() {
        $this->verifyLogin();
    }

    /**
     +----------------------------------------------------------
     * 获取当前URL
     +----------------------------------------------------------
     */
    protected function getUrl($no_query = false) {
        if($no_query) {
            return BASE_URL.$_SERVER['PATH_INFO'];
        }
        else {
            return HOST_URL.$_SERVER['REQUEST_URI'];
        }  
    }

    /**
     +----------------------------------------------------------
     * 获取当前页数
     +----------------------------------------------------------
     */
    protected function getPage() {
        return (isset($_GET['p']) && (int)$_GET['p'] > 0) ? (int)$_GET['p'] : 1;
    }

    /**
     +----------------------------------------------------------
     * 设定页面导航
     +----------------------------------------------------------
     */
    protected function setPageNav($total = null, $count = 20) {
        import('ORG.Util.Page');
        if (is_null($total)) {
            $page = new Page(3200, 20);
            $page->setConfig('theme', "%upPage% %downPage%");
        }
        else {
            $page = new Page($total, 20);
            $page->setConfig('theme', "%upPage% %downPage%");
        }
        $page_show = $page->show();
        $this->assign('page_show', $page_show);
    }

    /**
     +----------------------------------------------------------
     * 获取当前指针
     +----------------------------------------------------------
     */
    protected function getCursor() {
        return (isset($_GET['c'])) ? $_GET['c'] : -1;
    }

    /**
     +----------------------------------------------------------
     * 设定指针导航
     +----------------------------------------------------------
     */
    protected function setCursorNav($prev, $next) {
        $url   =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        $parse = parse_url($url);
        $page_show = '';
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params['c']);
            $url = $parse['path'].'?'.http_build_query($params);
        }
        if($prev) {
            $page_show .= '<a class="prev" href="'.$url.'&c='.$prev.'">上一页</a>';
        }
        if($next) {
            $page_show .= '<a class="next" href="'.$url.'&c='.$next.'">下一页</a>';
        }
        $this->assign('page_show', $page_show);
    }

    /**
     +----------------------------------------------------------
     * 设定当前用户Cookie
     +----------------------------------------------------------
     */
    protected function setProfileCookie($profile = null, $isRefresh = false) {
        if(is_null(cookie('profile')) || $isRefresh) {
            $t = getTwitter();
            $profile = $profile ? $profile : $t->verify(true);
            if($this->verifyData($profile)) {
                $profile_array = array(
                    'name' => $profile->name,
                    'avatar_http' => $profile->profile_image_url,
                    'avatar_https' => $profile->profile_image_url_https,
                    );
                cookie('profile', serialize($profile_array));
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 设定提示信息
     +----------------------------------------------------------
     */
    protected function setTips($text, $type = 'success') {
        session('tips', array('text' => $text, 'type' => $type));
    }

    /**
     +----------------------------------------------------------
     * 根据结果设定提示信息，并跳转
     +----------------------------------------------------------
     */
    protected function setResultTips($result, $action, $success_msg = null) {
        $refresh_url = session('?redirect_from') ? session('redirect_from') : __APP__;
        if (isset($result->error) || isset($result->errors)) {
            $this->setTips($action.'失败，请<a href="'.$refresh_url.'">刷新</a>后重试', 'error');
        }
        else {
            if(is_null($success_msg)) {
                $this->setTips($action.'成功，请<a href="'.$refresh_url.'">刷新</a>');
            }
            else {
                $this->setTips($success_msg);
            }
        }
        $this->doRedirect();
    }

    /**
     +----------------------------------------------------------
     * 验证数据
     +----------------------------------------------------------
     */
     protected function verifyData($value) {
         if (is_null($value)) {
             $error = array(
                 'id'      => 0,
                 'header'  => '网络错误',
                 'content' => '与Twitter的连接超时或无响应，可能是Twitter的超载鲸来袭，请耐心等待，稍后再试 ╮(╯_╰)╭',
                 );
             $this->view->assign('error', $error);
             return false;
         }
         elseif (isset($value->error) || isset($value->errors)) {
             $isProtected = false;
             $error_msg   = isset($value->error) ? $value->error : $value->errors;
             if (strpos($error_msg, 'not authenticate') > 0) {
                 $error = array(
                     'id'      => 1,
                     'header'  => '授权过期或不可用',
                     'content' => '记录在您Cookies的登录授权信息过期或不可用，<a href="'.__APP__.'/login">重新登录</a>后即可恢复。',
                     );
             }
             elseif (strpos($error_msg, 'limit exceeded') > 0) {
                 $error = array(
                     'id'      => 2,
                     'header'  => '达到API请求限制',
                     'content' => '您每小时请求Twitter的次数超过了350次（或搜索请求超过150次），可以休息一会儿，稍后再试 ╮(╯_╰)╭',
                     );
             }
             elseif (strpos($error_msg, 'ot authorized') > 0) {
                 $error = array(
                     'id'      => 3,
                     'header'  => '受保护的用户',
                     'content' => '只有经过对方确认的关注者才能访问其推文和完整个人资料 ╮(╯_╰)╭',
                     );
                 $isProtected = true;
                 $this->view->assign('isProtected', true);
             }
             elseif (strpos($error_msg, 'ot found') > 0) {
                 $error = array(
                     'id'      => 4,
                     'header'  => '找不到该用户或者推文',
                     'content' => '如果确认地址无误，可能是对方更改了ID或者删除了原推文 ╮(╯_╰)╭',
                     );
             }
             else {
                 $error = array(
                     'id'      => 10,
                     'header'  => '未知错误',
                     'content' => $error_msg,
                     );
             }
             if(!$isProtected) {
                 $this->view->assign('error', $error);
             }
             return false;
         }
         elseif (count($value) == 0) {
             $this->view->assign('isEmpty', true);
             return false;
         }
         else return true;
     }


    /**
     +----------------------------------------------------------
     * 注册模板变量
     +----------------------------------------------------------
     */
    protected function assign($name, $value) {
        if ($this->verifyData($value)) {
            $this->view->assign($name, $value);
        }
    }

    /**
     +----------------------------------------------------------
     * 根据设备输出模板
     +----------------------------------------------------------
     */
    protected function showTpl($name) {
        if(isLogin()) {
            $this->setProfileCookie();
            $profile = unserialize(cookie('profile'));
            if(is_array($profile)) {
                $this->assign('profile_footer', $profile);   
            }
        }
        $device = 'Mobile';
        if(MODULE_NAME != 'Login' && MODULE_NAME != 'Status' && MODULE_NAME != 'Message' && strcasecmp(ACTION_NAME, 'sendMessage') != 0) {
            session('redirect_from', $_SERVER['REQUEST_URI']);
        }
        if(session('?tips')) {
            $this->assign('tips', session('tips'));
            session('tips', null);
        }
        $this->display("$device:$name");
    }

    /**
     +----------------------------------------------------------
     * 确认操作
     +----------------------------------------------------------
     */
    protected function confirm() {
        if (tCookie('confirm') == 'yes' && session('confirmed') !== true) {
            $status = $this->getStatus();
            session('confirmed', true);
            $this->showTpl('status');
            return false;
        }
        else {
            session('confirmed', null);
            return true;
        }
    }

    /**
     +----------------------------------------------------------
     * 验证登录
     +----------------------------------------------------------
     */
    protected function verifyLogin() {
        if (!isLogin() && ACTION_NAME != 'about') {
            $this->showTpl('index');
            exit;
        }
    }

    /**
     +----------------------------------------------------------
     * 设定跳转Session
     +----------------------------------------------------------
     */
    protected function setRedirect() {
        if (IS_HTTPS) {
            $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }
        else {
            $url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }
        session('redirect_from', $url);
    }

    /**
     +----------------------------------------------------------
     * 跳转
     +----------------------------------------------------------
     */
    protected function doRedirect()
    {
        $url = !cookie('redirectTo') && session('?redirect_from') ? session('redirect_from') : BASE_URL;
        session('redirect_from', null);
        header("Location:$url");
    }

    /**
     +----------------------------------------------------------
     * 转到错误页面
     +----------------------------------------------------------
     */
    protected function toError($type, $info = false) {
        dump($type);
        dump($info);
        exit;
        //header('Location:'.BASE_URL);
    }

    /**
     +----------------------------------------------------------
     * 清空cookie和session
     +----------------------------------------------------------
     */
    protected function clearData() {
        cookie(null);
        session(null);
    }

    /**
     +----------------------------------------------------------
     * 空操作
     +----------------------------------------------------------
     */
    public function _empty(){
        // dump(ACTION_NAME);
        // dump(MODULE_NAME);
        // dump($_GET);
        header('Location:'.BASE_URL);

    }
}
?>