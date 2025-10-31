<?php

if (!defined('ABSPATH')) exit;

function pfm_format_number($number, $decimals = 0)
{
    return number_format((float)$number, $decimals, '.', ',');
}
