<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class CoinResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'coin',
            'name'
        ];
    }
}
