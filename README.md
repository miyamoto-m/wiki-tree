# ご確認いただくための動作方法

## 前提条件
- DockerおよびDocker Composeがインストールされていること

## 確認手順
1. GitHubリポジトリのクローン
```
git clone <repository-url>
cd <repository-directory>
```

2. Dockerコンテナのビルドと起動
```
docker-compose up --build -d
```

3. コンテナを起動
```
docker-compose run --rm app bash
```

4. PHPスクリプトの実行
```
php /app/index.php
```

5. 検索したいキーワードを入力してEnterキーを押下

## 備考
- MAX_DEPTHの値を変更することで、探索の深さを変更できます。(探索時間が長くなるため、初期値は2で設定しています。)
- IF_SLEEPの値を変更することで、各リクエストの間隔を調整できます。