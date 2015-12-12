#!/bin/bash

for i in {1..9}
do
	j="rm ../src/php_api/0$i"
	echo $j
	eval $j
done
