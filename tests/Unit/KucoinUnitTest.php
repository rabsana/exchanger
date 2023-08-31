<?php

namespace Rabsana\Exchanger\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exchangers\Kucoin;
use Rabsana\Exchanger\Tests\TestCase;

class KucoinUnitTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        Config::set("rabsana-exchanger.exchanger", 'kucoin');
        // Config::set("rabsana-exchanger.kucoin.apiKey", '6145751778cc190006bd97d6');
        // Config::set("rabsana-exchanger.kucoin.secretKey", '69533ce4-502b-4de3-8adb-4174ca813ea5');
        Config::set("rabsana-exchanger.kucoin.apiKey", '615947ed46e9ac0001f7566d');
        Config::set("rabsana-exchanger.kucoin.secretKey", '7746b73f-4cfe-408d-bd56-610bfeef8a90');
        Config::set("rabsana-exchanger.kucoin.passPhrase", '123456789');
    }

    public function test_the_kucoin_class_binded()
    {
        $this->assertInstanceOf(Kucoin::class, app(Exchanger::class));
    }

    // public function test_get_coins_method()
    // {
    //     dd(app(Exchanger::class)->getCoins(["BNB", "BTC", "ETH", "USDT"])->getData());
    // }

    // public function test_get_validations_method()
    // {
    //     dd(app(Exchanger::class)->getValidations(['BTCUSDT', 'ETHUSDT', 'USDTUSDT'])->getData());
    // }

    // public function test_get_prices_method()
    // {
    //     dd(app(Exchanger::class)->getPrices(["btcusdt", 'ethusdt', 'usdtusdt', 'bnbusdt']));
    // }

    // public function test_get_balances_method()
    // {
    //     dd(app(Exchanger::class)->getBalances(["btc", 'eth', 'bnb', 'trx', 'usdt', 'cake']));
    // }

    // public function test_get_networks_method()
    // {
    //     dd(app(Exchanger::class)->getNetworks(["btc", 'bnb', 'usdt']));
    // }

    // public function test_get_coin_deposit_address_method()
    // {
    //     dd(app(Exchanger::class)->getCoinDepositAddress("USDT", "Algorand"));
    // }

    // public function test_get_deposit_history_method()
    // {
    //     dd(app(Exchanger::class)->getDepositHistory()->getData());
    // }

    // public function test_get_withdraw_history_method()
    // {
    //     dd(app(Exchanger::class)->getWithdrawHistory()->getData());
    // }

    // public function test_withdraw_coin_method()
    // {
    //     dd(app(Exchanger::class)->withdrawCoin(
    //         'USDT',
    //         8,
    //         'TRC20',
    //         'TCzwPis2K2UrFzgPWxCbX4L1rF5BBDgKef'
    //     )->getData());
    // }

    // public function test_buy_market_method()
    // {
    //     dd(app(Exchanger::class)->buyMarket("TRXUSDT", 50.123123123)->getData());
    // }

    // public function test_sell_market_method()
    // {
    //     dd(app(Exchanger::class)->sellMarket("TRXUSDT", 100)->getData());
    // }

    // public function test_get_exchanger_coin_method()
    // {
    //     dd(app(Exchanger::class)->getExchangerCoin()->getData());
    // }

    // public function test_prepare_quantity_to_order_method()
    // {
    //     dd(app(Exchanger::class)->prepareQuantityToOrder(50 , 'TRXUSDT' , 'buy'));
    //     $this->assertEquals(app(Exchanger::class)->prepareQuantityToOrder(200, 'TRXUSDT', 'buy'), '200.2');
    //     $this->assertEquals(app(Exchanger::class)->prepareQuantityToOrder(0.5, 'BTCUSDT', 'sell'), '0.5');
    // }
}
