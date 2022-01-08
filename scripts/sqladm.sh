#!/bin/sh
set -euf -o pipefail

#
# these variables come from docker --link feature
#
root_passwd=$DB_ENV_MYSQL_ROOT_PASSWORD
db_host=$DB_PORT_3306_TCP_ADDR


neuhome=$(readlink -f $(dirname $0)/..)

param() {
  local configs="$neuhome/config/config.ini"
  if [ x"$(basename "$neuhome")" = x"NeuDev" ] ; then
    configs="$configs $neuhome/config/nonprod-config.ini"
  fi
  awk -vFS='=' '
    $1 == "'$1'" {
      res = ""
      q = ""
      for (i = 2; i <= NF ; i++) {
	res = res q $i
	q = "="
      }
    }
    END {
      print res
    }
  ' $configs
}
dnsparam() {
  param db_dns | tr ':;' '\n\n' | awk -vFS='=' '
    $1 == "'$1'" {
      print $2
    }
  '
}
db_user=$(param db_user)
db_pass=$(param db_pass)
db_name=$(dnsparam  dbname)


exec mysql -u$db_user -p$db_pass -h$db_host $db_name
