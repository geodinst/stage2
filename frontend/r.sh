#!/bin/bash
#The optimizer will only combine modules that are specified in arrays of string literals
#that are passed to top-level require and define calls,
#or the require('name') string literal calls in a simplified CommonJS wrapping.
#So, it will not find modules that are loaded via a variable name.
#You can always explicitly add modules that are not found via the optimizer's static analysis by using the include option.

LIB=/home/tomazz/work/lib
IN='app.js'
OUT='app.min.js'
CFGFILE='app.js'

#-o skipModuleInsertion=true

node $LIB/r.js -o name=$IN out=$OUT mainConfigFile=$CFGFILE