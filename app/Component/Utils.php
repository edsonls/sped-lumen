<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 08/08/2018
 * Time: 16:53
 */

namespace App\Component;


class Utils
{
    /**
     * @param object|array|string $data
     * @param bool $json
     */
    public static function debug($data, $json = false): void
    {
        if ($json) {
            echo json_encode($data);
        } else {
            var_dump($data);
        }
    }

    /**
     * @param array $head
     * @return bool
     */
    public static function validHeader(array $head): bool
    {
        return array_key_exists('NFE-METHOD', $head);
    }

    /**
     * @param array $response
     */
    public static function response(array $response): void
    {
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * @param float $v
     * @param int $decimal
     * @return float
     */
    public static function formatValue(float $v, $decimal = 2)
    {
        return floatval(number_format(
            $v,
            $decimal,
            ".",
            ""));
    }
}