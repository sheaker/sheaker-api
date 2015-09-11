TEMP_FILE=/tmp/sheaker-back.tgz
TEMP_DIR=/tmp/sheaker-back
PROD_DIR=/var/www/sheaker.com/sheaker-back
PROD_NAME=$(date +%s)

tar -zxf $TEMP_FILE

#sudo /etc/init.d/nginx stop

mkdir $PROD_DIR/$PROD_NAME
cp -pr $TEMP_DIR $PROD_DIR/$PROD_NAME

cd $PROD_DIR

#unlink $PROD_DIR/current
#ln -s $PROD_NAME current

cd current/public
ln -s ../../photos photos

#sudo /etc/init.d/nginx start

rm -rf $TEMP_DIR
