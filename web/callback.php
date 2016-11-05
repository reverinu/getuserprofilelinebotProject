<?php


// データベース
// ・game_room
// game_room_num(Int) game_room_id(String) game_mode(String) num_of_people(Int) num_of_roles(Int) num_of_votes(Int)
// ・user
// user_id(String) user_name(String) game_room_num(Int) role(String) voted_num(Int) is_roling(Bool) is_voting(Bool)
//
// 初期値
// ・グループ
// gameRoomNum = null
// gameRoomId = null
// gameMode = "BEFORE_THE_START"
// numOfPeople = 0
// numOfRoles = 0
// numOfVotes = 0
//
// ・個人
// userId = null
// userName = null
// role = "無し"
// votedNum = 0
// isRoleing = false
// isVoting = false
//
// ・ゲームモード
// modeName
// BEFORE_THE_START
// WAITING
// NIGHT
// NOON
// END
//
// ・役職
// role
// 無し
// 村人
// 占い師
// 怪盗
// 人狼
// 狂人
// 吊人


require('../vendor/autoload.php');


//POST

$input = file_get_contents('php://input');
$json = json_decode($input);
$event = $json->events[0];
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('w9SmZJ6zm2ln3DRx5gw6lxNgLi5Ayjx7ftGGpyEsKhM0sGStTEdwNeu7UdSe7H3Mj7ayGjRubK0xHN7onGWxEwL6K8lHyukidy2my3LQT02u+EsRK+Mqsvj4fe0OVCIEYzFMAC+VzUTNjINaAQiRbwdB04t89/1O/w1cDnyilFU=');
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '3095c84a53d38913b6716fb770f3f326']);


////////////////////////////
//データベースと接続する場所
////////////////////////////
$server = 'us-cdbr-iron-east-04.cleardb.net';
$username = 'b8613072c41507';
$password = 'a207894a';
$db = 'heroku_e0a333c38f14545';
$link = mysqli_connect($server, $username, $password, $db);


$PEOPLE3 = array('村人','占い師','怪盗','人狼','人狼');
$PEOPLE4 = array('村人','村人','占い師','怪盗','人狼','人狼');
$PEOPLE5 = array('村人','村人','占い師','怪盗','人狼','人狼','狂人');
$PEOPLE6 = array('村人','村人','村人','占い師','怪盗','人狼','人狼','狂人');

$GAMEMODE_BEFORE_THE_START = "BEFORE_THE_START";//@game前
$GAMEMODE_WAITING = "WAITING";//@game後
$GAMEMODE_NIGHT = "NIGHT";//夜時間
$GAMEMODE_NOON = "NOON";//昼時間
$GAMEMODE_END = "END";//投票結果開示


////////////////////////////
//メインループ
////////////////////////////
$gameMode = $GAMEMODE_BEFORE_THE_START;
// グループIDもしくはルームIDが取得できる$event->source->groupId or $event->source->roomId
// それをテーブルで検索してあればそこのレコードのGAMEMODEを$gamemodeに代入。無ければ$gameMode = $GAMEMODE_BEFORE_THE_START;ってif文を作ってほしい
if ("group" == $event->source->type) {
  $gameRoomId = $event->source->groupId;
} else if ("room" == $event->source->type) {
  $gameRoomId = $event->source->roomId;
} else if ("user" == $event->source->type) {
  $user_id = $event->source->userId;
  $user_id = mysqli_real_escape_string($link, $user_id);
  $result = mysqli_query($link, "select * from user where user_id = '$user_id'");
  $row = mysqli_fetch_row($result);
  $game_room_num = $row[3];
  $game_room_num = mysqli_real_escape_string($link, $game_room_num);
  $result = mysqli_query($link, "select * from game_room where game_room_num = '$game_room_num'");
  $row = mysqli_fetch_row($result);
  $gameRoomId = $row[2];
}
$gameRoomId = mysqli_real_escape_string($link, $gameRoomId);
if($result = mysqli_query($link, "select * from game_room where game_room_id = '$gameRoomId';")){
  $row = mysqli_fetch_row($result);
  if(null != $row){
    $game_mode = $row[3];
    $gameMode = $game_mode;
  }
}

if("message" == $event->type){
  DoActionAll($event->message->text);
  if ($GAMEMODE_BEFORE_THE_START == $gameMode){
    DoActionBefore($event->message->text);
  } else if ($GAMEMODE_WAITING == $gameMode) {
    DoActionWaiting($event->message->text);
  } else if ($GAMEMODE_NIGHT == $gameMode) {
    DoActionNight($event->message->text);
  } else if ($GAMEMODE_NOON == $gameMode) {
    DoActionNoon($event->message->text);
  } else if ($GAMEMODE_END == $gameMode){
    DoActionEnd($event->message->text);
  }
} else if ("join" == $event->type){
  DoActionJoin();
} else if ("leave" == $event->type) {
  DoActionLeave();
}
return;

////////////////////////////
//関数群
////////////////////////////
//全てに共通するDoAction,メッセージを見てアクションする
function DoActionAll($message_text){
  global $bot, $event, $link, $gameMode, $gameRoomId;
  if ("@help" == $message_text) {
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("[ヘルプ]\n@gameをグループチャットでコメントすることでゲーム開始前待機時間に移行します。そしてグループチャットがゲームルームとして認識され、ルームナンバーが発行されます。\nルームナンバーをそのままコピーして個人チャットで私にコメントすれば参加者として認識されます。\nゲーム開始前待機時間では、@memberをコメントすることで現在の参加者を見ることが出来ます。参加者が揃ったら@startしてください。ゲームが始まり夜時間へと移行します。\n夜時間では個人チャットに送られる私のコメントに従って行動してください。村人、狂人、人狼、吊人も了解ボタンを押してください。全員の行動が終われば自動的に議論時間へと移行します。\n議論時間の初めに個人チャットに投票ボタンをコメントします。ゲームルームで議論をし、投票する相手を決め投票してください。全員の投票が終われば自動的に投票結果、勝敗が開示され、ゲームが終了します。\nもう一度同じメンバーでやりたい場合は@newgameを、終わりたい、メンバーを追加したい場合は@endをゲームルームでコメントしてください。\n\n※ゲーム中に私をゲームルームから削除するとゲームがリセットされます");
    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
  } else if ("@rule" == $message_text) {
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ルール説明だよ");
    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
  } else if ("@debug" == $message_text) {//デバッグ用
    // $result = mysqli_query($link, "select * from game_room where game_room_id = '$gameRoomId'");
    // $row = mysqli_fetch_row($result);
    // $game_room_num = $row[1];
    // $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num'");
    // while($row = mysqli_fetch_row($result)){
    //   $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($row[4]);
    //   $response = $bot->pushMessage($event->source->groupId, $textMessageBuilder);
    // }

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($gameMode);
    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

  } else if ("@debug2" == $message_text) {
    // $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    // $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "人狼", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    // $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は人狼\n(「@ok」とコメントしてください)", $button);
    $button_message = CreateButtons("村人");
    $response = $bot->pushMessage("Uaa3a852ad12ceb1b4daca873a8462260", $button_message);
  } else if ("@del" == $message_text) {// デバッグ用
    $result = mysqli_query($link,"TRUNCATE TABLE game_room");
    $result = mysqli_query($link,"TRUNCATE TABLE user");
  } else if ("user" == $event->source->type) {// 一時的にこっち。最終的にはuser情報からテーブル持ってきて以下略（これだとゲーム中に途中参加できてしまう）
    $gameRoomNum = mysqli_real_escape_string($link, $message_text);
    $userId = mysqli_real_escape_string($link, $event->source->userId);
    if($result = mysqli_query($link, "select * from user where user_id = '$userId';")){
      $row = mysqli_fetch_row($result);
      if(null == $row){// 中身が空なら実行
        //個人チャット内
        if ($result = mysqli_query($link, "select * from game_room where game_room_num = '$gameRoomNum';")) {
          $row = mysqli_fetch_row($result);
          if(null != $row){
            $response = $bot->getProfile($event->source->userId);
            if ($response->isSucceeded()) {

              $result = mysqli_query($link, "update game_room set num_of_people = num_of_people+1 where game_room_num = '$gameRoomNum';");

              $profile = $response->getJSONDecodedBody();
              $user_name = mysqli_real_escape_string($link, $profile['displayName']);
              $user_id = mysqli_real_escape_string($link, $event->source->userId);
              $room_num = mysqli_real_escape_string($link, $row[1]);
              $result = mysqli_query($link, "insert into user (user_id, user_name, game_room_num, role, voted_num, is_roling, is_voting) values ('$user_id', '$user_name', '$room_num', '無し', 0, 'false', 'false');");
              $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($user_name . "はゲームに参加したよ！");
              $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
            }
          }
        }
      }
    }
  }
}
//BeforeのDoAction,メッセージを見てアクションする
function DoActionBefore($message_text){
  global $bot, $event, $link, $result;
  if("group" == $event->source->type || "room" == $event->source->type){
    if ("@game" == $message_text) {
      // ルームナンバー発行、テーブルにレコードを生成する、gameModeを移行する
      while(true){
        $gameRoomNum = mt_rand(100,999);
        $gameRoomNum = mysqli_real_escape_string($link, $gameRoomNum);
        $rnj = mysqli_query($link, "select * from game_room where game_room_num = '$gameRoomNum'");
        $row = mysqli_fetch_row($rnj);
        if(null == $row){
          break;
        }
      }
      $roomNumber = mysqli_real_escape_string($link, $gameRoomNum);
      if ("group" == $event->source->type){
        $gameRoomId = $event->source->groupId;
      } else if ("room" == $event->source->type) {
        $gameRoomId = $event->source->roomId;
      }
      $gameRoomId = mysqli_real_escape_string($link, $gameRoomId);
      $result = mysqli_query($link, "insert into game_room (game_room_num, game_room_id, game_mode, num_of_people, num_of_roles, num_of_votes) values ('$roomNumber', '$gameRoomId', 'WAITING', 0, 0, 0);");
      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ルームナンバーを発行したよ！\nルームナンバーは「" . $roomNumber . "」だよ！\n個人チャットでこの数字をコメントすればゲームに参加できるよ！");
      $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    }
  }
}
//WaitingのDoAction,メッセージを見てアクションする
function DoActionWaiting($message_text){
  global $bot, $event, $link, $gameRoomId, $GAMEMODE_NIGHT;
  if("group" == $event->source->type || "room" == $event->source->type){
    if ("@member" == $message_text) {
      // 現在参加者のみ表示
      $result = mysqli_query($link, "select * from game_room where game_room_id = '$gameRoomId'");
      $row = mysqli_fetch_row($result);
      if(null != $row){
        $num_of_people = $row[4];
        $game_room_num = $row[1];
        $game_room_num = mysqli_real_escape_string($link, $game_room_num);
        $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num'");
        $memberListText = "";
        while($row = mysqli_fetch_row($result)){
          $memberListText .= $row[2] . "\n";
        }
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("メンバー一覧(" . $num_of_people . ")\n" . $memberListText);
        $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
      }
    } else if ("@start" == $message_text) {
      // 参加者一覧を表示してからゲーム開始
      $GAMEMODE_NIGHT = mysqli_real_escape_string($link, $GAMEMODE_NIGHT);
      $result = mysqli_query($link, "update game_room set game_mode = '$GAMEMODE_NIGHT' where game_room_id = '$gameRoomId'");
      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("[ゲーム開始]\nワオーーーーン・・・\n\n\n狼の遠吠えが聞こえてくる。\n夜時間です。各自、個人チャットで行動してください");
      $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
      // 逃亡者生成
      $result = mysqli_query($link, "select * from game_room where game_room_id = '$gameRoomId'");
      $row = mysqli_fetch_row($result);
      if(null != $row){
        $room_num = $row[1];
        $room_num = mysqli_real_escape_string($link, $room_num);
        $result = mysqli_query($link, "insert into user (user_id, user_name, game_room_num, role, voted_num, is_roling, is_voting) values ('toubosya1', '逃亡者1', '$room_num', '無し', 0, 'false', 'false');");
        $result = mysqli_query($link, "insert into user (user_id, user_name, game_room_num, role, voted_num, is_roling, is_voting) values ('toubosya2', '逃亡者2', '$room_num', '無し', 0, 'false', 'false');");
      }

      Cast();
    }
  }
}
//NightのDoAction,メッセージを見てアクションする
function DoActionNight($message_text){
  global $bot, $event;
  //messageでif分けする（役職行動）
}
//NoonのDoAction,メッセージを見てアクションする
function DoActionNoon($message_text){
  global $bot, $event;
  //messageでif分けする(投票)
}
//EndのDoAction,メッセージを見てアクションする
function DoActionEnd($message_text){
  global $bot, $event;
  if ("@newgame" == $message_text) {

  } else if ("@end" == $message_text) {

  }
}
//部屋に入ったときに諸々発言
function DoActionJoin(){
  global $bot, $event;
  $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("僕はワンナイト人狼Botだよ！\n\nワンナイト人狼のルールを知りたいときは「@rule」\nこのbotの使い方を知りたいときは「@help」\nゲームを始めたいときは「@game」\n\nってコメントしてね！");
  $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
}
//部屋から退出させられるときの処理
function DoActionLeave(){
  global $bot, $event;
  $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ばいばーい！\nまたやりたくなったら入れてねー！");
  $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
}
//DoActionNightで役職行動のPostBack来たらこれを使う
function ProcessRoling(){
  //誰かが役職行動とるとカウント＋１とtrueにする、役職のカウントと参加人数を照合して同数になったらgameMode+1と全体チャットにその旨しを伝える
}
//DoActionNoonで投票のPostBack来たらこれを使う
function ProcessVoting(){
  //誰かが投票するとカウント＋１とtrueと投票された人に＋１にする、投票のカウントと参加人数を照合して同数になったらgameMode+1と投票結果開示する
}

function Cast(){
  global $link, $gameRoomId;
  $result = mysqli_query($link, "select * from game_room where game_room_id = '$gameRoomId';");
  $row = mysqli_fetch_row($result);
  if(null != $row){
    $num_of_people = $row[4];
    HandOut($num_of_people);
  }
}

function HandOut($num_of_people){
  global $bot, $event, $link, $PEOPLE3, $PEOPLE4, $PEOPLE5, $PEOPLE6, $gameRoomId;
  $result = mysqli_query($link, "select * from game_room where game_room_id = '$gameRoomId';");
  $row = mysqli_fetch_row($result);
  if(null != $row){

    $game_room_num = $row[1];
    $game_room_num = mysqli_real_escape_string($link, $game_room_num);
    if(3 == $num_of_people){

      shuffle($PEOPLE3);

      $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num'");
      $i = 0;
      while($row = mysqli_fetch_row($result)){
        $role = $PEOPLE3[$i];
        $role = mysqli_real_escape_string($link, $role);
        $user_id = $row[1];
        $result = mysqli_query($link, "update user set role = '$role' where user = '$user_id'");
        $i++;
      }

      //これがボタンに置き換わる
      $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num' limit 0, 1;");
      $row = mysqli_fetch_row($result);
      $user_id = $row[1];
      $button_message = CreateButtons(PEOPLE3[0]);
      $response = $bot->pushMessage($user_id, $button_message);
      $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num' limit 1, 1;");
      $row = mysqli_fetch_row($result);
      $user_id = $row[1];
      $button_message = CreateButtons(PEOPLE3[1]);
      $response = $bot->pushMessage($user_id, $button_message);
      $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num' limit 2, 1;");
      $row = mysqli_fetch_row($result);
      $user_id = $row[1];
      $button_message = CreateButtons(PEOPLE3[2]);
      $response = $bot->pushMessage($user_id, $button_message);
      //ここまで

    } else if(4 == $num_of_people){
      shuffle($PEOPLE4);
    } else if(5 == $num_of_people){
      shuffle($PEOPLE5);
    } else if(6 == $num_of_people){
      shuffle($PEOPLE6);
    }
  }
}
// 役職によってButtonの形状が異なる
function CreateButtons($role){
  if("村人" == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "村人", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は村人\n(「@ok」とコメントしてください)", $button);
  } else if("占い師" == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "占い師", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は占い師\n(「@ok」とコメントしてください)", $button);
  } else if("怪盗" == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "怪盗", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は怪盗\n(「@ok」とコメントしてください)", $button);
  } else if("人狼" == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "人狼", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は人狼\n(「@ok」とコメントしてください)", $button);
  } else if("狂人" == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "狂人", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は狂人\n(「@ok」とコメントしてください)", $button);
  }
}


////////////////////////////
//データベースとの接続を終了する場所
////////////////////////////
mysqli_free_result($result);
mysqli_close($link);



//
// //イベントタイプ判別
//
// if ("message" == $event->type) {            //一般的なメッセージ(文字・イメージ・音声・位置情報・スタンプ含む)
//
//     if ("@join" == $event->message->text) {
//     	$response = $bot->getProfile($event->source->userId);
//     	if ($response->isSucceeded()) {
//     		$profile = $response->getJSONDecodedBody();
//     		$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($profile['displayName'] . "はゲームに参加したよ！");
//     		$response2 = $bot->replyMessage($event->replyToken, $textMessageBuilder);
// 		  }
//
//     } else if ("text" == $event->message->type) {
//
//       if("group" == $event->source->type) {
//
//         //groupの話
//         $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("NU", "nu");
//         $action1 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("NO", "no");
//         $action2 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("NE", "ne");
//
//         $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("ひげ", "ひげげ", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0, $action1, $action2]);
//         $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("ひげがここにボタンで表示されてるよ", $button);
//
//
//         $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ぬ");
//         $response = $bot->pushMessage('R9b7dbfd03cbc9c2e4ab3624051c6b011', $button_message);
//       } else if("room" == $event->source->type) {
//
//     	}
//
//     } else {
//
//         $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ごめん、わかんなーい(*´ω｀*)");
//     }
//
// } elseif ("follow" == $event->type) {        //お友達追加時
//
//     $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("よろしくー");
//
// } elseif ("join" == $event->type) {           //グループに入ったときのイベント
//
//     $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('こんにちは よろしくー');
//
// } elseif ('beacon' == $event->type) {         //Beaconイベント
//
//     $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('Godanがいんしたお(・∀・) ');
//
// } else {
// }
//
// ////////////////////////////
// //データベースとの接続を終了する場所
// ////////////////////////////
//
// //$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
//
//syslog(LOG_EMERG, print_r($event->replyToken, true));
//
//syslog(LOG_EMERG, print_r($response, true));
