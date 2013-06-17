<?php

class Agent
{
        var $socketWrap;
        var $agent;
	var $id;

	var $heartPid;
	var $sem_id;
	var $shm_id;

        function __construct($Agent,$h,$p,$id)
        {
                $this->agent = $Agent;
		$this->heartPid = null;
		$this->id = $id;
	
                $this->socketWrap = new SocketWrapper($h,$p);
                if($Agent == 'VBA')
		{
			$min = VBA_START_PORT;
			$max = MOXI_START_PORT-1;
		}
		else
		{
			$min = MOXI_START_PORT;
			$max = MOXI_START_PORT + 1000;
		}
	
		while(1)
			if($this->socketWrap->connect(0,mt_rand($min,$max)) === TRUE)
				break;
	}
	function close()
	{
		$this->socketWrap->close_socket();
	}
	function set_non_blocking()
	{
		$this->socketWrap->set_nonBlock();
	}
        function readCommand($timeout=5)
        {
		$start = time();
		$len = $this->socketWrap->readData(4,$timeout);
		$time_spent = time() - $start;
		
		$newtimeout = $timeout - $time_spent;
//		echo "GOT LENGTH IN ";echo $time_spent;echo " which is ";echo $len;echo "rah\n";
		if($len === FALSE)
		{
//			echo "GOT FALSE\n";
			return(FALSE);
		}
		else if(!strcmp($len," "))
		{
//			echo "GOT NULL\n";
			return(" ");
		}
		else
		{
//			echo "FINDING DATA\n";
			$data = $this->socketWrap->readData((int)$len,$newtimeout);	
//			print_r($data);
		}
		
		if($data === FALSE)
		{
			return(FALSE);
		}
		else if(!strcmp($data," "))
		{
			return(" ");
		}
		else
		{
//			print_r(json_decode($data,true));
			return(json_decode($data,true));
        	}
	}
        function ReplyAgent($capacity=null)	//[-1=Agent is VBA but capacity not given]
        {
                $data = array();
		$data['Agent'] = $this->agent;

		if($this->agent == "VBA")
                {
                        if($capacity)
				$data['Capacity'] = $capacity;
                        else
                                return(-1);
                }
		return($this->socketWrap->sendData(json_encode($data)));
	}
        function ReplyOK()
        {
		$data = array();
		$data['Status'] = 'OK';
		
		return($this->socketWrap->sendData(json_encode($data)));
        }
        function Heartbeat()
        {
		$data = array();
		$data['Cmd'] = 'ALIVE';
		return($this->socketWrap->sendData(json_encode($data)));
        }
	function Start_Heart($timeout)		//Spawning another process to take care of the heartbeat .... Important to wait for this child in the parent
	{					//[-1=UNSUCCESSFULL, 0=SUCCESSFULL]
		
		if (FALSE != ($this->shm_id = shmop_open(ftok(".",chr(32 + $this->id)),"c",0644,2)))
		{
			$this->sem_id = sem_get($this->id,1);
			shmop_write($this->shm_id,"0",0);
		
			$this->heartPid = pcntl_fork();
			if ($this->heartPid == -1)
			{
				return(-1);
			}
			else if (!$this->heartPid)
			{
				$lastheart = time();
				while(1)
				{
					sem_acquire($this->sem_id);
			
					$status = shmop_read($this->shm_id,0,1);
					if (strcmp($status,"1"))
					{
						sem_release($this->sem_id);
						$now = time();
						if($now - $lastheart >= $timeout)
						{
							if($this->Heartbeat() === FALSE)
							{
								//sem_release($this->sem_id);
								shmop_close($this->shm_id);
								exit(0);
							}	
							$lastheart = $now;
						}
					}
					else
					{
						sem_release($this->sem_id);
						shmop_close($this->shm_id);
						exit(0);
					}
				}		
			}
			return(0);
		}
		else
			return(FALSE);
	}
	function Stop_Heart()			//Killing the heartbeat process
	{
		sem_acquire($this->sem_id);
		shmop_write($this->shm_id,"1",0);
		sem_release($this->sem_id);
		
		shmop_delete($this->shm_id);
		shmop_close($this->shm_id);

		pcntl_waitpid($this->heartPid,$status);
		if($status != 0)
			return(-1);
		else
			return(0);
	}
}
?>
