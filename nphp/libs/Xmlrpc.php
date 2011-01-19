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
 *
 */
class Xmlrpc {
    private $host;
    private $path;
    private $connection;
    private $debug = false;
    private $user;
    private $pass;

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
            echo "REQUEST:\n$request\n\n";
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
            echo "CURL INFO:\n";
            foreach(curl_getinfo($conn) as $name => $val)
                echo $name . ': ';
				echo var_dump($val);
				echo "\n";
            echo "\n\nRESPONSE:\n$response";
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

    private function encodeRequest($methodName,$args) {
        $req = '<?xml version="1.0" encoding="UTF-8" ?>';
        $params = '';
        foreach($args as $arg) {
            $parm = XmlRPC_Parm::encode($arg);
            $params .= sprintf('<param><value>%s</value></param>',$parm);
        }
        $req .= "\n<methodCall><methodName>$methodName</methodName><params>$params</params></methodCall>";
        return $req;
    }
    private function parseResponse($xmlStr) {
        $xml = simplexml_load_string(trim($xmlStr));
        $response = array();
        
        if (count($xml->fault) > 0) {
            //An error was returned
            $fault = $this->parseValue($xml->fault->value);
            trigger_error("<strong>XmlRPC</strong> :: ".$fault->faultCode.": ".$fault->faultString, E_USER_WARNING);
        }
		
		if(isset($xml->params->param)){
			if(is_array($xml->params->param)){

				if (count($xml->params->param) == 1)
		            $scalar = true;

		        foreach($xml->params->param as $param) {
		            $valueStruct = $param->value;

		            $value = $this->parseValue($valueStruct);
		            if ($scalar)
		                return $value;
		            else
		                $response[] = $value;
		        }

			} else {
				$response[] = $xml->params->param;
			}
		}
        
        return $response;
    }
    private function parseValue($valueStruct) {
        switch(true) {
            case count($valueStruct->struct) > 0:
                $value = new stdClass();
                foreach($valueStruct->struct->member as $member) {
                    $name = (string)$member->name;
                    $memberValue = $this->parseValue($member->value);
                    $value->$name = $memberValue;
                }
                return $value;
                break;
            case count($valueStruct->array) > 0:
                $value = array();
                foreach($valueStruct->array->data->value as $arrayValue) {
                    $value[] = $this->parseValue($arrayValue);
                }
                return $value;
                break;
            case count($valueStruct->i4) > 0:
                return (int)$valueStruct->i4;
            case count($valueStruct->int) > 0:
                return (int)$valueStruct->int;
            case count($valueStruct->boolean) > 0:
                return (boolean) $valueStruct->boolean;
            case count($valueStruct->string) > 0:
                return (string)$valueStruct->string;
            case count($valueStruct->double) > 0:
                return (double)$valueStruct->double;
            case count($valueStruct->dateTime) > 0:
                return (string)$valueStruct->dateTime;
            case count($valueStruct->base64) > 0:
                return (string)$valueStruct->base64;
        }
    }
    private function disconnect() {
        if ($this->connection)
            @curl_close($this->connection);
        $this->connection = null;
    }
    protected function getUrl() {
        return sprintf('http://%s%s',$this->host,$this->path);
    }
    private function getConnection() {
        if (!$this->connection)
            $this->connection = curl_init($this->getUrl());
        return $this->connection;
    }
    public function __destruct() {
        $this->disconnect();
    }
}

/* PARMS */
class XmlRPC_Parm {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }
    public function getType() {
        switch(true) {
            case is_bool($this->value):
                return 'boolean';
            case is_int($this->value):
                return 'int';
            case is_float($this->value):
            case is_double($this->value):
                return 'double';
                break;
            case is_object($this->value):
                return 'struct';
            case is_bool($this->value):
                return 'boolean';
            case is_array($this->value):
                return 'array';
            default:
            case is_string($this->value):
                return 'string';
                break;
        }
    }
    public function getValue() {
        return $this->value;
    }
    protected function getFormattedValue() {
        if(is_string($this->value)) return '<![CDATA['.$this->value.']]>';
		else return $this->value;
    }

    public function __toString() {
        return sprintf('<%1$s>%2$s</%1$s>',$this->getType(),$this->getFormattedValue());
    }
    /**
     *
     * @param mixed $arg
     * @return XmlRPC_Parm
     */
    public static function encode($arg) {
        switch(true) {
            case $arg instanceof XmlRPC_Parm:
                return $arg;
            case is_object($arg):
			case Utils::is_assoc($arg):
                return new XmlRPC_Struct($arg);
            case is_array($arg):
                return new XmlRPC_Array($arg);
            default:
            case is_bool($arg):
            case is_int($arg):
            case is_float($arg):
            case is_double($arg):
            case is_string($arg):
                return new XmlRPC_Parm($arg);
        }
    }
    public static function decode($param) {

    }
}
class XmlRPC_Struct extends XmlRPC_Parm{

    protected function getFormattedValue() {
        $result = '';
        foreach($this->getValue() as $name=>$value) {
            $parm = XmlRPC_Parm::encode($value);
            $result .= sprintf('<member><name>%s</name><value>%s</value></member>',$name,$parm);
        }
        return $result;
    }
    public function getType() {
        return 'struct';
    }
}

class XmlRPC_Array extends XmlRPC_Parm{

    protected function getFormattedValue() {
        $result = '<data>';
        foreach($this->getValue() as $value) {
            $parm = XmlRPC_Parm::encode($value);
            $result .= sprintf('<value>%s</value>',$parm);
        }
        return $result.'</data>';
    }
    public function getType() {
        return 'array';
    }
}
class XmlRPC_Date extends XmlRPC_Parm{

    protected function getFormattedValue() {
        return date('Ymd\TH:i:s',$this->value);
    }
    public function getType() {
        return 'dateTime.iso8601';
    }
}
class XmlRPC_Binary extends XmlRPC_Parm{

    protected function getFormattedValue() {
        return base64_encode($this->value);
    }
    public function getType() {
        return 'base64';
    }
}