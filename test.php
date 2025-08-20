<?php

require 'vendor/autoload.php';


use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

const IS_AVAILABLE_TEXT = 'In Stock';

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

function get_availability(string $text)
{
    return str_replace("Availability: ", "", $text);
}

function parse_product(Crawler $product)
{
    global $url;
    $title = $product->filter('h3');
    $image = $product->filter('img');
    // contains the .bg-white.rounded div container
    $wrapper = $product->children('div')->first();
    $coloursWrapper = $wrapper->children('div')->eq(0);
    $price = $wrapper->children('div')->eq(1);
    $availability = $wrapper->children('div')->eq(2);
    $delivery = $wrapper->children('div')->eq(3);

    $name = $title->filter('.product-name')->text();
    $capacity = $title->filter('.product-capacity')->text();
    $price = $price->text();
    // get the image src as it is on the img tag
    $imageSrc = $image->first()->attr("src");
    // get absolute url
    $imageUrl = UriResolver::resolve($imageSrc, $url);
    $colours = $coloursWrapper->filter('span[data-colour]')->each(function (Crawler $crawler) {
        return $crawler->attr('data-colour');
    });
    $availabilityText = get_availability($availability->text());
    return [
        'title' => $name . ' ' . $capacity,
        'price' => parseCurrency($price),
        'imageUrl' => $imageUrl,
        'capacityMB' => convertToMB($capacity),
        // todo: Each colour variant should be treated as a separate product.
        'colours' => $colours,
        'availabilityText' => $availabilityText,
        'isAvailable' => $availabilityText === IS_AVAILABLE_TEXT,
    ];
}

$prod = $products->eq(5);
dd(parse_product($prod));

$results = $products->each(function (Crawler $product) use ($url) {
    return parse_product($product);
});


dd($results);
