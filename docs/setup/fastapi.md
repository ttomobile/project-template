# FastAPI セットアップ手順

`apps/fastapi-app` 配下に FastAPI アプリケーションを初期化する際に実行したコマンドと、その標準出力／標準エラー、対話的に尋ねられた内容と回答を以下に記録します。

| ステップ | コマンド | 結果 (stdout/stderr) | 問い合わせ内容 | 応答 |
| --- | --- | --- | --- | --- |
| 1 | `mkdir -p apps/fastapi-app/app` | 出力なし。ディレクトリツリーを作成。 | なし | 該当なし |
| 2 | `python -m venv apps/fastapi-app/.venv` | 出力なし。`apps/fastapi-app/.venv` に仮想環境を作成。 | なし | 該当なし |
| 3 | `apps/fastapi-app/.venv/bin/pip install fastapi "uvicorn[standard]"` | FastAPI 関連パッケージをインストール。最終行: `Successfully installed annotated-types-0.7.0 ... websockets-15.0.1`。pip からアップグレード通知あり。 | なし | 追加対応不要 |
| 4 | `apps/fastapi-app/.venv/bin/pip freeze > apps/fastapi-app/requirements.txt` | 出力なし。依存関係を一括で `requirements.txt` に書き出し。 | なし | 該当なし |
| 5 | `cat <<'EOF' > apps/fastapi-app/requirements.txt`<br>`fastapi==0.119.0`<br>`uvicorn[standard]==0.38.0`<br>`EOF` | 直接利用する 2 パッケージのみに内容を調整して `requirements.txt` を上書き。 | なし | 該当なし |

## アプリケーションのエントリーポイント

FastAPI アプリケーションは `apps/fastapi-app/app/main.py` で定義されており、動作確認用の `/health` エンドポイントを公開しています。
