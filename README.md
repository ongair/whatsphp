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
    export pub_key=<pubnub_publish_key>
    export sub_key=<pubnub_subscribe_key>
    export pub_channel=<pubnub_channel>
    export url=http://0.0.0.0/
```
2. The account which we want to run

```
    php vendor/ongair/whatsapp/service.php 254733171036
```