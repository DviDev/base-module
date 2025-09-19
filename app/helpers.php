<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\View\ComponentAttributeBag;

if (! function_exists('prepareAttributes')) {
    function prepareAttributes(ComponentAttributeBag $attributes, array $attr = []): void
    {
        $array = collect($attr)->except('id')->merge($attributes->getAttributes())->all();
        $attributes->setAttributes($array);
        $attrs = $attributes->get('attr');

        $items = collect($attrs)
            ->merge($attributes->getAttributes())
            ->filter(fn ($i) => isset($i))
            ->forget('attr');
        if (! $items->has('id')) {
            $items->put('id', 'comp_'.now()->timestamp.Str::random(5));
        }
        $array = $items->all();
        if (isset($array['name'])) {
            $array['name'] = __($array['name']);
        }
        if (isset($array['placeholder'])) {
            $array['placeholder'] = __($array['placeholder']);
        }
        if (isset($array['label'])) {
            $array['label'] = ucfirst(trans(mb_strtolower($array['label'])));
        }

        $attributes->setAttributes($array);
    }
}

if (! function_exists('currency')) {
    function currency($value = 0): bool|string
    {
        return Number::format($value, 2, null, config('app.locale'));
    }
}

if (! function_exists('currencyToText')) {

    function currencyToText(float $value, $locale = null)
    {
        $formatter = new NumberFormatter($locale ?: config('app.locale'), NumberFormatter::SPELLOUT);

        // Separar a parte inteira e a parte decimal
        $partes = explode('.', number_format($value, 2, '.', ''));
        $parteInteira = $partes[0];
        $parteDecimal = $partes[1];

        // Converter a parte inteira para texto
        $textoInteiro = $formatter->format($parteInteira);

        // Converter a parte decimal para texto
        $textoDecimal = $formatter->format($parteDecimal);

        // Montar o texto final
        $textoFinal = $textoInteiro.' reais ';
        if ($parteDecimal > 0) {
            $textoFinal .= ' e '.$textoDecimal.' centavos';
        }

        return $textoFinal;
    }
}

if (! function_exists('toBRL')) {
    function toBRL($value = null): bool|string
    {
        $value = empty($value) ? 0 : $value;

        return Number::format($value, 2, null, 'pt_BR');
    }
}

if (! function_exists('toUS')) {
    function toUS($value = null): bool|string
    {
        $value = empty($value) ? 0 : $value;

        return str($value)->replace('.', '')->replace(',', '.')->value();
    }
}

if (! function_exists('carbon')) {
    function carbon($value = null): Carbon
    {
        return new Carbon($value);
    }
}
