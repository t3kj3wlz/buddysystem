<?php

abstract class API{

    protected $method;
    protected $endPoint;
    protected $verb;
    protected $file;
    protected $headers;
    protected $args = array();

    public function __construct($request)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
	header("Access-Control-Allow-Headers: request_token,auth_token,password,content-type");
        header("Content-Type: application/json");
        $this->headers = getallheaders();
	if(isset($this->headers['content-type'])){
	    $this->headers['Content-Type'] = $this->headers['content-type'];
	}
        $this->args = explode('/', rtrim($request,'/'));
        $this->endpoint = array_shift($this->args);
        if(array_key_exists(0,$this->args) && !is_numeric($this->args[0])){
            $this->verb = array_shift($this->args);
        }
        $this->method = $_SERVER['REQUEST_METHOD'];
        if($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD',$_SERVER)){
            if($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE'){
                $this->method = 'DELETE';
            }elseif($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT'){
                $this->method = 'PUT';
            }else{
                //todo new Exception
            }
        }
        switch ($this->method){
            case "DELETE":
                break;
	    case "OPTIONS":
		break;
            case "POST":
                if(preg_match("/application\/json/",$this->headers['Content-Type'])){
                    $this->request = $this->_cleanInputs(json_decode(file_get_contents("php://input",true)));
                }
                else{
                    $this->request = $this->_cleanInputs($_POST);
                }
                break;
            case "GET":
                $this->request = $this->_cleanInputs($_GET);
                break;
            case "PUT":
                $this->request = $this->_cleanInputs($_GET);
                $this->file = json_decode(file_get_contents("php://input"));
                break;
            default:
                $this->_response('Invalid Method',405);
                break;
        }
    }
    public function processApi(){
        if(method_exists($this,$this->endpoint)){
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("No Endpoint: $this->endpoint",404);
    }
    private function _response($data,$status = 200){
        header('HTTP/1.1 ' . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }
    private function _cleanInputs($data){
        $clean_input = array();
        if(is_array($data)){
            foreach($data as $key=>$value){
                $clean_input[$key] = $this->_cleanInputs($value);
            }
        }elseif(is_object($data)){
            $clean_input = new stdClass();
            foreach($data as $key=>$value){
                $clean_input->$key = $value;
            }
        }else{
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }
    private function _requestStatus($code){
        $status = array(
            200=>'OK',
            404=>'Not Found',
            405=>'Method Not Allowed',
            500=>'Internal Server Error'
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }
}
