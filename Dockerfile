FROM drupal:7.56-apache

#MAINTAINER: Ben Fairfield - benfa

#### Cosign Pre-requisites ###
WORKDIR /usr/lib/apache2/modules

ENV COSIGN_URL http://downloads.sourceforge.net/project/cosign/cosign/cosign-3.2.0/cosign-3.2.0.tar.gz
#ENV GIT_CLONE "git clone https://git.code.sf.net/p/cosign/code cosign-code"
ENV CPPFLAGS="-I/usr/kerberos/include"
ENV OPENSSL_VERSION 1.0.1t-1+deb8u7
ENV APACHE2=/usr/sbin/apache2

# install PHP and Apache2 here
RUN apt-get update \
	&& apt-get install -y wget gcc make openssl \
		libssl-dev=$OPENSSL_VERSION apache2-dev 

### Build Cosign ###
RUN wget "$COSIGN_URL" \
	&& mkdir -p src/cosign \
	&& tar -xvf cosign-3.2.0.tar.gz -C src/cosign --strip-components=1 \
	&& rm cosign-3.2.0.tar.gz \
	&& cd src/cosign \
	&& ./configure --enable-apache2=/usr/bin/apxs \
	&& sed -i 's/remote_ip/client_ip/g' ./filters/apache2/mod_cosign.c \
	&& make \
	&& make install \
	&& cd ../../ \
	&& rm -r src/cosign \
	&& mkdir -p /var/cosign/filter \
	&& chmod 777 /var/cosign/filter

#WORKDIR /etc/apache2

### Remove pre-reqs ###
RUN apt-get remove -y make wget \
	&& apt-get autoremove -y

EXPOSE 443

COPY . /var/www/html/

### Start script incorporates config files and sends logs to stdout ###
COPY start.sh /usr/local/bin
RUN chmod 755 /usr/local/bin/start.sh
CMD /usr/local/bin/start.sh
