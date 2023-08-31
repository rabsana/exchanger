<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class PriceResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'symbol',
            'price',
            'pricePrettified'
        ];
    }
}
