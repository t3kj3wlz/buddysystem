<?php namespace Buddy;

require_once __DIR__ . '/abstraction.php';

class ProfitJar extends Record{

    const TABLE = 'current_profit_jar';
    
    public $teh_date;
    public $current_profit;
    public $legacy;

    public function __construct($UID = null)
    {
        parent::__construct(self::TABLE,$UID);
    }
}