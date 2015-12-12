#!/bin/sh 
#update project config
configFileName=$(pwd)/src/config/apache-tomcat-8.0.27/webapps/ROOT/WEB-INF/classes/conf.properties
logFileName=$(pwd)/src/config/apache-tomcat-8.0.27/webapps/ROOT/WEB-INF/classes/log4j.properties
path=` echo $2 | sed 's#\/#\\\/#g'`
sed -i 's/log4j.appender.DAILY_ROLLING_FILE.File=.*/log4j.appender.DAILY_ROLLING_FILE.File='$path'/' $logFileName
sed -i 's/HOSTNAME=.*/HOSTNAME='$1'/' $configFileName
sed -i 's/CONFIG_API_SERVER=.*/CONFIG_API_SERVER=http:\/\/'$1':4201/' $configFileName
$(pwd)/src/config/apache-tomcat-8.0.27/bin/startup.sh

