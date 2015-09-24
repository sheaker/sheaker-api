APP_NAME=sheaker-back

TEMP_FILE=~/$APP_NAME.tar
TEMP_DIR=~/$APP_NAME
PROD_DIR=/var/www/sheaker.com/$APP_NAME
PROD_NAME=$(date +%s)

# Create logs directory and set his rights
mkdir $TEMP_DIR/logs
chmod 777 $TEMP_DIR/logs

cp -pr $TEMP_DIR $PROD_DIR/$PROD_NAME

cd $PROD_DIR

# Put correct rights
sudo chown -R ubuntu:www-data $PROD_NAME

# Copy configuration file from previous deploy
cp -p current/config/production.php $PROD_NAME/config

sudo /etc/init.d/nginx stop

# Switch versions
unlink current
ln -s $PROD_NAME current

sudo /etc/init.d/nginx start

rm -rf $TEMP_DIR
rm -rf $TEMP_FILE
