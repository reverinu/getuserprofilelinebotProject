require_once('/vendor/autoload.php');

new LineMessage;

class LineMessage{

  private $token = 'w9SmZJ6zm2ln3DRx5gw6lxNgLi5Ayjx7ftGGpyEsKhM0sGStTEdwNeu7UdSe7H3Mj7ayGjRubK0xHN7onGWxEwL6K8lHyukidy2my3LQT02u+EsRK+Mqsvj4fe0OVCIEYzFMAC+VzUTNjINaAQiRbwdB04t89/1O/w1cDnyilFU=';
  private $secret = '3095c84a53d38913b6716fb770f3f326';
  private $profile_array = array(); //プロフィールを格納する配列 displayName:表示名 userId:ユーザ識別子 pictureUrl:画像URL statusMessage:ステータスメッセージ

  private $replyToken;
  private $userId;
  private $httpClient;
  private $bot;

  function __construct(){

    $json_string = file_get_contents('php://input');
    $jsonObj = json_decode($json_string);
    $this->userId = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
    $this->replyToken = $jsonObj->{"events"}[0]->{"replyToken"};

    $this->httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->token);
    $this->bot = new \LINE\LINEBot($this->httpClient, ['channelSecret' => $this->secret]);

    $this->get_profile();

  }

  function get_profile(){

    $response = $this->bot->getProfile($this->userId);

    if ($response->isSucceeded()) {

      $profile = $response->getJSONDecodedBody();
      $displayName = $profile['displayName'];
      $userId = $profile['userId'];
      $pictureUrl = $profile['pictureUrl'];
      $statusMessage = $profile['statusMessage'];
      $this->profile_array = array("displayName"=>$displayName,"userId"=>$userId,"pictureUrl"=>$pictureUrl,"statusMessage"=>$statusMessage);
      $this->reply_message();
    }
  }

  function reply_message(){

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($this->profile_array["displayName"]."さんこんにちは！");
    $response = $this->bot->replyMessage($this->replyToken, $textMessageBuilder);   
  }

}