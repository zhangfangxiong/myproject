#/bin/sh

basedir=/data/www/dfs/raw
hostid=$1
if [ -z $1 ]; then
    echo "run sh $0 hostid"
    exit
fi

dirkeys='0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z'
echo $dirkeys
for i in $dirkeys
do
    for j in $dirkeys
    do
        for k in $dirkeys
        do
            dfsdir=$basedir/$hostid/$i/$j/$k
            echo $dfsdir
            mkdir -p $dfsdir
        done
    done
done
