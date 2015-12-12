ps -ef | grep 'business' | grep -v grep| awk '{print $2}' | xargs kill -9
