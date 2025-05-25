<?php

use Carbon\Carbon;

if (!function_exists('trimDecimalZero')) {
    function trimDecimalZero($number)
    {
        $exploadValue = explode('.', $number);

        if (count($exploadValue) > 1) {
            if (rtrim($exploadValue[1], '0') == '') {
                return $exploadValue[0];
            }

            return $exploadValue[0] . '.' . rtrim($exploadValue[1], '0');
        }

        return $exploadValue[0];
    }
}

if (! function_exists('rupiah')) {
    /**
     * @param  float  $angka
     * @param  bool  $prefix
     * @return string
     */
    function rupiah($angka, $prefix = true)
    {
        if (is_null($angka) || is_string($angka)) {
            return null;
        }

        if ($prefix) {
            $hasil_rupiah = 'Rp '.number_format($angka, 0, ',', '.');

            return $hasil_rupiah;
        }

        $hasil_rupiah = number_format($angka, 0, ',', '.');

        return $hasil_rupiah;
    }
}

if (! function_exists('with_prefix_diff')) {
    /**
     * @param  float  $angka
     * @return string
     */
    function with_prefix_diff($angka)
    {
        if (is_null($angka) ) {
            return null;
        }

        $prefix = $angka > 0 ? '+' : '-';

        return $prefix.' '.trimDecimalZero(abs($angka));
    }
}
