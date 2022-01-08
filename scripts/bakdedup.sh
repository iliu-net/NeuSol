#!/bin/sh
set -euf -o pipefail

dedup_backups() {
  local backup_dir="$1" prev=""

  find $backup_dir -name '*.zip' | while read zip
  do
    echo $(unzip -v $zip | awk '$1 == "Archive:" { next; } { $5=""; $6=""; print }' | md5sum | awk '{print $1}') $zip
  done | sort | while read md5 zip
  do
    if [ -z "$prev" ] ; then
      prev="$md5"
      continue
    fi
    if [ "$prev" = "$md5" ] ; then
      rm -v $zip
    fi
    prev="$md5"
  done
}

dedup_backups "$1"


