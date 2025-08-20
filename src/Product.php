<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

class Product
{
    public static function fromCrawledData(Crawler $crawler): Product
    {
        // todo: add the actual parsing logic here
        return new self();
    }

    public function toArray(): array
    {
        return [];
    }
}
