Usage
=====

You need composer in order to run this.

```
    composer require 'ongair/whatsapp'
```

Running the engine
==================
The command line args are

1. You need to set some environment variables

```
    export db=<database_url>
    export timeout=60
    export env=production
    export url=http://0.0.0.0/
```
2. The account which we want to run

```
    export ACCOUNT=254733171036
    php vendor/ongair/whatsapp/ongair.php
```