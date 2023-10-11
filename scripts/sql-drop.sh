#!/bin/sh
. $(dirname "$0")/sql-common.sh

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


