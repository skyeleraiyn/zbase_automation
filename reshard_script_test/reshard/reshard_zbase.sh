#!/bin/bash

# 	Copyright 2013 Zynga Inc
#
#   Licensed under the Apache License, Version 2.0 (the "License");
#   you may not use this file except in compliance with the License.
#   You may obtain a copy of the License at
#
#       http://www.apache.org/licenses/LICENSE-2.0
#
#   Unless required by applicable law or agreed to in writing, software
#   distributed under the License is distributed on an "AS IS" BASIS,
#   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#   See the License for the specific language governing permissions and
#   limitations under the License.

# Functions used.
die () {
    echo $1
	echo  "Usage: $(basename $0) start|stop|status [source_list] [destination_list]||[keyonly] [cluster]"
    exit 1
}

# Calculating current key counts
getStatus() {

	echo -e "Getting current counts.\n"

    # Add up dest curr_items.
    [[ -f /tmp/reshard_dest_count ]] && sudo rm /tmp/reshard_dest_count
    if [ "$keyonly" = "true" ];then
    export WCOLL=$source_list
    pdsh -R ssh "echo stats tap| nc localhost 11211 | grep -w ep_tap_queue_drain" >> /tmp/reshard_dest_count
    dest_count=$(cat /tmp/reshard_dest_count | awk '{sum += $4}'END'{print sum}')
    else
    cat $dest_list | sed 's/:.*//' > /tmp/reshard_dest_ips
    export WCOLL=/tmp/reshard_dest_ips
    pdsh -R ssh "echo stats | nc localhost 11211 | grep -w curr_items" >> /tmp/reshard_dest_count
    dest_count=$(cat /tmp/reshard_dest_count | awk '{sum += $4}'END'{print sum}')
    fi

    # Add up source curr_items.
    export WCOLL=$source_list
    [[ -f /tmp/reshard_source_count ]] && sudo rm /tmp/reshard_source_count

    if [ "$cluster" = "true" ];then 
    pdsh -R ssh "/opt/couchbase/bin/cbstats localhost:11210 all | grep -w curr_items" >> /tmp/reshard_source_count
    source_count=$(cat /tmp/reshard_source_count | awk '{sum += $3}'END'{print sum}')
    else
    pdsh -R ssh "echo stats | nc localhost 11211 | grep -w curr_items" >> /tmp/reshard_source_count
    source_count=$(cat /tmp/reshard_source_count | awk '{sum += $4}'END'{print sum}')
    fi

	# Get count of rejecteed keys.
	export WCOLL=$source_list
    [[ -f /tmp/reshard_rejected_count ]] && sudo rm /tmp/reshard_rejected_count
    pdsh -R ssh "if [ -f /tmp/rejected-keys ];then wc -l /tmp/rejected-keys | awk  '{print $1}';fi" >> /tmp/reshard_rejected_count
    rejected_count=$(cat /tmp/reshard_rejected_count | awk '{sum += $2}'END'{print sum}')

    echo "	Keys in source pool :$source_count"
    if [ "$keyonly" = "true" ];then
    echo -e "	Total keys received:$dest_count"
    echo -e "	Count of matched keys:$rejected_count\n"
    else 
    echo -e "	Keys in destination pool :$dest_count\n"
    echo -e "	Count of rejected keys :$rejected_count\n"
    fi
   # echo -e "	Count of source key - ( destination key + rejected keys ) :$(($source_count - ($dest_count + $rejected_count)))\n"
	export WCOLL=$source_list
    [[ -f /tmp/reshard_pdsh_out ]] && rm /tmp/reshard_pdsh_out
    pdsh -R ssh "ps -ef | grep -q '/opt/zbase/bin/ep_engine/management/tap-redistribute-keys.p[y]' || hostname" | sed 's/:.*//' > /tmp/reshard_pdsh_out
    wc=$(wc -l /tmp/reshard_pdsh_out | awk  '{print $1}')

	echo -e "\nChecking if reshard script is running on all source machines.\n"
    # If reshard script is not running, will start reshard script.
    if [[ $wc -ne 0 ]] ; then
		echo -e "\nReshared script stopped on $wc machine(s).\n"
    	echo -e "Please run this script with start command.\n"
		cat /tmp/reshard_pdsh_out
	else
		echo -e "\nReshard script is running on all source machines.\n"
	fi

}

stop_script() {
	# Check status before stopping.
	getStatus

	# Stop all running reshard scripts.
	export WCOLL=$source_list
	stop_command="running=\$(ps -ef | grep '/opt/zbase/bin/ep_engine/management/tap-redistribute-keys.p[y]') && pid=\$(echo \$running | awk '{print \$2}') && sudo kill \$pid"

	pdsh -R ssh $stop_command > /dev/null 2>&1

	[[ -f /tmp/reshard_pdsh_out ]] && rm /tmp/reshard_pdsh_out
	pdsh -R ssh "ps -ef | grep -q '/opt/zbase/bin/ep_engine/management/tap-redistribute-keys.p[y]' && hostname"  > /tmp/reshard_pdsh_out 2>/dev/null
	wc=$(wc -l /tmp/reshard_pdsh_out | awk  '{print $1}')

	if [[ $wc -ne 0 ]] ; then
		echo -e "Failed to stop on the following servers.\n"
		cat /tmp/reshard_pdsh_out
	else 
		echo -e "Stopped on all source machines.\n"
	fi
	exit
}


# Validating inputs

action=$1
source_list=$2
dest_list=$3
cluster="false"
keyonly="false"

[[ $# -lt 3 ]] && die

if [[ "$4" == "cluster" ]];then
cluster="true"
fi

options=''
[[ -f "$source_list" ]] || die "Source list not present." 
echo "third arg is $3"
if [[ "$3" == "keyonly" ]];then
keyonly="true";
if [ -z $RESHARD_FILTER ];then
RESHARD_FILTER="None"
fi
options=$options" -k $RESHARD_FILTER"
echo "" > /tmp/dest
dest_list="/tmp/dest"
else 
[[ -f $dest_list ]] || die "Destination list not present." 
fi

if [ "$action" = "start" ]; then
	echo -e "Starting...\n"
elif [ "$action" = "stop" ] ; then
	echo -e "Stopping...\n"
	stop_script
	exit
elif [ "$action" = "status" ] ; then
	getStatus
	exit
else
	die "Only start, stop and status is supported."
fi


# Exit if run as root.
[ `id -u` -eq 0 ] && die "Dont run script as root."

# Pull down required files from S3.
echo "Cleaning directories on source servers"
commands='sudo rm -rf /tmp/reshard ;sudo mkdir -p /opt/zbase/bin/ep_engine/management' 
export WCOLL=$source_list
pdsh -R ssh $commands
echo "Done cleaning directories on source."


echo "Copying down files from S3."
commands='mkdir -p ~/.s3conf/ && sudo cp /root/.s3conf/s3config.yml ~/.s3conf/s3config.yml && sudo chown `whoami` ~/.s3conf/s3config.yml && s3cmd get zstore:reshard_cluster.tar /tmp/reshard.tar && tar -xC /tmp/ -f /tmp/reshard.tar && sudo mv /tmp/reshard/* /opt/zbase/bin/ep_engine/management/ && rm ~/.s3conf/s3config.yml'
export WCOLL=$source_list
pdsh -R ssh $commands
echo "Done copying files from S3."


# Copying destination list to source.
echo "Copying destination list to source machines"
i=0
total_source=$(wc -l $source_list  | awk  '{print $1}')

if [[ "$4" == "cluster" ]];then
for ip in `cat $source_list` ; do tput sc ; echo -n "Copied $i / $total_source files" ; scp -o StrictHostKeyChecking=no $dest_list $ip:/tmp/reshard/destination_list.txt  ; ssh $ip "echo $ip > /tmp/reshard/machine_ip" ; ((i++)) ; tput el1 ; tput rc ; done 
else
for ip in `cat $source_list` ; do tput sc ; echo -n "Copied $i / $total_source files" ; scp -o StrictHostKeyChecking=no $dest_list $ip:/tmp/reshard/destination_list.txt  ; ssh $ip "echo $i > /tmp/reshard/source_index" ; ((i++)) ; tput el1 ; tput rc ; done 
echo "Done."
fi

# Command to start reshard.
if [[ "$cluster" == "true" ]];then
run_command="python /opt/zbase/bin/ep_engine/management/tap-redistribute-keys.py -s 5 -q 50 -d /tmp/reshard/destination_list.txt $options -z \$(cat /tmp/reshard/machine_ip):11210"
else
run_command="python /opt/zbase/bin/ep_engine/management/tap-redistribute-keys.py -s 5 -q 50 -d /tmp/reshard/destination_list.txt $options -n  $i -i \$(cat /tmp/reshard/source_index) localhost:11211"
fi

count=0
stopped=0

while true; 
do 
	# Checking if reshard script is already running
	echo -e "Checking if reshard script is running on source machines.\n"
	tput sc
	export WCOLL=$source_list
	[[ -f /tmp/reshard_pdsh_out ]] && rm /tmp/reshard_pdsh_out
	pdsh -R ssh "ps -ef | grep -q '/opt/zbase/bin/ep_engine/management/tap-redistribute-keys.p[y]' || hostname" | sed 's/:.*//' > /tmp/reshard_pdsh_out
	wc=$(wc -l /tmp/reshard_pdsh_out | awk  '{print $1}')

	# If reshard script is not running, will start reshard script.
	if [[ $wc -ne 0 ]] ; then
		if [[ $stopped -ne 0 ]] ; then
			echo -e "\nReshared script stopped on $wc machine(s).\n"
		fi
			echo -e "Starting resharding on the following machines.\n"
	    cat /tmp/reshard_pdsh_out
	    export WCOLL=/tmp/reshard_pdsh_out
	    pdsh -R ssh "[[ -f /tmp/rejected-keys ]] && sudo rm /tmp/rejected-keys" > /dev/null 2>&1
	    pdsh -R ssh "nohup $run_command > /dev/null 2>&1 &"
	else 
	    echo -n "Reshard script is running fine on all nodes."
		exit 0
		tput el1
		tput rc
	fi
	((stopped++))
	((count++))

	if [ $count -gt 2 ] ; then
	    count=0
	    getStatus
	fi
done




