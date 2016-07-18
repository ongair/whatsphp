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

Running Containers
==================
If running via a container you need to setup the same variables then run the image

```
    docker run -e db=$db -e url=$url -e aws_key_id=$aws_key_id -e aws_secret_access_key=$aws_secret_access_key -e slack_token=$slack_token -e account=<account> -t ongair/ongair:whatsphp
```
