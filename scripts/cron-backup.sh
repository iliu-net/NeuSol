#!/bin/sh
set -euf
(
  exec 2>&1
  apphome=$(dirname $(dirname $(readlink -f "$0")))
  bakdir="$1"

  cd $apphome
  php index.php /backup
  $apphome/scripts/bakdedup.sh $bakdir
  chown -R apache:apache $bakdir

) | logger -t neusol-backup
