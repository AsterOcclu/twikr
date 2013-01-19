<?php

define('IS_HTTPS', false); //若您使用https模式，请设置为true
define('HOST_URL', 'https://twitter.com'); //您的根目录地址
define('APP_URL', '/'); //您的子目录地址，若使用根目录，则只填写"/"
define('BASE_URL', HOST_URL.APP_URL);
define('BASE_PATH', dirname(realpath('./index.php')));
define('WHITE_LIST_ADMIN', '/BASE_PATH/white_list_admin.txt'); //保证文件可写
define('WHITE_LIST_INVITE', '/BASE_PATH/white_list_invite.txt'); //保证文件可写
define('STRANGER_LIST', '/BASE_PATH/stranger_list.txt'); //保证文件可写
define('SECURE_KEY', 'twikr'); //请随意替换
/* Twitter Lib Settings */
define('CONSUMER_KEY', ''); //请填写您的CONSUMER_KEY
define('CONSUMER_SECRET', ''); //请填写您的CONSUMER_SECRET
define('AUTHORIZE_URL', BASE_URL.'/Login/authorize');
define('OAUTH_CALLBACK', BASE_URL.'/Login/callBack');

$short_urls = array(
	'163.fm',
	'9911.ms',
	'aa.cx',
	'bit.ly',
	'digg.com',
	'ff.im',
	'fl5.me',
	'goo.gl',
	'htxt.it',
	'is.gd',
	'j.mp',
	'knb.im',
	'retwt.me',
	'rrurl.cn',
	'sinaurl.cn',
	't.cn',
	't.co',	
	'tinyurl.com',
	'tr.im',
	'yy.cx',
	'zi.mu',
	);
foreach ($short_urls as $short_url) {
	$short_urls_str .= "|$short_url";
}
$short_urls_str = strtr(substr($short_urls_str, 1), array('.'=>'\.'));

return array(
	/* Debug设置 */
	'SHOW_PAGE_TRACE'      => false,
	'TMPL_CACHE_ON'        => false,

	/* 时区设置 */
	'DEFAULT_TIMEZONE'     => 'UTC',

	/* 模板变量 */
	'TMPL_PARSE_STRING'    => array(
		'__CSS__'  => APP_URL.'/Public/css',
		'__JS__'   => APP_URL.'/Public/js',
		'__IMG__'  => APP_URL.'/Public/img',
		),
	
	/* URL模式设置 */
	'URL_MODEL'            => 2,
	'URL_CASE_INSENSITIVE' => true,

	/* 路由设置 */
	'URL_ROUTER_ON'        => true,
	'URL_ROUTE_RULES'      => array(
		'invite'                                       => 'Index/invite',
		'about'                                        => 'Index/about',
		'settings'                                     => 'Index/settings',
		'logout'                                       => 'Login/logout',
		'/^status\/(?:(\w+)\/)?(\d+)(?:\/(\w+))?\/?/i' => 'Status/:3?screen_name=:1&id=:2',
		'message/:id\d/:action'                        => 'Message/:2',
		'message/:id\d'                                => 'Message/reply',
		'search/:q'                                    => 'Index/search',
		'search'                                       => 'Index/search',
		'embed/image/:key'                             => 'Index/imgProxy',
		'/^(replies|favorites|fav|retweet|messages|profile|following|followers|lists)\/?(\w+)?/i' => 'User/:1?type=:2',
		'/^(\w+)\/((?!lists(?:\/\w*)?$)[^\/]+)\/(\w+)/i' => 'List/:3?screen_name=:1&slug=:2',
		'/index\/?(\w+)?\/?([^\/]+)?\/?(\w+)?/i'       => 'User/:1?screen_name=index&type=:2&slug=:1',
		'/^((?!login|status|message(?:\/\w*)?$)\w+)\/?([^\/]+)?\/?(\w+)?/i'  => 'User/:2?screen_name=:1&type=:3&slug=:2',
	),

	'TOKEN_ON'             => false, // 默认不开启Token表单认证

	/* Cookie设置 */
	'COOKIE_EXPIRE'        => 31536000, // 3600*24*365
	'COOKIE_PREFIX'        => 'twikr_',
	
	/* SESSION设置 */
	'SESSION_PREFIX'       => 'twikr_',

	/* SHORT_URLS设置 */
	'SHORT_URLS' => $short_urls_str,
);