<?php

require 'vendor/autoload.php';


use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

function dd($args) {
    var_dump($args);
    die();
}

$client = new Client();

$response = $client->request('GET', 'https://www.magpiehq.com/developer-challenge/smartphones/');

$html = $response->getBody()->getContents();
$crawler = new Crawler($html);

$products = $crawler->filter("#products .product");

$products->each(function(Crawler $product ) {
    dd($product->text());
});
