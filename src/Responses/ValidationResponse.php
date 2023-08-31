<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class ValidationResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'symbol',

            'minPrice',
            'maxPrice',
            'stepPrice',

            'minQty',
            'maxQty',
            'stepQty',

            'minQuote',
            'maxQuote',
            'stepQuote',

            'minNotional'
        ];
    }
}
