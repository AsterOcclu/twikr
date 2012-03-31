<?php

Vendor('Twitter.TwitterAPI');

function isMobile() {
    return true;
}

function tCookie($name, $value = null, $option = null) {
    if($value) {
        $value = function_exists('mcrypt_module_open') ? encrypt($value) : $value;
        cookie($name, $value, $option);
    }
    else {
        return is_null(cookie($name)) ? null : (function_exists('mcrypt_module_open') ? decrypt(cookie($name)) : cookie($name));
    }
}

function isLogin() {
    if (session('?login_status')) {
        return session('login_status') == 'verified' ? true : false;
    }
    elseif (tCookie('login_status') == 'verified') {
        session('login_status', 'verified');
        session('oauth_token', tCookie('oauth_token'));
        session('oauth_token_secret', tCookie('oauth_token_secret'));
        session('user_id', tCookie('user_id'));
        session('screen_name', tCookie('screen_name'));
        return true;
    }
    return false;
}

function format_diff_time($time) {
    $differ = $_SERVER['REQUEST_TIME'] - $time;
    if ($differ <= 0) {
        $dateFormated = 'Just Now';
    }
    else if ($differ < 60) {
        $dateFormated = ceil($differ) . '秒前';
    }
    else if ($differ < 3600) {
        $dateFormated = ceil($differ/60) . '分钟前';
    }
    else if ($differ < 86400) {
        $dateFormated = '约' . ceil($differ/3600) . '小时前';
    }
    else if ($differ < 2592000) {
        $dateFormated = '约' . ceil($differ/86400) . '天前';
    }
    else {
        $dateFormated = '约' . ceil($differ/2592000) . '月前';
    }
    return $dateFormated;
}

function getTwitter() {
    return new TwitterAPI(CONSUMER_KEY, CONSUMER_SECRET, session('oauth_token'), session('oauth_token_secret'));
}

function fix_character($text, $array = array()) {
    $replacement = array("⋯"=>"…");
    $replacement += $array;
    return strtr ($text, $replacement);
}

function find_medium($str) {
    $scheme = (IS_HTTPS && !cookie('proxify_img')) ? 'https://' : 'http://';
    $direct_url_reg  = '/^https?\:\/\/\S+\.(?:jpg|png|gif|bmp|jpeg)(?:\:\w+)?(?:[?#]\S*)?$/i';
    $img_map_reg     = '/^https?\:\/\/(?:www\.)?(flic\.kr|flickr\.com|hellotxt\.com|img\.ly|instagr\.am|lockerz\.com|moby\.to|ow\.ly|p\.twipple\.jp|picplz\.com|pixiv\.net|plixi\.com|tweetphoto\.com|twitgoo\.com|twitpic\.com|twitxr\.com|yfrog\.com)\S*\/([\w-]+)\/?/i';
    $twitter_map_reg = '/^https?\:\/\/(?:www\.)?twitter.com\/\S+\/status\/(\d+)\/photo\/(\d+)\/?$/i';
    if(preg_match($direct_url_reg, $str)) {
        return $str;
    }
    elseif (preg_match($img_map_reg, $str, $out)) {
        switch ($out[1]) {
            case 'flic.kr':
                $out[2] = base58_decode($out[2]);
            case 'flickr.com':
                $key     = '4ef2fe2affcdd6e13218f5ddd0e2500d';
                $id      = $out[2];
                $api_url = "http://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=$key&photo_id=$id&format=json&nojsoncallback=1";
                $img_obj = json_decode(processCurl($api_url));
                if(isset($img_obj->photo)) {
                    $photo   = $img_obj->photo;
                    $farm    = $photo->farm;
                    $server  = $photo->server;
                    $secret  = $photo->secret;
                    $format  = isset($photo->originalformat) ? $photo->originalformat : 'jpg';
                    $url     = $scheme."farm$farm.static.flickr.com/$server/$id".'_'.$secret.'.'.$format;
                    break;
                }
                return null;
            case 'hellotxt.com':
                $url = $scheme.$out[1].'/image/'.$out[2].'.s.jpg';
                break;
            case 'img.ly':
                $url = 'http://'.$out[1].'/show/large/'.$out[2];
                break;
            case 'instagr.am':
                if($out[2] != 'media') {
                    $url = $scheme.$out[1].'/p/'.$out[2].'/media/?size=m';   
                }
                else {
                    $url = preg_replace('/media\S*/i', 'media/?size=m', $out[0]);
                    $url = strtr($url, array('http://' => $scheme, 'https://' => $scheme));
                }
                break;
            case 'lockerz.com':
                $url = $scheme.'api.plixi.com/api/tpapi.svc/imagefromurl?size=medium&url=http%3A%2F%2Flockerz.com%2Fs%2F'.$out[2];
                return $url;
            case 'moby.to':
                $url = $scheme.'api.mobypicture.com?s=small&format=plain&k=OozRuDDauQlucrZ3&t='.$out[2];
                break;
            case 'ow.ly':
                $url = $scheme.'static.'.$out[1].'/photos/thumb/'.$out[2].'.jpg';
                break;
            case 'p.twipple.jp':
                $url = 'http://'.$out[1].'/show/large/'.$out[2];
                break;
            case 'picplz.com':
                $curl_url = 'http://picplz.com/oembed?url='.$out[0];
                $img_obj  = json_decode(processCurl($curl_url));
                if(isset($img_obj->html)) {
                    $img_str  = $img_obj->html;
                    if(preg_match('/<img src=\"(?:https?\:\/\/)?(\S+)\"/i', $img_str, $out)) {
                        $url = $scheme.$out[1];
                        break;
                    }
                }
                return null;
            case 'pixiv.net':
                $html = processCurl($str);
                if(preg_match('/<meta property="og:image" content="(\S+)(?:\w)(\.\w+)">/i', $html, $out) && count($out) == 3) {
                    $image_url = $out[1].'m'.$out[2];
                    $url = getEmbedImageUrl($image_url, $str);
                    return $url;
                }
                else return null;
            case 'plixi.com':
                $url = $scheme.'api.'.$out[1].'/api/tpapi.svc/imagefromurl?size=medium&url=http://plixi.com/p/'.$out[2];
                return $url;
            case 'tweetphoto.com':
                $url = $scheme.'api.plixi.com/api/TPAPI.svc/imagefromurl?size=medium&url='.$out[2];
                return $url;
            case 'twitgoo.com':
                $url = 'http://'.$out[1].'/'.$out[2].'/img';
                break;
            case 'twitpic.com':
                $url = 'http://'.$out[1].'/show/large/'.$out[2];
                break;
            case 'twitxr.com':
                if($out[2] != 'th') {
                    $url = 'http://'.$out[1].'/image/'.$out[2].'/th/';
                }
                else {
                    $url = $out[0];
                }
                break;
            case 'yfrog.com':
                $url = 'http://'.$out[1].'/'.$out[2].':iphone';
                break;
            default:
                return null;
        }
        if(!cookie('isNoProxifyImg')) {
            $url = getEmbedImageUrl($url);
        }
        return $url;
    }
    elseif (preg_match($twitter_map_reg, $str, $out)) {
        $t = getTwitter();
        $status = $t->showStatus($out[1]);
        if (isset($status->entities->media[$out[2]-1])) {
            $twitter_media = $status->entities->media[$out[2]-1];
            return IS_HTTPS ? $twitter_media->media_url_https : $twitter_media->media_url; 
        }
    }
    else {
        return null;
    }
}

// function getPathName() {
//     if(isset($_SERVER['PATH_INFO'])) {
//         return strtolower(substr($_SERVER['PATH_INFO'], 1));
//     }
//     else {
//         $pattern = '/^\/'.substr(__APP__, 1).'\/(\w*)[\/?&]?$/i';
//         if(preg_match($pattern, $_SERVER['REQUEST_URI'], $match)) {
//             return strtolower($match[1]);
//         }
//     }
// }

function format_entities($entities, $html, $show_img = true, $show_url = false) {
    $replacement   = array();
    $base_url      = __APP__;
    $mentions      = $entities->user_mentions;
    $urls          = $entities->urls;
    $expanded_urls = array(false);
    $hashtags      = $entities->hashtags;
    $medium_urls   = array(false);
    if(!empty($mentions)) {
        foreach($mentions as $mention) {
            $name    = $mention->screen_name;
            $pattern = "/([@＠]$name)/iu";
            $replace = "<a target=\"_blank\" href=\"$base_url/$name\">$1</a>";
            $html    = preg_replace ($pattern, $replace, $html);
        }
    }
    if(!empty($urls)) {
        foreach($urls as $url) {
            $replacement += array($url->url => "<a target=\"_blank\" href=\"$url->expanded_url\">$url->display_url</a>");
            $expanded_url = $url->expanded_url;
            if($show_url) {
                $result_temp = unShortUrl($expanded_url);
                if($result_temp) {
                    $expanded_urls[0] = true;
                    $expanded_urls[]  = $result_temp;
                    $expanded_url     = $result_temp;
                }
            }
            if ($show_img) {
                $medium_url = find_medium($expanded_url);
                if(!is_null($medium_url)) {
                    $medium_urls[0] = true;
                    $medium_urls[]  = $medium_url;
                }
            }
        }
    }
    if(!empty($hashtags)) {
        foreach($hashtags as $hashtag) {
            $tag = $hashtag->text;
            $replacement += array("#$tag"=>"<a target=\"_blank\" href=\"$base_url/search?q=%23$tag\">#$tag</a>","＃$tag"=>"<a href=\"http://twitter.com/search/%23$tag\">＃$tag</a>");
        }
    }
    if(isset($entities->media)) {
        $media = $entities->media;
        foreach($media as $medium) {
            $medium_url = IS_HTTPS ? $medium->media_url_https : $medium->media_url;
            $replacement += array($medium->url=>"<a target=\"_blank\" href=\"$medium_url:large\">$medium->display_url</a>");
            if ($show_img) {
                $medium_urls[0] = true;
                $medium_urls[] = cookie('isNoProxifyImg') ? $medium_url : getEmbedImageUrl($medium_url);
            }
/*          在有时API抽风的时候，会造成重复生成RT转发的短链接，可以用以下方法修正
            if(strpos($html, $medium->url)) {
                $replacement += array($medium->url=>"<a href=\"$medium_url\">$medium->display_url</a>");
            }
            else {
                if(preg_match_all('/https?\:\/\/t.co\/w+\/?/i', $html, $urls)) {
                    foreach($urls as $url) {
                        if($medium->expanded_url == $url[0]) {
                            $replacement += array($url[0] => "<a href=\"$medium_url\">$medium->display_url</a>");
                            break; // 这里break为了优化性能，但如果存在多个不同缩短地址指向于同一个展开地址会不完整，极个别情况。
                        }
                    }
                }
            }*/
        }
    }
    $tweet_conent = array(
        'text'  => strtr($html,$replacement),
        'urls'  => $expanded_urls,
        'media' => $medium_urls,
        );
    return $tweet_conent;
}

function unShortUrl($short_url, $isShort = false) {
    if($isShort) {
        $unshort_url_api = 'http://api.longurl.org/v2/expand?format=json&url='.urlencode($short_url);
        $response_obj = json_decode(processCurl($unshort_url_api));
        $expanded_url = $response_obj->{'long-url'};
        return array(
            'expanded' => $expanded_url,
            'display'  => (strlen($expanded_url) > 60) ? substr($expanded_url, 0, 57).'...' : $expanded_url,
            );
    }
    else {
        $short_urls = C('SHORT_URLS');
        $short_url_reg = "/^https?\:\/\/(?:www\.)?(?:$short_urls)\/\w+\/?$/i";
        if(preg_match($short_url_reg, $short_url)) {
            return unShortUrl($short_url, true);
        }
        return false;
    }
}

function doTranslate($text, $to = 'zh-CN') {
    $OAuth_url = 'https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/';
    $postdata = http_build_query(array(
            'client_id'     => 'Twikr',
            'client_secret' => 'D8i7kPMcrlF5GiQUIHxDpSipne0EpOqyCXQjER+x0Ww=',
            'scope'         => 'http://api.microsofttranslator.com',
            'grant_type'    => 'client_credentials',
            ));
    $response = json_decode(processCurl($OAuth_url, $postdata));
    if(!isset($response->access_token)) {
        return false;
    }
    $text           = urlencode($text);
    $translate_url  = "http://api.microsofttranslator.com/V2/Http.svc/Translate?text=$text&to=$to&contentType=text/html";
    $access_token   = $response->access_token;
    $request_header = array('Authorization: Bearer '. $access_token, 'Content-Type: text/xml');
    $response       = processCurl($translate_url, false, $request_header);
    if($response) {
        $response = (array) simplexml_load_string($response);
        return $response[0];
    }
    else {
        return false;
    }
}

function getEmbedImageUrl($url, $referer = false) {
    $embed_img_url  = __APP__.'/embed/image/';
    $url = $embed_img_url.setMd5Key($url).'?url='.urlencode($url);
    if ($referer) {
        $url .= '&referer='.urlencode($referer);
    }
    return $url;
}

function setCurlHeader($isMobile = false) {
    $curl_header = array('Accept-Language:zh-CN');
    if ($isMobile) {
        $curl_header[1] = 'User-Agent:mobile';
    }
    return $curl_header;
}

function processCurl($url, $postdata = false, $header = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT,120);
    if($postdata) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    }
    if($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $response     = curl_exec($ch);
    $responseInfo = curl_getinfo($ch);
    curl_close($ch);
    return $response;
    // if(intval($responseInfo['http_code']) == 200)
    //     return $response;       
    // else
    //     return false;
}

function imgProxy($url, $referer_url = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if($referer_url) {
        curl_setopt($ch, CURLOPT_REFERER, $referer_url);
    }
    $ret          = curl_exec($ch);
    $header       = curl_getinfo($ch);
    $http_code    = $header['http_code'];
    $content_type = $header['content_type'];
    if($http_code == 200) {
        if(strpos($content_type, 'image') !== false) {
            header('Content-Type:'.$content_type);
            echo $ret;
        }
    }
    elseif ($http_code == 301 || $http_code == 302) {
        $redirect_url = $header['redirect_url'];
        if (!empty($redirect_url)) {
            imgProxy($redirect_url, $referer_url);
        }
    }
}

function base58_encode($num) {
    $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $base_count = strlen($alphabet);
    $encoded = '';
    while ($num >= $base_count) {
        $div = $num/$base_count;
        $mod = ($num-($base_count*intval($div)));
        $encoded = $alphabet[$mod] . $encoded;
        $num = intval($div);
    }
    if ($num) $encoded = $alphabet[$num] . $encoded;
    return $encoded;
}

function base58_decode($num) {
    $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $decoded = 0;
    $multi = 1;
    while (strlen($num) > 0) {
        $digit = $num[strlen($num)-1];
        $decoded += $multi * strpos($alphabet, $digit);
        $multi = $multi * strlen($alphabet);
        $num = substr($num, 0, -1);
    }
    return $decoded;
}

function setMd5Key($plain_text) {
    $salt = defined(SECURE_KEY) ?  SECURE_KEY : '120e319b20b9577593e1413093128bd5';
    return substr(md5($salt.$plain_text.$salt), 0, 6);
}

function encrypt($plain_text) {
    $key = defined(SECURE_KEY) ?  SECURE_KEY : '120e319b20b9577593e1413093128bd5';
    $td = mcrypt_module_open('blowfish', '', 'cfb', '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    mcrypt_generic_init($td, $key, $iv);
    $crypt_text = mcrypt_generic($td, $plain_text);
    mcrypt_generic_deinit($td);
    return base64_encode($iv.$crypt_text);
}

function decrypt($crypt_text) {
    $key = defined(SECURE_KEY) ?  SECURE_KEY : '120e319b20b9577593e1413093128bd5';
    $crypt_text = base64_decode($crypt_text);
    $td = mcrypt_module_open('blowfish', '', 'cfb', '');
    $ivsize = mcrypt_enc_get_iv_size($td);
    $iv = substr($crypt_text, 0, $ivsize);
    $crypt_text = substr($crypt_text, $ivsize);
    mcrypt_generic_init($td, $key, $iv);
    $plain_text = mdecrypt_generic($td, $crypt_text);
    mcrypt_generic_deinit($td);
    return $plain_text;
}

if (!function_exists('mb_strlen')) {
    function mb_strlen($text, $encode) {
        if (strtolower($encode) == 'utf-8') {
            return preg_match_all('%(?:
                              [\x09\x0A\x0D\x20-\x7E]     # ASCII
                            | [\xC2-\xDF][\x80-\xBF]# non-overlong 2-byte
                            |  \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
                            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
                            |  \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
                            |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
                            | [\xF1-\xF3][\x80-\xBF]{3}   # planes 4-15
                            |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
                            )%xs',$text,$out);
        }
        else {
            return strlen($text);
        }
    }
}
