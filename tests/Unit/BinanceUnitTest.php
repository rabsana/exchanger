<?php

namespace Rabsana\Exchanger\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exchangers\Binance;
use Rabsana\Exchanger\Tests\TestCase;

class BinanceUnitTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        Config::set("rabsana-exchanger.exchanger", 'binance');
        Config::set("rabsana-exchanger.binance.apiKey", 'aYqVpVRUbVziTWD4KcHttezRc8RKtUIRDABr5YgoG2BxP7RG45gFIdB6LnPvmjJr');
        Config::set("rabsana-exchanger.binance.secretKey", 'Fk0kCXVxwkUh3na4P1hHdGrMv69yB3KdH05MRsLBLN0U7PSFVP1NJHdVdj52vAve');
    }

    public function test_the_binance_class_binded()
    {
        $this->assertInstanceOf(Binance::class, app(Exchanger::class));
    }

    // public function test_get_validations_method()
    // {
    //     dd(app(Exchanger::class)->getValidations(['BTCUSDT'])->getData());
    // }

    // public function test_get_prices_method()
    // {
    //     dd(app(Exchanger::class)->getPrices(["btcusdt",'ethusdt']));
    // }

    // public function test_get_balances_method()
    // {
    //     dd(app(Exchanger::class)->getBalances(["btc", 'eth', 'bnb', 'trx']));
    // }

    // public function test_get_networks_method()
    // {
    //     dd(app(Exchanger::class)->getNetworks(["btc", 'bnb']));
    // }

    // public function test_get_coin_deposit_address_method()
    // {
    //     dd(app(Exchanger::class)->getCoinDepositAddress("BNB" , "BSC"));
    // }

    // public function test_get_coins_method()
    // {
    //     dd(app(Exchanger::class)->getCoins(["BNB", "BTC", "ETH"])->getData());
    // }

    // public function test_get_deposit_history_method()
    // {
    //     dd(app(Exchanger::class)->getDepositHistory()->getData());
    // }

    // public function test_get_withdraw_history_method()
    // {
    //     dd(app(Exchanger::class)->getWithdrawHistory()->getData());
    // }

    // public function test_buy_market_method()
    // {
    //     dd(app(Exchanger::class)->buyMarket("TRXUSDT", 115)->getData());
    // }

    // public function test_sell_market_method()
    // {
    //     dd(app(Exchanger::class)->sellMarket("TRXUSDT", 115)->getData());
    // }

    // public function test_get_exchanger_coin_method()
    // {
    //     dd(app(Exchanger::class)->getExchangerCoin()->getData());
    // }

    // public function test_prepare_quantity_to_order_method()
    // {
    //     $this->assertEquals(app(Exchanger::class)->prepareQuantityToOrder(200, 'TRXUSDT', 'buy'), '200.2');
    //     $this->assertEquals(app(Exchanger::class)->prepareQuantityToOrder(0.5, 'BTCUSDT', 'sell'), '0.5005');
    // }
}
