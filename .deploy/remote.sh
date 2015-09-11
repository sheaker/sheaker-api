APP_NAME=sheaker-back

TEMP_FILE=/tmp/$APP_NAME.tar.gz
TEMP_DIR=/tmp/$APP_NAME
PROD_DIR=/var/www/sheaker.com/$APP_NAME
PROD_NAME=$(date +%s)

sudo /etc/init.d/nginx stop

mkdir $PROD_DIR/$PROD_NAME
cp -pr $TEMP_DIR $PROD_DIR/$PROD_NAME

cd $PROD_DIR

unlink $PROD_DIR/current
ln -s $PROD_NAME current

cd current/public
ln -s ../../photos photos

sudo /etc/init.d/nginx start

rm -rf $TEMP_DIR
rm -rf $TEMP_FILE
