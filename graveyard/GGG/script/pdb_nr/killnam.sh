#!/bin/sh
#
#	Kill processes by name.
#
# -- find all running processes, get "$2" ones, remove the grepper, 
#    select "\n  something" (i.e. PID) and thus obtain the "($3)" pids.
#
# -- make each of these processes receive the specified signal.
#
#			JRValverde (09 - may - 2000)
#
#
if [ $# -ne 3 ]
then
	echo "usage: killnam -SIGNAL -[c|u] name"
	echo "       -c  ==>  kill by command name"
	echo "       -u  ==>  kill by user name"
	echo "       -t  ==>  kill by pseudo tty"
	echo ""
	exit
fi

if [ "$2" = "-c" ]
then
    for pid in `/bin/ps -e -o pid,comm | grep " $3\$" | grep -v grep | sed -e 's/^  *//' -e 's/ .*//'`
    do
    	echo Sending $1 to $pid
	kill $1 $pid
    done
elif [ "$2" = "-u" ]
then
    for pid in `/bin/ps -e -o pid,user | grep " $3\$" | grep -v grep | sed -e 's/^  *//' -e 's/ .*//'`
    do
    	echo Sending $1 to $pid
	#kill $1 $pid
    done
elif [ "$2" = "-t" ]
then
    for pid in `/bin/ps -e -o pid,tty,user | grep "pts/${3} " | grep -v grep | sed -e 's/^  *//' -e 's/ .*//'`
    do
    	echo Sending $1 to $pid
	kill $1 $pid
    done
else
    echo "Error: unknown option [$2]"
    echo ""
    echo "usage: killnam -SIG -[c|u] name"
    echo ""
    exit
fi

