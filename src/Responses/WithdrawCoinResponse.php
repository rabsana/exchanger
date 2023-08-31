<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class WithdrawCoinResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'withdraw_id',
        ];
    }
}
