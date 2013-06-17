#!/bin/bash

# Functions used.
die () {
    echo $1
	echo  "   Usage: $(basename $0) start|stop source_list destination_list"
    exit 1
}


# Validating inputs

pool_name=$1
source_list=$2

[[ $# -ne 2 ]] && echo  "	Usage: $(basename $0) pool_name source_list " && exit

[[ -f "$source_list" ]] || die "Source list not present." 

# Exit if run as root.
[ `id -u` -eq 0 ] && die "Dont run script as root."

# Make directory with pool name and copy reject keys.
mkdir -p /tmp/rejected_keys/$pool_name/ ; for ip in `cat $source_list ` ; do echo -n "Copying rejected keys from : " ; dig -x $ip +short  ; scp $ip:/tmp/rejected-keys /tmp/rejected_keys/$pool_name/$ip ; done
