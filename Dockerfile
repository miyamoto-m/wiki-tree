# ベースイメージ
FROM php:8.2-cli

# 必要なツールをインストール
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# Composerのインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 作業ディレクトリの設定
WORKDIR /app

# 依存関係のインストール
COPY composer.json ./
RUN composer install

# アプリケーションコードのコピー
COPY . .

# コマンドの実行
CMD ["php", "/app/index.php"]