FROM ongair/ongair:14.04_php5.6
MAINTAINER Ongair

RUN mkdir /whatsapp
RUN cd /whatsapp && composer require ongair/whatsapp
RUN cd /whatsapp && mkdir logs

# Install upstart
ADD ./service.sh /whatsapp/service.sh
ADD ./monit /etc/monit/conf.d/ongair
ADD ./ongair.sh /etc/init.d/ongair

ENV env production
ENV timeout 300
ENV wait_timeout 30
ENV s3_bucket ongair-cdn