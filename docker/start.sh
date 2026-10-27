#!/bin/sh
# Render で起動するたびに実行されるスクリプト
set -e

# Render は PORT 環境変数でポートを指定してくる（デフォルト10000）
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Render が自動生成した APP_KEY には Laravel が必要とする "base64:" が付かないので補う
case "$APP_KEY" in
  base64:*) ;;
  *) export APP_KEY="base64:${APP_KEY}" ;;
esac

# APP_URL が未設定なら、Render が用意する公開URLを使う
if [ -z "$APP_URL" ] && [ -n "$RENDER_EXTERNAL_URL" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

# Aiven の MySQL はSSL接続が必須。CA証明書の中身を環境変数 DB_CA_CERT で受け取り、ファイルに書き出す
# Aiven の CA 証明書（公開情報なのでリポジトリに同梱）を優先して使う。
# 同梱ファイルが無い場合だけ、環境変数 DB_CA_CERT の中身を使う。
# （Render の入力欄に貼ると改行が消えて1行になることがあるため、
#   中身（base64部分）だけを取り出して、正しいPEM形式（64文字ごとに改行）に組み直す）
BUNDLED_CA=/var/www/html/docker/aiven-ca.pem
if [ -f "$BUNDLED_CA" ]; then
  export MYSQL_ATTR_SSL_CA="$BUNDLED_CA"
elif [ -n "$DB_CA_CERT" ]; then
  CERT_BODY=$(printf '%s' "$DB_CA_CERT" \
    | sed -e 's/\\n/ /g' \
          -e 's/-----BEGIN CERTIFICATE-----//g' \
          -e 's/-----END CERTIFICATE-----//g' \
    | tr -d ' \r\n\t')
  {
    echo "-----BEGIN CERTIFICATE-----"
    printf '%s' "$CERT_BODY" | fold -w 64
    echo
    echo "-----END CERTIFICATE-----"
  } > /tmp/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/tmp/db-ca.pem
fi

php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# テーブル作成（未実行のマイグレーションだけ流れる）
php artisan migrate --force

# 面接官が触れるデモ用データ（すでにあれば作り直さない）
php artisan db:seed --class=DemoSeeder --force

exec apache2-foreground
