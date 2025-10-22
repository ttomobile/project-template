# FastAPI セットアップ手順

`apps/fastapi-app` 配下に FastAPI アプリケーションを初期化する際に実行したコマンドと、その標準出力／標準エラー、対話的に尋ねられた内容と回答を以下に記録します。

| ステップ | コマンド | 結果 (stdout/stderr) | 問い合わせ内容 | 応答 |
| --- | --- | --- | --- | --- |
| 1 | `mkdir -p apps/fastapi-app/app` | 出力なし。ディレクトリツリーを作成。 | なし | 該当なし |
| 2 | `python -m venv apps/fastapi-app/.venv` | 出力なし。`apps/fastapi-app/.venv` に仮想環境を作成。 | なし | 該当なし |
| 3 | `source apps/fastapi-app/.venv/bin/activate`<br>(Windows の場合は `apps/fastapi-app/.venv\Scripts\activate`) | 仮想環境を有効化。シェルのプロンプトに `(.venv)` が付与される。 | なし | 該当なし |
| 4 | `pip install fastapi==0.119.0 "uvicorn[standard]==0.38.0"` | FastAPI 関連パッケージをインストール。最終行: `Successfully installed annotated-types-0.7.0 ... websockets-15.0.1`。pip からアップグレード通知あり。 | なし | 追加対応不要 |
| 5 | `pip freeze > apps/fastapi-app/requirements.txt` | 出力なし。依存関係を一括で `requirements.txt` に書き出し。 | なし | 該当なし |

> ℹ️ **補足**: `pip freeze` の出力には FastAPI が内部で利用する推移的依存関係を含む 19 個のパッケージが含まれます。これをそのまま `requirements.txt` としてコミットすると、どの環境でも同じバージョンがインストールされ、再現性とセキュリティスキャンの精度が維持されます。

## 依存関係管理に関する補足

Python では、直接依存のみを記述する「マニフェスト」と、推移的依存を含めてバージョンを固定する「ロックファイル」を分けて管理する運用が広まりつつあります。ただし一般的なテンプレートではまだ浸透していない場合も多いため、このリポジトリでもまずは `requirements.txt` にロックされた一覧を残すシンプルな構成にしています。

今後プロジェクトが成長したら、以下のようなツールでマニフェストとロックファイルを分離することを検討してください。

- [`pip-tools`](https://pip-tools.readthedocs.io/): `requirements.in` に直接依存を記述し、`pip-compile` で完全に固定された `requirements.txt` を生成。`pip-sync` で環境を同期できます。
- [`Poetry`](https://python-poetry.org/docs/): `pyproject.toml` に依存を宣言し、`poetry.lock` でバージョンを固定。`poetry export` で `requirements.txt` を生成することも可能です。

これらのツールを導入する際は、`requirements.in`（または `pyproject.toml`）と生成されたロックファイルをどちらもバージョン管理し、ドキュメントでもそれぞれの役割を明記すると利用者が迷いません。

## アプリケーションのエントリーポイント

FastAPI アプリケーションは `apps/fastapi-app/app/main.py` で定義されており、動作確認用の `/health` エンドポイントを公開しています。
