<?php

namespace Rabsana\Exchanger\Exchangers;

use Exception;
use Illuminate\Support\Facades\Log;
use Rabsana\Core\Support\Facades\Math;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exceptions\ExchangerDepositHasBeenClosedException;
use Rabsana\Exchanger\Exceptions\ExchangerException;
use Rabsana\Exchanger\Exchangers\Libs\BinanceLib;
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
use Rabsana\Exchanger\Traits\HttpTrait;

class Binance extends BinanceLib implements Exchanger
{
    use HttpTrait;

    public function getCoins(array $coins = []): CoinResponse
    {
        try {
            $coinsInfo = $this->binance()->coins();

            if (!empty($coins)) {
                $coinsInfo = collect($coinsInfo)->whereIn('coin', array_map("strtoupper", $coins))->all();
            }


            $response = [];
            foreach ($coinsInfo as $coin) {
                $response[] = [
                    'coin'      => $coin['coin'],
                    'name'      => $coin['name'],
                ];
            }

            return new CoinResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-BINANCE-ERROR-get-coins-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get coins");
        }
    }

    public function getValidations(array $symbols = []): ValidationResponse
    {
        try {

            $validations = $this->binance()->httpRequest($this->buildQueryString(
                "v3/exchangeInfo",
                [
                    'symbols' => empty($symbols) ? '' : '["' . implode('","', array_map("strtoupper", $symbols)) . '"]'
                ]
            ));

            $response = [];
            foreach ($validations['symbols'] as $validation) {

                $priceFilter = collect($validation['filters'])->where('filterType', 'PRICE_FILTER')->first();
                $qtyFilter = collect($validation['filters'])->where('filterType', 'LOT_SIZE')->first();
                $notionalFilter = collect($validation['filters'])->where('filterType', 'NOTIONAL')->first();

                $response[] = [
                    'symbol'        => $validation['symbol'],

                    'minPrice'      => $priceFilter['minPrice'],
                    'maxPrice'      => $priceFilter['maxPrice'],
                    'stepPrice'     => $priceFilter['tickSize'],

                    'minQty'        => $qtyFilter['minQty'],
                    'maxQty'        => $qtyFilter['maxQty'],
                    'stepQty'       => $qtyFilter['stepSize'],

                    'minQuote'      => 0,
                    'maxQuote'      => 0,
                    'stepQuote'     => 0,

                    'minNotional'   => $notionalFilter['minNotional'],
                ];
            }

            // add usdt validation
            $response[] = [
                'symbol'            => 'USDTUSDT',

                'minPrice'          => '1.00000000',
                'maxPrice'          => '1.00000000',
                'stepPrice'         => '0.00010000',

                'minQty'            => '10.00000000',
                'maxQty'            => '100000.00000000',
                'stepQty'           => '0.00010000',

                'minQuote'          => 0,
                'maxQuote'          => 0,
                'stepQuote'         => 0,

                'minNotional'       => '10.00000000',
            ];

            return new ValidationResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-BINANCE-ERROR-get-validations-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get validations");

            //
        }
    }

    public function getPrices(array $symbols = []): PriceResponse
    {
        try {

            $prices = $this->binance()->httpRequest('v3/ticker/price');

            // add usdt pair
            array_push($prices, [
                'symbol'            => "USDTUSDT",
                'price'             => 1,
                'pricePrettified'   => 1
            ]);

            if (!empty($symbols)) {
                $prices = collect($prices)->whereIn('symbol', array_map("strtoupper", $symbols))->all();
            }

            $response = [];
            foreach ($prices as $price) {
                $response[] = [
                    'symbol'            => $price['symbol'],
                    'price'             => $price['price'],
                    'pricePrettified'   => Math::numberFormat($price['price'])
                ];
            }

            return new PriceResponse($response);

            //
        } catch (Exception $e) {


            Log::error("EXCHANGER-PACKAGE-BINANCE-ERROR-get-prices-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get prices");

            //
        }
    }

    public function getBalances(array $coins = []): BalanceResponse
    {
        try {

            $balances = $this->binance()->coins();

            if (!empty($coins)) {
                $balances = collect($balances)->whereIn('coin', array_map("strtoupper", $coins))->all();
            }


            $response = [];
            foreach ($balances as $balance) {
                $response[] = [
                    'coin'              => $balance['coin'],
                    'balance'           => $balance['free'],
                    'balancePrettified' => Math::numberFormat((float) $balance['free'])
                ];
            }

            return new BalanceResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-BINANCE-ERROR-get-balances-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get balances");

            //
        }
    }

    public function getNetworks(array $coins = []): NetworkResponse
    {
        try {

            $networks = $this->binance()->coins();

            if (!empty($coins)) {
                $networks = collect($networks)->whereIn('coin', array_map("strtoupper", $coins))->all();
            }


            $response = [];
            foreach ($networks as $network) {

                $networkList = [];
                foreach ($network['networkList'] as $item) {
                    $networkList[] = [
                        'network'           => $item['network'],
                        'name'              => $item['name'],
                        'isDefault'         => (int) $item['isDefault'],
                        'withdrawEnable'    => (int) $item['withdrawEnable'],
                        'depositEnable'     => (int) $item['depositEnable'],
                        'addressRegex'      => $item['addressRegex'],
                        'memoRegex'         => $item['memoRegex'],
                        'withdrawFee'       => $item['withdrawFee'],
                        'withdrawMin'       => $item['withdrawMin'],
                        'withdrawMax'       => $item['withdrawMax'],
                        'description'       => $item['specialTips'] ?? ''
                    ];
                }

                $response[] = [
                    'coin'      => $network['coin'],
                    'networks'   => $networkList,
                ];
            }

            return new NetworkResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-get-networks-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get networks");
        }
    }

    public function getExchangerCoin(): ExchangerCoinResponse
    {
        return new ExchangerCoinResponse([
            'coin' => 'BNB'
        ]);
    }

    public function getCoinDepositAddress(string $coin, string $network): CoinDepositAddressResponse
    {
        try {

            $address = $this->binance()->depositAddress($coin, $network);

            return new CoinDepositAddressResponse([
                'coin'      => $address['coin'],
                'address'   => $address['address'],
                'memo'      => $address['tag'],
            ]);

            //
        } catch (Exception $e) {

            $code = $e->getCode() ?? 500;

            switch ($code) {
                case 4093:
                    throw new ExchangerDepositHasBeenClosedException("The deposit has been closed", $code);
                    break;

                default:
                    Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-get-coins-deposit-address-method: " . $e->getMessage());
                    throw new ExchangerException("something went wrong in get coin deposit address");
                    break;
            }
        }
    }

    public function getDepositHistory(array $params = []): DepositHistoryResponse
    {
        try {

            $coin = $params['coin'] ?? NULL;
            $filters = $this->createHistoryFilters($params);
            $histories = $this->binance()->depositHistory($coin, $filters);

            $response = [];
            foreach ($histories as $history) {
                $response[] = [
                    'coin'                      => $history['coin'],
                    'network'                   => $history['network'],
                    'amount'                    => $history['amount'],
                    'amountPrettified'          => Math::numberFormat($history['amount']),
                    'status'                    => $history['status'],
                    'statusPrettified'          => $this->getDepositStatus($history['status']),
                    'address'                   => $history['address'] ?? '',
                    'memo'                      => $history['addressTag'] ?? '',
                    'txid'                      => $history['txId'] ?? '',
                    'transferType'              => $history['transferType'],
                    'transferTypePrettified'    => $this->getTransferType($history['transferType']),
                    'createdAt'                 => ($history['insertTime']) ? date('Y-m-d H:i:s', $history['insertTime'] / 1000) : ''
                ];
            }

            return new DepositHistoryResponse($response, true);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-get-deposit-history: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get deposit history");

            //
        }
    }

    public function getWithdrawHistory(array $params = []): WithdrawHistoryResponse
    {
        try {

            $coin = $params['coin'] ?? NULL;
            $filters = $this->createHistoryFilters($params);
            $histories = $this->binance()->withdrawHistory($coin, $filters)['withdrawList'] ?? [];

            $response = [];
            foreach ($histories as $history) {
                $response[] = [
                    'coin'                      => $history['coin'],
                    'network'                   => $history['network'] ?? '',
                    'amount'                    => $history['amount'],
                    'amountPrettified'          => Math::numberFormat($history['amount']),
                    'status'                    => $history['status'],
                    'statusPrettified'          => $this->getWithdrawStatus($history['status']),
                    'address'                   => $history['address'] ?? '',
                    'txid'                      => $history['txId'] ?? '',
                    'id'                        => $history['id'] ?? '',
                    'transferType'              => $history['transferType'],
                    'transferTypePrettified'    => $this->getTransferType($history['transferType']),
                    'transferFee'               => $history['transactionFee'],
                    'createdAt'                 => $history['applyTime'],
                ];
            }

            return new WithdrawHistoryResponse($response, true);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-get-withdraw-history-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get withdraw history");

            //
        }
    }

    public function withdrawCoin(string $coin, float $quantity, string $network, string $address, string $memo = '', bool $internalTransferFeePayerIsUser = true, string $chain = ''): WithdrawCoinResponse
    {
        try {


            $withdraw = (array) $this->binance()->withdraw($coin, $address, $quantity, $memo, '', !$internalTransferFeePayerIsUser, $network);
            $response = [
                'withdraw_id' => $withdraw['id']
            ];

            return new WithdrawCoinResponse($response);

            //
        } catch (Exception $e) {


            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-withdraw-coin-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in withdraw coin method: " . $e->getMessage());


            //
        }
    }

    public function buyMarket(string $symbol, float $qty): BuyMarketResponse
    {
        try {
            $buy = $this->binance()->marketBuy($symbol, $qty);

            $commissionAsset = '';
            $commission = 0;
            if (!empty($buy['fills'])) {
                foreach ($buy['fills'] as $item) {
                    $commissionAsset = $item['commissionAsset'];
                    $commission = Math::add((float) $commission, (float) $item['commission']);
                }
            }

            $response = [
                'symbol'                        => $buy['symbol'],
                'side'                          => $buy['side'],
                'type'                          => $buy['type'],
                'status'                        => $buy['status'],
                'statusPrettified'              => $buy['status'],

                'qty'                           => $buy['origQty'],
                'qtyPrettified'                 => Math::numberFormat((float) $buy['origQty']),

                'executedQty'                   => $buy['executedQty'],
                'executedQtyPrettified'         => Math::numberFormat((float) $buy['executedQty']),

                'executedQuoteQty'              => $buy['cummulativeQuoteQty'],
                'executedQuoteQtyPrettified'    => Math::numberFormat((float) $buy['cummulativeQuoteQty']),

                'price'                         => $buy['price'],
                'pricePrettified'               => Math::numberFormat((float) $buy['price']),

                'commissionAsset'               => $commissionAsset,
                'commission'                    => $commission,
                'commissionPrettified'          => Math::numberFormat((float) $commission),

                'createdAt'                     => ($buy['transactTime']) ? date('Y-m-d H:i:s', $buy['transactTime'] / 1000) : '',
            ];

            return new BuyMarketResponse($response);

            //
        } catch (Exception $e) {


            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-buy-market-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in buy market: " . $e->getMessage());


            //
        }
    }

    public function sellMarket(string $symbol, float $qty): SellMarketResponse
    {
        try {

            $sell = $this->binance()->marketSell($symbol, $qty);

            $commissionAsset = '';
            $commission = 0;
            if (!empty($sell['fills'])) {
                foreach ($sell['fills'] as $item) {
                    $commissionAsset = $item['commissionAsset'];
                    $commission = Math::add((float) $commission, (float) $item['commission']);
                }
            }

            $response = [
                'symbol'                        => $sell['symbol'],
                'side'                          => $sell['side'],
                'type'                          => $sell['type'],
                'status'                        => $sell['status'],
                'statusPrettified'              => $sell['status'],

                'qty'                           => $sell['origQty'],
                'qtyPrettified'                 => Math::numberFormat((float) $sell['origQty']),

                'executedQty'                   => $sell['executedQty'],
                'executedQtyPrettified'         => Math::numberFormat((float) $sell['executedQty']),

                'executedQuoteQty'              => $sell['cummulativeQuoteQty'],
                'executedQuoteQtyPrettified'    => Math::numberFormat((float) $sell['cummulativeQuoteQty']),

                'price'                         => $sell['price'],
                'pricePrettified'               => Math::numberFormat((float) $sell['price']),

                'commissionAsset'               => $commissionAsset,
                'commission'                    => $commission,
                'commissionPrettified'          => Math::numberFormat((float) $commission),

                'createdAt'                     => ($sell['transactTime']) ? date('Y-m-d H:i:s', $sell['transactTime'] / 1000) : '',
            ];

            return new SellMarketResponse($response);
            //
        } catch (Exception $e) {


            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-sell-market-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in sell market: " . $e->getMessage());


            //
        }
    }

    public function prepareQuantityToOrder(float $quantity, string $symbol, string $side): string
    {
        // check the side is valid
        if (!in_array(strtoupper($side), ['BUY', 'SELL'])) {
            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-the-side-is-invalid-in-prepare-quantity-to-order-method: " . $side);
            throw new ExchangerException("The side is invalid. the valid side is buy or sell");
        }

        try {

            $commission = $this->binance()->commissionFee($symbol)[0][(strtoupper($side) == 'BUY') ? 'takerCommission' : 'makerCommission'];
            $stepSize = (float) $this->getValidations([$symbol])->getData()[0]['stepQty'];
            $burnBNB = (bool) $this->binance()->httpRequest("v1/bnbBurn", "GET", ['sapi' => true], true)['spotBNBBurn'] ?? false;
            $BNBBalance = (float) $this->getBalances(['BNB'])->getData()[0]['balance'];

            // decide to how calculate the commission
            if (
                $burnBNB &&
                Math::greaterThan((float) $BNBBalance, 0.01)
            ) {

                // The trade fee will pay with BNB coin
                $result = $quantity;

                //
            } else {

                // add a raito to pervent get insufficient_funds errors
                $raito = Math::multiply(
                    (float) ceil(
                        (float) Math::divide(
                            (float) Math::multiply((float) $commission, (float) $quantity),
                            (float) $stepSize
                        )
                    ),
                    (float) $stepSize
                );

                $result = Math::add((float) $quantity, (float) $raito);
            }

            Log::debug("prepareQuantityToOrder method: " . json_encode([
                'quantity'      => $quantity ?? '',
                'symbol'        => $symbol ?? '',
                'side'          => $side ?? '',
                'commission'    => $commission ?? '',
                'stepSize'      => $stepSize ?? '',
                'burnBNB'       => $burnBNB ?? '',
                'BNBBalance'    => $BNBBalance ?? '',
                'raito'         => $raito ?? '',
                'result'        => $result ?? '',
            ]));

            return Math::setParams([
                'precision'  => Math::decimalPlaceNumber((float) $stepSize)
            ])
                ->number($result);

            //
        } catch (Exception $e) {


            Log::debug("EXCHANGER-PACKAGE-BINANCE-ERROR-prepare-quantity-to-order-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in prepare quantity to order method");


            //
        }
    }

    public function depositIsCompleted($status): bool
    {
        return (bool) ((int) $status == 1);
    }

    public function orderIsCompleted($status): bool
    {
        return (bool) (strtoupper($status) == 'FILLED');
    }

    public function getTransferTypes(): array
    {
        return [
            0 => 'External',
            1 => 'Internal'
        ];
    }

    public function getDepositStatuses(): array
    {
        return [
            0   => 'Pending',
            1   => 'Success',
            6   => 'Credited but cannot withdraw',
        ];
    }

    public function getWithdrawStatuses(): array
    {
        return [
            0   => 'Email Sent',
            1   => 'Cancelled',
            2   => 'Awaiting Approval',
            3   => 'Rejected',
            4   => 'Processing',
            5   => 'Failure',
            6   => 'Completed'
        ];
    }

    public function getTransferType(int $type): string
    {
        return $this->getTransferTypes()[$type] ?? '';
    }

    public function getDepositStatus(int $status): string
    {
        return $this->getDepositStatuses()[$status] ?? '';
    }

    public function getWithdrawStatus(int $status): string
    {
        return $this->getWithdrawStatuses()[$status] ?? '';
    }
}
