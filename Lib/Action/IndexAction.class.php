<?php

class IndexAction extends CommonAction {

    public function test() {
        header('location:'.__APP__);
        // $url = 'http://www.pixiv.net/member_illust.php?illust_id=27442703&mode=medium';
        // $html = processCurl($url);
        // if(preg_match('/<meta property="og:image" content="(\S+)(?:\w)(\.\w+)">/i', $html, $out) && count($out) == 3) {
        //     $image_url = $out[1].'m'.$out[2];
        //     dump($image_url);
        // }
    }

    public function index() {
        $page     = $this->getPage();
        $t        = getTwitter();
        $statuses = $t->homeTimeline($page);
        $this->assign('statuses', $statuses);
        $this->setPageNav(null, count($statuses));
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