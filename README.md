Usage
=====

Install composer

```
curl -sS https://getcomposer.org/installer | php
php composer.phar install
apt-get install php5
apt-get install php5-mcrypt
apt-get install php5-mysql
```

This needs mod_rewrite enabled if running the web engine. This requires apache2 to be installed.

```sudo a2enmod rewrite```

Running the engine
==================
The command line args are

1. DB - The environment key for which database to use
2. URL - The environment key for which url to use

```
php ongair.php 254733171036 DB URL
```