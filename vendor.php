<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';

class Vendor extends Record{
    
    const TABLE = 'vendors';
    
    public $name;
    public $ppg_key;
    
    public function __construct($UID = null){
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($username){
        $results = $GLOBALS['db']
                ->database(self::DB)
                ->table(self::TABLE)
                ->select("UID")
                ->where("user","=","'" . $username . "'")
                ->get();
        while($row = mysqli_fetch_assoc($results)){
            $data[] = new self($row['UID']);
        }
        return $data;
    }
    public static function add($name,$username){
        $vendor = new self();
        $vendor->name = $name;
        $vendor->user = $username;
        return $vendor->create();
    }
}