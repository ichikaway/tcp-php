FROM php:8.3-cli

ENV TZ=Asia/Tokyo

RUN apt-get update && apt-get install -y libzip-dev zip unzip screen tcpdump iproute2 iptables less

RUN apt-get update && apt-get install -y locales \
 && sed -i 's/# ja_JP.UTF-8 UTF-8/ja_JP.UTF-8 UTF-8/' /etc/locale.gen \
 && locale-gen

ENV LANG=ja_JP.UTF-8
ENV LANGUAGE=ja_JP:ja
ENV LC_ALL=ja_JP.UTF-8


RUN docker-php-ext-install sockets zip

CMD ["tail", "-f", "/dev/null"]