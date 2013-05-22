#!/bin/sh

service=$1
instance=$2

if [ -z "$service" -o -z "$instance" ]; then
    echo "Usage: $0 <service> <instance number>"
    exit
fi

if [ ! -d "$service" ]; then
    echo "Directory $service not found in current work dir"
    exit
fi

mkdir -p /var/runit/$service.$instance/log
ln -s ${PWD}/$service/log/run /var/runit/$service.$instance/log
ln -s ${PWD}/$service/run /var/runit/$service.$instance
