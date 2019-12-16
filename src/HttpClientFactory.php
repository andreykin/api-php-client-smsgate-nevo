<?php

namespace Nevo;

use Http\Client\HttpClient;
use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\Authentication\QueryParam;

class HttpClientFactory
{
    /**
     * Build the HTTP client to talk with the API.
     *
     * @param null $host
     * @param string $user Username for the application on the API
     * @param string $pswd Password for the application on the API
     * @param Plugin[] $plugins List of additional plugins to use
     * @param HttpClient $client Base HTTP client
     *
     * usage $myApiClient = new Nevo\NevoClient(Nevo\HttpClientFactory::create('https://api.example.org', 'john', 's3cr3t'));
     *
     * @return HttpClient
     */
    public static function create($host = null, $user, $pswd, array $plugins = [], HttpClient $client = null)
    {
        if (!$client) {
            $client = HttpClientDiscovery::find();
        }
        $plugins[] = new NevoErrorPlugin();
        $plugins[] = new AuthenticationPlugin(
            new QueryParam(
                ['user' => $user, 'pswd' => $pswd]
            )
        );

        if ($host) {
            $plugins[] = new Plugin\BaseUriPlugin(UriFactoryDiscovery::find()->createUri($host.'/rest.api'));
        }

        return new PluginClient($client, $plugins);
    }
}
