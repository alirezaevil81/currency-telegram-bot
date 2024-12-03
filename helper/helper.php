<?php

use Utility\Config;
use Src\Currency;

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return Config::getInstance()->get($key, $default);
    }
}

if (!function_exists('execute_if_interval_passed')) {
    function execute_if_interval_passed(string $filePath, callable $callback): void
    {
        // مقدار پیش‌فرض زمان آخرین اجرا
        $lastExecutionTime = 0;

        // بررسی وجود فایل JSON
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($data['last_execution'])) {
                $lastExecutionTime = $data['last_execution'];
            }
        }

        // زمان فعلی
        $currentTime = time();

        // بررسی گذشتن 60 ثانیه
        if ($currentTime - $lastExecutionTime >= 60) {
            // اجرای کال‌بک
            $callback();
            // ذخیره زمان فعلی در فایل JSON
            $data = ['last_execution' => $currentTime];
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $remainingTime = 60 - ($currentTime - $lastExecutionTime);
            echo "Please wait {$remainingTime} seconds before executing again.\n";
        }
    }
}