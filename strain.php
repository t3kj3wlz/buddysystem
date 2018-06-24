<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';

class Strain extends Record{

    const TABLE = 'strains';

    public $name;
    public $thc;
    public $indica;
    public $sativa;
    public $description;
    public $inStock;
    public $inventory;
    public $ppg;
    public $img_path;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
    public static function get($option,$username){
        $data = array();
        $GLOBALS['db']->database(self::DB)->table(self::TABLE)->select("UID")->where("user","=","'" . $username . "'");
        switch (strtolower($option)){
            case "all":
                $GLOBALS['db']->orderBy("UID desc");
                break;
            case "active":
                $GLOBALS['db']->andWhere("inStock","=","1")->orderBy("UID desc");
                break;
            default:
                throw new \Exception('Invalid option');
        }
	$results = $GLOBALS['db']->get();
        while($row = mysqli_fetch_assoc($results)){
            $data[] = new self($row['UID']);
        }
        return $data;
    }
    public static function add(
            $name,
            $thcPercent,
            $indicaPercent,
            $sativaPercent,
            $description,
            $inStock,
            $inventory,
            $ppg,
            $img_path,
            $user){
        $strain = new self();
        $strain->name = $name;
        $strain->thc = $thcPercent;
        $strain->indica = $indicaPercent;
        $strain->sativa = $sativaPercent;
        $strain->description = $description;
        $strain->inStock = $inStock;
        $strain->inventory = $inventory;
        $strain->ppg = $ppg;
        $strain->img_path = $img_path;
        $strain->user = $user;
        return $strain->create();
    }
}
