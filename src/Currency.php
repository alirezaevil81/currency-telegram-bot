<?php

namespace Src;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Currency
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * دریافت داده‌ها از API
     */
    public function fetch(string $apiUrl): ?string
    {
        try {
            // ارسال درخواست GET به API
            $response = $this->client->get($apiUrl);

            if ($response->getStatusCode() === 200) {
                // بازگرداندن داده‌های دریافت‌شده
                return $response->getBody()->getContents();
            } else {
                echo "Failed to fetch data. HTTP Status: " . $response->getStatusCode() . "\n";
                return null;
            }
        } catch (RequestException $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * ذخیره‌سازی داده‌ها در فایل
     */
    public function save(string $data, string $savePath): bool
    {
        try {
            // ایجاد مسیر فایل در صورت وجود نداشتن
            if (!is_dir(dirname($savePath))) {
                mkdir(dirname($savePath), 0777, true);
            }

            // ذخیره داده‌ها در فایل
            if (file_put_contents($savePath, $data) !== false) {
                echo "JSON data saved successfully to {$savePath}.\n";
                return true;
            } else {
                echo "Failed to save JSON data to {$savePath}.\n";
                return false;
            }
        } catch (\Exception $e) {
            echo "Error occurred while saving: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
