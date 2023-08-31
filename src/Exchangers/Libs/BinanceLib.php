<?php

namespace Rabsana\Exchanger\Exchangers\Libs;

class BinanceLib extends \Binance\API
{

    public $binance;

    public function __construct()
    {
        $this->binance = new \Binance\API(
            config("rabsana-exchanger.binance.apiKey"),
            config("rabsana-exchanger.binance.secretKey")
        );
    }

    public function binance()
    {
        return $this->binance;
    }

    public function createHistoryFilters(array $params): array
    {
        $filters = [];

        if (isset($params['offset']) && is_numeric($params['offset'])) {
            $filters['offset'] = $params['offset'];
        }

        if (isset($params['limit']) && is_numeric($params['limit'])) {
            $filters['limit'] = $params['limit'];
        }

        if (isset($params['status']) && is_numeric($params['status'])) {
            $filters['status'] = $params['status'];
        }

        if (isset($params['fromDate'])) {
            $filters['startTime'] = strtotime($params['fromDate']) * 1000;
        }

        if (isset($params['toDate'])) {
            $filters['endTime'] = strtotime($params['toDate']) * 1000;
        }

        return $filters;
    }
}
