
## Installation

Simply run this following command in the api directory to install dependencies

```shell
composer install
```

Edit the /etc/hosts an add this line

```
127.0.0.1 gym4devs.sheaker.dev
```

Create a new file something like /etc/apache2/site-avalaible/sheaker.dev and adapt the configuration (paths...)

```
<VirtualHost *:80>
    ServerName gym4devs.sheaker.dev

    SetEnv APPLICATION_ENV development

    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Headers X-Requested-With,Content-Type,Authorization
    Header always set Access-Control-Allow-Methods "GET,POST,PUT,DELETE,OPTIONS"

    DocumentRoot /Users/kevin/work/hexagone/sheaker/website/dist

    Alias /api /Users/kevin/work/hexagone/sheaker/api/public
    <Directory /Users/kevin/work/hexagone/sheaker/api/public>
        Options Indexes MultiViews FollowSymLinks
        Require all granted

        RewriteEngine On
        RewriteBase /api/
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
        RewriteCond %{HTTP:Authorization} ^(.*)
        RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
    </Directory>
</VirtualHost>
```

Note: you may have to install some modules like rewrite
