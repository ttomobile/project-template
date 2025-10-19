# Laravel 12 初期セットアップ手順

この手順書では、リポジトリ直下に `apps/laravel-app` として Laravel 12 プロジェクトを新規作成した際のコマンドと、その結果について記録します。

## 実行したコマンド

1. Laravel プロジェクトを配置するディレクトリを用意します。

   ```bash
   mkdir -p apps
   ```

   - 質問事項: なし
   - 応答: 該当なし

2. Composer を利用して Laravel 12 プロジェクトを初期化します。

   ```bash
   composer create-project laravel/laravel apps/laravel-app "12.*"
   ```

   - 質問事項: なし（コマンド実行中に対話的な入力は求められませんでした）
   - 応答: 該当なし

   コマンド実行後、自動的に以下が行われました。
   - `.env` の雛形コピーとアプリケーションキーの生成
   - 依存パッケージのインストールと自動ロードファイルの生成
   - データベース用 SQLite ファイルの作成と初期マイグレーションの実行

## プロジェクト構成

- `apps/laravel-app/` : Laravel 12 プロジェクト本体
  - `.env` : 初期化時に `.env.example` からコピーされた環境変数ファイル
  - `artisan` : Laravel CLI エントリポイント
  - `bootstrap/`, `config/`, `database/`, `routes/`, `resources/` など標準ディレクトリ
  - `vendor/` : Composer によってインストールされた依存パッケージ（`.gitignore` によりバージョン管理対象外）

## 備考

- セットアップに追加の対話や確認は発生しませんでした。
- 今後、環境設定を変更する際は `apps/laravel-app/.env` を編集し、必要に応じて `php artisan` コマンドを利用してください。
