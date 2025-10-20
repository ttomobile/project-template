# Laravel App Contributor Notes

## テスト実行と環境設定
- このディレクトリ配下の変更では、`composer install` を実行して依存関係を整えてから `php artisan test` を実行してください。
- テスト環境では `.env.testing` を使用します。`APP_KEY` を含むファイルをリポジトリで管理しているので、削除したり値を空にしないでください。
- `php artisan test` の結果は PR の「動作確認」セクションに記載してください。
