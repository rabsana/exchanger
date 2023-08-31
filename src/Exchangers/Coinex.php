<?php

namespace Rabsana\Exchanger\Exchangers;

use Exception;
use Illuminate\Support\Facades\Log;
use Rabsana\Core\Support\Facades\Math;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exceptions\ExchangerException;
use Rabsana\Exchanger\Exchangers\Libs\CoinexLib;
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
use Rabsana\Exchanger\Responses\WithdrawCoinResponse;
use Rabsana\Exchanger\Responses\WithdrawHistoryResponse;
use Rabsana\Exchanger\Traits\HttpTrait;

class Coinex extends CoinexLib implements Exchanger
{
    use HttpTrait;

    public function getCoins(array $coins = []): CoinResponse
    {
        try {

            $coinsInfo = $this->httpRequest("v1/market/info");

            $coinsInfo = collect($coinsInfo['data'])->values()->all();

            $coinsInfo = collect($coinsInfo)->where('pricing_name', 'USDT')->all();

            $coinsInfo[] = [
                "name" => "USDTUSDT",
                "min_amount" => "10.0000",
                "maker_fee_rate" => "0.002",
                "taker_fee_rate" => "0.002",
                "pricing_name" => "USDT",
                "pricing_decimal" => 4,
                "trading_name" => "USDT",
                "trading_decimal" => 4
            ];

            if (!empty($coins)) {
                $coinsInfo = collect($coinsInfo)->whereIn('trading_name', array_map("strtoupper", $coins))->all();
            }

            $response = [];
            foreach ($coinsInfo as $coin) {
                $response[] = [
                    'coin'      => $coin['trading_name'],
                    'name'      => $coin['name'],
                ];
            }

            return new CoinResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-COINEX-ERROR-get-coins-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get coins");
        }
    }

    public function getValidations(array $symbols = []): ValidationResponse
    {
        try {

            $coinsInfo = $this->httpRequest("v1/market/info");

            $coinsInfo = collect($coinsInfo['data'])->values()->all();

            $coinsInfo = collect($coinsInfo)->where('pricing_name', 'USDT')->all();

            $coinsInfo[] = [
                "name" => "USDTUSDT",
                "min_amount" => "10.0000",
                "maker_fee_rate" => "0.002",
                "taker_fee_rate" => "0.002",
                "pricing_name" => "USDT",
                "pricing_decimal" => 4,
                "trading_name" => "USDT",
                "trading_decimal" => 4
            ];

            if (!empty($symbols)) {
                $coinsInfo = collect($coinsInfo)->whereIn('name', array_map("strtoupper", $symbols))->all();
            }

            foreach ($coinsInfo as $coin) {

                $response[] = [
                    'symbol'        => $coin['name'],

                    'minPrice'      => 0,
                    'maxPrice'      => 0,
                    'stepPrice'     => Math::convertScientificNumber(1 / (10 ** $coin['pricing_decimal'])),

                    'minQty'        => $coin['min_amount'],
                    'maxQty'        => 0,
                    'stepQty'       => Math::convertScientificNumber(1 / (10 ** $coin['trading_decimal'])),

                    'minQuote'      => 0,
                    'maxQuote'      => 0,
                    'stepQuote'     => 0,

                    'minNotional'   => 0,
                ];
            }

            return new ValidationResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-COINEX-ERROR-get-validations-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get validations");

            //
        }
    }

    public function getPrices(array $symbols = []): PriceResponse
    {
        try {

            $prices = $this->getRequest("https://www.coinex.com/res/market/usd/exchange/rate");

            $response = [];
            foreach ($prices['data'] as $coin => $price) {

                // filter the response
                if (!empty($symbols) && !in_array($coin . "USDT", array_map("strtoupper", $symbols))) {
                    continue;
                }

                $response[] = [
                    'symbol'               => $coin . "USDT",
                    'price'                => $price,
                    'pricePrettified'      => Math::numberFormat($price)
                ];
            }


            return new PriceResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-COINEX-ERROR-get-prices-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get prices");

            //
        }
    }

    public function getBalances(array $coins = []): BalanceResponse
    {
        try {

            $coins = $this->getCoins($coins)->getData();
            $balances = $this->httpRequest("v1/balance/info");

            $response = [];
            foreach ($coins as $coin) {

                $balance = 0;

                foreach ($balances['data'] as $key => $item) {

                    if ($key == $coin['coin']) {
                        $balance = $item['available'] ?? 0;
                        break;
                    }
                }


                $response[] = [
                    'coin'              => $coin['coin'],
                    'balance'           => Math::convertScientificNumber($balance),
                    'balancePrettified' => Math::numberFormat((float) $balance)
                ];
            }

            return new BalanceResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-COINEX-PACKAGE-ERROR-get-balances-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get balances");

            //
        }
    }

    public function getNetworks(array $coins = []): NetworkResponse
    {
        try {

            // get the networks from web service
            $networks = $this->httpRequest("v1/common/asset/config");

            // remove keys
            $networks = collect($networks['data'])->values()->all();

            // filter them
            if (!empty($coins)) {
                $networks = collect($networks)->whereIn('asset', array_map("strtoupper", $coins))->all();
            }

            // group them with asset
            $networks = collect($networks)->groupBy('asset')->all();

            // map them to response array
            $response = [];
            foreach ($networks as $coin => $network) {

                $networkList = [];
                foreach ($network as $networkKey => $item) {
                    $networkList[] = [
                        'network'           => $item['chain'],
                        'name'              => $item['asset'] . "-" . $item['chain'],
                        'isDefault'         => (int) ($networkKey == 0) ? 1 : 0,
                        'withdrawEnable'    => (int) $item['can_withdraw'],
                        'depositEnable'     => (int) $item['can_deposit'],
                        'addressRegex'      => '',
                        'memoRegex'         => '',
                        'withdrawFee'       => $item['withdraw_tx_fee'],
                        'withdrawMin'       => $item['withdraw_least_amount'],
                        'withdrawMax'       => 0,
                        'description'       => ''
                    ];
                }

                $response[] = [
                    'coin'       => strtoupper($coin),
                    'networks'   => $networkList,
                ];
            }

            return new NetworkResponse($response);
            //

        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-get-networks-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get networks");

            //
        }
    }

    public function getCoinDepositAddress(string $coin, string $network): CoinDepositAddressResponse
    {
        try {
            $addressInfo = $this->httpRequest("v1/balance/deposit/address/{$coin}", [
                'smart_contract_name'       => $network
            ]);

            $address = $addressInfo['data']['coin_address'];
            $memo = '';
            if ($memoPosition = strpos($address, ":")) {
                $memo = substr($address, $memoPosition + 1);
                $address = str_replace(":" . $memo, "", $address);
            }

            return new CoinDepositAddressResponse([
                'coin'      => $coin,
                'address'   => $address,
                'memo'      => $memo
            ]);

            //
        } catch (Exception $e) {


            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-get-coin-deposit-address-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get coin deposit address");

            //

        }
    }

    public function getExchangerCoin(): ExchangerCoinResponse
    {
        return new ExchangerCoinResponse([
            'coin' => 'CET'
        ]);
    }

    public function getDepositHistory(array $params = []): DepositHistoryResponse
    {
        try {
            $filters = $this->createHistoryFilters($params);
            $histories = $this->httpRequest("v1/balance/coin/deposit", $filters);

            $response = [];
            foreach ($histories['data']['data'] as $history) {
                $response[] = [
                    'coin'                      => $history['coin_type'],
                    'network'                   => $history['smart_contract_name'] ?? '',

                    'amount'                    => $history['amount'],
                    'amountPrettified'          => Math::numberFormat($history['amount']),

                    'status'                    => $history['status'],
                    'statusPrettified'          => $this->getDepositStatus($history['status']),

                    'address'                   => $history['coin_address'] ?? '',
                    'memo'                      => '',

                    'txid'                      => $history['tx_id'] ?? '',
                    'transferType'              => $history['transfer_method'],
                    'transferTypePrettified'    => $this->getTransferType($history['transfer_method']),
                    'createdAt'                 => ($history['create_time']) ? date('Y-m-d H:i:s', $history['create_time']) : ''
                ];
            }

            return new DepositHistoryResponse($response, true);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-get-deposit-history: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get deposit history");

            //
        }
    }

    public function getWithdrawHistory(array $params = []): WithdrawHistoryResponse
    {
        try {
            $filters = $this->createHistoryFilters($params);
            $histories = $this->httpRequest("v1/balance/coin/withdraw", $filters);

            $response = [];
            foreach ($histories['data']['data'] as $history) {
                $response[] = [
                    'coin'                      => $history['coin_type'],
                    'network'                   => $history['smart_contract_name'] ?? '',

                    'amount'                    => $history['actual_amount'],
                    'amountPrettified'          => Math::numberFormat($history['actual_amount']),

                    'status'                    => $history['status'],
                    'statusPrettified'          => $this->getWithdrawStatus($history['status']),

                    'address'                   => $history['coin_address'] ?? '',
                    'txid'                      => $history['tx_id'] ?? '',
                    'id'                        => $history['coin_withdraw_id'] ?? '',

                    'transferType'              => $history['transfer_method'],
                    'transferTypePrettified'    => $this->getTransferType($history['transfer_method']),
                    'transferFee'               => $history['tx_fee'],

                    'createdAt'                 => ($history['create_time']) ? date('Y-m-d H:i:s', $history['create_time']) : ''
                ];
            }

            return new WithdrawHistoryResponse($response, true);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-get-withdraw-history-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get withdraw history");

            //
        }
    }

    public function withdrawCoin(string $coin, float $quantity, string $network, string $address, string $memo = '', bool $internalTransferFeePayerIsUser = true, string $chain = ''): WithdrawCoinResponse
    {
        try {

            $withdrawFee = collect($this->getNetworks([$coin])->getData()[0]['networks'])->where('network', $network)->first()['withdrawFee'] ?? 0;

            $coinAddress = $address;
            if (!empty($memo)) {
                $coinAddress = $coinAddress . ":" . $memo;
            }

            $withdraw = $this->setMethod("POST")
                ->httpRequest(
                    "v1/balance/coin/withdraw",
                    [
                        'coin_type'             => $coin,
                        'smart_contract_name'   => $network,
                        'coin_address'          => $coinAddress,
                        'transfer_method'       => 'onchain',
                        'actual_amount'         => Math::subtract((float) $quantity, (float) $withdrawFee)
                    ]
                );

            if ($withdraw['code'] != 0) {
                throw new Exception(json_encode($withdraw));
            }

            $response = [
                'withdraw_id' => $withdraw['data']['coin_withdraw_id'] ?? ''
            ];

            return new WithdrawCoinResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-withdraw-coin-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in withdraw coin method: " . $e->getMessage());

            //
        }
    }


    public function buyMarket(string $symbol, float $qty): BuyMarketResponse
    {
        try {

            $symbolPrice = $this->getPrices([$symbol])->getData()[0]['price'];

            $buy = $this->setMethod("POST")
                ->httpRequest(
                    "v1/order/market",
                    [
                        'amount'    => Math::multiply((float) $qty, (float) $symbolPrice),
                        'market'    => $symbol,
                        'type'      => 'buy'
                    ]
                )['data'];

            $commissionAsset = (empty($buy['fee_asset']) || is_null($buy['fee_asset'])) ? "USDT" : $buy['fee_asset'];
            $commission = ($commissionAsset == 'USDT') ? $buy['money_fee'] : $buy['asset_fee'];

            $response = [
                'symbol'                        => $buy['market'],
                'side'                          => $buy['type'],
                'type'                          => $buy['order_type'],
                'status'                        => $buy['status'],
                'statusPrettified'              => $buy['status'],

                'qty'                           => $qty,
                'qtyPrettified'                 => Math::numberFormat((float) $qty),

                'executedQty'                   => $buy['deal_amount'],
                'executedQtyPrettified'         => Math::numberFormat((float) $buy['deal_amount']),

                'executedQuoteQty'              => $buy['deal_money'],
                'executedQuoteQtyPrettified'    => Math::numberFormat((float) $buy['deal_money']),

                'price'                         => $buy['price'],
                'pricePrettified'               => Math::numberFormat((float) $buy['price']),

                'commissionAsset'               => $commissionAsset,
                'commission'                    => $commission,
                'commissionPrettified'          => Math::numberFormat((float) $commission),

                'createdAt'                     => ($buy['create_time']) ? date('Y-m-d H:i:s', $buy['create_time']) : '',
            ];

            return new BuyMarketResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-buy-market-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in buy market: " . $e->getMessage());

            //
        }
    }


    public function sellMarket(string $symbol, float $qty): SellMarketResponse
    {
        try {

            $sell = $this->setMethod("POST")
                ->httpRequest(
                    "v1/order/market",
                    [
                        'amount'    => $qty,
                        'market'    => $symbol,
                        'type'      => 'sell'
                    ]
                )['data'];

            $commissionAsset = (empty($sell['fee_asset']) || is_null($sell['fee_asset'])) ? "USDT" : $sell['fee_asset'];
            $commission = ($commissionAsset == 'USDT') ? $sell['money_fee'] : $sell['asset_fee'];

            $response = [
                'symbol'                        => $sell['market'],
                'side'                          => $sell['type'],
                'type'                          => $sell['order_type'],
                'status'                        => $sell['status'],
                'statusPrettified'              => $sell['status'],

                'qty'                           => $qty,
                'qtyPrettified'                 => Math::numberFormat((float) $qty),

                'executedQty'                   => $sell['deal_amount'],
                'executedQtyPrettified'         => Math::numberFormat((float) $sell['deal_amount']),

                'executedQuoteQty'              => $sell['deal_money'],
                'executedQuoteQtyPrettified'    => Math::numberFormat((float) $sell['deal_money']),

                'price'                         => $sell['price'],
                'pricePrettified'               => Math::numberFormat((float) $sell['price']),

                'commissionAsset'               => $commissionAsset,
                'commission'                    => $commission,
                'commissionPrettified'          => Math::numberFormat((float) $commission),

                'createdAt'                     => ($sell['create_time']) ? date('Y-m-d H:i:s', $sell['create_time']) : '',
            ];

            return new SellMarketResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-sell-market-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in sell market: " . $e->getMessage());


            //
        }
    }

    public function prepareQuantityToOrder(float $quantity, string $symbol, string $side): string
    {
        // check the side is valid
        if (!in_array(strtoupper($side), ['BUY', 'SELL'])) {
            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-the-side-is-invalid-in-prepare-quantity-to-order-method: " . $side);
            throw new ExchangerException("The side is invalid. the valid side is buy or sell");
        }

        try {

            $commission = $this->httpRequest("v1/account/market/fee", ['market' => $symbol])['data'][(strtoupper($side) == 'BUY') ? 'taker' : 'maker'];
            $stepSize = $this->getValidations([$symbol])->getData()[0]['stepQty'];

            if (strtoupper($side) == 'BUY') {

                $symbolPrice = $this->getPrices([$symbol])->getData()[0]['price'];
                $amount = Math::multiply((float) $quantity, (float) $symbolPrice);
                $feeAmount = Math::multiply((float) $amount, (float) $commission);
                $raito = Math::divide((float) $feeAmount, (float) $symbolPrice);
                $result = Math::add((float) $quantity, (float) $raito);



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
                'symbolPrice'   => $symbolPrice ?? '',
                'quantity'      => $quantity ?? '',
                'symbol'        => $symbol ?? '',
                'side'          => $side ?? '',
                'commission'    => $commission ?? '',
                'stepSize'      => $stepSize ?? '',
                'raito'         => $raito ?? '',
                'result'        => $result ?? '',
            ]));

            return Math::setParams([
                'precision'  => Math::decimalPlaceNumber((float) $stepSize)
            ])
                ->number($result);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-COINEX-ERROR-prepare-quantity-to-order-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in prepare quantity to order method");


            //
        }
    }

    public function depositIsCompleted($status): bool
    {
        return (bool) (strtolower($status) == 'finish');
    }

    public function orderIsCompleted($status): bool
    {
        return (bool) (strtolower($status) == 'done');
    }

    public function getTransferTypes(): array
    {
        return [
            'onchain'       => 'On Chain',
            'local'         => 'Local'
        ];
    }

    public function getDepositStatuses(): array
    {
        return [
            'processing'    => 'Processing',
            'confirming'    => 'Confirming',
            'cancel'        => 'Cancel',
            'finish'        => 'Finish'
        ];
    }

    public function getWithdrawStatuses(): array
    {
        return [
            'audit'         => 'Audit',
            'pass'          => 'Pass',
            'processing'    => 'Processing',
            'confirming'    => 'Confirming',
            'not_pass'      => 'Not Pass',
            'cancel'        => 'Cancel',
            'finish'        => 'Finish',
            'fail'          => 'Fail',
            'to_confirm'    => 'To Confirm'
        ];
    }

    public function getTransferType($type): string
    {
        return $this->getTransferTypes()[$type] ?? '';
    }

    public function getDepositStatus($status): string
    {
        return $this->getDepositStatuses()[$status] ?? '';
    }

    public function getWithdrawStatus($status): string
    {
        return $this->getWithdrawStatuses()[$status] ?? '';
    }


    //
}
