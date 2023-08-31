<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class ExchangerCoinResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'coin'
        ];
    }
}
