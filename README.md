Usage
=====

Install composer

```
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

This needs mod_rewrite enabled if running the web engine

```sudo a2enmod rewrite```

Running the engine
==================
The command line args are

1. DB - The environment key for which database to use
2. URL - The environment key for which url to use

```
php ongair.php 254733171036 DB URL
```