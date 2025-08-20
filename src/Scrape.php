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

        // removing duplicate entries by checking checksums
        $seen = [];
        $products = array_filter($products, function (Product $product) use (&$seen) {
            $checksum = $product->getChecksum();
            if (isset($seen[$checksum])) {
                // checksum already encountered -> ignore
                return false;
            }
            // mark as seen -> keep
            $seen[$checksum] = true;
            return true;
        });

        $productsArray = array_map(function (Product $product) {
            return $product->toArray();
        }, $products);

        file_put_contents('output.json', json_encode($productsArray));
    }
}

$scrape = new Scrape();
$scrape->run();
