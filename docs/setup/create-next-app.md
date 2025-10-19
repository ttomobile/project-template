# Next.js アプリ作成手順ログ

## 実行コマンド

```bash
cd apps
npx create-next-app@latest
```

## 対話内容と回答

| 問い合わせ内容 | 回答 |
| --- | --- |
| `Ok to proceed? (y)` | `y` |
| `What is your project named?` | `my-app` |
| `Would you like to use TypeScript?` | `Yes` |
| `Which linter would you like to use?` | `None` |
| `Would you like to use Tailwind CSS?` | `Yes` |
| `Would you like your code inside a src/ directory?` | `Yes` |
| `Would you like to use App Router? (recommended)` | `Yes` |
| `Would you like to use Turbopack? (recommended)` | `Yes` |
| `Would you like to customize the import alias (@/* by default)?` | `Yes` |
| `What import alias would you like configured?` | `~/*` |

## 実行結果メモ

- プロジェクトは `apps/my-app` に生成され、npm で依存関係がインストールされました。
- CLI からは `Initializing project with template: app-tw` が表示され、Tailwind CSS 対応のテンプレートが適用されたことを確認しました。
- 生成後に `npm install -D @biomejs/biome` を実行し、lint コマンドを Biome ベースに差し替えています。
- PR 作成時のバイナリ差分制限を避けるため、生成された `src/app/favicon.ico` は削除しました。必要に応じて Next.js のドキュメントに従い独自のテキスト形式アイコンを追加してください。
