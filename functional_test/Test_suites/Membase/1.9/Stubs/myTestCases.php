<?php

abstract class VBS_Basic_TestCase extends ZStore_TestCase {
	public function test_recieve_init()
	{
		
		vbs_setup::initial_setup();
		remote_function::remote_execution_popen(VBS_IP, "sudo /etc/init.d/IPM restart",FALSE);
		
		$VBA = new VBA(VBS_IP,IPMAPPER_PORT,4);
		$result = vba_stub_functions::check_initialization($VBA);
		
		$this->assertEquals(TRUE,$result,"init command not received by VBA");
	}

	public function test_VBA_receive_config()
	{
		
		vbs_setup::reset_vbs();

		$VBA = array();

		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);			//Spawing Pre-defined no. of VBAs
                        $VBA[$i]->set_non_blocking();					// Refercommon/application_functions/vbs_stub_functions/Agent.php (for VBA Class) 
                }

		$result = vba_stub_functions::check_initialization($VBA);			//Make sure that we rae getting INIT from VBS
		$this->assertEquals(TRUE,$result,"init command not received by VBA");	// Refer common/application_functions/vbs_functions/vba_stub_functions.php (for vb_funtions Class)
		
		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->ReplyAgent(8);					//Reply with the type of Agent (with no. of discs as capacity)

		$config = Array();
		for($i=0;$i<NO_OF_VBA;$i++)
		{	
			$config[$i] = vba_stub_functions::get_config($VBA[$i]);		//Making sure we are getting the config (neither SOCKET_ERROR nor SOCKET_TIMEOUT and nor WRONG COMMAND) 
			if($config[$i] === FALSE)
			{
				$result = FALSE;
				break;
			}
		}
		
		$this->assertEquals(TRUE,$result,"Config command not received by VBA");
	}

	public function test_VBA_send_no_agent_but_ok()
	{
		
                vbs_setup::reset_vbs();

		$VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
		$this->assertEquals(TRUE,$result,"init command not received by VBA");

		for($i=0;$i<NO_OF_VBA;$i++)
                	$VBA[$i]->ReplyOK();

		$config = Array();
		for($i=0;$i<NO_OF_VBA;$i++)
		{
			$config[$i] = vba_stub_functions::get_config($VBA[$i]);
			if($config[$i] === FALSE)
			{
				$result = FALSE;
				break;
			}
		}
		
		$this->assertEquals(FALSE,$result,"Received Config inspite of incorrect data sent");
	}

	public function test_VBA_send_no_agent_but_alive()
	{
		
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }
	
		$result = vba_stub_functions::check_initialization($VBA);
		$this->assertEquals(TRUE,$result,"init command not received by VBA");

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Heartbeat();

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }
		
                $this->assertEquals(FALSE,$result,"Received Config inspite of incorrect data sent");	
	}
	public function test_VBA_send_multiple_agent()
	{
		
		vbs_setup::reset_vbs();

		$VBA = array();

		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
		$this->assertEquals(TRUE,$result,"init command not received by VBA");

		for($j=0;$j<100;$j++)
			for($i=0;$i<NO_OF_VBA;$i++)
                        	$VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

		$this->assertEquals(TRUE,$result,"Did not receive Config");
		
		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->ReplyOK();

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));			//Starting the Heartbeat in a child thread

		for($i=0;$i<NO_OF_VBA;$i++)
                {
			$status = $VBA[$i]->readCommand();
			if($status === FALSE || !is_string($status) || strcmp($status," "))			//Making sure that VBS do not disconnect VBA due to above bombardment
                        {											//In readCommand:
                                $result = FALSE;								// " " -> TIMEOUT, FALSE -> DISCONNECTON, Array -> json_decoded_command
                                break;
                        }

                }

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Stop_Heart();									//Always make sure that every Start_Heart has a Stop_Heart
														//Hence assert after Stop
		
		$this->assertEquals(TRUE,$result,"Either VBA down or received extra configs");
	}
	
	public function test_VBA_send_no_ok_but_alive()
	{
		
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

                $this->assertEquals(TRUE,$result,"Did not receive Config");

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Heartbeat();

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

		for($i=0;$i<NO_OF_VBA;$i++)
		{
			$status = $VBA[$i]->readCommand();
			if($status === FALSE || !is_string($status) || strcmp($status," "))
			{
				$result = FALSE;
				break;
			}
		}

		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

		
		$this->assertEquals(TRUE,$result,"Either VBA down or received unneceassary Data");

	}

	public function test_VBA_send_no_ok_but_agent()
	{
		
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");
	
                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

                $this->assertEquals(TRUE,$result,"Did not receive Config");

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->ReplyAgent(2);

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

		for($i=0;$i<NO_OF_VBA;$i++)
		{
                        $status = $VBA[$i]->readCommand();
                        if($status === FALSE || !is_string($status) || strcmp($status," "))
                        {
                                $result = FALSE;
                                break;
                        }
                }
		
		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();		

		
                $this->assertEquals(TRUE,$result,"Either VBA down or received unneceassary Data");

	}

	public function test_VBA_send_multiple_ok()
	{
		
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {       
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

		$this->assertEquals(TRUE,$result,"Did not receive Config");
		
		for($j=0;$j<100;$j++)
			for($i=0;$i<NO_OF_VBA;$i++)
				$VBA[$i]->ReplyOK();
		
		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $status = $VBA[$i]->readCommand(10);
                        if($status === FALSE || !is_string($status) || strcmp($status," "))
                        {
                                $result = FALSE;
                                break;
                        }
                }

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

		$this->assertEquals(TRUE,$result,"Either VBA down or received unneceassary Data");
	}

	public function test_VBA_heartbeat_with_less_time()
	{
		
                vbs_setup::reset_vbs();
	
		$VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
			$VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }
												//Verifying that VBS do not disconnect if it receives multiple HeartBeats
                $this->assertEquals(TRUE,$result,"Did not receive Config");			//in the designated period

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i])-15);
		
		sleep(vba_stub_functions::get_heart_beat_time($config[0])-10);

		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $status = $VBA[$i]->readCommand();
                        if($status === FALSE || !is_string($status) || strcmp($status," "))
                        {
                                $result = FALSE;
                                break;
                        }
                }

		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

		
		$this->assertEquals(TRUE,$result,"Either VBA down or received unneceassary Data");
	}
	
	public function test_VBA_heartbeat_with_more_time()
        {
		
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
		{ 
			$VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
			$VBA[$i]->set_non_blocking();
		}
	
		$result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }
													// Verifying that VBS shuts down the VBA connection after not receiving HeartBeat in 
                $this->assertEquals(TRUE,$result,"Did not receive Config");				// the HeartBeatTime

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]) + 10);

		sleep(vba_stub_functions::get_heart_beat_time($config[0])+5);
		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $status = $VBA[$i]->readCommand();
                        if($status === FALSE || !is_string($status) || strcmp($status," "))
                        {
                                $result = FALSE;
                                break;
                        }
                }
                
		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

		
		$this->assertEquals(FALSE,$result,"VBS didn't disconnect VBA even without any heartbeat");
        }

	public function test_verify_basic_communication()		
	{
		
		vbs_setup::reset_vbs();
		
		$VBA = array();

		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

		$result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {       
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }
													//Testing the basic interaction with the VBS
		$this->assertEquals(TRUE,$result,"Did not receive Config");

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->ReplyOK();

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));
	
		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $status = $VBA[$i]->readCommand();
                        if($status === FALSE || !is_string($status) || strcmp($status," "))
                        {
                                $result = FALSE;
                                break;
                        }
                }

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Stop_Heart();

		$this->assertEquals(TRUE,$result,"Either VBA got disconnected or sent unnecessary messsage");
		sleep(vba_stub_functions::get_heart_beat_time($config[0]));

		for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $status = $VBA[$i]->readCommand();
                        if($status !== FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }
		
		$this->assertEquals(TRUE,$result,"VBA should have been disconnected by now.");
	
	}

	public function test_config_structure()
	{
		vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

                $result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

		$config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

                $this->assertEquals(TRUE,$result,"Did not receive Config");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

		for($i=0;$i<NO_OF_VBA;$i++)
		{
			$status = vba_stub_functions::verify_config_structure($config[$i]);				//Verifying that the structure of CONFIG command is correct
			if(is_string($status))
			{
				$result = FALSE;
				break;
			}
		}

		for($i=0;$i<NO_OF_VBA;$i++)
			$VBA[$i]->Stop_Heart();

		$this->assertEquals(TRUE,$result,$status);
	}

	public function test_no_of_peers_in_config()
        {
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

                $result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

                $config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

		
                $this->assertEquals(TRUE,$result,"Did not receive Config");

                //print_r(vba_stub_functions::get_vbucket_list($config));		// For Active bucket list
		//print_r(vba_stub_functions::get_vbucket_list($config,FALSE));	// For Replica bucket list

		for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $peers_count = vba_stub_functions::get_no_of_peer_vbas($config[$i]);                         //Making sure that a particular node (VBA) has its replicas in all other N-1 nodes
                        if($peers_count !== NO_OF_VBA-1)                                                        //for a cluster with N nodes
                        {
                                $result = FALSE;
                                break;
                        }
                }

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

                $this->assertEquals(TRUE,$result,"VBA do not have replicas in some other members of the cluster");

        }

	public function test_active_data()
	{
		vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

                $result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

                $config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

                $this->assertEquals(TRUE,$result,"Did not receive Config");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

		$active_list = vba_stub_functions::get_vbucket_list($config);
		$status = vba_stub_functions::verify_vbucket_list($active_list);						//Verifying that the vbucket distribution is uniform and without any duplication
		if(is_string($status))								
			$result = FALSE;

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

                $this->assertEquals(TRUE,$result,$status);
	}	
	
	public function test_replica_data()
        {
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

                $result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

                $config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

                $this->assertEquals(TRUE,$result,"Did not receive Config");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

                $replica_list = vba_stub_functions::get_vbucket_list($config,FALSE);
                $status = vba_stub_functions::verify_vbucket_list($replica_list);
                if($status === FALSE || is_string($status))
                        $result = FALSE;

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

                $this->assertEquals(TRUE,$result,$status);

        }


	public function test_replica_active_not_on_same_box()
        {
		vbs_setup::push_vbucketserver_config();
                vbs_setup::reset_vbs();

                $VBA = array();

                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $VBA[$i] = new VBA(VBS_IP,IPMAPPER_PORT,4+$i);
                        $VBA[$i]->set_non_blocking();
                }

                $result = vba_stub_functions::check_initialization($VBA);
                $this->assertEquals(TRUE,$result,"init command not received by VBA");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyAgent(8);

                $config = Array();
                for($i=0;$i<NO_OF_VBA;$i++)
                {
                        $config[$i] = vba_stub_functions::get_config($VBA[$i]);
                        if($config[$i] === FALSE)
                        {
                                $result = FALSE;
                                break;
                        }
                }

                $this->assertEquals(TRUE,$result,"Did not receive Config");

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->ReplyOK();

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Start_Heart(vba_stub_functions::get_heart_beat_time($config[$i]));

                $status = vba_stub_functions::verify_replica_active_not_on_same_box(vba_stub_functions::get_vbucket_list($config),vba_stub_functions::get_vbucket_list($config,FALSE));
		if($status === FALSE || is_string($status))
			$result = FALSE;

                for($i=0;$i<NO_OF_VBA;$i++)
                        $VBA[$i]->Stop_Heart();

                $this->assertEquals(TRUE,$result,"VBA has active and replicas on the same box");

        }

}




class VBS_Basic_TestCase_Full extends VBS_Basic_TestCase {

	public function keyProvider() {
		return Data_generation::provideKeys();
	}

	public function keyValueProvider() {
		return Data_generation::provideKeyValues();
	}

	public function keyValueFlagsProvider() {
		return Data_generation::provideKeyValueFlags();
	}
}

?>

