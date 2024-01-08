#!/bin/bash

deletion_list=()

. $(dirname $0)/.env

cd ../../../

for id in $@; do
    deletion_list+=($id)
    retval=$($PHP_PATH $WP_CLI post meta get $id _notificationinstance)
    arr=$(echo $retval | sed "s/,/ /g")
    for iid in $arr; do
        deletion_list+=($iid)
    done
done

wp post delete ${deletion_list[@]} --force
# echo ${deletion_list[@]}