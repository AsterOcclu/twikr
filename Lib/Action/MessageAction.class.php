<?php

class MessageAction extends CommonAction {

    public function send() {
        if(empty($_POST['status'])) {
            $this->setTips('不能发送空私信', 'warning');
            $this->doRedirect();
        }
        else {
            $text = $_POST['status'];
        }
        if(empty($_POST['recipienter'])) {
            $this->setTips('请填写收件人', 'warning');
            $this->doRedirect();
        }
        else {
            $user = $_POST['recipienter'];
        }
        $t = getTwitter();
        $result = $t->sendDirectMessage($user, $text);
        $this->setResultTips($result, '发送私信');
    }

    public function reply($screen_name = null) {
        if(is_null($screen_name)) {
            $message = $this->getMessage();
            $recipienter = $message->recipient_screen_name == session('screen_name') ? $message->sender_screen_name : $message->recipient_screen_name;
            $this->assign('message', $message);
            $this->assign('recipienter', $recipienter);
            $this->showTpl('showMessage');
        }
        else  {
            $user = getTwitter()->showUser($screen_name);
            $this->assign('user', $user);
            $this->assign('recipienter', $user->screen_name);
            $this->showTpl('showMessage');
        }
    }

    public function delete() {
        $id = $this->getMessageID();
        $t = getTwitter();
        $result = $t->deleteDirectMessage($id);
        $this->setResultTips($result, '删除私信');
    }


    private function getMessageID() {
        if (isset($_GET['id'])) {
            return $_GET['id'];
        }
        else {
            header('Location:'.__APP__);
        }
    }

    private function getMessage() {
        $id      = $this->getMessageID();
        $t       = getTwitter();
        $message = $t->showDirectMessage($id);
        if(isset($message->id)) {
            return $message;
        }
        else {
            header('Location:'.__APP__);
        }
    }


}