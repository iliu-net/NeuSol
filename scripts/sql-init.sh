#!/bin/sh
. $(dirname "$0")/sql-common.sh

if ! (echo 'show databases;' | mysql -uroot -p$root_passwd -h$db_host | grep -q '^'"$db_name"'$') ; then
  echo 'Database does not exist -- creating database'
  mysql -uroot -p$root_passwd -h$db_host  <<-_EOF_
	create database $db_name;
	GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'%' IDENTIFIED BY '$db_pass';
	_EOF_
else
  echo 'Database already exists'
fi

if [ $(echo 'show tables;' | mysql -u$db_user -p$db_pass -h$db_host $db_name|wc -l) -eq 0 ] ; then
  echo "Database needs to be initialized"
  mysql -u$db_user -p$db_pass -h$db_host $db_name < "$(dirname "$0")/init.sql"
fi
