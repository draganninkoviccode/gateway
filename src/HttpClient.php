<?php

namespace G4\Gateway;

use G4\Gateway\Options;
use G4\Gateway\Params;
use G4\Gateway\HttpMethod;
use G4\Gateway\Profiler\Ticker;

class HttpClient
{

    /**
     * @var \Zend\Http\Client
     */
    private $client;

    /**
     * @var Options
     */
    private $options;


    private $profiler;


    public function __construct(Options $options)
    {
        $this->options  = $options;
        $this->profiler = Ticker::getInstance();
    }

    public function getClient()
    {
        if (! $this->client instanceof \Zend\Http\Client) {

            $this->client = new \Zend\Http\Client();
            $this->client
                ->setAdapter('\Zend\Http\Client\Adapter\Curl')
                ->setEncType(\Zend\Http\Client::ENC_URLENCODED)
                ->setOptions([
                    'timeout' => $this->options->getTimeout(),
                    'curloptions' => [
                        CURLOPT_SSL_VERIFYPEER => $this->options->getSslVerifyPeer()
                    ],
                ]);

            $this->client->getRequest()->getHeaders()->addHeaders($this->options->getHeaders());
        }

        return $this->client;
    }

    public function send(Url $url, HttpMethod $method)
    {
        $uniqueId = $this->profiler->start();

        $method->isPost()
            ? $this->getClient()->setParameterPost($url->getParams()->toArray())
            : $this->getClient()->setParameterGet($url->getParams()->toArray());

        $this->getClient()
            ->setUri($url->getUri())
            ->setMethod((string) $method)
            ->send();

        $this->profiler
            ->setUrl($uniqueId, $url->getUri())
            ->setMethod($uniqueId, (string) $method)
            ->setParams($uniqueId, $url->getParams()->toArray())
            ->end($uniqueId);

        return new Response($this->getClient()->getResponse(), $url);
    }
}