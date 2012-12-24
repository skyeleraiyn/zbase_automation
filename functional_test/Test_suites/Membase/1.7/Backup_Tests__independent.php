<?php


abstract class IBR_Backup_TestCase extends ZStore_TestCase {

	public function test_Simple_Backup() {
#AIM // Pump in keys to master and replicate it across to the slave. Run the backup script and check if backup is proper
#EXPECTED RESULT // All keys and checkpoint information are correctly placed in the backup

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "full");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);	
		//Pump in 5000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 5000, 5000, 1),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);

		//Check if key count on slave and backup are the same
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");
		//Check if last closed checkpoint on slave and backup are the same
		$chk_point_master = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_master, $chk_point_backup, "Checkpoint_mismatch in backup");
		
	}

	public function test_Single_Backup_2_Chkpoints() {
		#AIM // Pump in keys to fill 2 checkpoints to master and replicate it across to the slave. Run the backup script and check if backup is proper
		#EXPECTED RESULT // All keys and checkpoint information are correctly placed in the backup

		//Initialise the two machines
		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "full");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 10000 keys with chk_max_items set to 5000 so that only 2 checkpoint are there and they are closed
		$this->assertTrue(Data_generation::add_keys( 10000, 5000, 1),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		//Check if key count on slave and backup are the same
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");
		//Check if last closed checkpoint on slave and backup are the same
		$chk_point_master = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_master, $chk_point_backup, "Checkpoint_mismatch in backup");

	}

	public function test_Single_Backup_3_Chkpoints_Incr() {
		#AIM // Pump in keys to fill 3 checkpoints to master and replicate it across to the slave. Run the backup script as incr and check if backup is proper
		#EXPECTED RESULT // All keys and checkpoint information are correctly placed in the backup

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 15000 keys with chk_max_items set to 5000 so that only 3 checkpoint are there and they are closed
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 1),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		//Check if key count on slave and backup are the same
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");
		//Check if last closed checkpoint on slave and backup are the same
		$chk_point_master = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_master, $chk_point_backup, "Checkpoint_mismatch in backup");

	}

	public function test_Single_Backup_3_Chkpoints_Full() {
		#AIM // Pump in keys to fill 3 checkpoints to master and replicate it across to the slave. Run the backup script as full and check if backup is proper
		#EXPECTED RESULT // All keys and checkpoint information are correctly placed in the backup

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "full");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 15000 keys with chk_max_items set to 5000 so that only 3 checkpoint are there and they are closed
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 1),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		//Check if key count on slave and backup are the same
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");
		//Check if last closed checkpoint on slave and backup are the same
		$chk_point_master = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_master, $chk_point_backup, "Checkpoint_mismatch in backup");

	}

	public function test_Single_Backup_6_Chkpoints_Intermediate_Tap_Incr() {
		#AIM // Pump in keys, then register tap. Pump in more keys. Run backup script as incr and check the backup
		#EXPECTED RESULT // Only the keys pumped in after tap registration is backed up

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		tap_commands::deregister_backup_tap_name(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 15000 keys with chk_max_items set to 5000 so that only 3 checkpoint are there and they are closed
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 1),"Failed adding keys");
		//Register backup tap name
		tap_commands::register_backup_tap_name(TEST_HOST_2);
		//Pump in 15000 more keys with key id starting from 15001 
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 15001),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_backup, "15000", "Key_count_mismatch in backup");	// Fails: gets all the keys or 0 keys
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_backup, "3", "Checkpoint_mismatch in backup");

	}

	public function test_Single_Backup_6_Chkpoints_Intermediate_Tap_Full() {
		#AIM // Pump in keys, then register tap. Pump in more keys. Run backup script as full and check the backup
		#EXPECTED RESULT // Only the keys pumped in after tap registration is backed up

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		tap_commands::deregister_backup_tap_name(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "full");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 15000 keys with chk_max_items set to 5000 so that only 3 checkpoint are there and they are closed
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 1),"Failed adding keys");
		//Register backup tap name
		tap_commands::register_backup_tap_name(TEST_HOST_2);
		//Pump in 15000 more keys with key id starting from 4
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 15001),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		//Check if key count on slave and backup are the same
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_slave, $count_backup, "Key_count_mismatch in backup");
		//Check if last closed checkpoint on slave and backup are the same
		$chk_point_master = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_master, $chk_point_backup, "Checkpoint_mismatch in backup");

	}

	public function test_Backup_Inconsistent_Slave_Chk_True() {
		#AIM // Set inconsistent_slave_chk option to true on master. Pump in keys and take a backup
		#EXPECTED RESULT // Backup size should be 0

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "full");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "inconsistent_slave_chk", "true");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 15000 keys with chk_max_items set to 5000 so that only 3 checkpoint are there and they are closed
		Data_generation::add_keys( 15000, 5000, 1);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		//Check if key count on slave and backup are the same
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_backup, "0", "Key_count_mismatch in backup");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_backup, "0", "Checkpoint_mismatch in backup");

	}

	public function test_Repeated_Incr_Backup() {
		#AIM // Incremental backup with tap registered at start, then take incremental backup again 
		#EXPECTED RESULT // Backup size should be 0

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 5000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 5000, 5000, 1),"Failed adding keys");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_backup, "0", "Checkpoint_mismatch in backup");	// Fails: creates file even at the second attempt

	}

	public function test_Backup_With_Tap_To_Open_Checkpoint() {
		#AIM //Register tap name to point at current open checkpoint and then take a backup
		#EXPECTED RESULT // Backup size should be 0

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		tap_commands::deregister_backup_tap_name(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 5000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 15000, 5000, 1),"Failed adding keys");
		tap_commands::register_backup_tap_name(TEST_HOST_2);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_backup, "0", "Checkpoint_mismatch in backup");	// Fails: file is created

	}

	public function test_Backup_l1b() {
		#AIM // Pump in data to create x number of checkpoints(x closed and x+1 open). 
			//Register tap name with .l and .b option such that tap cursor points to last closed checkpoint and then take a backup
		#EXPECTED RESULT // Only the keys in the last closed checkpoint is backed up

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		tap_commands::deregister_backup_tap_name(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 5000);
		//Pump in 5000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 15000,  5000, 1),"Failed adding keys");
		$slave_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$slave_closed_chkpoint = $slave_closed_chkpoint - 1;
		//Registering tap name
		tap_commands::register_backup_tap_name(TEST_HOST_2, " -l ".$slave_closed_chkpoint." -b");
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($count_backup, "5000", "Key_count_mismatch in backup");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertEquals($chk_point_backup, "1", "Checkpoint_mismatch in backup");

	}

	public function test_Double_Backup_Single_Checkpoint() {
		#AIM // One checkpoint keys split over two backup files
		#EXPECTED RESULT //Data to be split over two backups

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 50000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		//Pump in 250000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 1500, 50000, 1, 10240),"Failed adding keys");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		sleep(60);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 10);
		sleep(5);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$slave_closed_chkpoint = stats_functions::get_checkpoint_stats(TEST_HOST_2, "last_closed_checkpoint_id");
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$count_backup += membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_1);		
		$this->assertEquals($count_backup, $count_slave, "Key_count_mismatch in backup");
		$chk_point_backup = membase_function::sqlite_chkpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_1);
		$this->assertEquals($chk_point_backup, $slave_closed_chkpoint, "Checkpoint_mismatch in backup");
	}

	public function test_Backup_Split_Size_Check() {
		#AIM //Modify split_size and verify that the backups being taken are of the specified size for 10MB, 25MB, 50MB 
		#EXPECTED RESULT // Backups of the required sizes are getting created

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 3600);
		//Pump in 250000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 1500, 500000, 1, 10240),"Failed adding keys");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		sleep(60);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 10);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$size = backup_tools_functions::get_backup_size(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertGreaterThanOrEqual(9961472, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(11010048, $size, "Backup database with greater size than expected");
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$count_backup += membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_1);
		$this->assertEquals($count_backup, $count_slave, "Key_count_mismatch in backup");

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 3600);
		//Pump in 250000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 3000, 500000, 1, 10240),"Failed adding keys");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		sleep(60);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 25);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$size = backup_tools_functions::get_backup_size(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertGreaterThanOrEqual(25690112, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(26738688, $size, "Backup database with greater size than expected");
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$count_backup += membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_1);
		$this->assertEquals($count_backup, $count_slave, "Key_count_mismatch in backup");

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 3600);
		//Pump in 250000 keys with chk_max_items set to 5000 so that only 1 checkpoint is there and it is closed
		$this->assertTrue(Data_generation::add_keys( 5500, 500000, 1, 10240),"Failed adding keys");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		sleep(60);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 50);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$size = backup_tools_functions::get_backup_size(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$this->assertGreaterThanOrEqual(51904512, $size, "Backup database with less size than expected");
		$this->assertLessThanOrEqual(52953088, $size, "Backup database with greater size than expected");
		$count_slave = stats_functions::get_all_stats(TEST_HOST_2, "curr_items");
		$count_backup = membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_0);
		$count_backup += membase_function::sqlite_cpoint_count(TEST_HOST_2, TEMP_OUTPUT_FILE_1);
		$this->assertEquals($count_backup, $count_slave, "Key_count_mismatch in backup");


	}

	public function test_Set_Delete_Backup() {
		#AIM //Incremental backup with tap registered at start, pump in 4k sets and then 4k deletes with high checkpoint closure rate. Forcefully close the checkpoint, take a backup.
		#EXPECTED RSEULT //Backup contains only delete mutations.

		membase_setup::reset_membase_vbucketmigrator(TEST_HOST_1, TEST_HOST_2);
		backup_tools_functions::clear_temp_backup_data(TEST_HOST_2);
		backup_tools_functions::set_backup_type(TEST_HOST_2, "incr");
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_max_items", 500000);
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 3600);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 50);
		$this->assertTrue(Data_generation::add_keys( 4000, 500000, 1),"Failed adding keys");
		$this->assertTrue(Data_generation::delete_keys( 4000, 1));
		flushctl_commands::set_flushctl_parameters(TEST_HOST_1, "chk_period", 60);
		sleep(60);
		backup_tools_functions::set_backup_const(TEST_HOST_2, "SPLIT_SIZE", 50);
		backup_tools_functions::run_backup_script(TEST_HOST_2);
		$del_mutations = sqlite_functions::sqlite_select(TEST_HOST_2,"count(*)", "cpoint_op where op='d' ", TEMP_OUTPUT_FILE_0);
		$set_mutations = sqlite_functions::sqlite_select(TEST_HOST_2,"count(*)", "cpoint_op where op='m' ", TEMP_OUTPUT_FILE_0);
		$this->assertEquals($del_mutations, 4000, "Delete mutations not available in backup");
		$this->assertEquals($set_mutations, 0, "Deletion of keys not done properly");
	}


}

class IBR_Backup_TestCase_Full extends IBR_Backup_TestCase{

	public function keyProvider() {
		return Utility::provideKeys();
	}

}

