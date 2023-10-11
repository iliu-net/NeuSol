#!/bin/sh
set -euf -o pipefail

#
# these variables come from docker --link feature
#
root_passwd=${DB_ENV_MYSQL_ROOT_PASSWORD:-}
db_host=${DB_PORT_3306_TCP_ADDR:-}

if [ -z "$root_passwd" ] || [ -z "$db_host" ] ; then
  echo "Missing db_host and/or root passwd" 1>&2
  exit 1
fi

apphome=$(readlink -f $(dirname $0)/..)

param() {
  local configs="$apphome/config/config.ini"
  (echo "$apphome"| grep -q -E 'Dev(/|$)') && configs="$configs $apphome/config/nonprod-config.ini"
  [ -f $(echo $apphome | cut -d/ -f1-3)/config.ini ] \
      && configs="$configs $(echo $apphome | cut -d/ -f1-3)/config.ini"

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
db_name=$(dnsparam dbname)
