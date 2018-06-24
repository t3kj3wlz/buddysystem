<?php namespace Buddy; 

require_once __DIR__ . '/abstraction.php';

class XferFactory{
    
    public static function create(string $type,int $UID = null){
        switch(strtolower($type)){
            case "btc":
                $xfer = new BtcXfer($UID);
                break;
            default:
                throw new \Exception('Invalid Type');
        }
        return $xfer;
    }
    public static function get($type,$option,$username){
        $transfer = XferFactory::create($type);
        return $transfer::get($option,$username);
    }
}

//abstract Class StashXfer{
//    
//    public static function complete(string $type, int $UID){
//        $xfer = XferFactory::create($type,$UID);
//    }
//}

class BtcXfer extends Record{

    const TABLE = 'stash_transfers';

    public $stash_used;
    public $btc_gained;
    public $stash_exceeded;
    public $initial_rate;
    public $stash_lost;
    public $completion_rate;
    public $stash_gained;
    public $completed_date;
    public $complete;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($option,$username){
        $data = array();
        $GLOBALS['db']->database(parent::DB)->table(self::TABLE)
                ->select("UID")
                ->where("user","=","'" . $username . "'");
        switch(strtolower($option)){
            case 'all':
                break;
            case 'active':
                $GLOBALS['db']->andWhere("complete","=","0");
                break;
            case 'complete':
                $GLOBALS['db']->andWhere("complete","=","1");
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
    public static function initiate($stash_used,$initial_rate,$username){
        Stash::increase('usd',-1 * abs($stash_used),$username);
        $transfer = new self();
        $transfer->stash_used = $stash_used;
        $transfer->initial_rate = $initial_rate;
        $transfer->user = $username;
        return $transfer->create();
    }
    public static function complete($UID,$btc_gained,$completion_rate,$username){
        $transfer = new self($UID);
        $transfer->btc_gained = $btc_gained;
        Stash::increase('btc',$transfer->btc_gained,$username);
        $transfer->complete = 1;
        $transfer->completion_rate = $completion_rate;
        $transfer->completed_date = date('Y-m-d H:i:s');
        $transfer->update();
        return $transfer;
    }
}
