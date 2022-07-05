<?php

namespace Grzojda\TwitterDownloader;

use Exception;
use stdClass;
use Symfony\Component\Dotenv\Dotenv;

class Downloader
{
    const TWITTER_API_URL = 'https://api.twitter.com/1.1/statuses/show.json?id=';
    const TWITTER_STATUS_REGEX = '/^https?:\/\/twitter\.com\/(?:#!\/)?(\w+)\/status(es)?\/(\d+)$/';

    protected $dotenv;
    protected $tweetId;
    protected $tweetUrl;
    protected $status = 'success';
    protected $message = 'Kliknij aby pobrać!';

    public function __construct(Dotenv $dotenv, array $post)
    {
        $this->dotenv = $dotenv;
        $this->validatePostData($post);
        $this->tweetId = $this->getIdFromUrl();
        $dotenv->loadEnv(__DIR__ . '/../.env');
    }

    public function getVideoUrl(): string
    {
        try {
            $result = $this->sendRequest();
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());
        }

        return json_encode(['url' => $result->extended_entities->media[0]->video_info->variants[1]->url ?? '', 'status' => $this->status, 'message' => $this->message]);
    }

    private function sendRequest(): stdClass
    {
        $authorization = 'Authorization: Bearer ' . $_ENV['BEARER_TOKEN'];

        $ch = curl_init(self::TWITTER_API_URL . $this->tweetId);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = json_decode($result);

        if ($httpcode != 200) {
            $this->setErrorMessage($result->errors[0]->message);
        }

        return $result;
    }

    private function getIdFromUrl(): string
    {
        $urlArray = explode('/', $this->tweetUrl);

        return end($urlArray);
    }

    private function setErrorMessage($message)
    {
        $this->status = 'error';
        $this->message = $message;
    }
    private function validatePostData($post)
    {
        if ($post['url']) {
            if (false === preg_match(self::TWITTER_STATUS_REGEX, $post['url'])) {
                $this->setErrorMessage('Provide valid twitter url');
                $this->tweetUrl = '';
            } else {
                $this->tweetUrl = $post['url'];
            }
        } else {
            $this->tweetUrl = '';
            $this->setErrorMessage('Url was not provided');
        }
    }
}
