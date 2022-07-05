<?php

use Grzojda\TwitterDownloader\Downloader;
use Symfony\Component\Dotenv\Dotenv;
header('Content-Type: application/json; charset=utf-8');

require '../vendor/autoload.php';
$downloader = new Downloader(new Dotenv(), $_POST);

echo $downloader->getVideoUrl();