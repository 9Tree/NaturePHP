<?php
#/*
#* 9Tree Shell Processes Class - v0.3.5
#* Shell process interaction functionalities (Unix)
#*/

class ShellProcess{
	
  function execute($command, $timeout = 60, $sleep = 2) { 
        // First, execute the process, get the process ID 

        $pid = self::start($command); 

        if( $pid === false ) 
            return false; 

        $cur = 0; 
        // Second, loop for $timeout seconds checking if process is running 
        while( $cur < $timeout ) { 
            sleep($sleep); 
            $cur += $sleep;  

            if( !self::exists($pid) ) 
                return true; // Process must have exited, success! 
        } 

        // If process is still running after timeout, kill the process and return false 
        self::kill($pid); 
        return false; 
    } 

    function start($commandJob, $log='/dev/null') { 

		$command = "nohup  $commandJob >>$log 2>&1 & disown & echo $!";
		
		$op = null;
        exec($command ,$op); 
        $pid = ((int)$op[0]); 

        if($pid!="") return $pid; 

        return false; 
    }

    function exists($pid) { 

        exec("ps ax | grep $pid 2>&1", $output); 

        while( list(,$row) = each($output) ) { 
				
				
                $row_array = explode(" ", trim($row)); 
                $check_pid = $row_array[0]; 

                if($pid == $check_pid) { 
                        return true; 
                } 

        } 

        return false; 
    } 

    function kill($pid) { 
        exec("kill -9 $pid", $output); 
    }

} 
?>