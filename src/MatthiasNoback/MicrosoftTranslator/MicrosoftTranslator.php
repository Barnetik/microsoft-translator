<?php

namespace MatthiasNoback\MicrosoftTranslator;

use MatthiasNoback\MicrosoftOAuth\AccessTokenProviderInterface;
use MatthiasNoback\MicrosoftTranslator\ApiCall;

use Buzz\Browser;

class MicrosoftTranslator
{
    const ACCESS_TOKEN_SCOPE = 'http://api.microsofttranslator.com';
    const ACCESS_TOKEN_GRANT_TYPE = 'client_credentials';

    /**
     * @var \Buzz\Browser
     */
    private $browser;

    /**
     * @var \MatthiasNoback\MicrosoftOAuth\AccessTokenProviderInterface
     */
    private $accessTokenProvider;

    public function __construct(Browser $browser, AccessTokenProviderInterface $accessTokenProvider)
    {
        $this->browser = $browser;
        $this->accessTokenProvider = $accessTokenProvider;
    }

    public function translate($text, $to, $from = '', $category = 'general')
    {
        $apiCall = new ApiCall\Translate($text, $to, $from, $category);

        return $this->call($apiCall);
    }

    public function translateArray(array $texts, $to, $from = '')
    {
        $apiCall = new ApiCall\TranslateArray($texts, $to, $from);

        return $this->call($apiCall);
    }

    /**
     * @param \MatthiasNoback\MicrosoftTranslator\ApiCall\ApiCallInterface $apiCall
     */
    private function call(ApiCall\ApiCallInterface $apiCall)
    {
        $url = $apiCall->getUrl();
        $method = $apiCall->getHttpMethod();
        $headers = array(
            'Authorization: Bearer '.$this->getAccessToken(),
            'Content-Type: text/xml',
        );
        $content = $apiCall->getRequestContent();

        $response = $this->browser->call($url, $method, $headers, $content);

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'API call was not successful, %d: %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        /* @var $response \Buzz\Message\Response */

        $responseContent = $response->getContent();

        return $apiCall->parseResponse($responseContent);
    }

    private function getAccessToken()
    {
        return $this->accessTokenProvider->getAccessToken(self::ACCESS_TOKEN_SCOPE, self::ACCESS_TOKEN_GRANT_TYPE);
    }
}