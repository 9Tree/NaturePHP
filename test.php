<?php
include('nphp/init.php');
Log::init(true);

$arr=array(0=>'TESTE0',1=>'TESTE1',2=>'TESTE2',3=>'TESTE3','HE'=>'TESTE');
Utils::array_insert($arr, 'teste11', 1);
Utils::array_insert($arr, 'teste5', 5);
Utils::array_insert($arr, 'teste111', 'HE');
Utils::array_insert($arr, 'teste111', 'HE');
var_dump($arr);
?>