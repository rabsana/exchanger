<?php

namespace Rabsana\Exchanger\Responses;

use Rabsana\Exchanger\Contracts\Abstracts\Response;

class NetworkResponse extends Response
{
    public function getKeys(): array
    {
        return [
            'coin',
            'networks',
            'networks.network',
            'networks.name',
            'networks.isDefault',
            'networks.withdrawEnable',
            'networks.depositEnable',
            'networks.addressRegex',
            'networks.memoRegex',
            'networks.withdrawFee',
            'networks.withdrawMin',
            'networks.withdrawMax',
            'networks.description',
        ];
    }
}
