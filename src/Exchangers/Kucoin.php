<?php

namespace Rabsana\Exchanger\Exchangers;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use KuCoin\SDK\Auth;
use KuCoin\SDK\PrivateApi\Account;
use KuCoin\SDK\PrivateApi\Deposit;
use KuCoin\SDK\PrivateApi\Order;
use KuCoin\SDK\PrivateApi\TradeFee;
use KuCoin\SDK\PrivateApi\Withdrawal;
use KuCoin\SDK\PublicApi\Currency;
use KuCoin\SDK\PublicApi\Symbol;
use Rabsana\Core\Support\Facades\Math;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exceptions\ExchangerException;
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

class Kucoin implements Exchanger
{

    use HttpTrait;

    public function getCoins(array $coins = []): CoinResponse
    {
        try {
            $coinsInfo = (new Currency())->getList();

            if (!empty($coins)) {
                $coinsInfo = collect($coinsInfo)->whereIn('currency', array_map("strtoupper", $coins))->all();
            }

            $response = [];
            foreach ($coinsInfo as $coin) {
                $response[] = [
                    'coin'      => $coin['currency'],
                    'name'      => $coin['fullName'],
                ];
            }

            return new CoinResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-coins-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get coins");
        }
    }

    public function getValidations(array $symbols = []): ValidationResponse
    {
        try {

            $symbolsInfo = (new Symbol())->getList();

            $symbolsInfo[] = [
                "symbol"            => "USDT-USDT",
                "name"              => "USDT-USDT",
                "baseCurrency"      => "USDT",
                "quoteCurrency"     => "USDT",
                "feeCurrency"       => "USDT",
                "market"            => "USDT",
                "baseMinSize"       => "0.01",
                "quoteMinSize"      => "0.01",
                "baseMaxSize"       => "10000000000",
                "quoteMaxSize"      => "10000000000",
                "baseIncrement"     => "0.0001",
                "quoteIncrement"    => "0.0001",
                "priceIncrement"    => "0.0001",
                "priceLimitRate"    => "0.1",
                "isMarginEnabled"   => false,
                "enableTrading"     => true
            ];

            $symbolsInfo = collect($symbolsInfo)->where('quoteCurrency', 'USDT')->all();

            if (!empty($symbols)) {

                $symbols = array_map(function ($symbol) {
                    return (substr($symbol, -4) == 'USDT') ? substr_replace($symbol, '', -4) : $symbol;
                }, $symbols);

                $symbolsInfo = collect($symbolsInfo)->whereIn('baseCurrency', array_map("strtoupper", $symbols))->all();

                //
            }

            $response = [];
            foreach ($symbolsInfo as $item) {

                $response[] = [
                    'symbol'        => str_replace('-', '', $item['symbol']),

                    'minPrice'      => $item['priceIncrement'],
                    'maxPrice'      => 0,
                    'stepPrice'     => $item['priceIncrement'],

                    'minQty'        => $item['baseMinSize'],
                    'maxQty'        => $item['baseMaxSize'],
                    'stepQty'       => $item['baseIncrement'],

                    'minQuote'      => $item['quoteMinSize'],
                    'maxQuote'      => $item['quoteMaxSize'],
                    'stepQuote'     => $item['quoteIncrement'],

                    'minNotional'   => 0,
                ];
            }

            return new ValidationResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-validations-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get validations");

            //
        }
    }

    public function getPrices(array $symbols = []): PriceResponse
    {
        try {

            // get prices
            $prices = (new Symbol())->getAllTickers()['ticker'];

            // add usdt price base on USDT-USDC symbol
            $prices[] = [
                "symbol"        => "USDT-USDT",
                "symbolName"    => "USDT-USDT",
                "buy"           => '1',
                "sell"          => '1',
            ];

            // filter prices
            $prices = collect($prices)->filter(function ($value, $key) use ($symbols) {

                $checkQuote = (substr($value['symbol'], -5) == '-USDT');
                $checkSymbols = (empty($symbols)) ? TRUE : in_array(str_replace("-", '', $value['symbol']), array_map("strtoupper", $symbols));

                return ($checkQuote && $checkSymbols);

                //
            })
                ->all();

            $response = [];
            foreach ($prices as $price) {
                if($price['buy'] == null) continue;
                $response[] = [
                    'symbol'            => str_replace("-", '', $price['symbol']),
                    'price'             => $price['buy'],
                    'pricePrettified'   => Math::numberFormat($price['buy'])
                ];
            }

            return new PriceResponse($response);

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-prices-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get prices");

            //
        }
    }

    public function getBalances(array $coins = []): BalanceResponse
    {
        try {

            $balances = (new Account($this->getAuth()))->getList();
            $allCoins = $this->getCoins($coins)->getData();
            $response = [];

            foreach ($allCoins as $item) {

                $response[$item['coin']] = [
                    'coin'                      => $item['coin'],
                    'balance'                   => '0',
                    'balancePrettified'         => '0'
                ];

                foreach ($balances as $secondItem) {

                    if (
                        $item['coin'] == $secondItem['currency'] &&
                        in_array($secondItem['type'], ['trade', 'main'])
                    ) {

                        $balance = Math::add((float) $response[$item['coin']]['balance'], (float) $secondItem['available']);

                        $response[$item['coin']]['balance'] = $balance;
                        $response[$item['coin']]['balancePrettified'] = Math::numberFormat($balance);


                        //
                    }
                }
            }


            return new BalanceResponse(collect($response)->values()->all());

            //
        } catch (Exception $e) {

            Log::error("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-balances-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get balances");

            //
        }
    }

    public function getNetworks(array $coins = []): NetworkResponse
    {
        try {

            $networks = $this->getRequest("https://www.kucoin.com/_api/currency/currency/chain-info")['data'];

            if (!empty($coins)) {
                $networks = collect($networks)->whereIn('currency', array_map("strtoupper", $coins))->all();
            }

            $networks = collect($networks)->groupBy('currency')->all();

            $response = [];
            foreach ($networks as $coin => $network) {

                $networkList = [];
                foreach ($network as $key => $item) {

                    if (!isset($item['withdrawMinFee'])) {
                        $info = (new Withdrawal($this->getAuth()))->getQuotas($coin, ($this->coinHasMultipleNetworks($coin)) ? $item['chain'] : null);
                        $item['isWithdrawEnabled'] = @$info['isWithdrawEnabled'];
                        $item['isDepositEnabled'] = @$info['isDepositEnabled'];
                        $item['withdrawMinFee'] = $info['withdrawMinFee'] ?? 0;
                    }

                    $networkList[] = [
                        'network'           => $item['chainName'] ?? '',
                        'name'              => $item['chain'] ?? '',
                        'isDefault'         => (int) ($key == 0) ? 1 : 0,
                        'withdrawEnable'    => (int) (is_null(@$item['isWithdrawEnabled']) || $item['isWithdrawEnabled'] != 'true') ? 0 : 1,
                        'depositEnable'     => (int) (is_null(@$item['isDepositEnabled']) || $item['isDepositEnabled'] != 'true') ? 0 : 1,
                        'addressRegex'      => '',
                        'memoRegex'         => '',
                        'withdrawFee'       => $item['withdrawMinFee'] ?? 0,
                        'withdrawMin'       => $item['withdrawMinSize'] ?? 0,
                        'withdrawMax'       => 0,
                        'description'       => @$item['withdrawDisabledTip'] ?? ''
                    ];
                }

                $response[] = [
                    'coin'       => $coin,
                    'networks'   => $networkList,
                ];
            }

            return new NetworkResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-networks-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get networks");
        }
    }

    public function getExchangerCoin(): ExchangerCoinResponse
    {
        return new ExchangerCoinResponse([
            'coin' => 'KCS'
        ]);
    }

    public function getCoinDepositAddress(string $coin, string $network): CoinDepositAddressResponse
    {
        try {
            $createAddress = (new Deposit($this->getAuth()))->createAddress($coin, ($this->coinHasMultipleNetworks($coin)) ? $this->networkPrettified($network) : null);
        } catch (Exception $e) {
        }

        try {

            $addresses = (new Deposit($this->getAuth()))->getAddresses($coin);

            $address = collect($addresses)->where('chain', $network)->first();

            return new CoinDepositAddressResponse([
                'coin'      => $coin,
                'address'   => $address['address'],
                'memo'      => $address['memo'],
            ]);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-coins-deposit-address-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get coin deposit address");

            //
        }
    }

    public function getDepositHistory(array $params = []): DepositHistoryResponse
    {
        try {

            $filters = $this->createHistoryFilters($params);
            $histories = (new Deposit($this->getAuth()))->getDeposits($filters)['items'];

            $response = [];
            foreach ($histories as $history) {

                if ($atSignPos = strpos($history['walletTxId'], '@')) {
                    $txid = substr_replace($history['walletTxId'], '', $atSignPos);
                } else {
                    $txid = $history['walletTxId'];
                }

                $response[] = [
                    'coin'                      => $history['currency'],
                    'network'                   => '',
                    'amount'                    => $history['amount'],
                    'amountPrettified'          => Math::numberFormat($history['amount']),
                    'status'                    => $history['status'],
                    'statusPrettified'          => $this->getDepositStatus($history['status']),
                    'address'                   => $history['address'] ?? '',
                    'memo'                      => $history['memo'] ?? '',
                    'txid'                      => $txid,
                    'transferType'              => $history['isInner'],
                    'transferTypePrettified'    => $this->getTransferType($history['isInner']),
                    'createdAt'                 => ($history['createdAt']) ? date('Y-m-d H:i:s', $history['createdAt'] / 1000) : ''
                ];
            }

            return new DepositHistoryResponse($response, true);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-deposit-history: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get deposit history");

            //
        }
    }

    public function getWithdrawHistory(array $params = []): WithdrawHistoryResponse
    {
        try {

            $filters = $this->createHistoryFilters($params);
            $histories = (new Withdrawal($this->getAuth()))->getList($filters)['items'];

            $response = [];
            foreach ($histories as $history) {

                if ($atSignPos = strpos($history['walletTxId'], '@')) {
                    $txid = substr_replace($history['walletTxId'], '', $atSignPos);
                } else {
                    $txid = $history['walletTxId'];
                }

                $response[] = [
                    'coin'                      => $history['currency'],
                    'network'                   => '',
                    'amount'                    => $history['amount'],
                    'amountPrettified'          => Math::numberFormat($history['amount']),
                    'status'                    => $history['status'],
                    'statusPrettified'          => $this->getWithdrawStatus($history['status']),
                    'address'                   => $history['address'] ?? '',
                    'txid'                      => $txid,
                    'id'                        => $history['id'] ?? '',
                    'transferType'              => $history['isInner'],
                    'transferTypePrettified'    => $this->getTransferType($history['isInner']),
                    'transferFee'               => $history['fee'],
                    'createdAt'                 => ($history['createdAt']) ? date('Y-m-d H:i:s', $history['createdAt'] / 1000) : ''
                ];
            }

            return new WithdrawHistoryResponse($response, true);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-get-withdraw-history-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in get withdraw history");

            //
        }
    }

    public function withdrawCoin(string $coin, float $quantity, string $network, string $address, string $memo = '', bool $internalTransferFeePayerIsUser = true, string $chain = ''): WithdrawCoinResponse
    {
        try {

            // try to transfer quantity from trade to main account
            try {
                $transfer = (new Account($this->getAuth()))->innerTransferV2((string) Str::uuid(), $coin, 'trade', 'main', $quantity);
            } catch (Exception $e) {
            }
            
            if(strtolower($coin) == 'usdt' && empty($chain)){
                $chain = 'trx';
            }

            $params = [
                'currency'      => $coin,
                'address'       => $address,
                'amount'        => $quantity,
                'chain'         => $chain,
                'feeDeductType' => 'INTERNAL',
            ];

            if(empty($params['chain'])){
                unset($params['chain']);
            }

            if (!empty($memo)) {
                $params['memo'] = $memo;
            }

            /*if ($this->coinHasMultipleNetworks($coin)) {
                $params['chain'] = $this->networkPrettified($network);
            }*/

            $withdraw = (new Withdrawal($this->getAuth()))->apply($params);

            $response = [
                'withdraw_id' => $withdraw['withdrawalId']
            ];

            return new WithdrawCoinResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-withdraw-coin-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in withdraw coin method: " . $e->getMessage());

            //
        }
    }

    public function buyMarket(string $symbol, float $qty): BuyMarketResponse
    {
        try {

            $symbolPrice = $this->getPrices([$symbol])->getData()[0]['price'];
            $stepQuote = $this->getValidations([$symbol])->getData()[0]['stepQuote'];

            $funds = Math::setParams(['precision' => Math::decimalPlaceNumber((float) $stepQuote)])
                ->number(Math::multiply((float) $qty, (float) $symbolPrice));

            // try to transfer funds from main account to trade account
            try {
                $transfer = (new Account($this->getAuth()))->innerTransferV2((string) Str::uuid(), 'USDT', 'main', 'trade', $funds);
            } catch (Exception $e) {
            }

            // place the buy order
            $buy = (new Order($this->getAuth()))->create([
                'clientOid' => (string) Str::uuid(),
                'side'      => 'buy',
                'symbol'    => substr_replace($symbol, '', -4) . '-USDT',
                'type'      => 'market',
                'funds'     => $funds
            ]);

            // get the order detail
            $order = (new Order($this->getAuth()))->getDetail($buy['orderId']);

            $response = [
                'symbol'                        => $symbol,
                'side'                          => $order['side'],
                'type'                          => $order['type'],
                'status'                        => $order['isActive'],
                'statusPrettified'              => ($order['isActive']) ? "Pending" : 'Filled or Cancelled',

                'qty'                           => $qty,
                'qtyPrettified'                 => Math::numberFormat((float) $qty),

                'executedQty'                   => $order['dealSize'],
                'executedQtyPrettified'         => Math::numberFormat((float) $order['dealSize']),

                'executedQuoteQty'              => $order['dealFunds'],
                'executedQuoteQtyPrettified'    => Math::numberFormat((float) $order['dealFunds']),

                'price'                         => $order['price'],
                'pricePrettified'               => Math::numberFormat((float) $order['price']),

                'commissionAsset'               => $order['feeCurrency'],
                'commission'                    => $order['fee'],
                'commissionPrettified'          => Math::numberFormat((float) $order['fee']),

                'createdAt'                     => ($order['createdAt']) ? date('Y-m-d H:i:s', $order['createdAt'] / 1000) : '',
            ];

            return new BuyMarketResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-buy-market-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in buy market: " . $e->getMessage());

            //
        }
    }


    public function sellMarket(string $symbol, float $qty): SellMarketResponse
    {
        try {
            // try to transfer size from main account to trade account
            try {
                $transfer = (new Account($this->getAuth()))->innerTransferV2((string) Str::uuid(), substr_replace($symbol, '', -4), 'main', 'trade', $qty);
            } catch (Exception $e) {
            }

            // place the sell order
            $sell = (new Order($this->getAuth()))->create([
                'clientOid' => (string) Str::uuid(),
                'side'      => 'sell',
                'symbol'    => substr_replace($symbol, '', -4) . '-USDT',
                'type'      => 'market',
                'size'      => $qty
            ]);

            // get the order detail
            $order = (new Order($this->getAuth()))->getDetail($sell['orderId']);

            $response = [
                'symbol'                        => $symbol,
                'side'                          => $order['side'],
                'type'                          => $order['type'],
                'status'                        => $order['isActive'],
                'statusPrettified'              => ($order['isActive']) ? "Pending" : 'Filled or Cancelled',

                'qty'                           => $qty,
                'qtyPrettified'                 => Math::numberFormat((float) $qty),

                'executedQty'                   => $order['dealSize'],
                'executedQtyPrettified'         => Math::numberFormat((float) $order['dealSize']),

                'executedQuoteQty'              => $order['dealFunds'],
                'executedQuoteQtyPrettified'    => Math::numberFormat((float) $order['dealFunds']),

                'price'                         => $order['price'],
                'pricePrettified'               => Math::numberFormat((float) $order['price']),

                'commissionAsset'               => $order['feeCurrency'],
                'commission'                    => $order['fee'],
                'commissionPrettified'          => Math::numberFormat((float) $order['fee']),

                'createdAt'                     => ($order['createdAt']) ? date('Y-m-d H:i:s', $order['createdAt'] / 1000) : '',
            ];

            return new SellMarketResponse($response);

            //
        } catch (Exception $e) {

            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-sell-market-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in sell market: " . $e->getMessage());

            //
        }
    }

    public function prepareQuantityToOrder(float $quantity, string $symbol, string $side): string
    {
        // check the side is valid
        if (!in_array(strtoupper($side), ['BUY', 'SELL'])) {
            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-the-side-is-invalid-in-prepare-quantity-to-order-method: " . $side);
            throw new ExchangerException("The side is invalid. the valid side is buy or sell");
        }

        $commission = (new TradeFee($this->getAuth()))->getTradeFees([substr_replace($symbol, '', -4) . '-USDT'])[0][(strtoupper($side) == 'BUY') ? 'takerFeeRate' : 'makerFeeRate'];
        $validation = $this->getValidations([$symbol])->getData()[0];

        try {

            if (strtoupper($side) == 'BUY') {

                $symbolPrice = $this->getPrices([$symbol])->getData()[0]['price'];
                $amount = Math::multiply((float) $quantity, (float) $symbolPrice);
                $feeAmount = Math::multiply((float) $amount, (float) $commission);
                $raito = Math::divide((float) $feeAmount, (float) $symbolPrice);
                $result = Math::add((float) $quantity, (float) $raito);

                $result = Math::setParams([
                    'precision'      => Math::decimalPlaceNumber((float) $validation['stepQuote'])
                ])->number((float) $result);


                //
            } else {

                $result = Math::setParams([
                    'precision'      => Math::decimalPlaceNumber((float) $validation['stepQty'])
                ])->number((float) $quantity);

                //
            }

            Log::debug("prepareQuantityToOrder method: " . json_encode([
                'symbolPrice'   => $symbolPrice ?? '',
                'quantity'      => $quantity ?? '',
                'symbol'        => $symbol ?? '',
                'side'          => $side ?? '',
                'commission'    => $commission ?? '',
                'validation'    => $validation ?? '',
                'raito'         => $raito ?? '',
                'result'        => $result ?? '',
            ]));

            return $result;

            //
        } catch (Exception $e) {


            Log::debug("EXCHANGER-PACKAGE-KUCOIN-ERROR-prepare-quantity-to-order-method: " . $e->getMessage());
            throw new ExchangerException("something went wrong in prepare quantity to order method");


            //
        }
    }


    public function depositIsCompleted($status): bool
    {
        return (bool) (strtoupper($status) == 'SUCCESS');
    }

    public function orderIsCompleted($status): bool
    {
        return (bool) ($status == false);
    }

    public function getTransferTypes(): array
    {
        return [
            false   => 'External',
            true    => 'Internal'
        ];
    }

    public function getDepositStatuses(): array
    {
        return [
            'PROCESSING'    => 'Processing',
            'SUCCESS'       => 'Success',
            'FAILURE'       => 'Failure',
        ];
    }

    public function getWithdrawStatuses(): array
    {
        return [
            'PROCESSING'        => 'Processing',
            'SUCCESS'           => 'Success',
            'FAILURE'           => 'Failure',
            'WALLET_PROCESSING' => 'Wallet Processing',
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

    private function getAuth()
    {
        return new Auth(
            Config::get("rabsana-exchanger.kucoin.apiKey"),
            Config::get("rabsana-exchanger.kucoin.secretKey"),
            Config::get("rabsana-exchanger.kucoin.passPhrase"),
            Auth::API_KEY_VERSION_V2
        );
    }

    protected function coinHasMultipleNetworks($coin): bool
    {
        return (bool) in_array($coin, $this->coinsWithMultipleNetworks());
    }

    protected function coinsWithMultipleNetworks(): array
    {
        return [
            "BTC", "USDT", "BCH", "ETH", "HPB", "KCS", "WTC", "AMB", "R", "ENJ", "TEL", "ITC", "CS", "ZIL", "LOOM", "DOCK", "MANA", "IOTX", "OLT", "CPC", "LOC",
            "USDC", "PAX", "SNX", "TUSD", "QKC", "MTV", "CRO", "RFOX", "COTI", "BNB", "BOLT", "CHZ", "ENQ", "ONE", "LUNA", "SDT", "WAX", "NULS", "MAP", "NWC", "AVA",
            "ROAD", "RHOC", "DAG", "STX", "USDN", "COMP", "KAI", "WAVES", "LINK", "CKB", "VELO", "YFI", "UNI", "UOS", "AAVE", "BAT", "UST", "GRT", "SUSHI", "1INCH",
            "MIR", "CRV", "QNT", "TRIAS", "SWINGBY", "AVAX", "ANC", "XCUR", "DODO", "HT", "EQZ", "CWS", "SHIB", "MATIC", "TLOS", "HOTCROSS", "JUP", "AIOZ", "MARSH", "OOE", "DPET", "ODDZ",
            "CTSI", "ALICE", "OPUL", "BAND", "DEXE", "TLM", "SOV", "C98"
        ];
    }

    protected function createHistoryFilters(array $params): array
    {
        $filters = [];

        if (isset($params['coin'])) {
            $filters['currency'] = $params['coin'];
        }

        if (isset($params['offset']) && is_numeric($params['offset'])) {
            $filters['currentPage'] = $params['offset'];
        }

        if (isset($params['limit']) && is_numeric($params['limit'])) {
            $filters['pageSize'] = $params['limit'];
        } else {
            $filters['pageSize'] = 500;
        }

        if (isset($params['status'])) {
            $filters['status'] = $params['status'];
        }

        if (isset($params['fromDate'])) {
            $filters['startAt'] = strtotime($params['fromDate']) * 1000;
        }

        if (isset($params['toDate'])) {
            $filters['endAt'] = strtotime($params['toDate']) * 1000;
        }

        return $filters;
    }

    public function networkPrettified($network)
    {
        if ($network == 'BEP20(BSC)') {
            return 'bsc';
        }

        if ($network == 'HECO') {
            return 'heco';
        }

        if ($network == 'KCC') {
            return 'kcc';
        }

        if ($network == 'Algorand') {
            return 'algo';
        }

        return $network;
    }
}
