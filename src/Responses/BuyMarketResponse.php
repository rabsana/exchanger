<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class BuyMarketResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'symbol',
            'side',
            'type',
            'status',
            'statusPrettified',

            'qty',
            'qtyPrettified',

            'executedQty',
            'executedQtyPrettified',

            'executedQuoteQty',
            'executedQuoteQtyPrettified',

            'price',
            'pricePrettified',

            'commissionAsset',
            'commission',
            'commissionPrettified',

            'createdAt',
        ];
    }
}
