# Project Template
## 概要
これは今後開発する新しいプロジェクトのテンプレートとするコードベースです。
プロジェクト開始時にスマートに整った環境で作業を開始出来るようにすることが目的です。
すべてのプロジェクトで必要となるファイルやディレクトリ構造を提供し、
必要に応じてオーバーライドされることを想定しています。

## 開発用コマンド
Laravel アプリケーションは `apps/laravel-app` に配置されています。ルートの `Makefile` から主要なコマンドを呼び出せるため、テンプレートから派生したプロジェクトでも同じ体験で開発を始められます。

| コマンド | 内容 |
| --- | --- |
| `make setup` | Composer/NPM の依存関係をインストールし、`.env` とアプリケーションキーを初期化します。 |
| `make lint` | PHP の Pint/Larastan とフロントエンドの ESLint をまとめて実行します。 |
| `make test` | PHPUnit と Vitest を順に実行します。 |
| `make run` | Laravel のアプリケーションサーバー・キュー・ログ監視・Vite を一括起動します。 |
| `make audit` | Composer Audit による依存脆弱性チェックを実行します。 |

バックエンド固有のコマンドは `composer run <task>`、フロントエンド固有のコマンドは `npm run <task>` で個別に実行できます。

## コード品質ツール
- PHP: [Laravel Pint](https://laravel.com/docs/pint) と [Larastan](https://github.com/larastan/larastan) を `composer run lint` で実行します。設定は `apps/laravel-app/phpstan.neon` にまとまっています。
- フロントエンド: [ESLint](https://eslint.org/) と [Prettier](https://prettier.io/) を導入し、`npm run lint` / `npm run format` から利用できます。ユニットテストは [Vitest](https://vitest.dev/) を採用しています。

## CI
`.github/workflows/ci.yaml` で Laravel 側の lint/test とフロントエンドの lint/test を自動実行します。GitHub Actions 上でも `Makefile` と同じコマンド群が利用されるため、ローカルと CI の挙動が揃います。
