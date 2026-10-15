# Bank Data Digital Muara Lawa

## Batas upload dokumen

Ukuran maksimum setiap file adalah **50 MiB** (`51200` KiB atau `52,428,800` byte). Laravel, pemeriksa file, dan formulir browser menggunakan konfigurasi `DOCUMENT_MAX_SIZE_KB` yang sama.

Untuk environment baru, gunakan nilai berikut tanpa memasukkan file `.env` production ke Git:

```ini
DOCUMENT_MAX_SIZE_KB=51200
```

PHP harus menerima request sebelum Laravel dapat memvalidasinya. Atur konfigurasi PHP-FPM/cPanel production menjadi:

```ini
upload_max_filesize = 50M
post_max_size = 64M
```

Setelah perubahan PHP atau `.env`, jalankan `php artisan optimize:clear` dari direktori repository agar konfigurasi cache diperbarui.

## Deployment cPanel

Repository aplikasi berada di `/home/muaralawa/repositories/bank-data-digital-muara-lawa`; document root berada di `/home/muaralawa/public_html`. Jalankan perintah berikut dari direktori repository setelah backup dan setelah meninjau perubahan yang akan ditarik:

```bash
git fetch origin
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan config:cache
```

Build Vite dibuat di `public/build`. Salin seluruh isi folder tersebut ke document root agar manifest dan asset hash selalu selaras:

```bash
rsync -a --delete public/build/ /home/muaralawa/public_html/build/
```

Perintah `--delete` hanya digunakan pada folder `public_html/build` untuk membuang asset hash lama yang sudah tidak dirujuk manifest. Jangan jalankan migration, seeder, atau mengubah `.env` kecuali memang ada perubahan yang telah ditinjau.
