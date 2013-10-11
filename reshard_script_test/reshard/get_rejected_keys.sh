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
