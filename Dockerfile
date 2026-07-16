# Menggunakan image FrankenPHP resmi dengan PHP 8.3
FROM dunglas/frankenphp:latest-php8.3

# Install ekstensi mysqli dan pdo_mysql yang dibutuhkan PHP Native
RUN install-php-extensions mysqli pdo_mysql

# Set working directory di dalam container
WORKDIR /app

# Copy seluruh isi folder project ke dalam folder /app di container
COPY . /app

# Berikan izin akses folder (penting agar server bisa membaca file)
RUN chown -R www-data:www-data /app

# Gunakan port 8080 (standar Railway)
EXPOSE 8080

# Jalankan FrankenPHP
CMD ["frankenphp", "php-server", "--listen", ":8080"]