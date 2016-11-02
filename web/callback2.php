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

    if ("@bye" == $event->message->text && ("group" == $event->source->type || "room" == $event->source->type)) {
    	if("group" == $event->source->type) {
    		$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);
    		$response2 = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    		$response3 = $bot->pushMessage('R9b7dbfd03cbc9c2e4ab3624051c6b011', $textMessageBuilder);
    		$response = $bot->leaveGroup($event->source->groupId);
    	} else if("room" == $event->source->type) {
    		$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);
    		$response2 = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    		$response3 = $bot->pushMessage('C56e234e2a4de4a584436e5b303f774ac', $textMessageBuilder);
    		$response = $bot->leaveRoom('R9b7dbfd03cbc9c2e4ab3624051c6b011');
    	}


    } else if ("@join" == $event->message->text) {
    	$response = $bot->getProfile($event->source->userId);
    	if ($response->isSucceeded()) {
    		$profile = $response->getJSONDecodedBody();
    		$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($profile['displayName'] . "はゲームに参加したよ！");
    		$response2 = $bot->replyMessage($event->replyToken, $textMessageBuilder);
		}

    } else if ("text" == $event->message->type) {

        //$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);

        if("group" == $event->source->type) {
    		//$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);
    		$response_format_text = [
    			"type" => "template",
    			"altText" => "はろはろー",
    			"template" => [
    				"type" => "buttons",
    				"thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg",
    				"title" => "めにゅー",
    				"text" => "めにゅーだよ！",
    				"actions" => [
    					[
    						"type" => "message",
    						"label" => "まる１",
    						"text" => "@1"
    					],
    					[
    						"type" => "message",
    						"label" => "まる２",
    						"text" => "@2"
    					],
    					[
    						"type" => "message",
    						"label" => "まる３",
    						"text" => "@3"
    					]
    				]
    			]
    		];
    		$post_data = [
				"to" => 'R9b7dbfd03cbc9c2e4ab3624051c6b011',
				"messages" => [$response_format_text]
			];
			$ch = curl_init("https://api.line.me/v2/bot/message/push");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json; charser=UTF-8',
			    'Authorization: Bearer ' . 'w9SmZJ6zm2ln3DRx5gw6lxNgLi5Ayjx7ftGGpyEsKhM0sGStTEdwNeu7UdSe7H3Mj7ayGjRubK0xHN7onGWxEwL6K8lHyukidy2my3LQT02u+EsRK+Mqsvj4fe0OVCIEYzFMAC+VzUTNjINaAQiRbwdB04t89/1O/w1cDnyilFU='
			    ));
			$result = curl_exec($ch);
			curl_close($ch);

    		//$actionBuilders = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("ぬ！", "nu");
    		//$buttonsMessageBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("めにゅー", "めにゅーだよ！", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", $actionBuilders);
    		//$response = $bot->pushMessage('R9b7dbfd03cbc9c2e4ab3624051c6b011', $textMessageBuilder);
    	} else if("room" == $event->source->type) {
    		//$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);
    		$response_format_text = [
    			"type" => "template",
    			"altText" => "はろはろー",
    			"template" => [
    				"type" => "buttons",
    				"thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg",
    				"title" => "めにゅー",
    				"text" => "めにゅーだよ！",
    				"actions" => [
    					[
    						"type" => "message",
    						"label" => "まる１",
    						"text" => "@1"
    					],
    					[
    						"type" => "message",
    						"label" => "まる２",
    						"text" => "@2"
    					],
    					[
    						"type" => "message",
    						"label" => "まる３",
    						"text" => "@3"
    					]
    				]
    			]
    		];
    		$post_data = [
				"to" => 'C56e234e2a4de4a584436e5b303f774ac',
				"messages" => [$response_format_text]
			];
			$ch = curl_init("https://api.line.me/v2/bot/message/push");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json; charser=UTF-8',
			    'Authorization: Bearer ' . 'w9SmZJ6zm2ln3DRx5gw6lxNgLi5Ayjx7ftGGpyEsKhM0sGStTEdwNeu7UdSe7H3Mj7ayGjRubK0xHN7onGWxEwL6K8lHyukidy2my3LQT02u+EsRK+Mqsvj4fe0OVCIEYzFMAC+VzUTNjINaAQiRbwdB04t89/1O/w1cDnyilFU='
			    ));
			$result = curl_exec($ch);
			curl_close($ch);


    		//$actionBuilders = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("ぬ！", "nu");
    		//$buttonsMessageBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("めにゅー", "めにゅーだよ！", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", $actionBuilders);
    		//$response = $bot->pushMessage('C56e234e2a4de4a584436e5b303f774ac', $textMessageBuilder);
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

    //なにもしない

}



//$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

syslog(LOG_EMERG, print_r($event->replyToken, true));

syslog(LOG_EMERG, print_r($response, true));

return;
