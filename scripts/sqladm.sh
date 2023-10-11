#!/bin/sh
. $(dirname "$0")/sql-common.sh

exec mysql -u$db_user -p$db_pass -h$db_host $db_name
