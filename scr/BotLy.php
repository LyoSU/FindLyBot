<?
require 'config.php';

class Ly
{
    static public function sendGet($url){
        $curl = curl_init();
        curl_setopt_array( $curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ] );
        $r = curl_exec( $curl );
        curl_close( $curl );
        return $r;
    }

    static public function sendMethod( $method, $parm, $token=NULL ){
        global $server, $tg_token;
        if( $token == NULL ) $token = $tg_token;
        $curl = curl_init();
        curl_setopt_array( $curl, [
            CURLOPT_URL => "https://api.telegram.org/bot$token/$method",
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query( $parm )
        ] );
        $r = curl_exec( $curl );
        curl_close( $curl );
        return json_decode($r, true);
    }

    static public function keyboard( $text, $number=NULL, $cb=NULL, $url=NULL ) {
        global $keyboard;
        if( !$cb ){
            $keyboard['keyboard'][$number][] = ['text' => $text];
        }else{
            if( !isset( $url ) ) $keyboard['inline_keyboard'][$number][] = ['text' => $text, 'callback_data' => json_encode( $cb )];
            if( isset( $url ) ) $keyboard['inline_keyboard'][$number][] = ['text' => $text, 'url' => $url];
        }
        return $keyboard;
    }
}

$Ly = new Ly();

require 'func.php';

$input = file_get_contents( 'php://input' );

if(isset($input)){

    $input = json_decode( $input, true );

    // file_put_contents( 'log.txt', json_encode( $input ) . PHP_EOL, FILE_APPEND | LOCK_EX );

    if( isset( $input['message'] ) ){
        $pre = $input['message'];
    }elseif( isset( $input['channel_post'] ) ){
        $pre = $input['channel_post'];
        $title = $pre['chat']['title'];
    }elseif( isset( $input['inline_query'] ) ){
        $pre = $input['inline_query'];
        $query = $pre['query'];
        $offset = $pre['offset'];
    }elseif( isset( $input['chosen_inline_result'] ) ){
        $pre = $input['chosen_inline_result'];
    }elseif( isset( $input['callback_query'] ) ){
        $pre = $input['callback_query'];
        $pre['message_id'] = $pre['message']['message_id'];
        $chat_id = $pre['message']['chat']['id'];
        $callback_query_id = $input['callback_query']['id'];
        $query = json_decode( $input['callback_query']['data'], true );
    }

    if( isset($pre) ){
        $message_id = $pre['message_id'];
        @$user_id = $pre['from']['id'];
        if(isset($pre['chat']['id'])) $chat_id = $pre['chat']['id'];
        $login = null;
        if( isset($pre['from']['first_name']) ) $login = $pre['from']['first_name'];
        if( isset($pre['from']['last_name']) ) $login .= ' ' . $pre['from']['last_name'];
        @$username = $pre['from']['username'];
        @$language = $pre['from']['language_code'];
        if(isset($pre['text'])) $text = $pre['text'];
        else $text = '';
    }

    if( isset($user_id) ){
        $user = R::findOne('user', 'telegram_id = ?', [$user_id]);

        if( !isset($user) ){
            $user = R::dispense('user');
            $user['login'] = $login;
            $user['username'] = $username;
            $user['telegram_id'] = $user_id;
            $user['time_reg'] = time();
            $user['time'] = time();
            R::store($user);
        }else{
            $user['login'] = $login;
            $user['username'] = $username;
        }
    }

}