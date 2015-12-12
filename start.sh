#!/bin/bash


export IP="192.168.1.199"
export OPTIONS="--debug"
export ROOT="/home/yqc/luffy"
export UPLOAD_DIR="${ROOT}/upload"
export TEMP="/dev/null"
export LOG_DIR="${ROOT}/Log"
export NOHUP_DIR="${ROOT}/Log/nohup"
export DB_INI="${ROOT}/src/ini"

echo "stop ..."
source ./stop.sh > ${TEMP} 2>&1


if [ -d ${LOG_DIR} ]
then
    rm -rf ${LOG_DIR}
fi

if [ -d ${UPLOAD_DIR} ]
then
    rm -rf ${UPLOAD_DIR}
fi

mkdir ${LOG_DIR} 
mkdir ${NOHUP_DIR}
mkdir ${UPLOAD_DIR}


echo 'start route ...'
nohup src/route/route.py ${OPTIONS} -i ${IP} > ${NOHUP_DIR}/route.log 2>&1 &
sleep 0.5

echo 'start db_api ...'
nohup python src/db_api/zoro/db_api.py -i=${IP} -ini=${DB_INI}/db.ini > ${LOG_DIR}/db_api.log 2>&1 &
sleep 0.5

echo 'start log ...'
nohup python src/log/nami/upload_log.py --local_log_path=${UPLOAD_DIR} > ${LOG_DIR}/upload_log.log 2>&1 &
sleep 0.5

echo 'start forward ...'
nohup src/forward/forward.py ${OPTIONS} -i ${IP} > ${NOHUP_DIR}/forward.log 2>&1 &
sleep 0.5

echo 'start control ...'
nohup python src/hancock/hancock.py ${OPTIONS} -i ${IP} > ${LOG_DIR}/control.log 2>&1 &
sleep 0.5

echo 'start config ...'
./src/config/start.sh ${IP} ${LOG_DIR}/config.log > ${TEMP} 2>&1
sleep 0.5
