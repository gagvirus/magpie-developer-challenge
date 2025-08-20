<?php

namespace App;

require 'vendor/autoload.php';

class Scrape
{
    public function run(): void
    {
        $crawler = new MagpieCrawler();

        // this contains the array of all product objects
        $products = $crawler->fetchAllProducts();

        $productsArray = array_map(function (Product $product) {
            return $product->toArray();
        }, $products);

        file_put_contents('output.json', json_encode($productsArray));
    }
}

$scrape = new Scrape();
$scrape->run();
