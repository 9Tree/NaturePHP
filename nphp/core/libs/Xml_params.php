<?php

/* PARMS */
class Xml_params {
    protected $value;

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
	public static function encode_params($args){
		$params = '';
		$parm = null;
		foreach($args as $arg) {
            $parm = self::encode($arg);
            $params .= sprintf('<param><value>%s</value></param>',$parm);
        }
		return $params;
	}
    public static function encode($arg) {
		switch(true) {
            case $arg instanceof Xml_params:
                return $arg;
            case is_object($arg):
			case Utils::is_assoc($arg):
                return new Xml_Struct($arg);
            case is_array($arg):
                return new Xml_Array($arg);
            default:
            case is_bool($arg):
            case is_int($arg):
            case is_float($arg):
            case is_double($arg):
            case is_string($arg):
                return new Xml_params($arg);
        }
    }
    public static function decode_params($params) {

		if(isset($params->param)){
			
			if(is_array($params->param)){
				
				$response = array();
				
				if (count($params->param) == 1)
		            $scalar = true;

		        foreach($params->param as $param) {
		            $valueStruct = $param->value;

		            $value = self::parseValue($valueStruct);
		            if ($scalar)
		                return $value;
		            else
		                $response[] = $value;
		        }
		
				return $response;
				
			} else return array($params->param);
			
		} else return array();
		
    }
	public static function parseAttributes($el){
		$ret = array();
		foreach($el->attributes() as $id=>$value){
			$ret[$id]=(string)$value;
		}
		return $ret;
	}
	public static function parseValue($valueStruct) {
        switch(true) {
            case count($valueStruct->struct) > 0:
                $value = new stdClass();
                foreach($valueStruct->struct->member as $member) {
                    $name = (string)$member->name;
                    $memberValue = self::parseValue($member->value);
                    $value->$name = $memberValue;
                }
                return $value;
                break;
            case count($valueStruct->array) > 0:
                $value = array();
                foreach($valueStruct->array->data->value as $arrayValue) {
                    $value[] = self::parseValue($arrayValue);
                }
                return $value;
                break;
            case count($valueStruct->i4) > 0:
                return (int)$valueStruct->i4;
            case count($valueStruct->int) > 0:
                return (int)$valueStruct->int;
            case count($valueStruct->boolean) > 0:
                return !!((string)$valueStruct->boolean);
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
}
class Xml_Struct extends Xml_params{

    protected function getFormattedValue() {
        $result = '';
        foreach($this->getValue() as $name=>$value) {
            $parm = Xml_params::encode($value);
            $result .= sprintf('<member><name>%s</name><value>%s</value></member>',$name,$parm);
        }
        return $result;
    }
    public function getType() {
        return 'struct';
    }
}

class Xml_Array extends Xml_params{

    protected function getFormattedValue() {
        $result = '<data>';
        foreach($this->getValue() as $value) {
            $parm = Xml_params::encode($value);
            $result .= sprintf('<value>%s</value>',$parm);
        }
        return $result.'</data>';
    }
    public function getType() {
        return 'array';
    }
}
class Xml_Date extends Xml_params{

    protected function getFormattedValue() {
        return date('Ymd\TH:i:s',$this->value);
    }
    public function getType() {
        return 'dateTime.iso8601';
    }
}
class Xml_Binary extends Xml_params{

    protected function getFormattedValue() {
        return base64_encode($this->value);
    }
    public function getType() {
        return 'base64';
    }
}
?>