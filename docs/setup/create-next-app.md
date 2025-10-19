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

## 依存関係運用メモ

- `package.json` では互換バージョンを許容するキャレット指定を維持しつつ、`package-lock.json` をコミットすることで実際にインストールされるバージョンを固定しています。ロックファイルを削除しない限り、`npm install` は同じバージョンを再現します。
- 新しい環境でのセットアップや CI では `npm ci` を使用し、ロックファイルの内容を厳密に再現します。
- 依存関係を能動的に更新したい場合のみ `npm update` を実行し、更新後はテストを通した上でロックファイルの変更をコミットしてください。
