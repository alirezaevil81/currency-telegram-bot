<?php

require_once "vendor/autoload.php";

use Telegram\Bot\Api;
use Src\Currency;

$telegram = new Api(config('token'));

execute_if_interval_passed(config('save_path.last_execute'), function () {
    $currency = new Currency();
    $currency->save($currency->fetch(config('api.v2')), config('save_path.data'));
});

// // اطلاعات JSON
$data = json_decode(file_get_contents(config('save_path.data')), true);

// تابع ایجاد دکمه‌های شیشه‌ای برای دسته‌بندی‌ها
function createMainCategoriesKeyboard(array $data): array
{
    return [
        'inline_keyboard' => [
            [['text' => '💰 طلا', 'callback_data' => 'category_gold']],
            [['text' => '💵 ارز', 'callback_data' => 'category_currency']],
            [['text' => '🔗 ارز دیجیتال', 'callback_data' => 'category_cryptocurrency']],
        ]
    ];
}

// تابع ایجاد دکمه‌های شیشه‌ای برای موارد در یک دسته
function createItemsKeyboard(array $items, string $category): array
{
    $buttons = [];
    foreach ($items as $item) {
        $buttons[] = [
            'text' => $item['name'],
            'callback_data' => "{$category}_{$item['name']}",
        ];
    }
    return ['inline_keyboard' => array_chunk($buttons, 2)];
}

// دریافت آپدیت‌ها از تلگرام
$update = $telegram->getWebhookUpdate();

if ($update->has('message')) {
    
    $message = $update->getMessage();

    // بررسی پیام استارت
    if ($message->get('text') === '/start') {
        $inlineKeyboard = createMainCategoriesKeyboard($data);

        $telegram->sendMessage([
            'chat_id' => $message->get('chat')['id'],
            'text' => 'یک دسته‌بندی را انتخاب کنید:',
            'reply_markup' => json_encode($inlineKeyboard),
        ]);
    }
} elseif ($update->has('callback_query')) {
    $callbackQuery = $update->getCallbackQuery();
    $callbackData = $callbackQuery->get('data'); // داده بازگشتی
    $chatId = $callbackQuery->get('message')['chat']['id'];

    // بررسی دسته‌بندی انتخاب شده
    if (strpos($callbackData, 'category_') === 0) {
        $category = str_replace('category_', '', $callbackData);
        if (isset($data[$category])) {
            $inlineKeyboard = createItemsKeyboard($data[$category], $category);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "موارد موجود در دسته‌بندی «{$category}»:",
                'reply_markup' => json_encode($inlineKeyboard),
            ]);
        }
    }

    // بررسی آیتم انتخاب شده
    foreach (['gold', 'currency', 'cryptocurrency'] as $category) {
        if (strpos($callbackData, "{$category}_") === 0) {
            $itemName = str_replace("{$category}_", '', $callbackData);
            $selectedItem = null;

            foreach ($data[$category] as $item) {
                if ($item['name'] === $itemName) {
                    $selectedItem = $item;
                    break;
                }
            }

            if ($selectedItem) {
                //
                $info = "💡 اطلاعات {$selectedItem['name']}:\n";
                //
                $info .= "⏰ زمان: {$selectedItem['time']}\n";

                $signs = ['💴','💵','💶','💷'];
                $randomKey = array_rand($signs);
                $sign = $signs[$randomKey];
                //
                $info .= "{$sign} قیمت: {$selectedItem['price']}\n";

                
                if ($selectedItem['change_percent'] < 0) {
                    //
                    $info .= "📉 تغییر قیمت: {$selectedItem['change_percent']}%\n";
                } elseif ($selectedItem['change_percent'] > 0) {
                    //
                    $info .= "📈 تغییر قیمت: {$selectedItem['change_percent']}%\n";
                } else {
                    //
                    $info .= "📊 تغییر قیمت: {$selectedItem['change_percent']}%\n";
                }
                

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $info,
                ]);
            }
        }
    }

    // پاسخ به Callback Query
    $telegram->answerCallbackQuery([
        'callback_query_id' => $callbackQuery->get('id'),
        'text' => 'انتخاب ثبت شد!',
        'show_alert' => false,
    ]);
}
