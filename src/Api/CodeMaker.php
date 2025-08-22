<?php

declare(strict_types=1);

namespace Sunnysideup\PageFavouritesBookmarker\Api;

class CodeMaker
{

    public static function make_alpha_num_code(int $length = 12): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charLen = strlen($chars);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $idx = random_int(0, $charLen - 1);
            $result .= $chars[$idx];
        }

        return $result;
    }

    public static function sanitize_code(string $code): string
    {
        // Remove any characters that are not alphanumeric
        return preg_replace('/[^a-zA-Z0-9]/', '', $code);
    }
}
