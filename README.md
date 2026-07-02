# sakuracloud-dns

さくらのクラウド DNS API を利用して、Let's Encrypt の DNS-01 認証で使用される `_acme-challenge` TXT レコードの登録・削除を行う CLI ツールです。

Let's EncryptのようなACME対応の証明書発行機関でワイルドカード証明書を取得したかったのですが、以下のようなケースのためにさくらのクラウドのDNSサービスをAPIを使用して対応することにしました。

- 何らかの理由でcertbotが入れられない
- _acme-challengeのTXTレコードを配置しているのが別のゾーンにあり、CNAME転送している

## 動作環境

* PHP 8.x
* Composer
* さくらのクラウド API アカウント

## インストール

```bash
git clone git@github.com:tohokuaiki/sakuracloud-acme-dns.git
cd sakuracloud-acme-dns

composer install
```

## 設定

さくらのクラウドのDNSサービスのAPI の認証情報を `.env` ファイルに設定します。

例:

```dotenv
ACCESS_TOKEN=xxxxxxxxxxxxxxxx
ACCESS_TOKEN_SECRET=xxxxxxxxxxxxxxxx
```

デフォルトではカレントディレクトリの `.env` を使用します。

別の設定ファイルを使用する場合は `--config-file` オプションを指定してください。

## 使い方

certbotをmanualオプション付きで使用します。
manual-auth-hookとmanual-cleanup-hookに対応するコマンド、sakura-cloud-cliを使ってください。

### sakura-cloud-cliコマンド
Symfony Consoleを使っていますので、```./sakura-cloud-cli list```でコマンド一覧が表示されます。

#### TXTレコードの登録 dns:acme-register

Let's Encrypt の DNS-01 認証で使用する `_acme-challenge` TXT レコードを登録します。

```bash
./sakura-cloud-cli dns:acme-register example.com
```

#### 引数

| 名前     | 説明        |
| ------ | --------- |
| `zone` | 操作対象のゾーン名 |

#### オプション

| オプション                 | 説明                            |
| --------------------- | ----------------------------- |
| `-f`, `--config-file` | API アカウント情報を記載した `.env` ファイル  |
| `-r`, `--region`      | さくらのクラウドのリージョン（デフォルト: `is1b`） |

例:

```bash
./sakura-cloud-cli dns:acme-register example.com \
    --config-file=/path/to/.env
```

---

### TXTレコードの削除

不要になった `_acme-challenge` TXT レコードを削除します。

```bash
./sakura-cloud-cli dns:acme-cleanup example.com
```

#### 引数

| 名前     | 説明        |
| ------ | --------- |
| `zone` | 操作対象のゾーン名 |

#### オプション

| オプション                 | 説明                            |
| --------------------- | ----------------------------- |
| `-f`, `--config-file` | API アカウント情報を記載した `.env` ファイル  |
| `-r`, `--region`      | さくらのクラウドのリージョン（デフォルト: `is1b`） |

## ヘルプ

各コマンドの詳細は `--help` で確認できます。

```bash
./sakura-cloud-cli dns:acme-register --help
./sakura-cloud-cli dns:acme-cleanup --help
```

## 利用例

Let's Encrypt の DNS-01 認証では、おおよそ以下の流れで使用します。

1. TXT レコードを登録
2. DNS が反映されるまで待機
3. ACME クライアントで証明書を取得・更新
4. TXT レコードを削除

### 初回の証明書を取得

```
mkdir -p ./letsencrypt/config ./letsencrypt/work ./letsencrypt/logs

certbot certonly \
  --manual \
  --preferred-challenges dns \
  --manual-auth-hook "./sakura-cloud-cli dns:acme-register <<さくらDNSゾーン名>>" \
  --manual-cleanup-hook "./sakura-cloud-cli dns:acme-cleanup <<さくらDNSゾーン名>>" \
  -d example.com -d '*.example.com' \
  --agree-tos -m itoh@example.jp \
  --config-dir ./letsencrypt/config \
  --work-dir ./letsencrypt/work \
  --logs-dir ./letsencrypt/logs
```

### 以後の更新
```
certbot renew \
  --config-dir ./letsencrypt/config \
  --work-dir ./letsencrypt/work \
  --logs-dir ./letsencrypt/logs
```

## ライセンス

MIT License




