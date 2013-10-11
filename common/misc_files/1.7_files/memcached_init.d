#! /bin/sh
#
# chkconfig: - 55 45
# description:  The memcached daemon is a network memory cache service.
# processname: memcached
# config: /etc/sysconfig/memcached

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

# Source function library.
. /etc/rc.d/init.d/functions

PORT=11211
USER=nobody
MAXCONN=1024
OPTIONS=""

if [ -f /etc/sysconfig/memcached ];then
    . /etc/sysconfig/memcached
fi

# Check that networking is up.
if [ "$NETWORKING" = "no" ]
then
    exit 0
fi

RETVAL=0
prog="memcached"

start () {
    echo -n $"Starting $prog: "
    # insure that /var/run/memcached has proper permissions
    mkdir -p /var/run/memcached
    chown $USER /var/run/memcached
    daemon "{ /opt/zbase/bin/memcached -v -d -p $PORT -u $USER -c $MAXCONN -P /var/run/memcached/memcached.pid $OPTIONS 2>&1 | logger -t '[zbase]' -- ; } &"
    RETVAL=$?
    echo
    [ $RETVAL -eq 0 ] && touch /var/lock/subsys/memcached
}

stop () {
    echo -n $"Stopping $prog: "
    killproc memcached
    RETVAL=$?
    echo
    if [ $RETVAL -eq 0 ] ; then
        rm -f /var/lock/subsys/memcached
        rm -f /var/run/memcached.pid
    fi
}

restart () {
    stop
    start
}


# See how we were called.
case "$1" in
    start)
        start
        ;;
    stop)
    stop
    ;;
    status)
    status memcached
    ;;
    restart|reload)
    restart
    ;;
    condrestart)
    [ -f /var/lock/subsys/memcached ] && restart || :
    ;;
    *)
    echo $"Usage: $0 {start|stop|status|restart|reload|condrestart}"
    exit 1
esac

exit $?
