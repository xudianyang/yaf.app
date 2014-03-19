<?php
/**
 * Yaf.app Framework
 *
 * @author xudianyang<120343758@qq.com>
 * @copyright Copyright (c) 2014 (http://www.phpboy.net)
 */

namespace Protocol\Http;

class Client
{
    /**
     * @var array 默认设置
     */
    protected static $defaultOptions = array(
        'userAgent' => 'Top Robot 1.0',     // 请求时的 user agent
        'connectionTimeout' => 10,          // 发起连接前等待超时时间
        'timeout' => 30,                    // 请求执行超时时间
        'sslVerifypeer' => false            // 是否从服务端进行验证
    );

    protected $options;

    /**
     * @var int HTTP 状态码
     */
    protected $httpCode;

    /**
     * @var 响应信息
     */
    protected $httpInfo = array();

    /**
     * @var 错误
     */
    protected $error;

    /**
     * @var 错误代码
     */
    protected $errno;

    public function __construct(array $options = array())
    {
        $this->options = array_merge(self::$defaultOptions, $options);
    }

    /**
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @return mixed
     */
    public function getErrno()
    {
        return $this->errno;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getHttpInfo()
    {
        return $this->httpInfo;
    }

    public function get($url, array $params = array(), array $extra_headers = array())
    {
        return $this->request($url, $params, 'GET', array(), $extra_headers);
    }

    public function post($url, array $params = array(), array $multipart = array(), array $extra_headers = array())
    {
        return $this->request($url, $params, 'POST', $multipart, $extra_headers);
    }

    protected function request($url, array $params = array(), $method = 'GET', array $multipart = array(), array $extra_headers = array())
    {
        $method = strtoupper($method);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, $this->options['userAgent']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->options['connectionTimeout']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->options['timeout']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->options['sslVerifypeer']);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $headers = (array)$extra_headers;
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if (!empty($params)) {
                    if ($multipart) {
                        foreach ($multipart as $key => $file) {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    } else {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                if ($method == 'DELETE') {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                }
                if (!empty($params)) {
                    $url = $url . (strpos($url, '?') !== false ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }

        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($curl);
        $this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $httpInfo = curl_getinfo($curl);
        if (is_array($httpInfo) && !empty($httpInfo)) {
            $this->httpInfo = array_merge($this->httpInfo, $httpInfo);
        }
        curl_close($curl);

        return $response;
    }
}