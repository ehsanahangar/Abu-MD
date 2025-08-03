<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Converts English digits to Persian digits.
 *
 * @param string|int $input The input string or number.
 * @return string The string with Persian digits.
 */
function chap_hesab_to_persian_digits( $input ) {
    $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    return str_replace( $english_digits, $persian_digits, $input );
}

/**
 * Converts a Gregorian date to Jalali (Persian) date.
 * This is a well-known algorithm.
 *
 * @param int $gy Gregorian year.
 * @param int $gm Gregorian month.
 * @param int $gd Gregorian day.
 * @param string $format The format for the output date.
 * @return string The formatted Jalali date.
 */
function chap_hesab_gregorian_to_jalali( $gy, $gm, $gd, $format = 'Y/m/d' ) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $jy = ( $gy <= 1600 ) ? 0 : 979;
    $gy -= ( $gy <= 1600 ) ? 621 : 1600;
    $gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
    $days = ( 365 * $gy ) + (int)( ( $gy2 + 3 ) / 4 ) - (int)( ( $gy2 + 99 ) / 100 ) + (int)( ( $gy2 + 399 ) / 400 ) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * (int)( $days / 12053 );
    $days %= 12053;
    $jy += 4 * (int)( $days / 1461 );
    $days %= 1461;
    $jy += (int)( ( $days - 1 ) / 365 );
    if ( $days > 365 ) $days = ( $days - 1 ) % 365;
    $jm = ( $days < 186 ) ? 1 + (int)( $days / 31 ) : 7 + (int)( ( $days - 186 ) / 30 );
    $jd = 1 + ( ( $days < 186 ) ? ( $days % 31 ) : ( ( $days - 186 ) % 30 ) );

    $formatted_date = str_replace(
        array('Y', 'm', 'd'),
        array($jy, str_pad($jm, 2, '0', STR_PAD_LEFT), str_pad($jd, 2, '0', STR_PAD_LEFT)),
        $format
    );

    return $formatted_date;
}
