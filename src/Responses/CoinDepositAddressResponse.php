<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class CoinDepositAddressResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'coin',
            'address',
            'memo'
        ];
    }
}
