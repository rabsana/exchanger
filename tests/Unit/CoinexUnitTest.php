<?php

namespace Rabsana\Exchanger\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exchangers\Coinex;
use Rabsana\Exchanger\Tests\TestCase;

class CoinexUnitTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        Config::set("rabsana-exchanger.exchanger", 'coinex');
        Config::set("rabsana-exchanger.coinex.accessId", 'B4947144852C4069BAE5A7E3AC56B97C');
        Config::set("rabsana-exchanger.coinex.secretKey", '13FF57F386BC0E67C089525565732E906576124DBDAD5723');
    }

    public function test_the_coinex_class_binded()
    {
        $this->assertInstanceOf(Coinex::class, app(Exchanger::class));
    }

    // public function test_get_coins_method()
    // {
    //     dd(app(Exchanger::class)->getCoins(["BNB", "BTC", "ETH" , "USDT"])->getData());
    // }

    // public function test_get_validations_method()
    // {
    //     dd(app(Exchanger::class)->getValidations(['BTCUSDT'])->getData());
    // }

    // public function test_get_prices_method()
    // {
    //     dd(app(Exchanger::class)->getPrices(["btcusdt", 'ethusdt', 'usdtusdt', 'bnbusdt']));
    // }

    // public function test_get_balances_method()
    // {
    //     dd(app(Exchanger::class)->getBalances(["btc", 'eth', 'bnb', 'trx' , 'usdt']));
    // }

    // public function test_get_networks_method()
    // {
    //     dd(app(Exchanger::class)->getNetworks(["btc", 'bnb']));
    // }

    // public function test_get_coin_deposit_address_method()
    // {
    //     dd(app(Exchanger::class)->getCoinDepositAddress("XLM", "XLM"));
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
    //         1,
    //         'CSC',
    //         '0x99b795d2fafaa174da3e5c6a4ea722d25fab38ed'
    //     )->getData());
    // }

    // public function test_buy_market_method()
    // {
    //     dd(app(Exchanger::class)->buyMarket("TRXUSDT", 50)->getData());
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
    //     dd(app(Exchanger::class)->prepareQuantityToOrder(50 , 'TRXUSDT' , 'buy'));
    //     $this->assertEquals(app(Exchanger::class)->prepareQuantityToOrder(200, 'TRXUSDT', 'buy'), '200.2');
    //     $this->assertEquals(app(Exchanger::class)->prepareQuantityToOrder(0.5, 'BTCUSDT', 'sell'), '0.5005');
    // }
}
