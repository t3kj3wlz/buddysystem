<?php namespace Buddy;

require_once __DIR__ . '/db.php';

if(!isset($GLOBALS['db'])){
    $db = new \DB();
}


interface RecordBehavior{
    public function create();
    public function update();
    public function setFields($updateObj);
}
interface StashBehavior{
    public static function increase($type,$amount,$username);
    public static function getCurrentStash($type,$username);
}
interface OrderBehavior{
    public static function arrive($type,$orderId,$username);
    public static function get($option,$username);
}
//interface XferBehavior{
//    public static function complete();
//}

abstract class Record implements RecordBehavior{

    const DB = 'buddy_system';
    const PRIMARYKEY = 'UID';
//    const DB = 'BUDdy_test';

    public $UID;
    public $created_date;
    public $user;

    protected $table;

    public function __construct($table,$UID){
        $this->table = $table;
        if(!is_null($UID)){
            $this->UID = $UID;
            $this->_build();
        }
    }

    protected function _build(){
        $results = $GLOBALS['db']
                ->database(self::DB)
                ->table($this->table)
                ->select("*")
                ->where("UID","=","'" . $this->UID . "'")
                ->get();
        if(!mysqli_num_rows($results)){
            throw new \Exception('Invalid UID');
        }
        while($row = mysqli_fetch_assoc($results)){
            foreach($row as $key=>$value){
                $this->$key = $value;
            }
        }
        return $this;
    }
    protected function _getUID(){
        $results = $GLOBALS['db']
                ->database(self::DB)
                ->table($this->table)
                ->select("UID")
                ->orderBy("UID desc limit 1")
                ->get();
        while($row = mysqli_fetch_assoc($results)){
            $this->UID = $row['UID'];
        }
        return $this;
    }
    public function create(){
        $this->created_date = date('Y-m-d H:i:s');
        $reflection = new \ReflectionObject($this);
        $data = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $upData = array();
        foreach($data as $obj){
            $key = $obj->name;
            if($this->$key === 0 || ($this->$key === 0 || !is_null($this->$key) && !empty($this->$key))){
                $upData[$key] = $this->$key;
            }
        }
        unset($upData['UID']);
        $results = $GLOBALS['db']
                ->database(self::DB)
                ->table($this->table)
                ->insert($upData)
                ->put();
        $this->_getUID()->_build();
        return $this;
    }
    public function update(){
        $reflection = new \ReflectionObject($this);
        $data = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $upData = array();
        foreach($data as $obj){
            $key = $obj->name;
            if($this->$key === 0 || ($this->$key === 0 || !is_null($this->$key) && !empty($this->$key))){
                $upData[$key] = $this->$key;
            }
        }
        unset($upData['UID']);
        $results = $GLOBALS['db']
                ->database(self::DB)
                ->table($this->table)
                ->update($upData)
                ->where("UID","=","'" . $this->UID . "'")
                ->put();
        return $this;
    }
    public function setFields($updateObj){
        if(!is_object($updateObj)){
            throw new \Exception('Trying to perform object method on non object');
        }
        foreach($updateObj as $key=>$value){
            $this->$key = $value;
        }
        return $this;
    }
}
