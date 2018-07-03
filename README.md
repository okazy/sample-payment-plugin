# sample-payment-plugin
EC-CUBE 3.nの決済プラグインサンプルです。
リンク型とトークン型の２種類のクレジットカード決済方法を追加できます。
EC-CUBE3.nは開発中であり、APIの仕様は変更になる場合があります。

# EC-CUBE3.n

- [本体ソースコード](https://github.com/EC-CUBE/ec-cube/tree/experimental/sf)
- [開発ドキュメント・マニュアル](http://doc3n.ec-cube.net/)

## EC-CUBEのインストール手順

利用できるPostgresかMySQLを立ち上げておきます。

1. [こちら](https://github.com/EC-CUBE/ec-cube)からEC-CUBEのリポジトリをclone
```git clone https://github.com/EC-CUBE/ec-cube.git```
1. ディレクトリを移動
```cd ec-cube```
1. `experimental/sf` のブランチをチェックアウト
```git checkout experimental/sf```
1. ec-cubeのインストールコマンドを実行。
```bin/console eccube:install```
1. DATABASE_URLを入力、他はそのままエンターでOK。
1. サーバの起動
```bin/console server:run```
1. ブラウザでアクセス
http://127.0.0.1:8000/

### DBごとのDATABASE_URL設定例

```
## PostgreSQL
DATABASE_URL=postgresql://db_user:db_password@127.0.0.1:5432/db_name

## MySQL
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
```

# プラグイン導入方法

## プラグインファイルの配置

`/app/Plugin/` にプラグインのファイルを配置してください。

本サンプルプラグインの場合は以下のようになります。

`/app/Plugin/sample-payment-plugin`

## コマンドラインインタフェース

### 利用例
- インストール
`bin/console eccube:plugin:install --code=SamplePayment`
- 有効化
`bin/console eccube:plugin:enable --code=SamplePayment`
- 無効化
`bin/console eccube:plugin:disable --code=SamplePayment`
- 削除
`bin/console eccube:plugin:uninstall --code=SamplePayment`

### プラグインジェネレータ

以下のコマンドで推奨ディレクトリ構成のプラグインサンプルが生成できます。

`bin/console eccube:plugin:generate`

## プラグインカスタマイズ

### 推奨ディレクトリ構成

プラグインのディレクトリ構成ですが、極力EC-CUBE3本体のディレクトリ構成に合わせる事を推奨します。但し、全てのディレクトリが必要ではなく必要に応じてディレクトリをプラグイン側に作成してください。

- ディレクトリ例

```
[プラグインコード]
  ├── Controller
  │   └── XXXXController.php
  ├── Entity
  │   └── XXXX.php
  ├── Form
  │   ├── Extension
  │   │   └── XXXXTypeExtension.php
  │   └── Type
  │           └── XXXXType.php
  ├── Repository
  │   └── XXXXRepository.php
  ├── Resource
  │   ├── assets
  │   │   ├── css
  │   │   │   └── xxxx.css
  │   │   ├── img
  │   │   │   ├── xxxx.gif
  │   │   │   ├── xxxx.jpg
  │   │   │   └── xxxx.png
  │   │   └── js
  │   │       └── xxxx.js
  │   ├── locale
  │   │   └── messages.ja.yaml
  │   │   └── validators.ja.yaml
  │   └── template
  │           ├── Block
  │           │   └── XXXX.twig
  │           ├── admin
  │           │   └── XXXX.twig
  │           └── XXXX.twig
  ├── Service
  │   └── XXXXService.php
  ├── PluginManager.php
  ├── LICENSE.txt
  ├── XXXXEvent.php
  ├── XXXXNav.php
  ├── XXXXTwigBlock.php
  └── config.yml
```

命名規約は[こちら](https://github.com/EC-CUBE/sample-payment-plugin/issues/6)のissueを参照

### ルーティングの追加

`@Route` アノテーションを付与したクラスファイルを `Controller` 以下に配置することで、サイトに新しいルーティングを追加することが可能です。

Controllerファイルについては開発ドキュメント・マニュアルの[Controllerのカスタマイズ](http://doc3n.ec-cube.net/customize_controller)ページをご確認ください。

### Entity拡張

クラスファイルを `Entity` 以下に配置することで新しいEntityを追加可能です。

traitと `@EntityExtension` アノテーションを使用して、既存Entityのフィールドを拡張可能です。

また、`@EntityExtension` アノテーションで拡張したフィールドに `@FormAppend` アノテーションを追加することで、フォームを自動生成できます。

Entityファイルについては開発ドキュメント・マニュアルの[Entityのカスタマイズ](http://doc3n.ec-cube.net/customize_entity)ページをご確認ください。

### FormType拡張

FormExtensionの仕組みを利用すれば、既存のフォームをカスタマイズすることができます。

`Form/Extension` に `AbstractTypeExtension` を継承したクラスファイルを作成することで、FormExtensionとして認識されます。

FormExtensionについては開発ドキュメント・マニュアルの[FormTypeのカスタマイズ](http://doc3n.ec-cube.net/customize_formtype)ページをご確認ください。

### イベントの追加

`EventSubscriberInterface` を実装し、イベントを追加します。
1. `Event.php` ファイルの `getSubscribedEvents()` メソッドの戻り値で追加するイベントを指定します。（3.0系の `event.yml` の内容に相当）
1. `Event.php` に呼び出すメソッドを定義します。

```php
class Event implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return ['eventName' => 'methodName'];
    }

    public function methodName(Event $event)
    {
    }
}
```

### 管理画面ナビの拡張

管理画面にプラグインのメニューを追加します。
以下のようにEccubeNavを実装すると, 対象メニュー内の最下部に追加されます。
プラグインの場合、有効時のみ表示されます。

```php
class Nav implements EccubeNav
{
    public static function getNav()
    {
        return [
            'order' => [
                'id' => 'sample_payment_admin_payment_status',
                'name' => 'sample_payment.admin.nav.payment_list',
                'url' => 'sample_payment_admin_payment_status',
            ],
        ];
    }
}
```

本体の管理画面ナビは `/app/config/eccube/packages/eccube_nav.yaml` で定義されています。

### Twigユーザ定義関数の読み込み

`EccubeTwigBlock`を実装し、対象のテンプレートファイルを読み込みます。

```php
class TwigBlock impletemts EccubeTwigBlock
{
    public static function getTwigBlocks()
    {
        return ['@SamplePayment/hello_block.twig']
    }
}
```

`/Resource/template/` 配下にblockの定義ファイルを作成します。

```html
{% block hello %}
    <h1>Hello, {{ name }}!</h1>
{% endblock %}
```

twigファイルに以下のように記載することでBlockが呼び出せます。

```
{{ eccube_block_hello({ name: 'hoge'}) }}
```

### PaymentMethodInterface の拡張

各決済ごとに `PaymentMethodInterface` を実装することで決済に独自の処理を追加できます。

#### `verify()`

注文手続き画面でsubmitされた時に実行する処理を実装します。
主に、クレジットカード決済の有効性チェックをするために使用します。
このメソッドは、 `PaymentResult` を返します。
`PaymentResult` には、実行結果、エラーメッセージなどを設定します。
`Response` を設定して、他の画面にリダイレクトしたり、独自の出力を実装することも可能です。
 
#### `apply()`

注文確認画面でsubmitされた時に、他の Controller へ処理を移譲する実装をします。
主にリンク式決済や、キャリア決済など、決済会社の画面へ遷移する必要がある場合に使用します。
また、独自に作成した Controller に遷移する場合にも使用できます。
このメソッドは `PaymentDispatcher` を返します。
`PaymentDispatcher` は、他の Controller へ `Redirect` もしくは `Forward` させるための情報を設定します。
決済会社の画面など、サイト外へ遷移させる場合は、 `Response` を設定します。

#### `checkout()`

注文確認画面でsubmitされた時に決済完了処理を記載します。
このメソッドは、 `PaymentResult` を返します。
`PaymentResult` には、実行結果、エラーメッセージなどを設定します。
3Dセキュア決済の場合は、 `Response` を設定して、独自の出力を実装することも可能です。

### PurchaseFlowについて

EC-CUBE3.nではPurchaseFlowをカスタマイズすることで購入フローのカスタマイズが可能になります。

PurchaseFlowについては開発ドキュメント・マニュアルの[Serviceのカスタマイズ](http://doc3n.ec-cube.net/customize_service#%E8%B3%BC%E5%85%A5%E3%83%95%E3%83%AD%E3%83%BC%E3%81%AE%E3%82%AB%E3%82%B9%E3%82%BF%E3%83%9E%E3%82%A4%E3%82%BA-2424)ページをご確認ください。

※PurchaseFlowは改善が進められており、ドキュメントの内容に古い部分があります。随時更新していきます。

### メッセージIDについて

メッセージファイルを `Resource/locale` 以下に配置すると多言語対応が可能です。

- messages.ja.yaml: 日本語のメッセージファイル
- validators.ja.yaml: 日本語のバリデーションメッセージファイル

例えば `messages.en.yaml` ファイルを用意し、EC-CUBE本体の `.env` ファイルで `ECCUBE_LOCALE=en` と設定すると読み込まれるメッセージファイルが切り替わります。

phpのソースコード内でメッセージを使用する場合にはグローバル関数の `trans()` が利用できます。

```
trans('message.id');
```

twigのソースコード内でメッセージを使用する場合には `trans` フィルタが利用できます。

```
{{ 'message.id'|trans }}
```

重複防止のためプラグイン内で利用するメッセージIDにはプラグインコードのプレフィックスをつけてください。

その他の命名規則については[こちら](https://github.com/EC-CUBE/ec-cube/issues/2597#issuecomment-345912583)のissueを確認してください。

### DBの更新方法

1. Entity拡張のORMアノテーションでDBの設定を更新
1. コマンドラインからプロキシファイルを作成 `bin/console eccube:generate:proxies`
1. DBの更新内容の確認 `bin/console doctrine:schema:update --dump-sql`
1. DBの更新を実行 `bin/console doctrine:schema:update --dump-sql --force`

## 決済プラグインについて

### ファイルごとの概要

TODO

### シーケンス図

[こちら](https://github.com/EC-CUBE/sample-payment-plugin/issues/11)のissueを参照

### 受注ステータスステートマシン図

[こちら](https://github.com/EC-CUBE/sample-payment-plugin/issues/10)のissueを参照
