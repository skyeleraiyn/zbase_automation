<?php
class cluster_setup	{

	public function setup_membase_cluster()	{
		vbs_setup::vbs_start_stop("stop");
		vba_setup::vba_cluster_start_stop("stop");
		membase_setup::clear_cluster_membase_database();
                membase_setup::restart_membase_cluster();
		vba_setup::vba_cluster_start_stop("start");
		vbs_setup::populate_and_copy_config_file();
		vbs_setup::vbs_start_stop("start");
	}

}
?>
