#!/bin/bash
if ![ -z "$5" ]
  then
     /usr/local/bin/phpbrew use $5
fi
/usr/bin/php $*

if ![ -z "$5" ]
  then
     /usr/local/bin/phpbrew off
fi
