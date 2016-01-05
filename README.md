
# Webラジオを保存するためのスクリプト

## 動作環境
- php
- php module : mbstring, xml, domxml


## 音泉 (現在音泉のみ対応)

### 保存Command

    /usr/bin/php onsen.php  -f <idlist.txt> -d <outputdir>

オプション  
-f idlist.txt  
保存する音泉の番組idの一覧  
idlist.txt : 番組idを改行区切りで記述したtextファイル  
番組idは後述のコマンドで取得  

-d outputdir  
保存先ディレクトリ  

### ID取得コマンド
    php onsen_idlist.php

実行結果(例)

    kamo             2016/01/04 名塚佳織のかもさん學園 名塚佳織
    tsukinone        2016/01/04 大原さやか朗読ラジオ　月の音色～radio for your pleasure tomorrow～ 大原さやか
    yuyuyu           2015/11/02 ラジオ「結城友奈は勇者である」勇者部活動報告 春夏秋冬 照井春佳 / 内山夕実 / 黒沢ともよ
    soruraru         2015/12/28 えとたまらじお～ソルラルくれにゃ！～ 村川梨衣 / 松井恵理子 / 花守ゆみり
    arslan_anime     2015/12/21 アルスラーン戦記～ラジオ・ヤシャスィーン！ 小林裕介 / 花江夏樹
    ensemble_stars   2015/12/14 ラジオ「あんさんぶるスターズ！」～夜闇の魔物に怯える子猫～ 増田俊樹 / 羽多野渉 / 小野友樹 / 細貝圭

実行結果は  

    番組ID           最終更新日 番組タイトル パーソナリティー

の順に表示される。  

ID取得コマンドは音泉トップページのデザインなどが変わると動作しなくなる可能性がある。  
