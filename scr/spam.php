<?
require 'BotLy.php';

$dialogCollection = R::findCollection( 'dialog', 'spam > 0 ORDER BY `time_reg` DESC' );
while ( $dialog = $dialogCollection->next() ) {
    $did = md5('se1'.$dialog['user_id']);
    $spam[$did][] = $dialog['id'];
    $spamMessage[$dialog['id']] = $dialog['message'];
}

arsort($spam);

if(isset($_GET['did'])){
    foreach ($spam[$_GET['did']] as &$v) {
        $messages = json_decode(base64_decode($spamMessage[$v]), true);
        foreach ($messages as &$message) {
            $file_id = '';

            if(isset($message['photo'])) $file_id = end($message['photo'])['file_id'];
            elseif(isset($message['audio'])) $file_id = $message['audio']['file_id'];
            elseif(isset($message['video'])) $file_id = $message['video']['file_id'];
            elseif(isset($message['animation'])) $file_id = $message['animation']['file_id'];
            elseif(isset($message['voice'])) $file_id = $message['voice']['file_id'];
            elseif(isset($message['video_note'])) $file_id = $message['video_note']['file_id'];
            elseif(isset($message['sticker'])) $file_id = $message['sticker']['file_id'];
            
            if(!empty($file_id)) echo "<a href='//ua.lyo.su/FindLyBot/file.php?file_id=$file_id'><b>Файл</b></a><br>";
            elseif(!isset($message['text'])) print_r($message);
            else echo "<b>Сообщение:</b> ".$message['text'].'<br>';
        }
        echo '<hr>';
    }
}else{
    foreach ($spam as $key => $value) if(count($value) > 5){
        echo "<a href='?did=$key'>Жалоб: ".count($value)."</a><br>";
    }
}