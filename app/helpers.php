<?php

use Carbon\Carbon;
use Illuminate\Support\Number;

function currency($value = 0): bool|string
{
    return Number::format($value, 2, null, config('app.locale'));
}

function currencyToText(float $value, $locale = null)
{
    $formatter = new \NumberFormatter($locale ?: config('app.locale'), \NumberFormatter::SPELLOUT);

    // Separar a parte inteira e a parte decimal
    $partes = explode('.', number_format($value, 2, '.', ''));
    $parteInteira = $partes[0];
    $parteDecimal = $partes[1];

    // Converter a parte inteira para texto
    $textoInteiro = $formatter->format($parteInteira);

    // Converter a parte decimal para texto
    $textoDecimal = $formatter->format($parteDecimal);

    // Montar o texto final
    $textoFinal = $textoInteiro . ' reais ';
    if ($parteDecimal > 0) {
        $textoFinal .= ' e ' . $textoDecimal . ' centavos';
    }
    return $textoFinal;
}

function toBRL($value = null): bool|string
{
    $value = empty($value) ? 0 : $value;
    return Number::format($value, 2, null, 'pt_BR');
}

function toUS($value = null): bool|string
{
    $value = empty($value) ? 0 : $value;
    return str($value)->replace('.', '')->replace(',', '.')->value();
}

function carbon($value = null): Carbon
{
    return new Carbon($value);
}
