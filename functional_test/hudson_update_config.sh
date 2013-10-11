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

sed -i -e "s#\"HUD_PARAM_TEST_HOST_ARRAY\"#$HUD_PARAM_TEST_HOST_ARRAY#" \
    -e "s#\"HUD_PARAM_STORAGE_SERVER\"#$HUD_PARAM_STORAGE_SERVER#" \
    -e "s#\"HUD_PARAM_DISK_MAPPER_SERVER_ACTIVE\"#$HUD_PARAM_DISK_MAPPER_SERVER_ACTIVE#" \
    -e "s#\"HUD_PARAM_DISK_MAPPER_SERVER_PASSIVE\"#$HUD_PARAM_DISK_MAPPER_SERVER_PASSIVE#" \
    -e "s#\"HUD_PARAM_ACTIVE_DM_KEY\"#$HUD_PARAM_ACTIVE_DM_KEY#" \
    -e "s#\"HUD_PARAM_ZBASE_BUILD\"#$HUD_PARAM_ZBASE_BUILD#" \
    -e "s#\"HUD_PARAM_PROXYSERVER_BUILD\"#$HUD_PARAM_PROXYSERVER_BUILD#" \
    -e "s#\"HUD_PARAM_BACKUP_TOOL_BUILD\"#$HUD_PARAM_BACKUP_TOOL_BUILD#" \
    -e "s#\"HUD_PARAM_PECL_BUILD\"#$HUD_PARAM_PECL_BUILD#" \
    -e "s#\"HUD_PARAM_DISK_MAPPER_BUILD\"#$HUD_PARAM_DISK_MAPPER_BUILD#" \
    -e "s#\"HUD_PARAM_STORAGE_SERVER_BUILD\"#$HUD_PARAM_STORAGE_SERVER_BUILD#" \
    -e "s#\"/tmp/results\"#'/tmp/$JOB_NAME'#" \
    -e "s#\"HUD_PARAM_TEST_SUITE_ARRAY\"#$HUD_PARAM_TEST_SUITE_ARRAY#" config.php
