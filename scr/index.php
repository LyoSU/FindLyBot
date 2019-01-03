<?
require 'BotLy.php';

$userData = json_decode(base64_decode($user['data']),true);

if(!isset($userData['wish']['sex'])) $userData['wish']['sex'] = 0;

$keyboard = [];

function menuSearchkeyboard()
{
    global $user, $userData;

    $sexArray = ['Любой', 'М', 'Ж'];
    $ageArray = ['-18', '18-24', '25-35', '36+'];

    if($user['search'] == 0){
        Ly::keyboard( '▶️ Ваш пол:', 10, ['m' => 1, 'i' => 'sex'] );
        foreach ($sexArray as $key => $value)
            Ly::keyboard( (($user['m_sex'] == $key)?"👉 ":"" ).$value, 11, ['m' => 1, 'm_sex' => $key] );

        if( $user['m_sex'] > 0 ){
            // Ly::keyboard( '▶️ Ваш возраст:', 20, ['i' => 'age'] );
            // foreach ($ageArray as $key => $value)
            //     Ly::keyboard( (($user['m_age'] == $key)?"👉 ":"" ).$value, 21, ['m_age' => $key] );

            Ly::keyboard( '▶️ Пол собеседника:', 50, ['m' => 1, 'i' => 'sex'] );
            foreach ($sexArray as $key => $value) {
                Ly::keyboard( (($userData['wish']['sex'] == $key)?"👉 ":"" ).$value, 51, ['m' => 1, 'w_sex' => $key] );
            }

            // Ly::keyboard( '▶️ Возраст собеседника:', 60, ['i' => 'age'] );
            // foreach ($ageArray as $key => $value)
            //     Ly::keyboard( (($userData['wish']['age'][$key] == 1)?"👉 ":"" ).$value, 61, ['w_age' => $key] );
        }

        Ly::keyboard( '▶️ Начать поиск ◀️', 100, ['m' => 1, 'search' => 1] );
    }else{
        Ly::keyboard( '▶️ Остановить поиск ◀️', 100, ['m' => 1, 'search' => 0] );
    }
}

$menuText = "Привет, <b>$login</b>\nЭто анонимный чат-бот для поиска случайного собеседника.\nНикто не узнает вашего имени и любой другой информации, пока вы сами не захотите этого.\n\n<b>Политика конфиденциальности: </b> /privacy";
$menuSearchText = "<b>Поиск собеседника...</b>";

if( $user['dialog_id'] > 0 ) $userDialog = R::findOne('dialog', 'id = ?', [$user['dialog_id']]);
if(isset($userDialog)){
    $partnerUser = R::findOne('user', 'id = ?', [$userDialog['partner_id']]);
    if($partnerUser['dialog_id'] == 0) $user['dialog_id'] = 0;
}

if( isset($input['message']) && $input['message']['chat']['type'] == 'private' ){

    if($user['ban'] == 1){
        Ly::sendMethod ( 'sendMessage', ['chat_id' => $chat_id, 'text' => "<b>Ваш аккаунт был заблокирован.</b>", 'parse_mode' => 'HTML' ] );
        exit;
    }

    if( $text == '/start' OR $text == 'Начать поиск' ){
        if(isset($partnerUser)){
            $message = "<b>Собеседник уже найден, общайтесь.</b>\nДля остановки общения отправьте /actions";
        }else{

            $searchCount = R::count('user', 'search > 0');
            $dialogCount = R::count('dialog', 'end = 0');

            if($user['search'] == 0) $message = $menuText;
            else $message = $menuSearchText;

            $message .= "\n\n<b>Людей в поиск:</b> ".$searchCount;
            $message .= "\n<b>Активных диалогов:</b> ".$dialogCount;

            menuSearchkeyboard();

            $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
            $keyboard = json_encode( $keyboard );

            $sendMessage = Ly::sendMethod ( 'sendMessage', ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
            unset($message);
            
            if(isset($userData['menu_id'])) Ly::sendMethod ( 'deleteMessage', ['chat_id' => $user['telegram_id'], 'message_id' => $userData['menu_id'] ] );
            $userData['menu_id'] = $sendMessage['result']['message_id'];
        }
    }elseif( ( $text == '/actions' OR $text == 'Действия' ) ){
        $message = "<b>Доступные действия:</b>";

        Ly::keyboard( "Обменяться контактами", 0, ['m' => 3, 'a' => 1] );
        Ly::keyboard( "Остановить диалог", 1, ['m' => 3, 'a' => 2] );

        $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
        $keyboard = json_encode( $keyboard );
    }elseif( $text == '/privacy' OR $text == 'Политика конфиденциальности' ){
        $message = "<b>Политика конфиденциальности бота:</b>\n\n<b>1. Какие данные мы собираем и для чего они?</b>\n1.1 Ваш публичный ID Telegram, имя и username. Они нужны для идентификации Вас как пользователя ботом.\n1.2 Ваши данные, которые Вы ввели в боте, такие как пол и возраст. Они нужны для подбора собеседника.\n1.3 Ваши сообщения с собеседником, на которые он подал жалобу. Они нужны для борьбы со спамом.\n\n<b>2. Кому мы передаем Ваши данные?</b>\nВаши данные в полной безопасности и третья сторона их никогда не получит.\n\n<b>3. Исключения</b>\n3.1 Вы можете сами сообщить информацию в переписке с собеседником, в таком случае она может быть использоватся им как угодно.\n3.2 Если собеседник пожаловался на ваш диалог, наши модераторы получат доступ к Вашим сообщениям в этом дилалоге. Ваше имя и другая информация не будет им доступна, если вы не писали её в сообщениях.\n\n\nПо любым вопросам писать @LyoSU или если у вас спам бан в @ly_oBot <i>(не рекомендуется)</i>";
    }else{
        if( isset($partnerUser) && isset($userDialog) ){

            $dialogMessage = json_decode(base64_decode($userDialog['message']),true);
            $dialogMessage[] = $pre;
            $userDialog['message'] = base64_encode(json_encode($dialogMessage));
            $userDialog['timeLast'] = time();

            if($user['contacts'] == 1 && $partnerUser['contacts'] == 1) $partnerUserLogin = "<a href=\"tg://user?id=".$user['telegram_id']."\">".$user['login']."</a>";
            else $partnerUserLogin = "<b>Собеседник</b>";
            
            // if(isset($pre['entities']) OR isset($pre['caption_entities'])) $message = "<b>Ошибка!</b>\nСсылки и форматирование в сообщении запрещены.";
            if(!isset($pre['caption'])) $pre['caption'] = '';

            if(isset($pre['photo']))
                Ly::sendMethod ( 'sendPhoto', ['chat_id' => $partnerUser['telegram_id'], 'photo' => end($pre['photo'])['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['audio']))
                Ly::sendMethod ( 'sendAudio', ['chat_id' => $partnerUser['telegram_id'], 'audio' => $pre['audio']['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['video']))
                Ly::sendMethod ( 'sendVideo', ['chat_id' => $partnerUser['telegram_id'], 'video' => $pre['video']['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['animation']))
                Ly::sendMethod ( 'sendAnimation', ['chat_id' => $partnerUser['telegram_id'], 'animation' => $pre['animation']['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['voice']))
                Ly::sendMethod ( 'sendVoice', ['chat_id' => $partnerUser['telegram_id'], 'voice' => $pre['voice']['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['video_note']))
                Ly::sendMethod ( 'sendVideoNote', ['chat_id' => $partnerUser['telegram_id'], 'video_note' => $pre['video_note']['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['sticker']))
                Ly::sendMethod ( 'sendSticker', ['chat_id' => $partnerUser['telegram_id'], 'sticker' => $pre['sticker']['file_id'], 'caption' => $pre['caption'], 'parse_mode' => 'HTML' ] );
            elseif(isset($pre['document']))
                $message = "<b>Ошибка!</b>\nДокументы запрещено отправлять.";
            elseif(!empty($text))
                Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => "$partnerUserLogin\n".$text, 'parse_mode' => 'HTML' ] );
        }else{
            $message = "<b>У тебя сейчас нет собеседника.</b>\nДля поиска собеседника напиши /start";
        }
    }
    if(isset($message)) Ly::sendMethod ( 'sendMessage', ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
}elseif( isset($input['callback_query']) ){

    $answerCallbackText = '';
    $show_alert = false;
    $keyboard = [];

    switch ($query['m']) {
        case 1:
            if(isset($query['search'])){
                if($user['search'] > 0) $user['search'] = 0;
                else $user['search'] = time();
            }elseif($user['search'] == 0 && $user['dialog_id'] == 0){
                if(isset($query['m_sex'])) $user['m_sex'] = $query['m_sex'];
                elseif(isset($query['m_age'])) $user['m_age'] = $query['m_age'];
                elseif(isset($query['w_sex'])) $userData['wish']['sex'] = $query['w_sex'];
                elseif(isset($query['w_age'])) $userData['wish']['age'][$query['w_age']] = (($userData['wish']['age'][$query['w_age']] == 1)?0:1);
            }

            menuSearchkeyboard();

            $keyboard['inline_keyboard'] = array_values($keyboard['inline_keyboard']);
            $keyboard = json_encode( $keyboard );

            $searchCount = R::count('user', 'search > 0');
            $dialogCount = R::count('dialog', 'end = 0');

            if($user['search'] == 0) $message = $menuText;
            else $message = $menuSearchText;

            $message .= "\n\n<b>Людей в поиск:</b> ".$searchCount;
            $message .= "\n<b>Активных диалогов:</b> ".$dialogCount;

            Ly::sendMethod ( 'editMessageText', ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
            if( isset($userData['menu_id']) && $message_id !== $userData['menu_id'] ) Ly::sendMethod ( 'editMessageText', ['chat_id' => $chat_id, 'message_id' => $userData['menu_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
        break;
        
        case 2:
            $userDialog = R::findOne('dialog', 'id = ?', [$query['dialog_id']]);
            if($query['t'] == 'spam'){
                $userDialog['spam'] = 1;
                $answerCallbackText = 'Благодарим, ваша жалоба отправлена модераторам на проверку';
            }elseif($query['t'] == 'ban'){
                $userDialog['ban'] = 1;
                $answerCallbackText = 'Вы больше не встретите этого пользователя в течение 1 часа';
            }
            $show_alert = true;
        break;

        case 3:
            if($query['a'] == 1){
                if(isset($partnerUser)){
                    if($partnerUser['contacts'] == 0){
                        $message = "<b>Вы предложили обменяться контактами.</b>";
            
                        Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => "<b>Собеседник предложил обменятся контактами.</b>\nДля обмена напиши /actions", 'parse_mode' => 'HTML' ] );
                        $user['contacts'] = 1;
                    }else{
                        $message = "<b>Контакты собеседника:</b> <a href=\"tg://user?id=".$partnerUser['telegram_id']."\">".$partnerUser['login']."</a>\nУдачного общения!";
            
                        if($user['contacts'] == 0){
                            Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => "<b>Контакты собеседника:</b> <a href=\"tg://user?id=".$user['telegram_id']."\">".$user['login']."</a>\nУдачного общения!", 'parse_mode' => 'HTML' ] );
                            $user['contacts'] = 1;
                        }
                    }
                }
            }elseif($query['a'] == 2){
                if( isset($partnerUser) ){
                    $spam_text = 'Сообщить о спаме';
                    $ban_text = 'Заблокировать на 1 час';
                    
                    Ly::keyboard( $spam_text, 0, ['m' => 2, 'dialog_id' => $partnerUser['dialog_id'], 't' => 'spam'] );
                    Ly::keyboard( $ban_text, 1, ['m' => 2, 'dialog_id' => $partnerUser['dialog_id'], 't' => 'ban'] );
        
                    $keyboard = json_encode( $keyboard );
                    
                    Ly::sendMethod ( 'sendMessage', ['chat_id' => $user['telegram_id'], 'text' => "<b>Диалог окончен</b>", 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
                    
                    $keyboard = [];
                    Ly::keyboard( $spam_text, 0, ['m' => 2, 'dialog_id' => $user['dialog_id'], 't' => 'spam'] );
                    Ly::keyboard( $ban_text, 1, ['m' => 2, 'dialog_id' => $user['dialog_id'], 't' => 'ban'] );
                    
                    $keyboard = json_encode( $keyboard );
                    
                    Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => "<b>Собеседник завершил диалог</b>", 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
        
                    $keyboard = [];
        
                    $message = "Вы можете начать новый поиск собеседника, написав /start";
        
                    Ly::keyboard( "Начать поиск", 0 );
        
                    $keyboard['keyboard'] = array_values($keyboard['keyboard']);
                    $keyboard['resize_keyboard'] = true;
                    $keyboard = json_encode( $keyboard );
        
                    Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
        
                    $partnerUser['dialog_id'] = 0;
                    $partnerUser['contacts'] = 0;
                    R::store($partnerUser);
                    $user['dialog_id'] = 0;
                    $user['contacts'] = 0;
                    R::store($user);
                    
                    $partnerDialog = R::findOne('dialog', 'user_id = ? && partner_id = ? ORDER BY `time_reg` DESC', [$userDialog['partner_id'], $userDialog['user_id']]);

                    $partnerDialog['end'] = time();
                    R::store($partnerDialog);

                    $userDialog['end'] = time();
                    
                }else{
                    $message = "<b>У тебя сейчас нет собеседника.</b>\nДля поиска собеседника напиши /start";
                }
            }
            Ly::sendMethod ( 'sendMessage', ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
        break;
    }
    
    Ly::sendMethod ( 'answerCallbackQuery', [ 'callback_query_id' => $callback_query_id, 'text' => $answerCallbackText, 'show_alert' => $show_alert ] );
}

$user['data'] = base64_encode(json_encode($userData));
$user['time'] = time();

if(isset($user_id)) R::store($user);
if(isset($userDialog)) R::store($userDialog);