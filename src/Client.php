<?php

namespace Iliuxu\Acm;

use Iliuxu\Acm\Exception\AcmException;
use Iliuxu\Acm\Traits\HttpRequest;

class Client
{
    use HttpRequest;

    protected $version = '0.0.1';

    protected $accessKey;

    protected $secretKey;

    protected $endpoint;

    protected $port;

    protected $namespace;

    protected $appName;

    public $server = [];

    const DEFAULT_PORT = '8080';

    const ACM_HOST = 'http://host:port/diamond-server/';

    protected $api = [
        'getServer' => self::ACM_HOST . 'diamond',
        'getConfig' => self::ACM_HOST . 'config.co',
        'removeConfig' => self::ACM_HOST . 'datum.do?method=deleteAllDatums',
        'publishConfig' => self::ACM_HOST . 'basestone.do?method=syncUpdateAll',
    ];

    public function __construct($endpoint, $port)
    {
        $this->endpoint = $endpoint;
        $this->port = $port;
    }

    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;
    }

    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }
    public function setAppName($appName)
    {
        $this->appName = $appName;
    }

    public function getServerStr()
    {
        $serverHost = $this->replaceHost($this->endpoint, $this->port, $this->api['getServer']);
        return $this->get($serverHost);
    }

    private function replaceHost($endpoint, $port, $host)
    {
        return str_replace(['host','port'], [$endpoint, $port], $host);
    }

    public function refreshServer()
    {
        $this->server  = [];
        $serverStr = $this->getServerStr();
        if (is_string($serverStr)) {
            $server = explode("\n", $serverStr);
            $server = array_filter($server);
            foreach ($server as $value) {
                $value = trim($value);
                $singleServerArray = explode(':', $value);
                $singleServer = null;
                if (count($singleServerArray) == 1) {
                    $singleServer = [$singleServerArray[0], self::DEFAULT_PORT];
                } else {
                    $singleServer = [$singleServerArray[0], $singleServerArray[1]];
                }
                $this->server[$singleServer[0]] = $singleServer;
            }
        }
    }

    private function selectServer($host)
    {
        $singleServer = $this->server[array_rand($this->server)];
        return $this->replaceHost($singleServer[0], $singleServer[1], $host);
    }

    public function getConfig($dataId, $group)
    {
        $this->checkAccessKeyAndSecretKey();
        Util::checkDataId($dataId);
        $group = Util::checkGroup($group);
        $acmHost = $this->selectServer($this->api['getConfig']);
        $query = [
            'dataId'=>$dataId,
            'group'=>$group,
            'tenant'=>$this->namespace,
        ];
        return $this->get($acmHost, $query, $this->getCommonHeaders($group));
    }

    public function removeConfig($dataId, $group)
    {
        $this->checkAccessKeyAndSecretKey();
        Util::checkDataId($dataId);
        $group = Util::checkGroup($group);
        $acmHost = $this->selectServer($this->api['removeConfig']);
        $params = [
            'dataId'=>$dataId,
            'group'=>$group,
            'tenant'=>$this->namespace,
        ];
        return $this->post($acmHost, $params, $this->getCommonHeaders($group));
    }

    public function publishConfig($dataId, $group, $content)
    {
        $this->checkAccessKeyAndSecretKey();
        Util::checkDataId($dataId);
        $group = Util::checkGroup($group);
        $acmHost = $this->selectServer($this->api['publishConfig']);
        $params = [
            'dataId'=>$dataId,
            'group'=>$group,
            'tenant'=>$this->namespace,
            'content'=>$content
        ];
        if ($this->appName) {
            $params['appName'] = $this->appName;
        }
        return $this->post($acmHost, $params, $this->getCommonHeaders($group));
    }

    public function getCommonHeaders($group)
    {
        $headers = [];
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
        $headers['Spas-AccessKey'] = $this->accessKey;
        $ts = intval(microtime(true) * 1000);
        $headers['timeStamp'] = $ts;
        $signStr = $this->namespace . '+';
        if (is_string($group)) {
            $signStr .= $group . '+';
        }
        $signStr .= $ts;
        $headers['Spas-Signature'] = base64_encode(hash_hmac('sha1', $signStr, $this->secretKey,true));
        return $headers;
    }
    private function checkAccessKey()
    {
        if (!is_string($this->accessKey)) {
            throw new AcmException('access key error');
        }
    }

    private function checkSecretKey()
    {
        if (!is_string($this->secretKey)) {
            throw new AcmException('secret key error');
        }
    }

    private function checkAccessKeyAndSecretKey()
    {
        $this->checkAccessKey();
        $this->checkSecretKey();
    }
}
