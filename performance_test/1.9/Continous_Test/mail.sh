#!/bin/bash
failure=$(cat /tmp/continous_test/result.log|grep `date +"%d-%m-%Y"`|grep ERROR)
echo $failure |mail -s "Failure report for `date +"%d-%m"`" aasok@zynga.com
