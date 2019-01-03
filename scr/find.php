<?
require 'BotLy.php';

while(1){
    newSearch:

    $keyboard = [];

    $userCollection = R::findCollection( 'user', 'search > 0 ORDER BY m_sex DESC, `search` ASC' );
    while ( $user = $userCollection->next() ) {
        $userData = json_decode(base64_decode($user['data']), true);

        if( $user['search']+2000 < time() ){
            Ly::sendMethod ( 'deleteMessage', ['chat_id' => $user['telegram_id'], 'message_id' => $userData['menu_id'] ] );
            $message = '<b>Поиск завершен, так как Вы слишком долго находились в ожидании.</b>';
            Ly::sendMethod ( 'sendMessage', ['chat_id' => $user['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML' ] );
            $user['search'] = 0;
            R::store($user);
        }else{

            if( $user['m_sex'] == 0 ) $findCollection = R::findCollection('user', 'id != ? && search > 0 && m_sex = 0 ORDER BY `search` ASC', [ $user['id'] ]);
            else $findCollection = R::findCollection('user', 'id != ? && search > 0 && m_sex = ? ORDER BY `search` ASC', [ $user['id'], $userData['wish']['sex'] ]);
            
            while ( $find = $findCollection->next() ) {
                $findData = json_decode(base64_decode($find['data']), true);

                if( $findData['wish']['sex'] == 0 OR $findData['wish']['sex'] == $user['m_sex'] OR ( $user['m_sex'] == 0 && $find['m_sex'] == 0 ) ){

                    $findLastDialog = R::findOne('dialog', 'user_id = ? && partner_id = ? ORDER BY `id` DESC', [$find['id'], $user['id']]);
                    $userLastDialog = R::findOne('dialog', 'user_id = ? && partner_id = ? ORDER BY `id` DESC', [$user['id'], $find['id']]);

                    $dtime = $findLastDialog['time']+3600;

                    if( !isset($findLastDialog) OR ( $findLastDialog['ban'] == 0 && $userLastDialog['ban'] == 0 ) OR time() > $dtime ){
                        $dialog = R::dispense('dialog');
                        $dialog['user_id'] = $user['id'];
                        $dialog['partner_id'] = $find['id'];
                        $dialog['timeReg'] = time();
                        $dialog['timeLast'] = time();
                        $user_dialog_id = R::store($dialog);

                        $dialog = R::dispense('dialog');
                        $dialog['user_id'] = $find['id'];
                        $dialog['partner_id'] = $user['id'];
                        $dialog['timeReg'] = time();
                        $dialog['timeLast'] = time();
                        $find_dialog_id = R::store($dialog);

                        if(isset($user_dialog_id) && isset($find_dialog_id)){
                            $user['dialog_id'] = $user_dialog_id;
                            $user['search'] = 0;
                            R::store($user);

                            $find['dialog_id'] = $find_dialog_id;
                            $find['search'] = 0;
                            R::store($find);

                            $message = "<b>Найден собеседник для общения.</b>\nПриятного общения 😉";

                            Ly::keyboard( "Действия", 0 );

                            $keyboard['keyboard'] = array_values($keyboard['keyboard']);
                            $keyboard['resize_keyboard'] = true;
                            $keyboard = json_encode( $keyboard );

                            Ly::sendMethod ( 'sendMessage', ['chat_id' => $user['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
                            Ly::sendMethod ( 'sendMessage', ['chat_id' => $find['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );

                            Ly::sendMethod ( 'deleteMessage', ['chat_id' => $user['telegram_id'], 'message_id' => $userData['menu_id'] ] );
                            Ly::sendMethod ( 'deleteMessage', ['chat_id' => $find['telegram_id'], 'message_id' => $findData['menu_id'] ] );

                            goto newSearch;
                        }
                    }
                }
            }
        }
    }

    sleep(1);

    $dialogCollection = R::findCollection( 'dialog', '`time_last` < ? && end = 0 ORDER BY `time_last` ASC LIMIT 1', [time()-86400] );
    while ( $userDialog = $dialogCollection->next() ) {
        $partnerUser = R::findOne('user', 'id = ?', [$userDialog['partner_id']]);
        $partnerDialog = R::findOne('dialog', 'user_id = ? && partner_id = ? && time_reg = ?', [$userDialog['partner_id'], $userDialog['id'], $userDialog['time_reg']]);

        echo $userDialog['id'].PHP_EOL;

        if(isset($dialogUser) && isset($partnerUser)){
                
                $message = "<b>Диалог окончен, так как он был слишком долго неактивен.</b>";

                $spam_text = 'Сообщить о спаме';
                $ban_text = 'Заблокировать на 1 час';
                
                Ly::keyboard( $spam_text, 0, ['m' => 2, 'dialog_id' => $partnerUser['dialog_id'], 't' => 'spam'] );
                Ly::keyboard( $ban_text, 1, ['m' => 2, 'dialog_id' => $partnerUser['dialog_id'], 't' => 'ban'] );
    
                $keyboard = json_encode( $keyboard );
                
                Ly::sendMethod ( 'sendMessage', ['chat_id' => $userDialog['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
                
                $keyboard = [];
                Ly::keyboard( $spam_text, 0, ['m' => 2, 'dialog_id' => $userDialog['dialog_id'], 't' => 'spam'] );
                Ly::keyboard( $ban_text, 1, ['m' => 2, 'dialog_id' => $userDialog['dialog_id'], 't' => 'ban'] );
                
                $keyboard = json_encode( $keyboard );
                
                Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
    
                $keyboard = [];
    
                $message = "Вы можете начать новый поиск собеседника, написав /start";
    
                Ly::keyboard( "Начать поиск", 0 );
    
                $keyboard['keyboard'] = array_values($keyboard['keyboard']);
                $keyboard['resize_keyboard'] = true;
                $keyboard = json_encode( $keyboard );
    
                Ly::sendMethod ( 'sendMessage', ['chat_id' => $partnerUser['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
                Ly::sendMethod ( 'sendMessage', ['chat_id' => $userDialog['telegram_id'], 'text' => $message, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard ] );
    
                $partnerUser['dialog_id'] = 0;
                $partnerUser['contacts'] = 0;
                R::store($partnerUser);
                $userDialog['dialog_id'] = 0;
                $userDialog['contacts'] = 0;
                R::store($userDialog);

                $partnerDialog['end'] = time();
                R::store($partnerDialog);
        }
        $userDialog['end'] = time();
        R::store($userDialog);
    }

    sleep(1);
}