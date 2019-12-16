{
    "repositories": [
        {
            "type": "git",
            "url": "/Users/andrey/PhpstormProjects/smsgate/.git"
        }
    ],
    "require": {
        "php-http/guzzle6-adapter": "^2.0",
        "andreykin/smsgate-nevo-php-client": "dev-master"
    }
}

require "vendor/autoload.php";

use Nevo\NevoSmsClient;
use Nevo\HttpClientFactory;

$apiClient = new NevoSmsClient(HttpClientFactory::create('SERVERURL', 'USER', 'PASS'));

// отправка сообщения
$result = $apiClient->send(['PHONE1','PHONE2'], 'Тест сообщения');
var_dump($result);

// статус сообщения для одного адресата
$result = $apiClient->msg("MESSAGEID");
var_dump($result);