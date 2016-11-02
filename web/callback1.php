<?php
//アーカイブ




require('../vendor/autoload.php');



//POST

$input = file_get_contents('php://input');
$json = json_decode($input);
$event = $json->events[0];
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('w9SmZJ6zm2ln3DRx5gw6lxNgLi5Ayjx7ftGGpyEsKhM0sGStTEdwNeu7UdSe7H3Mj7ayGjRubK0xHN7onGWxEwL6K8lHyukidy2my3LQT02u+EsRK+Mqsvj4fe0OVCIEYzFMAC+VzUTNjINaAQiRbwdB04t89/1O/w1cDnyilFU=');
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '3095c84a53d38913b6716fb770f3f326']);



//イベントタイプ判別

if ("message" == $event->type) {            //一般的なメッセージ(文字・イメージ・音声・位置情報・スタンプ含む)

    if ("@join" == $event->message->text) {
    	$response = $bot->getProfile($event->source->userId);
    	if ($response->isSucceeded()) {
    		$profile = $response->getJSONDecodedBody();
    		$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($profile['displayName'] . "はゲームに参加したよ！");
    		$response2 = $bot->replyMessage($event->replyToken, $textMessageBuilder);
		  }

    } else if ("text" == $event->message->type) {

      if("group" == $event->source->type) {

        //groupの話
        $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("NU", "nu");
        $action1 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("NO", "no");
        $action2 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("NE", "ne");

        $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("ひげ", "ひげげ", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0, $action1, $action2]);
        $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("ひげがここにボタンで表示されてるよ", $button);


        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ぬ");
        $response = $bot->pushMessage('R9b7dbfd03cbc9c2e4ab3624051c6b011', $button_message);
      } else if("room" == $event->source->type) {

    	}

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
}



//$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

syslog(LOG_EMERG, print_r($event->replyToken, true));

syslog(LOG_EMERG, print_r($response, true));

return;
