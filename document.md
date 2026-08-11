# ANATANOHISYO 要件定義書（MVP・確定版）

本ドキュメントと [`docs/er-and-routes.md`](docs/er-and-routes.md) を MVP 設計の確定版とする。

---

## 1. アプリ概要

### アプリ名

ANATANOHISYO（あなたの秘書）

### 目的

仕事・研究・日常生活などの「やること」と「実際にやったこと」を、プロジェクト単位または日常の単発タスクとして簡単に管理できる個人向け Web アプリ。

### コンセプト

- 予定（Todo）と実績（Activity Log）を同じ個人空間で扱う
- 用途を限定しない汎用プロジェクトで、仕事・研究・家事・旅行・運動などをまとめる
- スマートフォンからの日常利用を最優先する
- **Today を最重要画面**とし、今日の確認・完了・記録を少ない操作で行えること
- MINNANOKOE とは独立した別アプリである

### MVP で作るもの（確定）

1. 認証
2. Project
3. Todo
4. Activity Log
5. Calendar
6. Today（ホーム画面。独立データモデルではない）

Research / Medication / Exercise などの用途別専用機能は追加しない。

---

## 2. ユーザーと前提

| 項目 | 内容 |
| --- | --- |
| 利用者 | 本人のみ（個人利用） |
| マルチテナント | なし。データはユーザー単位で完全分離 |
| 共有・公開 | なし |
| 端末 | スマートフォン優先、レスポンシブ |
| タイムゾーン | **Asia/Tokyo**（期限超過判定・「今日」判定の基準） |
| 認証 | Laravel Starter Kit（Blade）・メール＋パスワード |

---

## 3. UI / 技術方針（確定）

| 項目 | 方針 |
| --- | --- |
| 画面 | **Blade 中心** |
| 使わないもの（MVP） | Livewire / Inertia / Vue / React 等 |
| JavaScript | 必要最低限。Vite 経由 |
| 構成方針 | Laravel 標準に近い、理解しやすく保守しやすい構成 |
| フロントビルド | Vite |
| バックエンド | Laravel、PHP 8.4 環境、MySQL |
| 本番 | ロリポップを想定 |

---

## 4. 機能要件

### 4.1 認証（確定）

Laravel の Starter Kit を利用し、**Blade 中心の UI** に合わせる。

利用する機能:

- メールアドレス＋パスワード
- ユーザー登録
- ログイン
- ログアウト
- パスワードリセット

含めないもの:

- メールアドレス認証の必須化（`verified` は必須にしない）
- Google ログイン等の Social Login

ログイン必須でアプリ本体を利用する。  
Project / Todo / Activity Log / Activity Log Photo は、ログインユーザー本人のデータのみ操作可能（Laravel Policy 等でサーバー側認可）。

### 4.2 Project

用途を限定しない汎用プロジェクト。

| 項目 | 説明 |
| --- | --- |
| プロジェクト名 | 必須 |
| 説明 | 任意 |
| 開始日 | 任意 |
| 期限 | 任意 |
| ステータス | `active` / `completed` / `on_hold` |

振る舞い:

- 一覧・詳細・作成・編集・削除
- 詳細で配下の Todo・Activity Log を確認
- **削除しても Todo / Activity Log は削除しない。`project_id` を NULL にして保持する**

### 4.3 Todo

| 項目 | 説明 |
| --- | --- |
| タイトル | 必須 |
| Project | 任意 |
| due_date | 任意（nullable） |
| due_time | 任意（nullable） |
| 優先順位 | `low` / `medium` / `high` |
| 完了状態 | 未完了 / 完了 |
| メモ | 任意 |
| 繰り返し | `none` / `daily` / `weekly` / `monthly` |
| リマインダー | `remind_at` を DB 保持（実通知はしない） |
| recurrence_parent_id | 次回生成元の自己参照（重複生成防止用） |

#### リマインダー

- DB に日時を保持するのみ
- メール / Push 等の実通知は実装しない
- 将来 Laravel Notifications 等を追加できる設計とする

#### 繰り返し・complete / reopen（確定）

- `daily` / `weekly` / `monthly` の Todo を **complete したとき、次回 Todo を 1 件だけ生成**する
- 無限に事前生成はしない
- **reopen しても、すでに生成された次回 Todo は削除しない**
- **同じ元 Todo をもう一度 complete しても、次回 Todo を重複生成しない**
- 判定のため `todos.recurrence_parent_id`（自己参照 FK）を持つ
  - 生成された次回 Todo に、元 Todo の id を `recurrence_parent_id` として保存する
  - complete 時に「この Todo を親とする子が既にあるか」を見て、あれば生成をスキップする
- recurrence 専用テーブルは作らない

次回 Todo の引き継ぎ:

- `user_id`, `project_id`, `title`, `memo`, `priority`, `recurrence`, `due_time`
- `due_date`: 元の `due_date`（なければ完了日）を起点に +1 day / +1 week / +1 month
- `remind_at`: ある場合は期限のずれに合わせてずらす

#### 期限超過判定（確定・Asia/Tokyo）

未完了 Todo について:

| 条件 | 判定 |
| --- | --- |
| `due_date` が今日より前 | 期限超過 |
| `due_date` が今日、かつ `due_time` が現在時刻より前 | 期限超過 |
| `due_date` が今日、かつ `due_time` が null | **当日中は期限超過にしない** |
| `due_date` が null | 期限超過にしない |

「今日」「現在時刻」はすべて **Asia/Tokyo** 基準。

#### Todo 完了と Activity Log

- **別処理**とする
- 完了後、任意で「活動記録として残す」→ Activity Log 作成画面へ
- Activity Log は Todo に紐付いていてもいなくてもよい

### 4.4 Activity Log

| 項目 | 説明 |
| --- | --- |
| タイトル | 必須 |
| Project | 任意 |
| 関連 Todo | 任意 |
| 実施日 | 必須 |
| 実施時刻 | 任意 |
| 作業時間 | 任意（分） |
| メモ | 任意 |
| 写真 | 任意（下記） |

#### 写真（確定）

| 項目 | 仕様 |
| --- | --- |
| 枚数 | 最大 5 枚 / Activity Log |
| サイズ | 最大 5MB / 枚 |
| 形式 | JPEG / PNG / WebP |
| 初期 disk | **`public`** |
| 保存 | Laravel Filesystem（`disk` カラムで将来変更可） |
| 実ファイル削除 | Activity Log 削除・Photo 削除・編集時の写真削除のとき、**DB と同時に実ファイルも削除**する。不要ファイルを残さない |

### 4.5 Calendar

- 専用テーブルは作らない
- Todo の `due_date` / `due_time` と Activity Log の `performed_on` 等から月表示・日別表示を構築する

### 4.6 Today（最重要画面・確定）

独立テーブルは持たない。ログイン後ホーム。

スマートフォンで次を少ない操作で行える UI を優先する:

- 今日の Todo 確認
- Todo 完了
- Activity Log 追加

表示順:

1. **期限超過 Todo**
2. **今日期限の Todo**（当日・未完了・かつ期限超過ではないもの）
3. **今日完了した Todo**
4. **今日の Activity Log**
5. **進行中 Project**

**`due_date` が null の Todo は Today に自動表示しない。**

---

## 5. データ関係（概念）

```
User
├── Projects
├── Todos
│     └── Todos（recurrence_parent_id による次回生成）
└── Activity Logs
        └── Activity Log Photos

Project
├── Todos（任意）
└── Activity Logs（任意）

Todo
└── Activity Log（任意関連。完了処理とは独立）
```

---

## 6. 非機能要件

| 区分 | 内容 |
| --- | --- |
| タイムゾーン | Asia/Tokyo |
| セキュリティ | 認証必須。Policy によるサーバー側認可必須 |
| ファイル | Filesystem `public` 初期。削除時は実ファイルも削除 |
| 保守 | Blade + 薄い JS。過剰な抽象化を避ける |

---

## 7. 画面構成（MVP）

| 画面 | 目的 |
| --- | --- |
| 登録 / ログイン / パスワードリセット | Starter Kit（Blade） |
| Today | 最重要ホーム |
| Project CRUD | プロジェクト管理 |
| Todo CRUD + complete / reopen | やること管理 |
| Activity Log CRUD + 写真 | 実績記録 |
| Calendar 月 / 日 | 時間軸ビュー |

スマホ向けに Today / Todo / Calendar / Project / Activity 程度の下部ナビ等を想定。

---

## 8. 主な操作フロー

### 今日の Todo を片付ける

1. Today を開く
2. 期限超過・今日期限を確認
3. 完了（繰り返しなら次回 1 件生成。重複生成はしない）
4. 任意で「活動記録として残す」

### reopen

1. 完了済み Todo を reopen
2. すでに生成済みの次回 Todo は残す
3. 再度 complete しても、子が既にあれば次回は作らない

---

## 9. MVP 対象外

- Research / Medication / Exercise 等の Extension
- Livewire / Inertia / Vue / React
- Social Login
- メール認証必須
- リマインダー実通知
- Calendar 専用テーブル
- recurrence 専用テーブル
- 共有・公開・管理者画面・多言語

---

## 10. 関連ドキュメント

- DB・URL 設計（確定版）: [`docs/er-and-routes.md`](docs/er-and-routes.md)

---

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-08-11 | 初版 |
| 2026-08-11 | 設計レビュー一次反映 |
| 2026-08-11 | **確定版**（Blade / Starter Kit / recurrence_parent_id / 期限超過 / 写真削除 / Today 優先 / 実装範囲） |
