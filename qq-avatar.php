<?php
/*
Plugin Name: QQ Avatar
Plugin URI: http://wordpress.org/extend/plugins/qq-avatar/
Author: Meng Zhuo
Author URI: http://mengzhuo.org
Version: 0.3.5
License: GPLv2 or later
Description: Replace Gravatar while commenter use QQ numeric email address.如果用户使用数字QQ邮箱留言且公开空间头像，就<strong>将Gravatar替换成QQ头像</strong> | <strong><a href="http://me.alipay.com/mengzhuo">捐助</a></strong> | <a title="使用本插件即表示您知晓并接受本条款" href="http://mengzhuo.org/lab/qq-avatar/agreement.htm">条款</a>
*/
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
define('QQ_REQUEST','http://r.qzone.qq.com/cgi-bin/user/cgi_personal_card?uin=');
define('QQ_REQUEST_UA','Mozilla/5.0 (Windows NT 5.1; rv:9.0) Gecko/20100101 Firefox/9.0');
define('CACHE','qq_avatar_cache');
define('QA','qq-avatar'); //QA is short for qq-avatar

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}
if ( !function_exists('wp_remote_get') ){
    _e("Please update your WordPress to 3.1 or above",QA);
}
if ( !function_exists('qq_get_home_path')  ){
//get from file.php in wp-admin;
    function qq_get_home_path() {
	    $home = get_option( 'home' );
	    $siteurl = get_option( 'siteurl' );
	    if ( $home != '' && $home != $siteurl ) {
		    $wp_path_rel_to_home = str_replace($home, '', $siteurl); /* $siteurl - $home */
		    $pos = strpos($_SERVER["SCRIPT_FILENAME"], $wp_path_rel_to_home);
		    $home_path = substr($_SERVER["SCRIPT_FILENAME"], 0, $pos);
		    $home_path = trailingslashit( $home_path );
	    } else {
		    $home_path = ABSPATH;
	    }

	    return $home_path;
    }
}

/* main function */
function qq_avatar($original_img,$comment_info){

    $qq_data = get_option('QQ_Avtar');
    
    if (!is_array($qq_data)){
        // first time humm...
        $default_array = array('failed_uin'=>array('262652047'=>NULL));
        add_option('QQ_Avatar',$default_array,'','no');
    }
    
    if (is_object($comment_info)){
        $cache = qq_get_home_path().CACHE;
        if ( !is_dir($cache) ){
            if ( !mkdir( $cache ) ){
                $warning = new WP_Error('broke', "Can't make cache dir, please check your permisson");
            }
        }
        
        if ( isset($comment_info->comment_author_email) && preg_match('/^\d+@(vip\.)?qq.com$/',$comment_info->comment_author_email) ){
            //Thank FSM, It's an QQ number
            $qq_uin = explode("@", $comment_info->comment_author_email);
            $qq_uin = $qq_uin[0];
            
            if (!isset($qq_data['failed_uin'][$qq_uin]) || !is_array($qq_data['failed_uin'][$qq_uin])){
                $qq_uin_md5 = md5($qq_uin);
			
			    $file_url = "$cache/$qq_uin_md5.jpg";
                
                if (file_exists($file_url) && time()-filemtime($file_url) > 2592000){
                    unlink($file_url);
                }
                
			    if (file_exists($file_url)){
                    
				    $original_img = preg_replace("/src='[^']*'/",
                          "src='".get_home_url().'/'.CACHE."/$qq_uin_md5.jpg'",
                           $original_img);
				    return $original_img;
			    }

                $response = wp_remote_get( QQ_REQUEST.$qq_uin,
                                            array('user-agent'=>QQ_REQUEST_UA,
                                                   'timeout'=>1
                                            )
                                         );
                
            }else{
                $response = $qq_data['failed_uin'][$qq_uin];
            }
            
            if( !is_wp_error( $response ) && $response['response']['code']==200 ) {
            //Thank FSM, nothing went wrong, Initiating transformation, Gee...Gaa...
            
                $qq_info = json_decode(str_replace(array('_Callback(',');'),
                                                array('',''),
                                                $response['body']
                                                ));
                if (!is_numeric($qq_info->uin)){
                    //how the hell you get through this...                     
                    return $original_img;
                }
                
                $qq_uin_md5 = md5($qq_info->uin);//yes, it's recheck.
                
                if ( isset($qq_info->avatarUrl) && !file_exists($file_url) ){
                
                    $pic_handle = wp_remote_get($qq_info->avatarUrl,
                                                 array('user-agent'=>QQ_REQUEST_UA,
                                                'timeout'=>4
                                        ));
                    if (is_wp_error($pic_handle) || $pic_handle['response']['code'] != 200){
                        
                        $qq_data['failed_uin'][$qq_uin] = $response;
                        update_option('QQ_Avatar',$qq_data);
                        
                        return $original_img."<!-- ".$qq_info->avatarUrl.'=>'.$pic_handle->get_error_message()." -->";
                    }
                    
                    $pic_handle = $pic_handle['body'];
                    
                    $temp_pic = fopen( $file_url,'wb+');
                    
                    if (fwrite($temp_pic,$pic_handle)){
                          $original_img = preg_replace("/src='[^']*'/",
			                      "src='".get_home_url().'/'.CACHE."/$qq_uin_md5.jpg'",
			                       $original_img);
                    }
                    else{
                        $warning = new WP_Error('broke', "No more disk space to Storage QQ_Avatar Cache");
                    }
                    
                    fclose($temp_pic);
                    
                    if (isset ($qq_data['failed_uin'][$qq_uin]))
                            unset ($qq_data['failed_uin'][$qq_uin]);
                }
            }
        }
    }
    if (is_wp_error($warning)){
        $original_img = $original_img.'<--'.$warning->get_error_message().'-->';
    }
    return $original_img;
}
add_filter('get_avatar', 'qq_avatar', 6, 2);
