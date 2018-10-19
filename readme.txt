■概要
ソフト名「グループウェアL01」
フリーソフトウェア

PHPで動作するフリー・オープンソースのグループウェアです。
データベースはMySQL、SQLiteに対応しています。
利用規約の範囲内に限り、無料で利用できます。

■セットアップ
1.アプリケーションの設置
ダウンロードした圧縮ファイルを解凍し、Webサーバーの任意のディレクトリにアップロードしてください。

2.パーミションの変更
アップロードしたファイルのパーミションを変更します。
(サーバーのOSがWindowsの場合、またはサーバーの環境によってはこの手順は必要ありません。)
「group/application/config.php」を 「606」または「666」に変更します。
「group/application/upload/forum」を 「707」または「777」に変更します。
「group/application/upload/message」を 「707」または「777」に変更します。
「group/application/upload/project」を 「707」または「777」に変更します。
「group/application/upload/storage」を 「707」または「777」に変更します。
「group/application/upload/temporary」を 「707」または「777」に変更します。
データベースがSQLiteの場合は
「group/application/database/group.sqlite2」を 「606」または「666」に変更します。

3.セットアップの実行
セットアップページ「group/setup.php」にブラウザでアクセスしてください。
(ドメインが「http://example.com/」、設置したグループウェアのディレクトリ名が「group」の場合は「http://example.com/group/setup.php」にアクセスします。)
サーバー情報と管理者情報を入力し、「実行」をクリックします。
セットアップが開始されます。

4.パーミションの変更とセットアップファイルの削除
設定ファイルのパーミションを戻します。
「application/config.php」のパーミションを「604」または「644」に変更します。
setup.phpをサーバーから削除してください。
これでセットアップは終了です。
「group/」にアクセスし、ログインしてください。 

■アンインストール
インストールしたフォルダごと削除し、データベースのテーブルを削除してください。

■動作環境
Webサーバが稼動し、PHPがインストールされている環境
PHP　…　PHP4.4以降、mbstringが有効でshort_open_tag=Onの環境
データベース　…　MySQL4.0以降、SQLite2
ブラウザ　…　Firefox3以降、GoogleChrome、Safari4以降、IE7以降
文字コード　…　UTF-8
基本的にWebサーバでPHPが動作し、データベースが使用できる環境なら利用可能です。
InternetExplorerはブラウザ自体にバグが多いため他のブラウザをお勧めします。

■作者/連絡
作者「株式会社リミットリンク」
アプリケーションの不備・ご意見・ご要望がございましたら下記までお願い致します。
http://limitlink.jp/

■利用規約
1．著作権
グループウェアL01（以下「本ソフトウェア」とする）はオープンソースのソフトウェアです。
本ソフトウェアの著作権は、株式会社リミットリンクが保有します。
画面、ソース等に記載されている著作権表示やロゴ等および各プログラム中に記載された「Copyright」に関する記述のいずれも削除・移動できません。
2．使用法
本ソフトウェアをコンピュータにインストールして使用することができます。
本ソフトウェアのソースコードを改変して利用することもできますが、再配布はできません。
本ソフトウェアのソースコードを改変して作成したプログラムにも本利用規約が適用されます。
3．免責
本ソフトウェアをご利用される方は自らの責任と負担にて使用することを確認し、同意するものとします。
本ソフトウェアの使用または使用不能によって生じたいかなる損害の責任も負いません。
また、本ソフトウェアが支障なく若しくは誤作動なく作動することも保証いたしません。
バグの改善やサポートの義務は一切ないものとします。
4．再配布等の禁止
有償、無償に関わらず本ソフトウェアを再配布することはできません。
有償、無償に関わらず本ソフトウェアを使用したサービスを提供することはできません。
有償、無償に関わらず本ソフトウェアをレンタルすることはできません。
有償、無償に関わらず本ソフトウェアを第三者に設置することはできません。
その他本ソフトウェアを使用することで利益を生じる使い方をすることはできません。
5．セキュリティについて
セキュリティの確保には配慮しておりますが、アプリケーションのみのセキュリティ対策には限界があります。
認証プログラムはユーザーを判別するためのもので、データを保護するものではありません。
htaccess等でのアクセス制限、SSLでの通信など、サーバー側での対策を必ず行ってください。
6．その他
本ソフトウェアは、予告なくバージョンアップする場合があります。
対応するプラットホームの変更や追加、新規機能の追加、ソフトウェアの不具合の改良等につきましても同様です。
また、本規約は予告なく変更することがあります。

■更新履歴
version1.11
2011/02/04 ファイルアップロード時日本語ファイル名を誤認識する処理を修正、ファイル共有で編集時にファイルサイズがクリアされてしまう不具合を修正。
2010/05/14 SSLでファイルダウンロード時のIEバグに対応、TODOヘルプ内容修正、郵便番号辞書を最新に更新。
2009/11/27 register_globalsがOnの場合にログインユーザーに関する不具合を修正、スケジュール追加時の不具合を修正。
2009/10/23 メッセージ返信、転送時の添付ファイル処理を修正、register_globalsがOnの場合にログアウトしてしまう不具合を修正。
2009/08/07 アドレス帳でCSV出力時に閲覧権限のないデータをダウンロードしてしまう問題を修正、検索フォーム・ヘルプ内容・ページタイトルの一部変更。
2009/07/27 認証時のセッション情報とCSSを変更。
2009/07/17 ヘルプを追加、施設管理権限を変更、ファイルサイズが取得できない問題を修正。
2009/07/09 jqueryを最新版に更新。
2009/07/07 IE6でJavaScriptのsubmitが動作しない部分を修正、IEでファイルダウンロード時に文字化けする部分を修正、CSSを若干変更。
2009/06/17 トップページのメッセージ作成リンクURLを修正。
2009/06/12 ログインしているユーザーの名前を表示、メッセージ、掲示板、ブックマーク既読の色を変更、セットアップに種類とグループ名追加。
2009/06/01 プロジェクト管理のタスクデータのステータス表示修正。
2009/05/16 アップロード用のフォルダ修正。
2009/05/15 MySQLで文字化けする場合への対策。



■タイムカードQRコード打刻打刻端末の設定
0.Firefox 31.0 (日本語版)に限定、32にしたらカメラが固まる現象が見受けられる※32,64bitを注意しましょう
https://download-installer.cdn.mozilla.net/pub/firefox/releases/31.0/win32/ja/Firefox%20Setup%2031.0.exe
1.Firefoxホームページをタイムレコーダーに設定（カメラを有効にする→現時点では毎回手動で許可しないといけない）
2.Firefoxフルスクリーン表示
3.ntpサーバを"ntp.nict.jp"に設定（vSphereでもホストとゲストを時間同期するように設定し、ホストもntpサーバと同期するように設定する）
ntp更新頻度を一日：regedit→HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\W32Time\TimeProviders\NtpClient\SpecialPollInterval→10進→3600＝60×60一時間一回
4.Firefoxをスタートに追加
5.keyFreezeをインストールして、実行する
6.音量を最大にする
7.インターネットの疎通を確認
8.Firefoxの自動更新とWindowsUpdateの自動更新をOFFにします！！
9.コンバネでJavaの自動更新をOFF
10.スクリーンセーバーを無効に、電源プランも常にオンにして、モニターハードディスクのスリープはなし！


■サーバについて
1..php.iniのタイムゾーンを有効化
# vi /etc/php.ini
date.timezone = "Asia/Tokyo"
# apachectl configtest
# service httpd restart

2.エラーログはデフォルトの場所に出力される
# tail -f /var/log/httpd/error_log


■ソースコード
# cd /var/www/html/
# svn co http://192.168.25.123/repos/abngroup abngroup




■config.php
vi application/config.php

//データベースの種類
define('DB_STORAGE', 'mysql');
//データベースのホスト名
define('DB_HOSTNAME', 'localhost');
//データベース名
define('DB_DATABASE', 'groupware');
//データベースユーザー名
define('DB_USERNAME', 'aben');
//データベースパスワード
define('DB_PASSWORD', 'abnabn');
//テーブル接頭辞
define('DB_PREFIX', 'grp_');
//データベースポート番号
define('DB_PORT', '3306');
//データベース文字コード設定
define('DB_CHARSET', 'utf8');
//データベースファイル
define('DB_FILE', DIR_PATH.'database/group.sqlite2');
//郵便番号データファイル
define('DB_POSTCODE', DIR_PATH.'database/KEN_ALL.CSV');


■jQueryによるvalidate
http://jqueryvalidation.org/
header.phpにで、導入
<script type="text/javascript" src="<?=$root?>js/jquery.validation/jquery.validate.js"></script>
<script type="text/javascript" src="<?=$root?>js/jquery.validation/additional-methods.js"></script>
additional-methods.jsはカスタマイズvalidateを追加する場所です。
viewで例
<script type="text/javascript">
$("#formname").validate();
$("#timepicker_close").rules('add', { greaterThan: "#timepicker_open" });
</script>
で使えます。現在はまだ使われていない。



■サーバインストール

Centos6.5 64bit
固定IP
開発ツールオプション

setenforce 0
service iptables stop

php.ini
date.timezone = "Asia/Tokyo"

yum install -y php-mbstring
service httpd start

vi /var/www/html/grp/application/config.php
======================================================================================================
<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */

/**
 * アプリケーション設定
 */
//アプリケーション
define('APP_TYPE', 'group');

/**
 * 制限設定
 */
//表示件数
define('APP_LIMIT', '50');
//最大表示件数
define('APP_LIMITMAX', '1000');
//アップロードファイル
define('APP_FILESIZE', '25000000');
define('APP_EXTENSION', 'exe');

/**
 * 認証設定
 */
//有効期限
define('APP_EXPIRE', '7200');
//アイドルタイム
define('APP_IDLE', '3600');

/**
 * パス設定
 */
//アプリケーションディレクトリ
define('DIR_PATH', dirname(__FILE__).'/');
//モデルディレクトリ
define('DIR_MODEL', DIR_PATH.'model/');
//ビューディレクトリ
define('DIR_VIEW', DIR_PATH.'view/');
//ライブラリディレクトリ
define('DIR_LIBRARY', DIR_PATH.'library/');
//ファイルディレクトリ
define('DIR_UPLOAD', DIR_PATH.'upload/');

/**
 * データベース設定
 */
//データベースの種類
define('DB_STORAGE', 'mysql');
//データベースのホスト名
define('DB_HOSTNAME', 'localhost');
//データベース名
define('DB_DATABASE', 'groupware');
//データベースユーザー名
define('DB_USERNAME', 'root');
//データベースパスワード
define('DB_PASSWORD', 'aben2236');
//テーブル接頭辞
define('DB_PREFIX', 'grp_');
//データベースポート番号
define('DB_PORT', '5432');
//データベース文字コード設定
define('DB_CHARSET', 'utf8');
//データベースファイル
define('DB_FILE', DIR_PATH.'database/group.sqlite2');
//郵便番号データファイル
define('DB_POSTCODE', DIR_PATH.'database/KEN_ALL.CSV');

/**
 * Google API
 */
# https://code.google.com/apis/console/ -> Simple API Access -> Key for browser apps (with referers)
define('GOOGLE_API','AIzaSyBc7gCSDnlaLI9LFcZC843or6QmKajnrYI');
?>
======================================================================================================

yum install php-mysql

chmode 666  application/config.php
chmod 666  application/config.php
chmod 777 -R application/upload/
chmod 666 application/database/group.sqlite2
mv setup.php.bak setup.php
...

