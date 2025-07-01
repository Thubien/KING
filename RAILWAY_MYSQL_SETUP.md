# Railway MySQL Setup - Hızlı Çözüm

## Sorun
Projenizde SQLite varsayılan olarak ayarlanmış, ama production için MySQL kullanmak istiyorsunuz.

## ✅ Yapılan Düzeltmeler

### 1. Database Default Connection Değiştirildi
```php
// config/database.php
'default' => env('DB_CONNECTION', 'mysql'), // sqlite'dan mysql'e değiştirildi
```

### 2. Railway Deployment Guide Güncellendi
- PostgreSQL yerine MySQL kullanımı eklendi
- Doğru environment variable'lar güncellendi

### 3. nixpacks.toml MySQL Extension'ları Eklendi
```toml
nixPkgs = ["...", "php82", "php82Extensions.pdo", "php82Extensions.pdo_mysql", "php82Extensions.mysqli", "nodejs-18_x", "npm"]
```

## 🚀 Railway'de MySQL Kurulumu

### 1. Railway Dashboard'da:
```bash
railway add mysql
```

### 2. Environment Variables (Otomatik ayarlanır):
```
MYSQL_HOST=mysql.railway.internal
MYSQL_PORT=3306
MYSQL_DATABASE=railway
MYSQL_USERNAME=root
MYSQL_PASSWORD=randompassword
MYSQL_URL=mysql://root:randompassword@mysql.railway.internal:3306/railway
```

### 3. Laravel Environment Variables:
Railway dashboard'da şunları ekle:
```
DB_CONNECTION=mysql
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
DB_DATABASE=${MYSQL_DATABASE}
DB_USERNAME=${MYSQL_USERNAME}
DB_PASSWORD=${MYSQL_PASSWORD}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

## 📊 Mevcut SQLite Verilerini MySQL'e Aktarma

Eğer SQLite'daki verileri MySQL'e aktarmak istiyorsanız:

### 1. Export SQLite Data:
```bash
php artisan db:seed --class=DemoDataSeeder  # Mevcut veriler varsa
```

### 2. Railway MySQL'e Migration:
```bash
railway run php artisan migrate:fresh --seed
```

## 🔧 Alternatif: Local MySQL Test

Eğer önce local'de test etmek istiyorsanız:

### 1. Local MySQL Docker:
```bash
docker run --name mysql-test -e MYSQL_ROOT_PASSWORD=password -e MYSQL_DATABASE=king -p 3306:3306 -d mysql:8.0
```

### 2. .env Dosyasını Güncelleyin:
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=king
DB_USERNAME=root
DB_PASSWORD=password
```

### 3. Migration:
```bash
php artisan migrate:fresh --seed
```

## ⚡ Hemen Deploy Etmek İçin:

```bash
# 1. Railway'e bağlan
railway link

# 2. MySQL servis ekle
railway add mysql

# 3. Environment variables set et (yukarıdaki MySQL ayarları)

# 4. Deploy et
railway up

# 5. Migration çalıştır
railway run php artisan migrate:fresh --seed
```

Bu değişikliklerle artık Railway'de MySQL ile sorunsuz çalışacak! 🎉 