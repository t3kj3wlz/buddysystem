<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';


class Buyer extends Record{

    const TABLE = 'buyers';

    public $buyer;
    public $legacy;
    public $active;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($depth,$username){
        $data = array();
        $GLOBALS['db']->database(self::DB)->table(self::TABLE)
                ->select("UID")
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
            case 3:
                $GLOBALS['db']->andWhere("active","=","1");
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
    public static function add($name,$username,$active = 1,$legacy = 0){
        $buyer = new self();
        $buyer->buyer = $name;
        $buyer->user = $username;
        $buyer->active = $active;
        $buyer->legacy = $legacy;
        return $buyer->create();
    }
}