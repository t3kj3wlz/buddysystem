<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';
require_once __DIR__ . '/stash.php';
require_once __DIR__ . '/inventory.php';

abstract class OrderFactory{
    public static function createOrder($type,$UID = null){
        switch(strtolower($type)){
            case "btc":
                $order = new BtcOrder($UID);
                break;
            default:
                throw new \Exception('Invalid Order Type');
        }
        return $order;
    }
    public static function get($type,$option,$username){
        $order = self::createOrder($type);
        return $order::get($option,$username);
    }
}
abstract class Order implements OrderBehavior{
    
    public $received_date;
    public $strain;
    public $product_amount;
    
    public static function arrive($type,$orderId,$username){
        $order = OrderFactory::createOrder($type,$orderId);
        if($order->received_date != null){
            throw new Exception('Double Arrived Order');
        }
        Inventory::increase($order->strain,$order->product_amount,$username);
        $order->received_date = date('Y-m-d H:i:s');
        $order->update();
        return $order;
    }
}

class BtcOrder extends Record{

    const TABLE = 'btc_orders';

    public $vendor;
    public $strain;
    public $product_amount;
    public $btc_amount;
    public $usd_amount;
    public $shipping_amount_usd;
    public $shipping_amount_btc;
    public $btc_fees;
    public $btc_total_amount;
    public $usd_total_amount;
    public $shipped_date;
    public $received_date;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($option,$username){
        $data = array();
        $GLOBALS['db']->database(self::DB)->table(self::TABLE)
                ->select("UID")
                ->where("user","=","'" . $username . "'");
        switch(strtolower($option)){
            case 'all':
                break;
            case 'active':
                $GLOBALS['db']->andWhere("received_date","is","null");
                break;
            case 'delivered':
                $GLOBALS['db']->andWhere("received_date","is not","null");
                break;
            default:
                throw new \Exception('Invalid Option');
        }
        $results = $GLOBALS['db']->get();
        while($row = mysqli_fetch_assoc($results)){
            $data[] = new self($row['UID']);
        }
        return $data;
    }
    public static function initiate(
            $vendorId,
            $strainId,
            $productAmount,
            $btcAmount,
            $usd_amount,
            $btc_fees,
            $btc_shipping,
            $usd_shipping,
            $usd_total_amount,
            $username){
        $order = new self();
        $order->vendor = $vendorId;
        $order->strain = $strainId;
        $order->product_amount = $productAmount;
        $order->btc_amount = $btcAmount;
        $order->usd_amount = $usd_amount;
        $order->btc_fees = $btc_fees;
        $order->shipping_amount_btc = $btc_shipping;
        $order->shipping_amount_usd = $usd_shipping;
        $order->usd_total_amount = $usd_total_amount;
        $order->btc_total_amount = $order->btc_amount + $order->btc_fees + $order->shipping_amount_btc;
        $order->user = $username;
        Stash::increase('btc',-1 * abs($order->btc_total_amount),$username);
        return $order->create();
    }
}