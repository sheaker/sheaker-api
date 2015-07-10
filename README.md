
## Installation

Simply run this following command in the api directory to install dependencies

```shell
composer install
```

Edit the /etc/hosts an add this line

```
127.0.0.1 gym4devs.sheaker.local
```

Create a new file something like /etc/apache2/site-avalaible/sheaker.local and adapt the configuration (paths...) like prod example in https://github.com/Hexagone/sheaker-back/tree/master/conf.d

Note: you may have to install some modules like rewrite
