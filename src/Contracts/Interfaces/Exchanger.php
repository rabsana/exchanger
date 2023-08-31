<?php

namespace Rabsana\Exchanger\Contracts\Interfaces;

use Rabsana\Exchanger\Responses\BalanceResponse;
use Rabsana\Exchanger\Responses\BuyMarketResponse;
use Rabsana\Exchanger\Responses\CoinDepositAddressResponse;
use Rabsana\Exchanger\Responses\CoinResponse;
use Rabsana\Exchanger\Responses\DepositHistoryResponse;
use Rabsana\Exchanger\Responses\ExchangerCoinResponse;
use Rabsana\Exchanger\Responses\NetworkResponse;
use Rabsana\Exchanger\Responses\PriceResponse;
use Rabsana\Exchanger\Responses\SellMarketResponse;
use Rabsana\Exchanger\Responses\ValidationResponse;
use Rabsana\Exchanger\Responses\WithdrawHistoryResponse;
use Rabsana\Exchanger\Responses\WithdrawCoinResponse;

interface Exchanger
{
    // *** Examples
    // $coins = ['BTC', 'ETH' , 'BNB'];
    // $symbols = ['BTCUSDT' , 'ETHUSDT' , 'BNBUSDT'];

    public function getCoins(array $coins = []): CoinResponse;

    public function getValidations(array $symbols = []): ValidationResponse;

    public function getPrices(array $symbols = []): PriceResponse;

    public function getBalances(array $coins = []): BalanceResponse;

    public function getNetworks(array $coins = []): NetworkResponse;

    public function getExchangerCoin(): ExchangerCoinResponse;


    // before using buy and sell market method you can send the symbol quantity to calculate commission, step size, ...
    public function prepareQuantityToOrder(float $quantity, string $symbol, string $side): string;



    // *** Example:
    // $coin = 'BNB';
    // $network = 'BSC';
    public function getCoinDepositAddress(string $coin, string $network): CoinDepositAddressResponse;




    // *** The params to filter deposit history response must be something like this
    // $params = [
    //      'coin'       => 'BTC',                  // optional
    //      'status'     => '0',                    // optional // base on the deposit statuses method
    //      'fromDate'   => '2019-05-06 13:00:10',  // optional // format : Y-m-d H:i:s
    //      'toDate'     => '2020-05-06 13:00:10',  // optional // format : Y-m-d H:i:s
    //      'offset'     => 0,                      // optional
    //      'limit'      => 100                     // optional
    // ];
    // *
    public function getDepositHistory(array $params = []): DepositHistoryResponse;

    public function depositIsCompleted($status): bool;



    // *** The params to filter withdraw history response must be something like this
    // $params = [
    //      'coin'                  => 'BTC',                  // optional
    //      'status'                => '0',                    // optional // base on the withdraw statuses method
    //      'fromDate'              => '2019-05-06 13:00:10',  // optional // format : Y-m-d H:i:s
    //      'toDate'                => '2020-05-06 13:00:10',  // optional // format : Y-m-d H:i:s
    //      'offset'                => 0,                      // optional
    //      'limit'                 => 100                     // optional
    // ];
    // *
    public function getWithdrawHistory(array $params = []): WithdrawHistoryResponse;







    public function withdrawCoin(string $coin, float $quantity, string $network, string $address, string $memo = '', bool $internalTransferFeePayerIsUser = true, string $chain = ''): WithdrawCoinResponse;




    public function buyMarket(string $symbol, float $qty): BuyMarketResponse;

    public function sellMarket(string $symbol, float $qty): SellMarketResponse;

    public function orderIsCompleted($status): bool;




    public function getDepositStatuses(): array;

    public function getWithdrawStatuses(): array;

    public function getTransferTypes(): array;
}
