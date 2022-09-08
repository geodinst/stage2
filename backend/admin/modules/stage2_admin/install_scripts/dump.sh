#!/bin/bash
export PGPASSWORD=$1
FNAME=$2
pg_dump -h localhost -U stage2_admin -c --no-owner stage2 -f $FNAME