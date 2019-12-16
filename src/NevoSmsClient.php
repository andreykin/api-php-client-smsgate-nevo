<?php

namespace Nevo;

use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;

class NevoSmsClient
{
    /**
     * Клиент HTTP
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Фабрика запросов HTTP
     * @var RequestFactory
     */
    private $requestFactory;

    public function __construct(HttpClient $httpClient = null, RequestFactory $requestFactory = null)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * Выполняет произвольный запрос
     *
     * @param $uri
     * @param array $params
     * @return array
     * @throws \Http\Client\Exception
     */
    public function apiCall($uri, $params = [])
    {
        // удалить пустые значения
        $params = array_filter($params);
        $uri .= http_build_query($params);

        $request = $this->requestFactory->createRequest('GET', $uri);
        $response = $this->httpClient->sendRequest($request);

        $result = [];

        if ($response->getStatusCode() == 200) {
            $lines = preg_split('/\n|\r/', $response->getBody()->getContents(), null, PREG_SPLIT_NO_EMPTY);
            foreach ($lines as $line) {
                parse_str($line, $res);
                $result[] = $res;
            }
        }

        return $result;
    }

    /**
     * Отправляет СМС
     *
     * @param array|string $phones Номер телефона абонента или список номеров в виде массива
     * @param string|null $text Текст сообщения в кодировке UTF-8 (по умолчанию). Возможно
     * использование другой кодировки, которая при запросе указывается с помощью дополнительного параметра charset"
     * @param string|null $charset Кодировка текста SMS сообщения в запросе.
     * @param integer|null $rep Запросить подтверждение о доставки сообщения.
     * @return array
     * @throws \Http\Client\Exception
     */
    public function send($phones, $text = null, $charset = null, $rep = null)
    {
        // http://<ip_address>:<port>/rest.api?cmd=send&user=<пользователь>&pswd=<пароль>&phones=<телефоны>&text=<сообщение>

        // Примеры:
        // http://localhost:8080/rest.api?cmd=send&user=SMS&pswd=&phones=79001234567&text=Test+message
        // https://192.168.0.1:8080/rest.api?cmd=send&user=SMS&pswd=123&phones=79001234567;79001234568;
        // 79001234569&text=Тестовое%20сообщение&charset=windows-1251&rep=1

        // Ответ сервера:

        // Если все параметры запроса правильные и сообщение добавлено в очередь на отправку, то сервер
        // возвращает в http-заголовке ответа:
        // HTTP/1.1 200 OK

        // В теле ответа возвращаются номер абонента "phone", которому отправляется сообщение и
        // уникальный идентификатор данного сообщения "id", в следующем формате:
        // phone=79001234567&id=7ef98495-597c-4a99-8030-a58e7e9d1f13
        // phone=79001234568&id=dc8e4e83-82a5-4d0f-accc-c320a6759850
        // ...

        // Если в результате обработки запроса произошла ошибка (неправильные параметры, неизвестное имя
        // пользователя или неправильный пароль и т.д.), то сервер возвращает в http-заголовке ответа код из
        // группы 4XX. В теле ответа будет указана причина отклонения запроса (см. 4.4.3).

        if (is_array($phones)) {
            $phones = implode(";", $phones);
        }

        $params = [
            'cmd' => 'send',
            'phones' => $phones,
            'text' => $text,
            'charset' => $charset,
            'rep' => $rep
        ];

        return $this->apiCall('?', $params);
    }


    /**
     * Проверяет статус доставки
     *
     * @param $id идентификатор сообщения
     * @return array
     * @throws \Http\Client\Exception
     */
    public function msg($id)
    {
        // к компьютеру, на котором установлен SMSGATE:
        // http://<ip_address>:<port>/rest.api?cmd=msg&user=<пользователь>&pswd=<пароль>
        // &id=<идентификатор>

        // Ответ сервера:

        // Если все параметры запроса правильные, то сервер возвращает в http-заголовке ответа:
        // HTTP/1.1 200 OK

        // В теле ответа возвращаются параметры запрошенного исходящего сообщения в следующем
        // формате:
        // phone=79001234567&id=7ef98495-597c-4a99-8030-a58e7e9d1f13&status=3&err=0x00000000&err_msg=

        // Параметры ответа:
        // phone=<телефон> (обязательный) Телефон абонента исходящего сообщения.
        // id=<идентификатор> (обязательный) Уникальный идентификатор исходящего сообщения, назначенный сервером.
        // status=<состояние> (обязательный) Текущее состояние обработки исходящего сообщения. Возможны следующие значения:
        //      1 Сообщение находится в очереди на отправку.
        //      2 Сообщение передано на устройство. Результат отправки определяется параметром "err=<код_ошибки>".
        //      3 Сообщение отправлено, получено "подтверждение о доставке". Результат доставки определяется параметром "err=<код_ошибки>"
        // err=<код_ошибки> (обязательный) Результат выполнения операции отправки или получения "подтверждения о доставке" (см. status=<состояние>).
        // err_msg=<расшифровка> (обязательный) Расшифровка кода ошибки, если код ошибки не равен 0x00000000.

        $params = [
            'cmd' => 'msg',
            'id' => $id,
        ];

        return $this->apiCall('?', $params);
    }
}