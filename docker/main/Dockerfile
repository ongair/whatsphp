FROM ongair/ongair:14.04_php5.6
MAINTAINER Ongair

RUN mkdir /whatsapp
RUN cd /whatsapp && composer require ongair/whatsapp
RUN cd /whatsapp && mkdir logs

# Install upstart
ADD ./service.sh /whatsapp/service.sh
ADD ./monit /etc/monit/conf.d/ongair
ADD ./ongair.sh /etc/init.d/ongair

ENV ENV production
ENV TIMEOUT 60
ENV WAIT_TIMEOUT 10