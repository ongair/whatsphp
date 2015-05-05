Setup
=====

1. Clone whatsapi official into /var/www/whatsapi-offical

  ```
    git clone https://github.com/kimenye/WhatsAPI-Official.git whatsapi-official
    cd whatsapi-official
    git config core.fileMode false
  ```

2. Change the permissions for this folder
  
  ```
    chmod -R 777 whatsapi-official
  ```

3. Clone this app

  ```
    git clone https://github.com/sproutke/whatsphp.git whatsapp
    cd whatsapp
    mkdir tmp
    git config core.fileMode false
  ```

4. Change the permissions for this folder as well
  ```
    chmod -R 777 whatsapp
    sudo chown -R root:www-data whatsapp
  ```

5. Install php-mysql
  ```
    sudo apt-get install php5-mysql
  ```