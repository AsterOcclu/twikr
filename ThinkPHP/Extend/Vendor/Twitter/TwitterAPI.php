<?php
require_once('TwitterOAuth.php');
/**
* TwitterAPI Class
*/
class TwitterAPI extends TwitterOAuth {
	/**
	+----------------------------------------------------------
	* ---------- Activity ----------
	+----------------------------------------------------------
	*/
	function aboutMe($cursor = false) {
		$url                  = $this->privte_api_host.'activity/about_me.'.$this->format;
		// $args['count']        = 10;
		// $args['since_id']     = 10;
		// $args['max_position'] = 5;
		return $this->get($url, $args);
	}

	/**
	+----------------------------------------------------------
	* ---------- Block ----------
	+----------------------------------------------------------
	*/
	function blockingIDs() {
		$url = 'blocks/blocking/ids';
		return $this->get($url);
	}

	function blockingList($cursor = false) {
		$url  = 'blocks/blocking';
		$args = array();
		$args['cursor']      = $cursor ? $cursor : -1;
		$args['count']       = 30;
		$args['per_page']    = 30;
		$args['skip_status'] = 1;
		return $this->get($url, $args);
	}

	function blockUser($id) {
		$url = "blocks/create/$id";
		return $this->post($url);
	}

	function isBlocked($id) {
		$url = "blocks/exists/$id";
		return $this->get($url);
	}

	function unblockUser($id) {
		$url = "blocks/destroy/$id";
		return $this->delete($url);
	}
	/**
	+----------------------------------------------------------
	* ---------- Messages ----------
	+----------------------------------------------------------
	*/
	function showDirectMessage($id, $include_entities = true) {
		$url = "direct_messages/show/$id";
		$args = array();
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function deleteDirectMessage($id) {
		$url = "direct_messages/destroy/$id";
		return $this->delete($url);
	}

	function directMessages($page = false, $since_id = false, $count = null, $include_entities = true) {
		$url  = 'direct_messages';
		$args = array();
		if($since_id)
			$args['since_id'] = $since_id;
		if($page)
			$args['page'] = $page;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function sendDirectMessage($user, $text) {
		$url  = 'direct_messages/new';
		$args = array();
		$args['user'] = $user;
		if($text)
			$args['text'] = $text;
		return $this->post($url, $args);
	}

	function sentDirectMessage($page = false, $since = false, $since_id = false, $include_entities = true) {
		$url  = 'direct_messages/sent';
		$args = array();
		if($since)
			$args['since'] = $since;
		if($since_id)
			$args['since_id'] = $since_id;
		if($page)
			$args['page'] = $page;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- List ----------
	+----------------------------------------------------------
	*/
	function addListMember($slug, $screen_name) {
		$url  = "lists/members/create_all";
		$args = array();
		$args['slug']              = $slug;
		$args['screen_name']       = $screen_name;
		$args['owner_screen_name'] = $this->screen_name;
		return $this->post($url, $args);
	}

	function beAddedLists($username, $cursor = false) {
		$url  = "$username/lists/memberships";
		$args = array();
		if($cursor)
			$args['cursor'] = $cursor;
		return $this->get($url, $args);
	}

	function createList($name, $description = false, $mode = false) {
		$url  = 'lists/create';
		$args = array();
		$args['name'] = $name;
		if($description)
			$args['description'] = $description;
		if($isProtect)
			$args['mode'] = $mode;
		return $this->post($url, $args);
	}

	function createdLists($username, $cursor = false) {
		$url  = "$username/lists";
		$args = array();
		if($cursor)
			$args['cursor'] = $cursor;
		return $this->get($url, $args);
	}

	function allLists($screen_name, $page = 1, $count = 20) {
		$url  = "lists/all";
		$args = array();
		$args['screen_name'] = $screen_name;
		$args['page']        = $page;
		$args['count']       = $count;
		return $this->get($url, $args);
	}

	function editList($slug, $name = false, $description = false, $mode = false) {
		$url  = "lists/update";
		$args = array();
		$args['slug'] = $slug;
		$args['owner_screen_name'] = $this->screen_name;
		if($name)
			$args['name'] = $name;
		if($description)
			$args['description'] = $description;
		if($isProtect)
			$args['mode'] = $mode;
		return $this->post($url, $args);
	}

	function followedLists($username, $cursor = false) {
		$url  = "$username/lists/subscriptions";
		$args = array();
		if($cursor)
			$args['cursor'] = $cursor;
		return $this->get($url, $args);
	}

	function deleteListMember($slug, $screen_name) {
		$url  = 'lists/members/destroy';
		$args = array();
		$args['slug'] = $slug;
		$args['screen_name'] = $screen_name;
		$args['owner_screen_name'] = $this->screen_name;
		return $this->post($url, $args);
	}

	function deleteList($slug) {
		$url  = 'lists/destroy';
		$args = array();
		$args['owner_screen_name'] = $this->screen_name;
		$args['slug'] = $slug;
		return $this->post($url, $args);
	}

	function followList($owner_screen_name, $slug) {
		$url = 'lists/subscribers/create';
		$args = array();
		$args['owner_screen_name'] = $owner_screen_name;
		$args['slug'] = $slug;
		return $this->post($url, $args);
	}

	function unfollowList($owner_screen_name, $slug) {
		$url = 'lists/subscribers/destroy';
		$args = array();
		$args['owner_screen_name'] = $owner_screen_name;
		$args['slug'] = $slug;
		return $this->post($url, $args);
	}

	function isFollowedList($id) {
		$arr = explode('/', $id);
		$url = "$arr[0]/$arr[1]/subscribers/$this->screen_name";
		return $this->get($url);
	}

	function listFollowers($id, $cursor = false) {
		$arr  = explode('/', $id);
		$url  = "$arr[0]/$arr[1]/subscribers";
		$args = array();
		if($cursor)
			$args['cursor'] = $cursor;
		return $this->get($url, $args);
	}

	function listInfo($owner_screen_name, $slug) {
		$url  = "lists/show";
		$args = array();
		$args['owner_screen_name'] = $owner_screen_name;
		$args['slug'] = $slug;
		return $this->get($url, $args);
	}

	function listMembers($owner_screen_name, $slug, $cursor = -1) {
		$url = 'lists/members';
		$args = array();
		$args['slug']              = $slug;
		$args['cursor']            = $cursor;
		$args['owner_screen_name'] = $owner_screen_name;
		return $this->get($url, $args);
	}

	function listSubscribers($owner_screen_name, $slug, $cursor = -1) {
		$url = 'lists/subscribers';
		$args = array();
		$args['slug']              = $slug;
		$args['cursor']            = $cursor;
		$args['owner_screen_name'] = $owner_screen_name;
		return $this->get($url, $args);
	}

	function listStatuses($owner_screen_name, $slug, $page = 1, $per_page = 20, $since_id = false, $include_rts = true, $include_entities = true) {
		$url  = 'lists/statuses';
		$args = array();
		$args['owner_screen_name'] = $owner_screen_name;
		$args['slug'] = $slug;
		if($page)
			$args['page'] = $page;
		if($per_page)
			$args['per_page'] = $per_page;
		if($since_id)
			$args['since_id'] = $since_id;
		if($include_rts)
			$args['include_rts'] = $include_rts;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- Friendship ----------
	+----------------------------------------------------------
	*/
	function isProtected($screen_name) {
		if($screen_name == $this->screen_name) {
			return false;
		}
		$url      = 'users/show';
		$args     = array('screen_name' => $screen_name);
		$response = $this->get($url, $args);
		if(isset($response->protected) && $response->protected && !$response->following) {
			return true;
		}
		else {
			return false;
		}
	}

	function outgoing($cursor = -1) {
		$url  = 'friendships/outgoing';
		$args = array();
		$args['cursor'] = $cursor;
		return $this->get($url, $args);
	}

	function incoming($cursor = -1) {
		$url  = 'friendships/incoming';
		$args = array();
		$args['cursor'] = $cursor;
		return $this->get($url, $args);
	}

	function followUser($id, $notifications = false) {
		$url  = "friendships/create/$id";
		$args = array();
		if($notifications)
			$args['follow'] = true;
		return $this->post($url, $args);
	}

	function destroyUser($id) {
		$url = "friendships/destroy/$id";
		return $this->delete($url);
	}

	function friends($id = false, $page = false, $count = 30) {
		$url  = 'statuses/friends';
		$url .= $id ? "/$id" : '';
		$args = array();
		if( $id )
			$args['id'] = $id;
		if( $count )
			$args['count'] = (int) $count;
		$args['cursor'] = $page ? $page : -1;
		return $this->get($url, $args);
	}

	function followers($id = false, $page = false, $count = 30) {
		$url = 'statuses/followers';
		$url .= $id ? "/$id" : '';
		if( $id )
			$args['id'] = $id;
		if( $count )
			$args['count'] = (int) $count;
		$args['cursor'] = $page ? $page : -1;
		return $this->get($url, $args);
	}

	function isFriend($user_a, $user_b) {
		$url = 'friendships/exists';
		$args = array();
		$args['user_a'] = $user_a;
		$args['user_b'] = $user_b;
		return $this->get($url, $args);
	}

	function relationship($target, $source = false, $isAuth = true) {
		if($isAuth) {
			$url = 'friendships/show';
			$args = array();
			$args['target_screen_name'] = $target;
			if($source)
				$args['source_screen_name'] = $source;
			return $this->get($url, $args);
		}
		else {
			$url = $this->host."friendships/show.json?source_screen_name=$source&target_screen_name=$target";
			return json_decode($this->http($url, 'GET'));
		}
	}

	function displayRetweet($screen_name, $status) {
		$url  = 'friendships/update';
		$args = array();
		$args['screen_name'] = $screen_name;
		$args['retweets']    = $status;
		return $this->post($url, $args);
	}

	function showUser($screen_name = false, $id = false) {
		$url = 'users/show';
		$args = array();
		if ($id) {
			$args['user_id'] = $id;
		}
		else {
			$args['screen_name'] = $screen_name ? $screen_name : $this->screen_name;
		}
		return $this->get($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- Ratelimit ----------
	+----------------------------------------------------------
	*/
	function ratelimit() {
		$url = 'account/rate_limit_status';
		return $this->get($url,array(),false);
	}
	/**
	+----------------------------------------------------------
	* ---------- Retweet ----------
	+----------------------------------------------------------
	*/
	function getRetweeters($id, $count = false) {
		$url = "statuses/retweets/$id";
		if($count)
			$url .= "?count=$count";
		return $this->get($url);
	}

	function retweet($id) {
		$url = "statuses/retweet/$id";
		return $this->post($url);
	}

	function retweets($id, $count = 20, $include_entities = true) {
		if($count > 60 || $count < 0) {
			$count = 60;
		}
		$url  = "statuses/retweets/$id";
		$args = array();
		$args['count'] = $count;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	// Returns the 20 most recent retweets posted by the authenticating user.
	function retweeted_by_me($page = false, $count = 20, $since_id = false, $max_id = false, $include_entities = true) {
		$url  = 'statuses/retweeted_by_me';
		$args = array();
		if($since_id)
			$args['since_id'] = $since_id;
		if($max_id)
			$args['max_id'] = $max_id;
		if($count)
			$args['count'] = $count;
		if($page)
			$args['page'] = $page;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	// Returns the 20 most recent retweets posted by the authenticating user's friends.
	function retweeted_to_me($page = false, $count = false, $since_id = false, $max_id = false, $include_entities = true) {
		$url  = 'statuses/retweeted_to_me';
		$args = array();
		if($since_id)
			$args['since_id'] = $since_id;
		if($max_id)
			$args['max_id'] = $max_id;
		if($count)
			$args['count'] = $count;
		if($page)
			$args['page'] = $page;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function retweets_of_me($page = false, $count = false, $since_id = false, $max_id = false, $include_entities = true) {
		$url  = 'statuses/retweets_of_me';
		$args = array();
		if($since_id)
			$args['since_id'] = $since_id;
		if($max_id)
			$args['max_id'] = $max_id;
		if($count)
			$args['count'] = $count;
		if($page)
			$args['page'] = $page;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- Search ----------
	+----------------------------------------------------------
	*/
	function search($q, $page = false, $rpp = 20, $include_entities = true) {
		$searchApiUrl = strpos($this->host, 'twitter.com') > 0 ? 'http://search.twitter.com' : $this->host;
		$url = $searchApiUrl.'/search.'.$this->format;
		$args = array();
			$args['q'] = $q;
		if($page)
			$args['page'] = $page;
		if($rpp)
			$args['rpp'] = $rpp;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- Spam ----------
	+----------------------------------------------------------
	*/
	function reportSpam($screen_name) {
		$url  = 'report_spam';
		$args = array();
		$args['screen_name'] = $screen_name;
		return $this->post($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- Timeline ----------
	+----------------------------------------------------------
	*/
	function deleteStatus($id) {
		$url = "statuses/destroy/$id";
		return $this->delete($url);
	}

	function homeTimeline($page = false, $max_id = false, $count = false, $include_entities = true) {
		$url  = 'statuses/home_timeline';
		$args = array();
		if(!$max_id && $page)
			$args['page'] = $page;
		if($max_id)
			$args['max_id'] = $max_id;
		if($count)
			$args['count'] = $count;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		// $args['include_rts'] = true;
		// $args['include_my_retweet'] = 1;
		return $this->get($url, $args);
	}

	function friendsTimeline($page = false, $since_id = false, $count = false, $include_entities = true) {
		$url  = 'statuses/friends_timeline';
		$args = array();
		if($page)
			$args['page'] = $page;
		if($since_id)
			$args['since_id'] = $since_id;
		if($count)
			$args['count'] = $count;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function getFavorites($page = false, $userid = '', $include_entities = true) {
		$url  = 'favorites/'.$userid;
		$args = array();
		if($page)
			$args['page'] = $page;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function makeFavorite($id) {
		$url = "favorites/create/$id";
		return $this->post($url);
	}

	function publicTimeline($sinceid = false, $include_entities = true) {
		$url  = 'statuses/public_timeline';
		$args = array();
		if($sinceid)
			$args['since_id'] = $sinceid;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function removeFavorite($id) {
		$url = "favorites/destroy/$id";
		return $this->post($url);
	}

	function replies($page = false, $since_id = false, $include_entities = true) {
		$url  = 'statuses/mentions';
		$args = array();
		$args['include_rts'] = true;
		if($page)
			$args['page'] = (int) $page;
		if($since_id)
			$args['since_id'] = $since_id;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function showStatus($id, $include_entities = true) {
		$url  = "statuses/show/$id";
		$args = array();
		$args['include_my_retweet'] = true;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function update($status, $replying_to = false, $include_entities = true) {
		$url = 'statuses/update';
		$args = array();
		$args['status'] = $status;
		if($replying_to)
			$args['in_reply_to_status_id'] = $replying_to;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->post($url, $args);
	}

	function userTimeline($page = false, $screen_name = false, $count = false, $since_id = false, $include_rts = true, $include_entities = true) {
		$url  = 'statuses/user_timeline';
		$args = array();
		if($page)
			$args['page'] = $page;
		if($screen_name)
			$args['screen_name'] = $screen_name;
		if($count)
			$args['count'] = $count;
		if($since_id)
			$args['since_id'] = $since_id;
		if($include_rts)
			$args['include_rts'] = $include_rts;
		if($include_entities)
			$args['include_entities'] = $include_entities;
		return $this->get($url, $args);
	}

	function trends($woeid = 1) {
		$url = "trends/$woeid";
		return $this->get($url);
	}
	/**
	+----------------------------------------------------------
	* ---------- Misc ----------
	+----------------------------------------------------------
	*/
	function twitterAvailable() {
		$url = 'help/test';
		if($this->get($url) == 'ok') {
			return true;
		}
		return false;
	}

	function verify($skip_status = false) {
		$url  = 'account/verify_credentials';
		$args = array('skip_status' => $skip_status);
		return $this->get($url, $args);
	}

	function updateProfile($fields = array(), $skip_status = true) {
		$url = 'account/update_profile';
		$args = array();
		foreach($fields as $pk => $pv) {
			$args[$pk] = $pv;
		}
		$args['skip_status'] = $skip_status;
		return $this->post($url, $args);
	}
	/**
	+----------------------------------------------------------
	* ---------- media ----------
	+----------------------------------------------------------
	*/
	function updateProfileImage($image, $skip_status = true) {
		$url  = 'account/update_profile_image';
		$args = array('image' => $image);
		if($skip_status)
			$args['skip_status'] = $skip_status;
		return $this->post($url, $args);
	}

	function updateProfileBackground($image, $skip_status = true) {
		$url  = 'account/update_profile_background_image';
		$args = array('image' => $image);
		if($skip_status)
			$args['skip_status'] = $skip_status;
		return $this->post($url, $args);
	}

	function updateMedia($status, $image, $replying_to = false) {
		$url  = 'statuses/update_with_media';
		$args = array();
		$args['status']  = $status;
		$args['media'][] = $image;
		if($replying_to)
			$args['in_reply_to_status_id'] = $replying_to;
		return $this->post($url, $args);
	}
}