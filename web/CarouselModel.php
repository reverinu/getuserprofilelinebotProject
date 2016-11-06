<?php

/**
 * Created by IntelliJ IDEA.
 * User: ashi_psn
 * Date: 2016/11/06
 * Time: 2:48
 */

use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;

class CarouselModel
{

    static function sendCarousel($game_room_id, $link, $bot)
    {
        $userlist = self::getUserList($game_room_id, $link);
        self::sendRoomUsers($userlist, $bot);
        return $userlist;
    }


    static function getUserList($game_room_id, $link)
    {
        //$result = mysqli_query($link, "select user_id from user where game_room_num = '$game_room_id'");
        $userdata = array();
        $result1 = mysqli_query($link, "select user_id,user_name from user where game_room_num = '$game_room_id' and user_name != '逃亡者'");
        while ($userlistrow = mysqli_fetch_row($result1)) {
            $data = array($userlistrow[0] => $userlistrow[1]);
            $userdata[] = $data;
        }
        return $userdata;
    }


    static function sendRoomUsers($userList, $bot)
    {


        for ($i = 0; $i < count($userList); $i++) {

            if((key($userList[$i]) == "toubosya1") || (key($userList[$i]) == "toubosya2")){
                continue;
            }
            $senderlist = array();
            $CarouselColumnTemplates = array();

            foreach ($userList as $userdata) {
                foreach ($userdata as $userid => $username) {
                    if($userid == key($userList[$i])){
                        continue;
                    }

                    //プロフィール画像取得テスト
                    error_log($bot->getProfile($userid)->getJSONDecodedBody()["pictureUrl"]);
                    $imgurl = $bot->getProfile($userid)->getJSONDecodedBody()["pictureUrl"];
//$imgurl."jpeg"
                    $senderlist[] = $userid;
                    $col = new CarouselColumnTemplateBuilder('投票先指定', $username,"https://" . $_SERVER['SERVER_NAME'] . "/vote.jpg", [
                        new MessageTemplateActionBuilder('投票', '投票@' . $username)
                    ]);
                    $CarouselColumnTemplates[] = $col;
                }
            }

            $carouselTemplateBuilder = new CarouselTemplateBuilder($CarouselColumnTemplates);
            //CarouselColumnTemplateBuilderの配列
            $templateMessage = new TemplateMessageBuilder('投票してください(投票@投票したい人のLINEネーム)', $carouselTemplateBuilder);
            $response = $bot->pushMessage(key($userList[$i]), $templateMessage);
            error_log("response : " . print_r($response,true) );

        }
    }

}
