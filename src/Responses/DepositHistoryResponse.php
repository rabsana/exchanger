<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class DepositHistoryResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'coin',
            'network',
            'amount',
            'amountPrettified',
            'status',
            'statusPrettified',
            'address',
            'memo',
            'txid',
            'transferType',
            'transferTypePrettified',
            'createdAt',
        ];
    }
}
