# PTIG - PHP Twitter IRC Gateway -

# Status

Alpha

# Requirements

* php 5.4 higher
* php-uv (https://github.com/chobie/php-uv)
* OpenSSL support enabled
* Socket support enabled

# 使い方

````
git clone https://github.com/chobie/ptig
cd ptig

composer install
cd bin
# 認証する
php oauth_request.php
# URLが表示されるのでそこに行ってPINを取得する
php authenticate.php TOKEN SECRET PIN
# $HOME/.ptig/config.yamlに情報が保存される

cd ..
# foregroundで動くのでgodとかsupervisor使って
./bin/ptig run --port 6669

# 好きなIRC Clientでlocalhost:6669にアクセスすればOK

````

## Channel Naming Convention

* #twitter (streaming)
* #mention (mention)
* #favorites (fav)
* #search_<QUERY> (search result)

## Features

* チャンネルで発言するとtweet更新
* list用の自動チャンネル作成
* /me search <query> で検索
* /me rt <id> で指定IDのツイートをリツイート
* /me favorites <id> で指定IDのツイートをふぁぼ
* /me re <id> で指定IDのツイートに対して返信
* /me follow <screen_name> でscreen_nameの人をfollow
* /me unfollow <screen_name> でscreen_nameの人をunfollow
* /me block <screen_name> でscreen_nameの人をblock
* /me unblock <screen_name> でscreen_nameの人をunblock
* /me list add <screen_name> <list>でscreen_nameの人をlistに追加
* /me list remove <screen_name> <list>でscreen_nameの人をlistから削除

* InputFilter / OutputFilterによるツイートの処理
* NaiveBayseによるカテゴリ分け機能（要学習）

ゆめがひろがりんぐですね！

# License

MIT License