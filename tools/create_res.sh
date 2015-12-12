#!/bin/bash

for i in {1..9}
do
	j="ln -s /media/disk/0$i ../src/php_api/0$i"
	echo $j
	eval $j
done
