<?php

declare(strict_types=1);

final class Validator
{
    public static function item(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $unit = trim((string) ($input['unit'] ?? ''));

        if ($name === '' || mb_strlen($name) > 150) {
            $errors['name'] = '商品名は1〜150文字で入力してください。';
        }
        if ($category === '' || mb_strlen($category) > 100) {
            $errors['category'] = 'カテゴリは1〜100文字で入力してください。';
        }
        if ($unit === '' || mb_strlen($unit) > 30) {
            $errors['unit'] = '単位は1〜30文字で入力してください。';
        }

        return $errors;
    }

    public static function movement(array $input): array
    {
        $errors = [];
        $itemId = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($input['quantity'] ?? null, FILTER_VALIDATE_INT);

        if (!$itemId || $itemId < 1) {
            $errors['item_id'] = '商品を選択してください。';
        }
        if (!$quantity || $quantity < 1 || $quantity > 1000000) {
            $errors['quantity'] = '数量は1〜1,000,000で入力してください。';
        }
        return $errors;
    }
}

