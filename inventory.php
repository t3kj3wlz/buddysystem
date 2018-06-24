<?php namespace Buddy;

//an inventory object is an inventory "snapshot" at a given time

require_once __DIR__ . '/abstraction.php';
require_once __DIR__ . '/strain.php';

class Inventory extends Record{
    
    const TABLE = 'current_inventory';

    public $teh_date;
    public $current_inventory;
    public $legacy;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($depth,$username){
        $data = null;
        $GLOBALS['db']->database(self::DB)
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
    public static function getCurrentInventory($username){
        $data = null;
        $results = $GLOBALS['db']
                ->database(self::DB)
                ->table(self::TABLE)
                ->select(parent::UID)
                ->where("user","=","'" . $username . "'")
                ->orderBy("UID desc limit 1")
                ->get();
        while($row = mysqli_fetch_assoc($results)){
            $data = new self($row[parent::PRIMARYKEY]);
        }
        return $data;
    }
    public static function calculateCurrentInventory($username){
        $inventory = 0;
        $strains = Strain::get('active',$username);
        foreach($strains as $strain){
            $inventory += $strain->inventory;
        }
        return $inventory;        
    }
    public static function updateInventory($username){
        $data = new self();
        $data->current_inventory = self::calculateCurrentInventory($username);
        $data->user = $username;
        $data->create();
        return $data;
    }
    public static function increase($strainId,$amount,$username){
        $strain = new Strain($strainId);
        $strain->inventory += $amount;
        if($strain->inventory < 0){
            throw new \Exception('Creating a negative Inventory');
        }elseif($strain->user != $username){
            print_r($strain);
            $str = 'Accessing Restriced Resource ' . $username . ' | ' . $strain->user;
            throw new \Exception($str);
        }elseif($strain->inventory == 0){
            $strain->inStock = 0;
        }else{
            $strain->inStock = 1;
        }
        $strain->update();
        self::updateInventory($username);
        return $strain;
    }
}
