FROM php:7.0-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
		mediainfo \
	 && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-install exif

COPY photo_sorter.phar /usr/bin/photo_sorter
RUN chmod +x /usr/bin/photo_sorter

CMD [ "/usr/bin/photo_sorter", "/psrc", "/pdst" ]
