<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';

abstract class StashFactory{
    
    public static function createStash($type,$UID = null){
        switch (strtolower($type)){
            case "btc":
                $stash = new BtcStash($UID);
                break;
            case "usd":
                $stash = new UsdStash($UID);
                break;
            default:
                throw new \Exception('Unsupported Stash Type');
        }
        return $stash;
    }
}
abstract class Stash implements StashBehavior{
    
    public $current_stash;
    
    public function __construct(){}
    
    public static function getCurrentStash($type,$username){
        $stash = StashFactory::createStash($type);
        $results = $GLOBALS['db']
                ->database($stash::DB)
                ->table($stash::TABLE)
                ->select('UID')
                ->where("user","=","'" . $username . "'")
                ->orderBy("UID desc limit 1")
                ->get();
        while($row = mysqli_fetch_assoc($results)){
            $stash = StashFactory::createStash($type,$row['UID']);
        }
        return $stash;
    }
    public static function increase($type,$amount,$username){
        $current = self::getCurrentStash($type,$username);
        $new = StashFactory::createStash($type);
        $new->current_stash = $current->current_stash + $amount;
        $new->user = $username;
        $new->create();
        return $new;
    }
}

class UsdStash extends Record{

    const TABLE = 'current_stash';

    public $teh_date;
    public $current_stash;
    public $legacy;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($depth,$username){
        $data = array();
        $GLOBALS['db']
            ->database(parent::DB)
            ->table(self::TABLE)
            ->select(parent::PRIMARYKEY)
            ->where("user","=","'" . $username . "'");
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
            $data[] = new self($row[parent::PRIMARYKEY]);
        }
        return $data;
    }

}
class BtcStash extends Record{

    const TABLE = 'current_stash_btc';

    public $current_stash;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($username){
        $data = array();
        $results = $GLOBALS['db']
            ->database(parent::DB)
            ->table(self::TABLE)
            ->select(parent::PRIMARYKEY)
            ->where("user","=","'" . $username . "'")
            ->get();
        while($row = mysqli_fetch_assoc($results)){
            $data[] = new self($row[parent::PRIMARYKEY]);
        }
        return $data;
    }
}