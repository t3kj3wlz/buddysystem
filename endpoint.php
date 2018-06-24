<?php

//todo you should not actually be able to create new inventories or stashes. Remove.

require_once __DIR__ . '/api.php';
require_once __DIR__ .'/transaction.php';
require_once __DIR__ . '/inventory.php';
require_once __DIR__ .'/strain.php';
require_once __DIR__ . '/buyer.php';
require_once __DIR__ . '/btcOrder.php';
require_once __DIR__ . '/transfer.php';
require_once __DIR__ . '/vendor.php';


class EndPoint extends \API{

    const ACCOUNTS = 'http://192.168.1.77/';

    protected $user;

    public function __construct($request,$origin)
    {
        parent::__construct($request);
        if(isset($this->headers['request_token']) && ! isset($this->headers['password'])){
            throw new \Exception('Missing required headers.');
        }elseif(!isset($this->headers['auth_token']) && !isset($this->headers['request_token'])){
            throw new \Exception('Access Denied. No Token Present.');
        }elseif(!$this->_verifyToken() && !isset($this->headers['request_token'])){
            throw new \Exception('Access Denied. Invalid Token');
        }
    }
    private function _verifyToken(){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,self::ACCOUNTS . "verify/");
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('auth_token: ' . $this->headers['auth_token']));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);
        if(isset($output->error)){
            return false;
        }
        $this->user = $output;
        return true;
    }
    private function _authenticate(){
        $headers = array('request_token: ' . $this->headers['request_token'],'password: ' . $this->headers['password']);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,self::ACCOUNTS . "authenticate/");
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);
        if(isset($output->error)){
            throw new \Exception($output->error);
        }
        $this->headers['auth_token'] = $output->token;
        $this->_verifyToken();
        return $output;
    }
    protected function example(){
        return array("endPoint"=>$this->endpoint,"verb"=>$this->verb,"args"=>$this->args,"request"=>$this->request);
    }
    protected function authenticate(){
        return $this->_authenticate();
    }
    protected function verify(){
        if(!$this->_verifyToken()){
            throw new \Exception('Token Rejected');
        }
        return $this->headers['auth_token'];
    }
    protected function transaction(){
        $data = null;
        if(strtolower($this->verb) == 'sale' && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = \Buddy\Transaction::makeSale($this->request->buyer,$this->request->strain,$this->request->amount,$this->request->payment,$this->user->username,$this->request->front,$this->request->discrepency);
        }elseif(strtolower($this->verb) == 'tip' && !isset($this->args[0]) && $this->method == 'POST'){
            $data = \Buddy\Transaction::acceptTip($this->request->amount,$this->request->buyer,$this->user->username);
        }elseif(strtolower($this->verb) == 'purchase' && !isset($this->args[0]) && $this->method == 'POST'){
            $data = \Buddy\Transaction::makePurchase($this->request->vendor,$this->request->strain,$this->request->amount,$this->request->payment,$this->user->username,$this->request->front);
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\Transaction::get("all",0,$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\Transaction($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\Transaction($this->args[0]);
            if($data->user != $this->user->username){
                throw new Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;        
    }
    //\Buddy\Inventory::getCurrentInventory($this->user->username);
    //\Buddy\Inventory::calculateCurrentInventory($this->user->username);
    protected function inventory(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = new \Buddy\Inventory();
            $data->setFields($this->request)->create();
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\Inventory::get(0,$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\Inventory($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\Inventory($this->args[0]);
            if($data->user != $this->user->username){
                throw new Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;        
    }
    protected function stash_usd(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = new \Buddy\UsdStash();
            $data->setFields($this->request)->create();
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\UsdStash::get(0,$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\UsdStash($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\UsdStash($this->args[0]);
            if($data->user != $this->user->username){
                throw new Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
    protected function stash_btc(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = new \Buddy\BtcStash();
            $data->setFields($this->request)->create();
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\BtcStash::get($this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\BtcStash($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\BtcStash($this->args[0]);
            if($data->user != $this->user->username){
                throw new \Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
    protected function strain(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = new \Buddy\Strain();
	    $this->request->user = $this->user->username;
            $data->setFields($this->request)->create();
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\Strain::get('all',$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\Strain($this->args[0]);
	    if($data->user != $this->user->username){
	        throw new \Exception('Trying to access restricted resouce');
	    }
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\Strain($this->args[0]);
            if($data->user != $this->user->username){
                throw new \Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
    protected function buyer(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = new \Buddy\Buyer();
	    $this->request->user = $this->user->username;
            $data->setFields($this->request)->create();
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\Buyer::get(0,$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\Buyer($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\Buyer($this->args[0]);
            if($data->user != $this->user->username){
                throw new \Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
    protected function order(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = \Buddy\BtcOrder::initiate(
                $this->request->vendorID,
                $this->request->strainID,
                $this->request->product_amount,
                $this->request->btc_amount,
                $this->request->usd_amount,
                $this->request->btc_fees,
                $this->request->shipping_amount_btc,
                $this->request->shipping_amount_usd,
                $this->request->usd_total_amount,
                $this->user->username);
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\BtcOrder::get('all',$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\BtcOrder($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\BtcOrder($this->args[0]);
            if($data->user != $this->user->username){
                throw new Exception('Trying to access resetricted resource');
            }
            $data = \Buddy\Order::arrive('btc',$this->file->UID,$this->user->username);
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
    protected function xfer(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = \Buddy\BtcXfer::initiate($this->request->stash_used,$this->request->initial_rate,$this->user->username);
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\BtcXfer::get('all',$this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\BtcXfer($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\BtcXfer($this->args[0]);
            if($data->user != $this->user->username){
                throw new Exception('Trying to access resetricted resource');
            }
            $data = \Buddy\BtcXfer::complete($this->file->UID,$this->file->btc_gained,$this->file->completion_rate,$this->user->username);
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
    protected function vendor(){
        $data = null;
        if(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'POST'){ //create
            $data = new \Buddy\Vendor();
	    $this->request->user = $this->user->username;
            $data->setFields($this->request)->create();
        }elseif(!isset($this->verb) && !isset($this->args[0]) && $this->method == 'GET'){ //get all
            $data = \Buddy\Vendor::get($this->user->username);
        }elseif(!isset($this->verb) &&(int)$this->args[0] && $this->method == 'GET'){ //get by id
            $data = new \Buddy\Vendor($this->args[0]);
        }elseif((int)$this->args[0] && $this->method == 'PUT'){ //update by id
            $data = new \Buddy\Vendor($this->args[0]);
            if($data->user != $this->user->username){
                throw new Exception('Trying to access resetricted resource');
            }
            $data->setFields($this->file)->update();
        }elseif(isset($this->verb)){
            $data = $this->_parseVerb();
        }else{
            throw new \Exception('Malformed Request');
        }
        return $data;
    }
}
/*
 * \Buddy\BtcXfer::get('all',$this->user->username)
 * \Buddy\BtcXfer::get('active',$this->user->username)
 * \Buddy\BtcXfer::get('complete',$this->user->username)
 * \Buddy\BtcXfer::initiate($this->request->stash_used,$this->request->initial_rate,$this->user->username)
 * \Buddy\OrderFactory::get('btc','all',$this->user->username)
 * \Buddy\OrderFactory::get('btc','active',$this->user->username)
 * \Buddy\OrderFactory::get('btc','delivered',$this->user->username)
 * \Buddy\BtcOrder::initiate($vendorID,$strainID,$product_amount,$btc_amount,$usd_amount,$btc_fees,$shipping_amount_btc,$shipping_amount_usd,$usd_total_amount,$username);
 * \Buddy\Buyer::get(0,$this->user->username) -- all
 * \Buddy\Buyer::get(3,$this->user->username) -- active
 * \Buddy\Buyer::get(2,$this->user->username) -- legacy
 * \Buddy\Strain::get('all',$this->user->username)
 * \Buddy\Strain::add($thc,$indica,$sativa,$description,$inStock,$inventory,$ppg,$img_path,$username)
 * \Buddy\Strain::get('active',$this->user->username)
 * \Buddy\Stash::getCurrentStash($this->args[0],$this->user->username)
 * \Buddy\Transaction::acceptTip($this->request->amount,$this->request->buyer,$this->user->username)
 * \Buddy\Inventory::getCurrentInventory($this->user->username)
 * \Buddy\Inventory::getCurrentInventory($this->user->username)
 * \Buddy\Inventory::calculateCurrentInventory($this->user->username)
 * \Buddy\Transaction::get("all",0,$this->user->username)
 * \Buddy\Transaction::get("all",2,$this->user->username)
 * \Buddy\Transaction::get("sale",0,$this->user->username)
 * \Buddy\Transaction::get("purchase",0,$this->user->username)
 * \Buddy\Transaction::makeSale($buyer,$strain,$amount,$payment,$username);
 *
 * // print_r(Order::arrive('btc',12,$user));
 * /print_r(BtcXfer::complete(11,0.05010272,6976.91,$user));
 * */
