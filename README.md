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
php composer/bin/ptig.php

# 好きなIRC Clientでlocalhost:6669にアクセスすればOK

````

## Channel Naming Convention

* #twitter (streaming)
* #mention (mention)
* #search_<QUERY> (search result)

## Features

* チャンネルで発言するとtweet更新
* list用の自動チャンネル作成
* /me search <query> で検索

RT, Reply, Favとかはまだない

# License

MIT License