#!/bin/sh

if [ "$1" != "" ]; then  
  id=$(docker ps -q -f "name=$1")
  echo  "Stopping $id ..."
  docker stop $id
  echo  "Stopped $id"
  docker rm $id
  echo  "Removed $id"
  echo  "Starting $1 ..."
  docker run -e aws_bucket=$aws_bucket -e db=$db -e url=$url -e aws_key_id=$aws_key_id -e aws_secret_access_key=$aws_secret_access_key -e slack_token=$slack_token -e account=$1 --name $1 -t ongair/ongair:whatsphp
else
  echo "*********************************"
  echo "*                               *"
  echo "* No account provided           *"
  echo "* Usage: deploy.sh 254722100200 *"
  echo "*                               *"
  echo "*********************************"
fi