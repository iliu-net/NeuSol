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

echo "This will lead to data loss"
echo -n "Are you sure? [y/N] "
read YESNO
if [ x"$YESNO" != x"y" ] ; then
  echo "Aborted!"
  exit
fi

isqlcmd="mysql -uroot -p$root_passwd -h$db_host"

if (echo 'show databases;' | $isqlcmd | grep -q '^'"$db_name"'$') ; then
  # https://www.cyberciti.biz/faq/how-to-delete-remove-user-account-in-mysql-mariadb/

  if [ -n "$(echo "SHOW GRANTS FOR '$db_user'@'%';"|$isqlcmd --skip-column-names)" ] ; then
    echo "Revoking privileges for $db_user"
    echo "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '$db_user'@'%';" | $isqlcmd
  fi
  if [ -n "$(echo "SELECT * FROM mysql.user WHERE User = '$db_user' AND Host = '%';" | $isqlcmd --skip-column-names)" ] ; then
    echo "Dropping user $db_user"
    echo "DROP USER '$db_user'@'%';" | $isqlcmd
  fi
  if [ -n "$(echo 'show databases;'|$isqlcmd --skip-column-names|awk '$1 == "'"$db_name"'" { print }')" ] ; then
    echo "Dropping database $db_name"
    echo "DROP DATABASE $db_name;" | $isqlcmd
  fi
else
  echo 'Database does not exist'
fi


