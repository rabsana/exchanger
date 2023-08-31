<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class BalanceResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'coin',
            'balance',
            'balancePrettified'
        ];
    }
}
