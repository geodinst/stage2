#!/bin/bash
export PGPASSWORD=$1
FNAME=$2

psql -h localhost -U stage2_admin stage2_test < $FNAME
