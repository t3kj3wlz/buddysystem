<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';
require_once __DIR__ . '/buyer.php';
require_once __DIR__ . '/strain.php';
require_once __DIR__ . '/stash.php';
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/vendor.php';


class Transaction extends Record{

    const TABLE = 'Transactions';
    const APPROVEDTYPES = array(
        'S',
        'P',
        'T'
    );

    public $teh_date;
    public $trans_type;
    public $product_amount;
    public $payment;
    public $front;
    public $buyer;
    public $discrepency;
    public $legacy;
    public $strain;
    public $front_paid;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function add(
            $type,
            $amount,
            $payment,
            $buyer,
            $strain,
            $username,
            $front = null,
	    $discrepency = null){
        $transaction = new self();
        if(!in_array($type,self::APPROVEDTYPES)){
            throw new Exception('Invalid Transaction Type');
        }
        $transaction->trans_type = $type;
        if($buyer == 0){
            $buyerName = 'Me';
        }elseif($buyer < 0){
            $buyer = new Vendor(abs($buyer));
            $buyerName = $buyer->name;
        }else{
            $buyer = new Buyer($buyer);
            $buyerName = $buyer->buyer;
        }
        if($strain > 0){
            $strain = new Strain($strain);
            $transaction->strain = $strain->name;
        }
        $transaction->teh_date = date('Y-m-d');
        $transaction->product_amount = $amount;
        $transaction->payment = $payment;
        $transaction->buyer = $buyerName;
        $transaction->front = $front;
        $transaction->user = $username;
	$transaction->discrepency = $discrepency;
        return $transaction->create();
    }
    public static function get($option,$depth,$username){
        $data = array();
        $GLOBALS['db']
                ->database(self::DB)
                ->table(self::TABLE)
                ->select('UID')
                ->where("user","=","'" . $username . "'");
        switch (strtolower($option)){
            case "sale":
                $GLOBALS['db']->andWhere("trans_type","=","'S'");
                $GLOBALS['db']->andWhere("buyer","!=","'Me'");
                break;
            case "purchase":
                $GLOBALS['db']->andWhere("trans_type","=","'P'");
                break;
            case "tip":
                $GLOBALS['db']->andWhere("trans_type","=","'T'");
                break;
            case "personal":
                $GLOBALS['db']->andWhere("trans_type","=","'S'")
                    ->andWhere("buyer","=","'Me'")
                    ->andWhere("discrepency","=","0");
                break;
            case "discrepency":
                $GLOBALS['db']->andWhere("trans_type","=","'S'")->andWhere("discrepency","=","1");
                break;
            case "all":
                break;
            default:
                throw new \Exception('Invalid Option');
        }
        switch ($depth){
            case 0:
                break;
            case 1:
                $GLOBALS['db']->andWhere("legacy","=","0");
                break;
            case 2:
                $GLOBALS['db']->andWhere("legacy","=","1");
                break;
            default:
                throw new \Exception('Invalid Depth');
        }
        $results = $GLOBALS['db']->get();
        while($row = mysqli_fetch_assoc($results)){
            $data[] = new self($row['UID']);
        }
        return $data;
    }
    public static function makeSale($buyer,$strain,$amount,$payment,$username,$front = false,$discrepency = false){
        if($buyer != 0 && !$front && !$discrepency){
            Stash::increase('usd',$payment,$username);
        }
        Inventory::increase($strain,-1 * abs($amount),$username);
        return self::add("S",$amount,$payment,$buyer,$strain,$username,$front,$discrepency);
    }
    public static function makePurchase($vendor,$strain,$amount,$payment,$username,$front = false){
      if(!$front){
        Stash::increase('usd',-1 * abs($payment),$username);
      }
      Inventory::increase($strain,$amount,$username);
      return self::add("P",$amount,$payment,$vendor,$strain,$username,$front);
    }
    public static function acceptTip($amount,$buyer,$username){
        self::add("T",0,$amount,$buyer,-1,$username);
        return Stash::increase('usd',$amount,$username);
    }
    public static function settleFront($UID){
      try{
        $transaction = new self($UID);
      }catch(\Exception $e){
        throw new \Exception($e->getMessage());
      }
      if(!$transaction->front){
        throw new \Exception('Cannot settle a non front');
      }
      if($transaction->trans_type == 'P'){
        Stash::increase('usd',-1 * abs($transaction->payment),$transaction->user);
      }else{
        Stash::increase('usd',$transaction->payment,$transaction->user);
      }
      $transaction->front_paid = date('Y-m-d H:i:s');
      $transaction->update();
      return $transaction;
    }

}
