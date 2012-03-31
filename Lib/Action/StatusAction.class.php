<?php

class StatusAction extends CommonAction {

    public function index() {
        $this->getStatus();
        $this->showTpl('showTweet');
    }

    public function translate() {
        $this->assign('isTranslate', true);
        $status = $this->getStatus(false);
        if($status) {
            $to_lang = cookie('to_lang') ? cookie('to_lang') : 'zh-CN';
            $result = doTranslate($status->text, $to_lang);
            $this->assign('translation', $result);
            $this->assign('status', $status);
            $this->assign('to_lang', $to_lang);
        }
        $this->showTpl('showTweet');
    }

    public function update() {
        if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
            Vendor('tmhOAuth.tmhOAuth');
            Vendor('tmhOAuth.tmhUtilities');
            $tmhOAuth = new tmhOAuth(array(
              'consumer_key'    => CONSUMER_KEY,
              'consumer_secret' => CONSUMER_SECRET,
              'user_token'      => session('oauth_token'),
              'user_secret'     => session('oauth_token_secret'),
            ));
            $image = file_get_contents($_FILES['image']['tmp_name']);
            $code = $tmhOAuth->request('POST', 'https://upload.twitter.com/1/statuses/update_with_media.json',
                array(
                    'status'   => $_POST['status'],
                    'media[]'  => $image,
                    ),
                true, // use auth
                true  // multipart
            );
            $response = json_decode($tmhOAuth->response['response']);
            $this->setResultTips($response, '发送图文');
        }
        elseif (!empty($_POST['status'])) {
            $t = getTwitter();
            $response = $t->update($_POST['status'], $_POST['in_reply_to_id']);
            $this->setResultTips($response, '发送推文');
        }
        else {
            $this->setTips('不能发送空推文', 'warning');
            $this->doRedirect();
        }
    }

    public function reply() {
        $status = $this->getStatus();
        if($status) {
            $sentText = '@'.$status->user->screen_name.' ';
            $this->assign('sentText', $sentText);
            $this->assign('sentInReplyTo', $status->id_str);
        }
        $this->showTpl('showTweet');
    }

    public function replyAll() {
        $status = $this->getStatus();
        if($status) {
            $sentText = '@'.$status->user->screen_name.' ';
            foreach ($status->entities->user_mentions as $mention) {
                $mention_name = "@$mention->screen_name ";
                if(!stristr($sentText, $mention_name) && strcasecmp(session('screen_name'), $mention->screen_name) != 0) {
                    $sentText .= $mention_name;
                }
            }
            $this->assign('sentText', $sentText);
            $this->assign('sentInReplyTo', $status->id_str);
        }
        $this->showTpl('showTweet');
    }

    public function quote() {
        $status = $this->getStatus();
        if($status) {
            $this->assign('sentText', ' RT @'.$status->user->screen_name.' '.$status->text);
            if (!cookie('isNoReplyQuote')) {
                $this->assign('sentInReplyTo', $status->id_str);
            }
        }
        $this->showTpl('showTweet');

    }

    public function favorite() {
        $id     = $this->getStatusID();
        $t      = getTwitter();
        $result = $t->makeFavorite($id);
        $this->setResultTips($result, '收藏推文');
    }

    public function retweet() {
        $id     = $this->getStatusID();
        $t      = getTwitter();
        $result = $t->retweet($id);
        $this->setResultTips($result, '转发推文');
    }
    
    public function unrt() {
        $status = $this->getStatus();
        $id     = $status->current_user_retweet->id_str;
        $t      = getTwitter();
        $result = $t->deleteStatus($id);
        $this->setResultTips($result, '撤销转发');
    }

    public function delete() {
        $id     = $this->getStatusID();
        $t      = getTwitter();
        $result = $t->deleteStatus($id);
        $this->setResultTips($result, '删除推文');
    }

    public function unfav() {
        $id     = $this->getStatusID();
        $t      = getTwitter();
        $result = $t->removeFavorite($id);
        $this->setResultTips($result, '撤销收藏');
    }

    private function getStatusID() {
        if (isset($_GET['id'])) {
            return $_GET['id'];
        }
        else {
            header('Location:'.__APP__);
        }
    }

    private function getStatus($isAssign = true) {
        $id          = $this->getStatusID();
        $screen_name = $_GET['screen_name'];
        $t           = getTwitter();
        $status      = $t->showStatus($id);
        if (isset($status->error) && strpos($status->error, 'not authorized') > 0) {
            $this->assign('isProtected', true);
            if(!empty($screen_name)) {
                $user = $t->showUser($screen_name);
                if(!isset($user->error)) $this->assign('user', $user);
            }
        }
        else {
            if(isset($status->user)) {
                $user = $status->user;
                if(strcasecmp($user->screen_name, $screen_name) != 0) {
                    $id = $this->getStatusID();
                    $replacement = array(
                        "status/$screen_name/$id" => "status/$user->screen_name/$id",
                        "status/$id"              => "status/$user->screen_name/$id",
                        );
                    $url = strtr($_SERVER['REQUEST_URI'], $replacement);
                    header("Location:$url");
                }
                $this->assign('user', $status->user);
            }
            if ($isAssign) {
                $this->assign('status', $status);
            }
            return $status;
        }
        return false;
    }

    public function _empty() {
        $this->index();
    }

}
