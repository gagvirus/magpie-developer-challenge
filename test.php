<?php

require 'vendor/autoload.php';


use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

function dd($args)
{
    var_dump($args);
    die();
}

function convertToMB(string $value): float
{
    // Normalize input (remove spaces, uppercase)
    $value = strtoupper(trim(str_replace(' ', '', $value)));

    // Extract number + unit using regex
    if (!preg_match('/^([\d\.]+)(MB|GB|TB)$/', $value, $matches)) {
        throw new InvalidArgumentException("Invalid size format: $value");
    }

    $number = (float)$matches[1];
    $unit = $matches[2];

    switch ($unit) {
        case 'MB':
            return $number;
        case 'GB':
            return $number * 1024;
        case 'TB':
            return $number * 1024 * 1024;
        default:
            throw new InvalidArgumentException("Unknown unit: $unit");
    }
}

function parseCurrency(string $value): float
{
    // Remove everything except digits and decimal point
    $clean = preg_replace('/[^\d.]/', '', $value);
    return (float)$clean;
}

$client = new Client();

$url = 'https://www.magpiehq.com/developer-challenge/smartphones/';

$response = $client->request('GET', $url);

$html = $response->getBody()->getContents();
$crawler = new Crawler($html);

$products = $crawler->filter("#products .product");

$results = $products->each(function (Crawler $product) use ($url) {
    $name = $product->filter('.product-name')->text();
    $price = $product->filter('.my-8.block.text-center.text-lg')->text();
    $imageSrc = $product->filter('img')->first()->attr("src");
    $imageUrl = UriResolver::resolve($imageSrc, $url);
    $capacity = $product->filter('.product-capacity')->text();
    $colours = $product->filter('span[data-colour]')->each(function(Crawler $crawler) {
        return $crawler->attr('data-colour');
    });
    return [
        'title' => $name . ' ' . $capacity,
        'price' => parseCurrency($price),
        'imageUrl' => $imageUrl,
        'capacityMB' => convertToMB($capacity),
        // todo: Each colour variant should be treated as a separate product.
        'colours' => $colours,
    ];
});


dd($results);
