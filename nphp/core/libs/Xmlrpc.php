<?php
/**
 * @author Henrik Hofmeister
 * @license MIT
 * @version 1.0
 *
 * XmlRPC using SimpleXML and CURL
 *
 * Note: original exception system has been replaced with trigger_error() 
 * to better adjust usage within NaturePhp. by Carlos Ouro
 * A few features and bug corrections added too.
 *
 */
class Xmlrpc{
    protected $host;
    protected $path;
    protected $connection;
    protected $debug = false;
    protected $user;
    protected $pass;

    public function  __construct($host,$path) {
        $this->host = $host;
        $this->path = $path;
    }
    public function setHost($host) {
        $this->disconnect();
        $this->host = $host;
    }
    public function setCredentials($user,$pass) {
        $this->user = $user;
        $this->pass = $pass;
    }
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function call($methodName, $args=array()) {
        $request = $this->encodeRequest($methodName,$args);
        if ($this->debug) {
			trigger_error('<strong>XmlRPC</strong> :: CURL REQUEST: '.$request, E_USER_NOTICE);
        }
        $conn = $this->getConnection();
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);

        if($this->debug)
        {
            curl_setopt($conn, CURLOPT_VERBOSE, 1);
        }
        curl_setopt($conn, CURLOPT_USERAGENT,'PHP XML-RPC Client');
        curl_setopt($conn, CURLOPT_POST, 1);
        curl_setopt($conn, CURLOPT_POSTFIELDS, $request);
        //curl_setopt($conn, CURLOPT_HEADER, 1);
        if ($this->user)
            curl_setopt($conn, CURLOPT_USERPWD,$this->user.':'.$this->pass);

        $response = curl_exec($conn);
       	
        if ($this->debug) {
			trigger_error('<strong>XmlRPC</strong> :: CURL INFO starting.', E_USER_NOTICE);
			foreach(curl_getinfo($conn) as $name => $val)
				trigger_error('<strong>XmlRPC</strong> :: '.$name.': '.$val, E_USER_NOTICE);
			trigger_error('<strong>XmlRPC</strong> :: RESPONSE:<br />'.str_replace("\n", '<br />', $response), E_USER_NOTICE);
			trigger_error('<strong>XmlRPC</strong> :: CURL INFO ended.', E_USER_NOTICE);
        }
        $httpCode = curl_getinfo($conn,CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            if ($httpCode == 401)
				trigger_error('<strong>XmlRPC</strong> :: Invalid user name or password - authentication failed', E_USER_WARNING);
            else
				trigger_error("<strong>XmlRPC</strong> :: Server replied with an error code: $httpCode", E_USER_WARNING);
        }
        if (empty($response)) {
            trigger_error('<strong>XmlRPC</strong> :: Empty response from server at: '.$this->getUrl(), E_USER_WARNING);
        }

        $result = $this->parseResponse($response);
        return $result;
    }
    public function __call($name, $arguments) {
        return call_user_method_array('call',$this, array($name, $arguments));
    }

    protected function encodeRequest($methodName,$args) {
        $req = '<?xml version="1.0" encoding="UTF-8" ?>';
		$params = XmlRPC_Params::encode_params($args);
        $req .= "\n<methodCall><methodName>$methodName</methodName><params>$params</params></methodCall>";
        return $req;
    }
    protected function parseResponse($xmlStr) {
		libxml_use_internal_errors(true);
        $xml = simplexml_load_string(trim($xmlStr));
		$response = array();

		if(!$xml){
			trigger_error("<strong>XmlRPC</strong> :: Failed Loading.", E_USER_WARNING);
			$errors = libxml_get_errors();
		    foreach($errors as $error) {
				trigger_error("<strong>XmlRPC</strong> :: ".$error->message, E_USER_WARNING);
		    }
		} else {
			//check faults
			if (count($xml->fault) > 0) {
	            //An error was returned
	            //$fault = XmlRPC_Params::parseValue($xml->fault->value);
	            trigger_error("<strong>XmlRPC</strong> :: Fault ".$fault->faultCode.": ".$fault->faultString, E_USER_WARNING);
	        }
			//parse data
			$response = XmlRPC_Params::decode_params($xml->params);
		}
        
        return $response;
    }
    protected function disconnect() {
        if ($this->connection)
            @curl_close($this->connection);
        $this->connection = null;
    }
    protected function getUrl() {
        return sprintf('http://%s%s',$this->host,$this->path);
    }
    protected function getConnection() {
        if (!$this->connection)
            $this->connection = curl_init($this->getUrl());
        return $this->connection;
    }
    public function __destruct() {
        $this->disconnect();
    }
}
