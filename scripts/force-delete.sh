#!/bin/sh -x

deletion_list=()

for id in $@; do
    deletion_list+=($id)
    retval=$(wp post meta get $id _notificationinstance)
    arr=$(echo $retval | sed "s/,/ /g")
    for iid in $arr; do
        deletion_list+=($iid)
    done
done

wp post delete $deletion_list --force
# echo $deletion_list