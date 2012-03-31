<?php

class ListAction extends UserAction {

    private $slug;
    private $list_info;

    function __construct() {
        parent::__construct();
        if (empty($_GET['slug'])) {
            header('location:'.__APP__."/$this->screen_name");
            return;
        }
        $this->slug      = $_GET['slug'];
        $this->list_info = $this->listInfo();
        $this->assign('isListModule', true);
    }

    public function statuses() {
        $page     = $this->getPage();
        $t        = getTwitter();
        $statuses = $t->listStatuses($this->screen_name, $this->slug, $page);
        $this->showUserinfo();
        $this->assign('statuses', $statuses);
        $this->setPageNav();
        $this->showTpl('timeline');
    }

    public function members() {
        $cursor   = $this->getCursor();
        $t        = getTwitter();
        $response = $t->listMembers($this->screen_name, $this->slug, $cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        $user_list_header = $this->list_info->name.' 内的 '.$this->list_info->member_count. ' 位成员';
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $response);
        $this->showTpl('user_list');
    }

    public function subscribers() {
        $cursor   = $this->getCursor();
        $t        = getTwitter();
        $response = $t->listSubscribers($this->screen_name, $this->slug, $cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        $user_list_header = $this->list_info->name.' 的 '.$this->list_info->subscriber_count. ' 位订阅者';
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $response);
        $this->showTpl('user_list');
    }

    public function edit() {
        if (isset($_POST['name']) && isset($_POST['description']) && isset($_POST['mode'])) {
            $t = getTwitter();
            if (isset($_POST['members']) && !empty($_POST['members'])) {
                $add_screen_name = strtr(trim($_POST['members']), array('，' => ','));
                $response = $t->addListMember($this->slug, $add_screen_name);
                if($_POST['name'] == $this->list_info->name && $_POST['description'] == $this->list_info->description && $_POST['mode'] == $this->list_info->mode) {
                    if(!isset($response->error)) {
                        $this->setTips('添加成员成功，<a href="'.__APP__."/$this->screen_name/$response->slug\">查看</a>");
                        header('location:'.__APP__."/$this->screen_name/$this->slug/edit");
                    }
                    else {
                        $this->setTips("添加失败，错误信息 $response->error", 'error');
                        header('location:'.__APP__."/$this->screen_name/$this->slug/edit");
                    }
                    return;
                }
            }
            $response = $t->editList($this->slug, $_POST['name'], $_POST['description'], $_POST['mode']);
            if(isset($response->slug)) {
                $this->setTips('修改列表成功，<a href="'.__APP__."/$this->screen_name/$response->slug\">查看</a>");
                header('location:'.__APP__."/$this->screen_name/$response->slug/edit");
            }
            else {
                $this->setTips("修改失败，错误信息 $response->error", 'error');
                header('location:'.__APP__."/$this->screen_name/$this->slug/edit");
            }
        }
        else {
            $this->showTpl('edit_info');
        }
    }

    public function followList() {
        $t      = getTwitter();
        $result = $t->followList($this->screen_name, $this->slug);
        $this->setResultTips($result, '订阅列表');
    }

    public function unfoList() {
        $t      = getTwitter();
        $result = $t->unfollowList($this->screen_name, $this->slug);
        $this->setResultTips($result, '取消订阅');
    }

    public function deleteList() {
        $t      = getTwitter();
        $result = $t->deleteList($this->slug);
        $this->setResultTips($result, '删除列表');
    }

    public function delMember() {
        if (isset($_GET['del_name'])) {
            $t           = getTwitter();
            $screen_name = $_GET['del_name'];
            $result      = $t->deleteListMember($this->slug, $screen_name);
            $this->setResultTips($result, '删除列表成员');
        }
        else {
            $this->doRedirect();
        }
    }

    private function listInfo() {
        $t         = getTwitter();
        $list_info = $t->listInfo($this->screen_name, $_GET['slug']);
        if (isset($list_info->error) && strpos($list_info->error, 'ot found') > 0) {
            header('location:'.__APP__."/$this->screen_name");
            return false;
        }
        $this->assign('list_info', $list_info);
        return $list_info;
    }

    public function _empty() {
        header('location:'.__APP__."/$this->screen_name/$this->slug");
    }
}