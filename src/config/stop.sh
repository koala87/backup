#!/bin/sh
$(pwd)/src/config/apache-tomcat-8.0.27/bin/shutdown.sh
ps -ef | grep 'tomcat' | grep -v grep| awk '{print $2}' | xargs kill -9
