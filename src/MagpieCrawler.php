<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

class MagpieCrawler
{
    private const URLS = [
        'PRODUCTS' => 'developer-challenge/smartphones',
    ];
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://www.magpiehq.com',
        ]);
    }

    /**
     * @return Product[]
     * @throws GuzzleException
     */
    public function fetchAllProducts(): array
    {
        $products = [];
        $page = 1;
        do {
            $pageProducts = $this->fetchProductsPage($page);
            $products = array_merge($products, $pageProducts);
            $page++;
        } while (!empty($pageProducts));
        return $products;
    }

    /**
     * @param int $page
     * @return Product[]
     * @throws GuzzleException
     */
    public function fetchProductsPage(int $page = 1): array
    {
        $url = self::URLS["PRODUCTS"];
        $response = $this->client->get($url, [
            'params' => [
                'page' => $page,
            ]
        ]);
        $crawler = new Crawler($response->getBody()->getContents());

        return $crawler->filter("#products .product")->each(function (Crawler $crawler) {
            return Product::fromCrawledData($crawler);
        });
    }
}
