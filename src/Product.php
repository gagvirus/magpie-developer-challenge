<?php

namespace App;

use DateTime;
use Exception;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

class Product
{
    const IS_AVAILABLE_TEXT = 'In Stock';
    private string $name;
    private string $capacity;
    private string $price;
    private string $imageUrl;
    private string $colour;
    private string $availabilityText;
    private string $shippingText;

    public function __construct(string $name, string $capacity, string $price, string $imageUrl, string $colour, string $availabilityText, string $shippingText)
    {
        $this->name = $name;
        $this->capacity = $capacity;
        $this->price = $price;
        $this->imageUrl = $imageUrl;
        $this->colour = $colour;
        $this->availabilityText = $availabilityText;
        $this->shippingText = $shippingText;
    }

    /**
     * @param Crawler $product
     * @param string $url
     * @return Product[]
     */
    public static function fromCrawledData(Crawler $product, string $url): array
    {
        $title = $product->filter('h3');
        $image = $product->filter('img');
        // contains the .bg-white.rounded div container
        $wrapper = $product->children('div')->first();
        $coloursWrapper = $wrapper->children('div')->eq(0);
        $price = $wrapper->children('div')->eq(1);
        $availability = $wrapper->children('div')->eq(2);
        $shippingText = "";
        if ($wrapper->children('div')->count() > 3) {
            $shippingText = $wrapper->children('div')->eq(3)->text();
        }

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
        $availabilityText = str_replace("Availability: ", "", $availability->text());

        $products = [];

        foreach ($colours as $colour) {
            $products[] = new self($name, $capacity, $price, $imageUrl, $colour, $availabilityText, $shippingText);
        }
        return $products;
    }

    public function toArray(): array
    {
        $shippingDate = $this->getShippingDate();
        $shippingDateStr = $shippingDate ? $shippingDate->format('Y-m-d') : null;
        return [
            'title' => $this->name . ' ' . $this->capacity,
            'price' => $this->getPrice(),
            'imageUrl' => $this->imageUrl,
            'capacityMB' => $this->getCapacityMB(),
            'colour' => $this->colour,
            'availabilityText' => $this->availabilityText,
            'isAvailable' => $this->availabilityText === self::IS_AVAILABLE_TEXT,
            'shippingText' => $this->shippingText,
            'shippingDate' => $shippingDateStr,
        ];
    }

    private function getShippingDate(): ?DateTime
    {
        $shippingText = trim(strtolower($this->shippingText));

        // 1. Check for "unavailable" or no date
        if (str_contains($shippingText, 'unavailable')) {
            return null;
        }

        // 2. Handle "tomorrow"
        if (str_contains($shippingText, 'tomorrow')) {
            return new DateTime('tomorrow');
        }

        // 3. Remove ordinal suffixes (st, nd, rd, th)
        $shippingText = preg_replace('/(\d+)(st|nd|rd|th)/i', '$1', $shippingText);

        // 4. Extract explicit dates (DD MMM YYYY or YYYY-MM-DD)
        if (preg_match('/(\d{1,2}\s+[a-z]{3,9}\s+\d{4})/i', $shippingText, $matches)) {
            $date = DateTime::createFromFormat('j M Y', $matches[1]);
            if ($date !== false) {
                return $date;
            }
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $shippingText, $matches)) {
            try {
                return new DateTime($matches[1]);
            } catch (Exception $e) {
                return null;
            }
        }

        // 5. Handle keywords like "available on", "delivery by", "delivery from", "have it"
        if (preg_match('/(?:available on|delivery by|delivery from|have it)\s+(\d{1,2}\s+[a-z]{3,9}\s+\d{4})/i', $shippingText, $matches)) {
            $date = DateTime::createFromFormat('j M Y', $matches[1]);
            if ($date !== false) {
                return $date;
            }
        }

        // Free delivery/shipping without date, everything else

        return null;
    }

    public function getChecksum(): string
    {
        return md5(json_encode([$this->name, $this->capacity, $this->colour]));
    }

    private function getPrice(): float
    {
        // Remove everything except digits and decimal point
        $clean = preg_replace('/[^\d.]/', '', $this->price);
        return (float)$clean;
    }

    private function getCapacityMB(): float
    {
        // Normalize input (remove spaces, uppercase)
        $value = strtoupper(trim(str_replace(' ', '', $this->capacity)));

        // Extract number + unit using regex
        if (!preg_match('/^([\d.]+)(MB|GB|TB)$/', $value, $matches)) {
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
}
