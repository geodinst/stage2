<?php

namespace Drupal\stage2_admin;

class BackgroundProcess
{
	private $error=true;
	private $pid;
	public function __construct($Command)
	{
		$pid=$this->run_in_background($Command);
		$this->pid=$pid;
		if (!self::is_process_running($pid)) return;
		$this->error=false;
	}
	
	public function pid()
	{
		return $this->pid;
	}
	
	public function hasError()
	{
		return $this->error;
	}
	
	/**
	 * http://nsaunders.wordpress.com/2007/01/12/running-a-background-process-in-php/
	 */
	private function run_in_background($Command, $Priority = 0)
	{
		if($Priority)
			$cmd="nohup nice -n $Priority $Command > /dev/null 2> /dev/null";
		else
			$cmd="nohup $Command > /dev/null 2> /dev/null";
		
		$PID = shell_exec("$cmd & echo $!");
		return trim($PID);
	}
	
	public static function is_process_running($PID)
	{
		if (empty($PID)) return false;
		exec("ps $PID", $ProcessState);
		return(count($ProcessState) >= 2);
	}
	
	public static function kill_process($PID){
		if (empty($PID)) return;
		exec("kill $PID");
	}
}