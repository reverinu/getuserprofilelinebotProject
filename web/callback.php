<?php





require('../vendor/autoload.php');



//POST

$input = file_get_contents('php://input');

$json = json_decode($input);

$event = $json->events[0];



$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('w9SmZJ6zm2ln3DRx5gw6lxNgLi5Ayjx7ftGGpyEsKhM0sGStTEdwNeu7UdSe7H3Mj7ayGjRubK0xHN7onGWxEwL6K8lHyukidy2my3LQT02u+EsRK+Mqsvj4fe0OVCIEYzFMAC+VzUTNjINaAQiRbwdB04t89/1O/w1cDnyilFU=');

$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '3095c84a53d38913b6716fb770f3f326']);



//イベントタイプ判別

if ("message" == $event->type) {            //一般的なメッセージ(文字・イメージ・音声・位置情報・スタンプ含む)

    //テキストメッセージにはオウムで返す

    if ("@bye" == $event->message->text && "group" == $event->source->type || "room" == $event->source->type) {
    	//$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("退出するよ！じゃあね！");
    	//$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    	if("group" == $event->source->type) {
    		$response = $bot->leaveGroup($event->source->groupId);
    	} else if("room" == $event->source->type) {
    		$response = $bot->leaveRoom($event->source->roomId);
    	}
    	
    	return;
    	
    
    } else if ("text" == $event->message->type) {

        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);

    } else {

        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ごめん、わかんなーい(*´ω｀*)");

    }

} elseif ("follow" == $event->type) {        //お友達追加時

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("よろしくー");

} elseif ("join" == $event->type) {           //グループに入ったときのイベント

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('こんにちは よろしくー');

} elseif ('beacon' == $event->type) {         //Beaconイベント

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('Godanがいんしたお(・∀・) ');

} else {

    //なにもしない

}



$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

syslog(LOG_EMERG, print_r($event->replyToken, true));

syslog(LOG_EMERG, print_r($response, true));

return;