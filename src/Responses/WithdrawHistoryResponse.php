<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class WithdrawHistoryResponse extends Response
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
            'txid',
            'id',
            'transferType',
            'transferTypePrettified',
            'transferFee',
            'createdAt',
        ];
    }
}
