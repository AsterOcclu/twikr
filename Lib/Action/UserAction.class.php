<?php

class UserAction extends CommonAction {

    protected $screen_name;

    function __construct() {
        parent::__construct();
        $this->screen_name = isset($_GET['screen_name']) ? $_GET['screen_name'] : session('screen_name');
        $this->assign('screen_name', $this->screen_name);
    }

    public function index() {
        $this->userTimeline();
    }

    public function profile() {
        $t = getTwitter();
        if(!empty($_POST)) {
            $image = $_FILES['avatar']['tmp_name'];
            $tips[0]  = true;
            $tips[1]  = '';
            if (!empty($image)) {
                $image_encode = base64_encode(file_get_contents($image));
                $response = $t->updateProfileImage($image_encode);
                if (isset($response->id)) {
                    $tips[0] = true;
                    $tips[1] = '修改头像成功 ';
                    $profile = $response;
                    session('isRefreshProfile', null);
                    $this->setProfileCookie($profile, true);
                }
                else {
                    $tips[0] = false;
                    $tips[1] = '修改头像失败 ';
                }
            }
            $profile = isset($profile) ? $profile : $t->verify(true);
            if (isset($profile->id)) {
                foreach ($_POST as $key => $value) {
                    if ($value != $profile->$key) {
                        $newProfile = $t->updateProfile($_POST);
                        break;
                    }
                }
            }
            else {
                $newProfile = $t->updateProfile($_POST);
            }
            if (isset($newProfile)) {
                if (isset($newProfile->id)) {
                    $tips[0] = $tips[0] && true;
                    $tips[1] .= '修改个人资料成功';
                    $this->setProfileCookie($newProfile, true);
                }
                else {
                    $tips[0] = false;
                    $tips[1] .= '修改个人资料失败';
                }
            }
            if (empty($tips[1])) {
                $type    = 'warning';
                $tips[1] = '您没有修改任何信息';
            }
            else {
                $type = $tips[0] ? 'success' : 'error';
                $tips[1] .= '，请<a href="'.__SELF__.'">刷新</a>';
            }
            $this->setTips($tips[1], $type);
            header('location:'.__SELF__);
        }
        else {
           $profile = $t->verify(true);
           $this->assign('profile', $profile);
           $this->showTpl('edit_info');
       }
    }

    public function replies() {
        if (strcasecmp($this->screen_name, session('screen_name')) != 0) {
            header('location:'.__APP__.'/'.$this->screen_name.'/mentions');
        }
        $page = $this->getPage();
        $t = getTwitter();
        $statuses = $t->replies($page);
        $this->assign('statuses', $statuses);
        $this->setPageNav(null, count($statuses));
        $this->showTpl('timeline');
    }

    public function fav() {
        header('location:'.__APP__.'/favorites');
    }

    public function favorites() {
        $page     = $this->getPage();
        $t        = getTwitter();
        $statuses = $t->getFavorites($page, $this->screen_name);
        $user     = $this->showUserinfo();
        $this->assign('statuses', $statuses);
        $total    = isset($user->favourites_count) ? $user->favourites_count : 0;
        $this->setPageNav($total);
        $this->showTpl('timeline');
    }

    public function mentions() {
        if (strcasecmp($this->screen_name, session('screen_name')) == 0) {
            $this->showUserinfo();
            $this->replies();
        }
        else {
            $t    = getTwitter();
            $this->showUserinfo();
            R('Index/search', array("@$this->screen_name"));
        }
    }

    public function messages() {
        $page = $this->getPage();
        $t    = getTwitter();
        if (isset($_GET['type']) && strcasecmp($_GET['type'], 'sent') == 0) {
            $this->assign('isInbox', false);
            $messages = $t->sentDirectMessage($page);
        }
        else {
            $this->assign('isInbox', true);
            $messages = $t->directMessages($page);
        }
        $this->assign('messages', $messages);
        $this->setPageNav(null, count($messages));
        $this->showTpl('timeline');
    }

    public function sendMessage() {
        R('Message/reply', array($this->screen_name));
    }

    public function retweet() {
        $page = $this->getPage();
        $t    = getTwitter();
        $type = isset($_GET['type']) ? strtolower($_GET['type']): '';
        if ($type == 'of_me') {
            $statuses = $t->retweets_of_me($page);
        }
        elseif ($type == 'to_me') {
            $statuses = $t->retweeted_to_me($page);
        }
        else {
            $this->assign('rts_by_me', true);
            $statuses = $t->retweeted_by_me($page);
        }
        $this->assign('statuses', $statuses);
        $this->setPageNav(null, count($statuses));
        $this->showTpl('timeline');
    }

    public function followUser() {
        $t      = getTwitter();
        $result = $t->followUser($this->screen_name);
        if (isset($result->protected) && $result->protected) {
            $this->setTips('已发送关注请求，等待对方确认');
            $this->doRedirect();
        }
        elseif (isset($result->error) && strpos($result->error, 'already') + strpos($result->error, '已经') > 0 ) {
            $this->setTips('已关注或等待对方确认关注请求', 'warning');
            $this->doRedirect();
        }
        else {
            $this->setResultTips($result, '关注好友');
        }
    }

    public function unfoUser() {
        $t      = getTwitter();
        $result = $t->destroyUser($this->screen_name);
        $this->setResultTips($result, '取消关注');
    }

    public function block() {
        $t      = getTwitter();
        $result = $t->blockUser($this->screen_name);
        $this->setResultTips($result, '屏蔽用户');
    }

    public function unblock() {
        $t      = getTwitter();
        $result = $t->unblockUser($this->screen_name);
        $this->setResultTips($result, '撤销屏蔽');
    }

    public function enableRetweet() {
        $t      = getTwitter();
        $result = $t->displayRetweet($this->screen_name, true);
        $success_msg = '@'.$this->screen_name.'转发的推文将重新出现你的时间线中';
        $this->setResultTips($result, '开启转发功能', $success_msg);
    }

    public function disableRetweet() {
        $t      = getTwitter();
        $result = $t->displayRetweet($this->screen_name, false);
        $success_msg = '@'.$this->screen_name.'转发的推文将不会出现你的时间线中';
        $this->setResultTips($result, '关闭转发功能', $success_msg);
    }

    public function reportSpam() {
        $t      = getTwitter();
        $result = $t->reportSpam($this->screen_name);
        $this->setResultTips($result, '报告用户发送垃圾信息');
    }

    public function info() {
        $this->showUserinfo(null, false);
        $this->showTpl('user_info');
    }

    public function lists() {
        $t        = getTwitter();
        $type     = isset($_GET['type']) ? strtolower($_GET['type']): '';
        $user     = $this->showUserinfo();
        $name     = $user->screen_name == session('screen_name') ? '我' : " $user->name ";
        $isCursor = true;
        if ($type == 'created') {
            $cursor      = $this->getCursor();
            $response    = $t->createdLists($this->screen_name, $cursor);
            $list_header = '由'.$name.'创建的列表';
        }
        elseif ($type == 'memberships') {
            $cursor      = $this->getCursor();
            $response    = $t->beAddedLists($this->screen_name, $cursor);
            $list_header = '存在'.$name.'的列表';
        }
        elseif ($type == 'following') {
            $cursor      = $this->getCursor();
            $response    = $t->followedLists($this->screen_name, $cursor);
            $list_header = $name.'订阅的列表';
        }
        elseif ($type == 'new') {
            if (strcasecmp($this->screen_name, session('screen_name')) != 0) {
                header('location:'.__APP__."/$this->screen_name/lists");
                return;
            }
            if (isset($_POST['name']) && isset($_POST['description']) && isset($_POST['mode'])) {
                $t = getTwitter();
                $response = $t->createList($_POST['name'], $_POST['description'], $_POST['mode']);
                if (isset($response->slug)) {
                    if (isset($_POST['members']) && !empty($_POST['members'])) {
                        $add_screen_name = strtr(trim($_POST['members']), array('，' => ','));
                        $response = $t->addListMember($response->slug, $add_screen_name);
                        if (isset($response->error)) {
                            $this->setTips("添加成员失败，$response->error", 'error');
                            header('location:'.__APP__."/$this->screen_name/$response->slug");
                        }
                        else {
                            $this->setTips('创建列表成功，<a href="'.__APP__."/$this->screen_name/$response->slug\">查看</a>");
                            header('location:'.__APP__."/$this->screen_name/$response->slug/members");
                        }
                    }
                    else {
                        $this->setTips('创建列表成功，请添加列表成员');
                        header('location:'.__APP__."/$this->screen_name/$response->slug/edit");
                    }
                }
                else {
                    $this->setTips("创建列表失败，$response->error", 'error');
                    header('location:'.__APP__."/$this->screen_name/lists/new");
                }
            }
            else {
                $this->assign('isCreatList', true);
                $this->showTpl('edit_info');
            }
            return;
        }
        elseif ($type == '') {
            $page     = $this->getPage();
            $isCursor = false; 
            $lists    = $t->allLists($this->screen_name, $page);
            $this->setPageNav(null, count($lists));
            $list_header = $name.'的全部列表';
        }
        else {
            header('location:'.__APP__."/$this->screen_name/lists");
        }
        if ($isCursor) {
            $lists = isset($response->lists) ? $response->lists : null;
            if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
                $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
            }
        }
        $this->assign('isListModule', true);
        $this->assign('list_header', $list_header);
        $this->assign('lists', $lists);
        $this->showTpl('lists');
    }

    public function following() {
        $cursor   = $this->getCursor();
        $t        = getTwitter();
        $response = $t->friends($this->screen_name, $cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        $user = $this->showUserinfo();
        $name     = $user->screen_name == session('screen_name') ? '我' : " $user->name ";
        $user_list_header = $name."正在关注的 $user->friends_count 位用户";
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $response);
        $this->showTpl('user_list');
    }

    public function followers() {
        $cursor   = $this->getCursor();
        $t        = getTwitter();
        $response = $t->followers($this->screen_name, $cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        $user = $this->showUserinfo();
        $name = $user->screen_name == session('screen_name') ? '我' : $user->name;
        $user_list_header = $name."的 $user->followers_count 位关注者";
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $response);
        $this->showTpl('user_list');
    }

    public function blocking() {
        $user = $this->showUserinfo();
        if ($user->screen_name != session('screen_name')) {
            $user_list_header = '您没有权限查看他人的屏蔽列表';
            $this->assign('user_list_header', $user_list_header);
            $this->showTpl('user_list');
            return;
        }
        $t        = getTwitter();
        $cursor   = $this->getCursor();
        $response = $t->blockingList($cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        if (empty($response)) {
            $user_list_header = '尚未屏蔽任何人';
        }
        else {
            $user_list_header = '已屏蔽的用户列表';
        }
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $response);
        $this->showTpl('user_list');
    }

    public function outgoing() {
        $user = $this->showUserinfo();
        if ($user->screen_name != session('screen_name')) {
            $user_list_header = '您没有权限查看他人发出的好友请求';
            $this->assign('user_list_header', $user_list_header);
            $this->showTpl('user_list');
            return;
        }
        $t        = getTwitter();
        $cursor   = $this->getCursor();
        $response = $t->outgoing($cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        $user_list = array();
        if (empty($response->ids)) {
            $user_list_header = '尚无等待其他用户确认的好友请求';
        }
        elseif (isset($response->ids)) {
            $user_list_header = '等待其他用户确认的好友请求列表';
            $ids              = $response->ids;
            foreach ($ids as $id) {
                $user_list[] = $t->showUser(false, $id);
            }
        }
        else {
            $user_list_header = '未知错误';
        }
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $user_list);
        $this->showTpl('user_list');
    }

    public function incoming() {
        $user = $this->showUserinfo();
        if ($user->screen_name != session('screen_name')) {
            $user_list_header = '您没有权限查看向他人发送的好友请求';
            $this->assign('user_list_header', $user_list_header);
            $this->showTpl('user_list');
            return;
        }
        $t        = getTwitter();
        $cursor   = $this->getCursor();
        $response = $t->incoming($cursor);
        if (isset($response->previous_cursor_str) && isset($response->next_cursor_str)) {
            $this->setCursorNav($response->previous_cursor_str, $response->next_cursor_str);
        }
        $user_list = array();
        if (empty($response->ids)) {
            $user_list_header = '尚无等待您确认的好友请求';
        }
        elseif (isset($response->ids)) {
            $user_list_header = '等待您确认的好友请求列表';
            $ids              = $response->ids;
            foreach ($ids as $id) {
                $user_list[] = $t->showUser(false, $id);
            }
        }
        else {
            $user_list_header = '未知错误';
        }
        $this->assign('user_list_header', $user_list_header);
        $this->assign('user_list', $user_list);
        $this->showTpl('user_list');
    }

    private function userTimeline() {
        $page     = $this->getPage();
        $t        = getTwitter();
        $statuses = $t->userTimeline($page, $this->screen_name);
        $this->showUserinfo($statuses);
        $this->assign('statuses', $statuses);
        $this->setPageNav();
        $this->showTpl('timeline');
    }

    protected function showUserinfo($response = null, $withCache = true) {
        if ($withCache && S($this->screen_name)) {
            $user = S($this->screen_name);
        }
        else {
            if (!isset($response->error) && is_array($response) && isset($response[0]->user)) {
                $user = $response[0]->user;
            }
            else {
                $t    = getTwitter();
                $user = $t->showUser($this->screen_name);
            }
            S($this->screen_name, $user, 300);
        }
        $this->assign('user', $user);
        return $user;
    }

    public function _empty() {
        R("List/statuses");
    }
}