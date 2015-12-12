#!/bin/bash

export TEMP="/dev/null"

echo "stop route ..."
kill -9 `ps aux | grep route.py | grep -v grep | awk '{print $2}'` > ${TEMP} 2>&1

echo "stop control ..."
kill -9 `ps aux | grep control.py | grep -v grep | awk '{print $2}'` > ${TEMP} 2>&1

echo "stop db api ..."
kill -9 `ps aux | grep db_api.py | grep -v grep | awk '{print $2}'` > ${TEMP} 2>&1

echo "stop upload log ..."
kill -9 `ps aux | grep upload_log.py | grep -v grep | awk '{print $2}'` > ${TEMP} 2>&1

echo "stop forward ..."
kill -9 `ps aux | grep forward.py | grep -v grep | awk '{print $2}'` > ${TEMP} 2>&1

echo "stop control ..."
kill -9 `ps aux | grep hancock.py | grep -v grep | awk '{print $2}'` > ${TEMP} 2>&1

echo "stop config ..."
./src/config/stop.sh > ${TEMP} 2>&1
