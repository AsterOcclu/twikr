<?php

class IndexAction extends CommonAction {

    public function invite() {
        if (!isset($_POST['invite_user_name'])) {
            $this->assign('isInvite', true);
            $this->showTpl('edit_info');
        }
        elseif (!preg_match('/^\w+$/', $_POST['invite_user_name'])){
            $this->setTips('您提交的用户名不合法', 'error');
            $this->doRedirect();
        }
        else {
            $white_list_admin  = unserialize(file_get_contents(WHITE_LIST_ADMIN));
            $white_list_admin  = is_array($white_list_admin) ? $white_list_admin : array();
            $white_list_invite = unserialize(file_get_contents(WHITE_LIST_INVITE));
            $white_list_invite = is_array($white_list_invite) ? $white_list_invite : array();
            $white_list        = array_merge($white_list_admin, $white_list_invite);
            if (array_search($_POST['invite_user_name'], $white_list) === false) { 
                $white_list_invite[] = $_POST['invite_user_name'];
                file_put_contents(WHITE_LIST_INVITE, serialize($white_list_invite));
                $this->setTips('邀请成功！');
            }
            else {
                $this->setTips('您邀请的用户已在白名单之列', 'warning');
            }
            $this->doRedirect();
        }
    }

    public function index() {
        $max_id   = $this->getMaxID();
        $page     = $this->getPage();
        $t        = getTwitter();
        $statuses = $t->homeTimeline($page, $max_id, 21);
        if (!session('isRefreshProfile')) {
            $this->setProfileCookie(null, true);
            session('isRefreshProfile', true);
        }
        if ($this->verifyData($statuses)) {
            $last_status = $statuses[count($statuses) - 1];
            if (isset($statuses[20])) {
                unset($statuses[20]);
            }
            $this->assign('statuses', $statuses, true);
            $max_id = $last_status->id_str;
            $this->setPageNav(null, count($statuses));
            $this->assign('max_id', $max_id, true);
        }
        $this->showTpl('timeline');
    }

    public function about() {
        if (isLogin()) {
            $this->assign('myself_name', session('screen_name'));
        }
        $this->showTpl('about');
    }

    public function settings() {
        if (!empty($_POST)) {
            if (isset($_POST['_backup'])) {
                # 还原设定
                cookie('isNoReplyAll', null);
                cookie('isNoReplyQuote', null);
                cookie('isHideImage', null);
                cookie('isNoProxifyImg', null);
                cookie('isHideTranslate', null);
                cookie('isRecoverUrl', null);
                cookie('isHideActionBtn', null);
                cookie('redirectTo', null);
                $this->setTips('恢复初始设定成功');
            }
            else {
                if (isset($_POST['isReplyAll']) && $_POST['isReplyAll'] == 'on') {
                    cookie('isNoReplyAll', null);
                }
                else {
                    cookie('isNoReplyAll', true);
                }
                if (isset($_POST['isReplyQuote']) && $_POST['isReplyQuote'] == 'on') {
                    cookie('isNoReplyQuote', null);
                }
                else {
                    cookie('isNoReplyQuote', true);
                }
                if (isset($_POST['isHideImage']) && $_POST['isHideImage'] == 'on') {
                    cookie('isHideImage', null);
                }
                else {
                    cookie('isHideImage', true);
                }
                if (isset($_POST['isProxifyImg']) && $_POST['isProxifyImg'] == 'on') {
                    cookie('isNoProxifyImg', null);
                }
                else {
                    cookie('isNoProxifyImg', true);
                }
                if (isset($_POST['isHideTranslate']) && $_POST['isHideTranslate'] == 'on') {
                    cookie('isHideTranslate', true);
                }
                else {
                    cookie('isHideTranslate', null);
                }
                if (isset($_POST['isRecoverUrl']) && $_POST['isRecoverUrl'] == 'on') {
                    cookie('isRecoverUrl', true);
                }
                else {
                    cookie('isRecoverUrl', null);
                }
                if (isset($_POST['isHideActionBtn']) && $_POST['isHideActionBtn'] == 'on') {
                    cookie('isHideActionBtn', true);
                }
                else {
                    cookie('isHideActionBtn', null);
                }
                if ($_POST['redirectTo'] == 'indexPage') {
                    cookie('redirectTo', true);
                }
                else {
                    cookie('redirectTo', null);
                }
                $this->setTips('修改设定成功');
            }
            header('location:'.__APP__.'/settings');
        }
        else {
            $this->showTpl('settings');
        }
    }

    public function search($q = null) {
        if (isset($_GET['q'])) {
            $q = $_GET['q'];
        }
        if (!is_null($q)) {
            $page     = $this->getPage();
            $t        = getTwitter();
            $response = $t->search($q, $page);
            $this->assign('response', $response);
            if (!empty($response->results)) {
                $this->setPageNav(null, count($response->results));
            }
        }
        $this->showTpl('search_timeline');
    }

    public function imgProxy() {
        if (isset($_GET['key']) && isset($_GET['url']) && $_GET['key'] == setMd5Key($_GET['url'])) {
            $referer_url = isset($_GET['referer']) ? $_GET['referer'] : null;
            imgProxy($_GET['url'], $referer_url);
        }
    }
}