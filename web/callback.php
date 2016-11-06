<?php


// データベース
// ・game_room
// id game_room_num(Int) game_room_id(String) game_mode(String) num_of_people(Int) num_of_roles(Int) num_of_votes(Int)
// ・user
// id user_id(String) user_name(String) game_room_num(Int) role(String) voted_num(Int) is_roling(Bool) is_voting(Bool)



require('../vendor/autoload.php');
require('../web/CarouselModel.php');


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
  global $bot, $event, $link, $gameMode, $gameRoomId, $PEOPLE3, $game_room_num, $GAMEMODE_NOON;
  if ("@help" == $message_text) {
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("[ヘルプ]\n@gameをグループチャットでコメントすることでゲーム開始前待機時間に移行します。そしてグループチャットがゲームルームとして認識され、ルームナンバーが発行されます。\nルームナンバーをそのままコピーして個人チャットで私にコメントすれば参加者として認識されます。\nゲーム開始前待機時間では、@memberをコメントすることで現在の参加者を見ることが出来ます。参加者が揃ったら@startしてください。ゲームが始まり夜時間へと移行します。\n夜時間では個人チャットに送られる私のコメントに従って行動してください。村人、狂人、人狼、吊人も了解ボタンを押してください。全員の行動が終われば自動的に議論時間へと移行します。\n議論時間の初めに個人チャットに投票ボタンをコメントします。ゲームルームで議論をし、投票する相手を決め投票してください。全員の投票が終われば自動的に投票結果、勝敗が開示され、ゲームが終了します。\n最後に@endをゲームルームでコメントしてください。\n\n※ゲーム中に私をゲームルームから削除するとゲームがリセットされます");
    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
  } else if ("@rule" == $message_text) {
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ルール説明\nhttps://www.google.co.jp/search?q=%E3%83%AF%E3%83%B3%E3%83%8A%E3%82%A4%E3%83%88%E4%BA%BA%E7%8B%BC&ie=&oe=#q=%E3%83%AF%E3%83%B3%E3%83%8A%E3%82%A4%E3%83%88%E4%BA%BA%E7%8B%BC+%E3%81%A8%E3%81%AF");
    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
  // } else if ("@debug" == $message_text) {//デバッグ用
  //   $result = mysqli_query($link, "select is_voting from user where game_room_num = '$game_room_num'");
  //   while($row = mysqli_fetch_row($result)){
  //     $text .= "区切り" . $row[0] . "\n";
  //   }
  //   $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
  //   $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
  //
  // } else if ("@debug2" == $message_text) {
  //   $message = CreateUranaiButton($event->source->userId);
  //   $response = $bot->replyMessage($event->replyToken, $message);
  //
  } else if ("@del" == $message_text) {// デバッグ用
    $result = mysqli_query($link,"TRUNCATE TABLE game_room");
    $result = mysqli_query($link,"TRUNCATE TABLE user");
    $result = mysqli_query($link,"TRUNCATE TABLE user_temp");
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
      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ルームナンバーを発行したよ！\nルームナンバーは「" . $roomNumber . "」だよ！\n個人チャットでこの数字をコメントすればゲームに参加できるよ！\n「@member」で現在参加者表示");
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
      $result = mysqli_query($link, "select num_of_people from game_room where game_room_id = '$gameRoomId'");
      $row = mysqli_fetch_row($result);
      if(3 <= $row[0] && 5 >= $row[0]){
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
          $result = mysqli_query($link, "insert into user (user_id, user_name, game_room_num, role, voted_num, is_roling, is_voting) values ('toubosya1', '逃亡者', '$room_num', '無し', 0, 'false', 'false');");
          $result = mysqli_query($link, "insert into user (user_id, user_name, game_room_num, role, voted_num, is_roling, is_voting) values ('toubosya2', '逃亡者', '$room_num', '無し', 0, 'false', 'false');");
        }
        Cast();
      } else {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("わたしは3~5人しか対応していません。\nゲームを始められません");
        $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
      }
    }
  }
}
//NightのDoAction,メッセージを見てアクションする
function DoActionNight($message_text){
  global $bot, $event, $link, $GAMEMODE_NOON, $game_room_num;
  //messageでif分けする（役職行動）
  if("user" == $event->source->type) {
    $userId = $event->source->userId;
    $userId = mysqli_real_escape_string($link, $userId);

    $result = mysqli_query($link, "select is_roling from user where user_id = '$userId'");
    $row = mysqli_fetch_row($result);
    if(0 == $row[0]){
      if("@ok" == $message_text){

        $result = mysqli_query($link, "select role from user where user_id = '$userId';");
        $row = mysqli_fetch_row($result);
        if("占い師" == $row[0]){
          $button_message = CreateUranaiButton($userId);
          $response = $bot->pushMessage($userId, $button_message);
        } else if ("怪盗" == $row[0]){
          $button_message = CreateKaitoButton($userId);
          $response = $bot->pushMessage($userId, $button_message);
        } else {
          $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($row[0] . "行動完了。しばらくお待ちください。");
          $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
          $result = mysqli_query($link, "update user set is_roling = 1 where user_id = '$userId'");
          $result = mysqli_query($link, "update game_room set num_of_roles = num_of_roles+1 where game_room_num = '$game_room_num'");
        }
      } else {
        $result = mysqli_query($link, "select user_name from user where game_room_num = '$game_room_num'");

        $uranai = "";
        $kaito = "";
        $isExist = false;
        while($row = mysqli_fetch_row($result)){
          if("占い@" . $row[0] == $message_text){
            $uranai = $row[0];
            $isExist = true;
          }
          if("怪盗@" . $row[0] == $message_text){
            $kaito = $row[0];
            $isExist = true;
          }
        }
        if($isExist){
          $result = mysqli_query($link, "select user_id from user_temp where role = '占い師'");
          $row = mysqli_fetch_row($result);
          if("占い@" . $uranai == $message_text && $userId == $row[0]){
            $uranai = mysqli_real_escape_string($link, $uranai);
            $result = mysqli_query($link, "select role from user_temp where user_name = '$uranai'");
            $text = "占い結果\n";
            while($row = mysqli_fetch_row($result)){
              $text .= $uranai . "の役職は" . $row[0] . "\n";
            }
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
            $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

            $result = mysqli_query($link, "update user set is_roling = 1 where user_id = '$userId'");
            $result = mysqli_query($link, "update game_room set num_of_roles = num_of_roles+1 where game_room_num = '$game_room_num'");
          }

          $result = mysqli_query($link, "select user_id from user_temp where role = '怪盗'");
          $row = mysqli_fetch_row($result);
          if("怪盗@" . $kaito == $message_text && $userId == $row[0]){
            $result = mysqli_query($link, "select role from user where user_id = '$userId'");
            $row = mysqli_fetch_row($result);
            $myself = $row[0];
            $myself = mysqli_real_escape_string($link, $myself);
            $kaito = mysqli_real_escape_string($link, $kaito);
            $result = mysqli_query($link, "select role from user where user_name = '$kaito'");
            $row = mysqli_fetch_row($result);
            $yourself = $row[0];
            $yourself = mysqli_real_escape_string($link, $yourself);
            $result = mysqli_query($link, "update user set role = '$yourself' where user_id = '$userId'");
            $result = mysqli_query($link, "update user set role = '$myself' where user_name = '$kaito'");

            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($kaito . "と入れ替わったよ\n" . "あなたは" . $yourself . "になりました");
            $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

            $result = mysqli_query($link, "update user set is_roling = 1 where user_id = '$userId'");
            $result = mysqli_query($link, "update game_room set num_of_roles = num_of_roles+1 where game_room_num = '$game_room_num'");

          }
        }
      }
      $result = mysqli_query($link, "select num_of_people from game_room where game_room_num = '$game_room_num'");
      $row = mysqli_fetch_row($result);
      $num_of_people = $row[0];
      $result = mysqli_query($link, "select num_of_roles from game_room where game_room_num = '$game_room_num'");
      $row = mysqli_fetch_row($result);
      $num_of_roles = $row[0];

      if($num_of_people == $num_of_roles){
        $GAMEMODE_NOON = mysqli_real_escape_string($link, $GAMEMODE_NOON);
        $result = mysqli_query($link, "update game_room set game_mode = '$GAMEMODE_NOON' where game_room_num = '$game_room_num'");

        $result = mysqli_query($link, "select game_room_id from game_room where game_room_num = '$game_room_num'");
        $row = mysqli_fetch_row($result);
        $game_room_id = $row[0];
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("[議論開始]\n朝になりました\n\n\nこの中に狼が潜んでいるかもしれません。\n議論を始めてください。");
        $response = $bot->pushMessage($game_room_id, $textMessageBuilder);

        // カルーセル
        $result = mysqli_query($link, "select game_room_num from user order by id desc limit 1");
        $row = mysqli_fetch_row($result);
        error_log(print_r($row,true));
        CarouselModel::sendCarousel($row[0],$link,$bot);
      }
    }

  }
}
//NoonのDoAction,メッセージを見てアクションする
function DoActionNoon($message_text){
  global $bot, $event, $link, $game_room_num;
  //messageでif分けする(投票)
  if("user" == $event->source->type){
    $userId = $event->source->userId;
    $userId = mysqli_real_escape_string($link, $userId);
    $result = mysqli_query($link, "select is_voting from user where user_id = '$userId'");
    $row = mysqli_fetch_row($result);
    $is_voting = $row[0];
    if(0 == $is_voting){
      $result = mysqli_query($link, "select user_name from user where user_id != '$userId' and user_name != '逃亡者'");
      while($row = mysqli_fetch_row($result)){
        if("投票@" . $row[0] == $message_text){
          $user_name = $row[0];
        }
      }
      $user_name = mysqli_real_escape_string($link, $user_name);
      $result = mysqli_query($link, "update user set is_voting = 1 where user_id = '$userId'");
      $result = mysqli_query($link, "update user set voted_num = voted_num+1 where user_name = '$user_name'");
      $result = mysqli_query($link, "update game_room set num_of_votes = num_of_votes+1 where game_room_num = '$game_room_num'");

      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($user_name . "に投票しました。");
      $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    }

    $result = mysqli_query($link, "select num_of_people from game_room where game_room_num = '$game_room_num'");
    $row = mysqli_fetch_row($result);
    $num_of_people = $row[0];
    $result = mysqli_query($link, "select num_of_votes from game_room where game_room_num = '$game_room_num'");
    $row = mysqli_fetch_row($result);
    $num_of_votes = $row[0];

    if($num_of_people == $num_of_votes){
      $GAMEMODE_END = mysqli_real_escape_string($link, $GAMEMODE_END);
      $result = mysqli_query($link, "update game_room set game_mode = '$GAMEMODE_END' where game_room_num = '$game_room_num'");


      $result = mysqli_query($link, "select user_name , role , voted_num from user where game_room_num = '$game_room_num' and user_name != '逃亡者'");

      $text = "投票結果開示\n";
      while($row = mysqli_fetch_row($result)){
        $text .= $row[0] . "は" . $row[1] . "(" . $row[2] . "票)\n";
      }
      $result = mysqli_query($link, "select voted_num from user where game_room_num = '$game_room_num' order by voted_num desc");
      $row = mysqli_fetch_row($result);
      $max_voted = $row[0];
      $max_voted = mysqli_real_escape_string($link, $max_voted);
      $result = mysqli_query($link, "select user_name, role from user where game_room_num = '$game_room_num' and voted_num = '$max_voted'");

      $text .= "\n\n吊られた人\n";
      $i = 0;
      while($row = mysqli_fetch_row($result)){
        $text .= $row[0] . "\n";
        $role_temp[$i] = $row[1];
        $i++;
      }
      $issue = "狼陣営";
      for($k = 0; $k < $i; $k++){
        if("人狼" == $role_temp[$k]){
          $issue = "村陣営";
        }
      }
      $text .= $issue . "の勝利！\n\n「@end」をコメントしてね！";

      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);


      $result = mysqli_query($link, "select game_room_id from game_room where game_room_num = '$game_room_num'");
      $row = mysqli_fetch_row($result);
      $game_room_id = $row[0];
      $response = $bot->pushMessage($game_room_id, $textMessageBuilder);

    }
  }
}
//EndのDoAction,メッセージを見てアクションする
function DoActionEnd($message_text){
  global $bot, $event, $link, $gameRoomId;
  if("group" == $event->source->type || "room" == $event->source->type){
    $result = mysqli_query($link, "select game_room_num from game_room where game_room_id = '$gameRoomId'");
    $row = mysqli_fetch_row($result);
    $game_room_num = $row[0];
    // if ("@newgame" == $message_text) {
    //   $result = mysqli_query($link, "delete from game_room where game_room_num = '$game_room_num'");
    //   $result = mysqli_query($link, "delete from user where game_room_num = '$game_room_num'");
    //   $result = mysqli_query($link, "delete from user_temp where game_room_num = '$game_room_num'");
    //   // ルームナンバー発行、テーブルにレコードを生成する、gameModeを移行する
    //   while(true){
    //     $gameRoomNum = mt_rand(100,999);
    //     $gameRoomNum = mysqli_real_escape_string($link, $gameRoomNum);
    //     $rnj = mysqli_query($link, "select * from game_room where game_room_num = '$gameRoomNum'");
    //     $row = mysqli_fetch_row($rnj);
    //     if(null == $row){
    //       break;
    //     }
    //   }
    //   $roomNumber = mysqli_real_escape_string($link, $gameRoomNum);
    //   if ("group" == $event->source->type){
    //     $gameRoomId = $event->source->groupId;
    //   } else if ("room" == $event->source->type) {
    //     $gameRoomId = $event->source->roomId;
    //   }
    //   $gameRoomId = mysqli_real_escape_string($link, $gameRoomId);
    //   $result = mysqli_query($link, "insert into game_room (game_room_num, game_room_id, game_mode, num_of_people, num_of_roles, num_of_votes) values ('$roomNumber', '$gameRoomId', 'WAITING', 0, 0, 0);");
    //   $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ルームナンバーを発行したよ！\nルームナンバーは「" . $roomNumber . "」だよ！\n個人チャットでこの数字をコメントすればゲームに参加できるよ！");
    //   $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    // } else
    if ("@end" == $message_text) {
      // $result = mysqli_query($link, "delete from game_room where game_room_num = '$game_room_num'");
      // $result = mysqli_query($link, "delete from user where game_room_num = '$game_room_num'");
      // $result = mysqli_query($link, "delete from user_temp where game_room_num = '$game_room_num'");
      $result = mysqli_query($link,"TRUNCATE TABLE game_room");
      $result = mysqli_query($link,"TRUNCATE TABLE user");
      $result = mysqli_query($link,"TRUNCATE TABLE user_temp");

      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("お疲れ様！\n飽きたら退出させてね！");
      $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
    }
  }
}
//部屋に入ったときに諸々発言
function DoActionJoin(){
  global $bot, $event;
  $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("僕はワンナイト人狼Botだよ！(３～５人対応)\n\nワンナイト人狼のルールを知りたいときは「@rule」\nこのbotの使い方を知りたいときは「@help」\nゲームを始めたいときは「@game」\n\nってコメントしてね！");
  $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
}
//部屋から退出させられるときの処理
function DoActionLeave(){
  global $bot, $event, $link;
  $result = mysqli_query($link,"TRUNCATE TABLE game_room");
  $result = mysqli_query($link,"TRUNCATE TABLE user");
  $result = mysqli_query($link,"TRUNCATE TABLE user_temp");
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
  global $bot, $event, $link, $PEOPLE3, $PEOPLE4, $PEOPLE5, $gameRoomId;
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
        $role[$i] = $PEOPLE3[$i];
        $user_id[$i] = $row[1];
        $i++;
      }
      $result = mysqli_query($link, "update user set role = '$role[0]' where user_id = '$user_id[0]'");
      $result = mysqli_query($link, "update user set role = '$role[1]' where user_id = '$user_id[1]'");
      $result = mysqli_query($link, "update user set role = '$role[2]' where user_id = '$user_id[2]'");
      $result = mysqli_query($link, "update user set role = '$role[3]' where user_id = '$user_id[3]'");
      $result = mysqli_query($link, "update user set role = '$role[4]' where user_id = '$user_id[4]'");

      $result = mysqli_query($link, "insert into user_temp select * from user");

      //これがボタンに置き換わる
      $button_message = CreateButtons($PEOPLE3[0]);
      $response = $bot->pushMessage($user_id[0], $button_message);
      $button_message = CreateButtons($PEOPLE3[1]);
      $response = $bot->pushMessage($user_id[1], $button_message);
      $button_message = CreateButtons($PEOPLE3[2]);
      $response = $bot->pushMessage($user_id[2], $button_message);
      //ここまで



    } else if(4 == $num_of_people){
      shuffle($PEOPLE4);
      $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num'");
      $i = 0;
      while($row = mysqli_fetch_row($result)){
        $role[$i] = $PEOPLE3[$i];
        $user_id[$i] = $row[1];
        $i++;
      }
      $result = mysqli_query($link, "update user set role = '$role[0]' where user_id = '$user_id[0]'");
      $result = mysqli_query($link, "update user set role = '$role[1]' where user_id = '$user_id[1]'");
      $result = mysqli_query($link, "update user set role = '$role[2]' where user_id = '$user_id[2]'");
      $result = mysqli_query($link, "update user set role = '$role[3]' where user_id = '$user_id[3]'");
      $result = mysqli_query($link, "update user set role = '$role[4]' where user_id = '$user_id[4]'");
      $result = mysqli_query($link, "update user set role = '$role[5]' where user_id = '$user_id[5]'");

      $result = mysqli_query($link, "insert into user_temp select * from user");

      //これがボタンに置き換わる
      $button_message = CreateButtons($PEOPLE3[0]);
      $response = $bot->pushMessage($user_id[0], $button_message);
      $button_message = CreateButtons($PEOPLE3[1]);
      $response = $bot->pushMessage($user_id[1], $button_message);
      $button_message = CreateButtons($PEOPLE3[2]);
      $response = $bot->pushMessage($user_id[2], $button_message);
      $button_message = CreateButtons($PEOPLE3[3]);
      $response = $bot->pushMessage($user_id[3], $button_message);
      //ここまで
    } else if(5 == $num_of_people){
      shuffle($PEOPLE5);
      $result = mysqli_query($link, "select * from user where game_room_num = '$game_room_num'");
      $i = 0;
      while($row = mysqli_fetch_row($result)){
        $role[$i] = $PEOPLE3[$i];
        $user_id[$i] = $row[1];
        $i++;
      }
      $result = mysqli_query($link, "update user set role = '$role[0]' where user_id = '$user_id[0]'");
      $result = mysqli_query($link, "update user set role = '$role[1]' where user_id = '$user_id[1]'");
      $result = mysqli_query($link, "update user set role = '$role[2]' where user_id = '$user_id[2]'");
      $result = mysqli_query($link, "update user set role = '$role[3]' where user_id = '$user_id[3]'");
      $result = mysqli_query($link, "update user set role = '$role[4]' where user_id = '$user_id[4]'");
      $result = mysqli_query($link, "update user set role = '$role[5]' where user_id = '$user_id[5]'");
      $result = mysqli_query($link, "update user set role = '$role[6]' where user_id = '$user_id[6]'");

      $result = mysqli_query($link, "insert into user_temp select * from user");

      //これがボタンに置き換わる
      $button_message = CreateButtons($PEOPLE3[0]);
      $response = $bot->pushMessage($user_id[0], $button_message);
      $button_message = CreateButtons($PEOPLE3[1]);
      $response = $bot->pushMessage($user_id[1], $button_message);
      $button_message = CreateButtons($PEOPLE3[2]);
      $response = $bot->pushMessage($user_id[2], $button_message);
      $button_message = CreateButtons($PEOPLE3[3]);
      $response = $bot->pushMessage($user_id[3], $button_message);
      $button_message = CreateButtons($PEOPLE3[4]);
      $response = $bot->pushMessage($user_id[4], $button_message);
      //ここまで
    }
  }
}
// 役職によってButtonの形状が異なる
function CreateButtons($role){
  global $link, $PEOPLE3, $PEOPLE4, $PEOPLE5, $gameRoomId;
  if('村人' == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "村人", "https://" . $_SERVER['SERVER_NAME'] . "/murabito.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は村人\n(「@ok」とコメントしてください)", $button);
  } else if('占い師' == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "占い師", "https://" . $_SERVER['SERVER_NAME'] . "/uranai.jpg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は占い師\n(「@ok」とコメントしてください)", $button);
  } else if('怪盗' == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "怪盗", "https://" . $_SERVER['SERVER_NAME'] . "/kaito.jpg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は怪盗\n(「@ok」とコメントしてください)", $button);
  } else if('人狼' == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "人狼", "https://" . $_SERVER['SERVER_NAME'] . "/jinro.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は人狼\n(「@ok」とコメントしてください)", $button);
  } else if('狂人' == $role){
    $action0 = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("了解", "@ok");
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("あなたの役職", "狂人", "https://" . $_SERVER['SERVER_NAME'] . "/kyojin.jpeg", [$action0]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("あなたの役職は狂人\n(「@ok」とコメントしてください)", $button);
  }
}
function CreateUranaiButton($userId){
  global $bot, $event, $link, $gameRoomId;
  $user_id = mysqli_real_escape_string($link, $userId);
  $result = mysqli_query($link, "select game_room_num from user where user_id = '$user_id'");
  $row = mysqli_fetch_row($result);
  $game_room_num = $row[0];
  $game_room_num = mysqli_real_escape_string($link, $game_room_num);
  $result = mysqli_query($link, "select user_name from user where user_id != '$user_id' and game_room_num = '$game_room_num'");
  $i = 0;
  while($row = mysqli_fetch_row($result)){
    $user_name = $row[0];
    $user_names[$i] = $user_name;
    $action[$i] = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder($user_name, "占い@" . $user_name);
    $i++;
  }
  if(4 == $i){
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("占い先指定", "誰を占う？", "https://" . $_SERVER['SERVER_NAME'] . "/uranai.jpg", [$action[0], $action[1], $action[2]]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰を占う？\n(占い@" . $user_names[0] . "/占い@" . $user_names[1] . "/占い@" . $user_names[2] . ")", $button);
  } else if(5 == $i){
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("占い先指定", "誰を占う？", "https://" . $_SERVER['SERVER_NAME'] . "/uranai.jpg", [$action[0], $action[1], $action[2], $action[3]]);
    return $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰を占う？\n(占い@" . $user_names[0] . "/占い@" . $user_names[1] . "/占い@" . $user_names[2] . "/占い@" . $user_names[3] . ")", $button);
  } else if(6 == $i){
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("占い先指定", "誰を占う？", "https://" . $_SERVER['SERVER_NAME'] . "/uranai.jpg", [$action[0], $action[1], $action[2], $action[3]]);
    $button2 = new  \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("占い先指定", "誰を占う？", "https://" . $_SERVER['SERVER_NAME'] . "/uranai.jpg", [$action[4]]);
    $message = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
    $button_message = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰を占う？\n(占い@" . $user_names[0] . "/占い@" . $user_names[1] . "/占い@" . $user_names[2] . "/占い@" . $user_names[3] . ")", $button);
    $button_message2 = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰を占う？\n(占い@" . $user_names[4] . ")", $button2);
    $message->add($button_message);
    $message->add($button_message2);
    return $message;
  }

}

function CreateKaitoButton($userId){
  global $bot, $event, $link, $gameRoomId;
  $user_id = mysqli_real_escape_string($link, $userId);
  $result = mysqli_query($link, "select game_room_num from user where user_id = '$user_id'");
  $row = mysqli_fetch_row($result);
  $game_room_num = $row[0];
  $game_room_num = mysqli_real_escape_string($link, $game_room_num);
  $result = mysqli_query($link, "select user_name from user where user_id != '$user_id' and game_room_num = '$game_room_num'");
  $i = 0;
  while($row = mysqli_fetch_row($result)){
    $user_name = $row[0];
    $user_names[$i] = $user_name;
    $action[$i] = new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder($user_name, "怪盗@" . $user_name);
    $i++;
  }
  if(4 == $i){
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("入れ替わり先指定", "誰と入れ替わる？", "https://" . $_SERVER['SERVER_NAME'] . "/kaito.jpg", [$action[0], $action[1]]);
    return $button_message2 = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰と入れ替わる？\n(怪盗@" . $user_names[0] . "/怪盗@" . $user_names[1] . ")", $button);
  } else if (5 == $i) {
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("入れ替わり先指定", "誰と入れ替わる？", "https://" . $_SERVER['SERVER_NAME'] . "/kaito.jpg", [$action[0], $action[1], $action[2]]);
    return $button_message2 = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰と入れ替わる？\n(怪盗@" . $user_names[0] . "/怪盗@" . $user_names[1] . "/怪盗@" . $user_names[2] . ")", $button);
  } else if (6 == $i) {
    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("入れ替わり先指定", "誰と入れ替わる？", "https://" . $_SERVER['SERVER_NAME'] . "/kaito.jpg", [$action[0], $action[1], $action[2], $action[3]]);
    return $button_message2 = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("誰と入れ替わる？\n(怪盗@" . $user_names[0] . "/怪盗@" . $user_names[1] . "/怪盗@" . $user_names[2] . "/怪盗@" . $user_names[3] . ")", $button);
  }

}


////////////////////////////
//データベースとの接続を終了する場所
////////////////////////////
mysqli_free_result($result);
mysqli_close($link);
