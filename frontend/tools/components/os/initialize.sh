#!/bin/bash

# set sudo password
echo ${CONFIG[pass]} | sudo -S apt-get update
