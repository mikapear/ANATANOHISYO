# ER 図・テーブル案・URL 設計（ANATANOHISYO・確定版）

`document.md` と対になる MVP 設計の確定版。実装はこの Dual ドキュメントに従う。

---

## 1. ER 図（概念モデル）

```mermaid
erDiagram
    users ||--o{ projects : owns
    users ||--o{ todos : owns
    users ||--o{ activity_logs : owns

    projects ||--o{ todos : "optional has"
    projects ||--o{ activity_logs : "optional has"

    todos ||--o{ todos : "recurrence_parent generates next"
    todos ||--o{ activity_logs : "optional link"

    activity_logs ||--o{ activity_log_photos : has

    users {
        bigint id PK
        string name
        string email UK
        string password
        timestamps
    }

    projects {
        bigint id PK
        bigint user_id FK
        string name
        text description "nullable"
        date start_date "nullable"
        date due_date "nullable"
        string status "active completed on_hold"
        timestamps
    }

    todos {
        bigint id PK
        bigint user_id FK
        bigint project_id FK "nullable nullOnDelete"
        bigint recurrence_parent_id FK "nullable self unique"
        string title
        text memo "nullable"
        date due_date "nullable"
        time due_time "nullable"
        string priority "low medium high"
        boolean is_completed
        datetime completed_at "nullable"
        string recurrence "none daily weekly monthly"
        datetime remind_at "nullable"
        timestamps
    }

    activity_logs {
        bigint id PK
        bigint user_id FK
        bigint project_id FK "nullable nullOnDelete"
        bigint todo_id FK "nullable nullOnDelete"
        string title
        date performed_on
        time performed_at "nullable"
        unsignedInt duration_minutes "nullable"
        text memo "nullable"
        timestamps
    }

    activity_log_photos {
        bigint id PK
        bigint activity_log_id FK "cascadeOnDelete"
        string disk "default public"
        string path
        string original_name "nullable"
        string mime_type "nullable"
        unsignedInt size_bytes "nullable"
        int sort_order
        timestamps
    }
```

### 設計判断メモ

| 判断 | 理由 |
| --- | --- |
| Blade + Vite（薄い JS） | 理解しやすく保守しやすい。Livewire / Inertia / SPA は使わない |
| `recurrence_parent_id` 自己参照 + UNIQUE | reopen 後の再 complete でも次回の重複生成を防ぐ |
| recurrence 専用テーブルなし | MVP の単純さを優先 |
| `due_date` / `due_time` 分離 | 期限超過ルールを明確に実装するため |
| Calendar / Today テーブルなし | 既存データ集約で足りる |
| 写真 `disk` + 削除時に実ファイル削除 | 初期は `public`。将来差し替え可。ゴミファイルを残さない |
| 完了と Activity Log 分離 | UX 上「記録する」は任意 |

### 外部キー方針（確定）

| 関係 | ON DELETE |
| --- | --- |
| `todos.project_id` → `projects.id` | `SET NULL` |
| `todos.recurrence_parent_id` → `todos.id` | `SET NULL` |
| `activity_logs.project_id` → `projects.id` | `SET NULL` |
| `activity_logs.todo_id` → `todos.id` | `SET NULL` |
| `activity_log_photos.activity_log_id` → `activity_logs.id` | `CASCADE`（アプリ側で実ファイルも削除） |
| 各表の `user_id` → `users.id` | `CASCADE` |

### 所有と整合

1. 業務データの操作は常に本人のみ（Policy）
2. 関連付ける Project / Todo も同一ユーザーのみ
3. Photo は親 Activity Log の所有者に従う

### インデックス案

- `projects(user_id, status)`
- `todos(user_id, is_completed, due_date)`
- `todos(user_id, project_id)`
- `todos(user_id, completed_at)`
- `todos(recurrence_parent_id)` **UNIQUE**（NULL 可。親あたり子は最大 1）
- `activity_logs(user_id, performed_on)`
- `activity_logs(user_id, project_id)`
- `activity_logs(todo_id)`
- `activity_log_photos(activity_log_id, sort_order)`

---

## 2. テーブル一覧

### `users`（Starter Kit / Laravel 標準）

| カラム | 型 | 備考 |
| --- | --- | --- |
| id | bigint PK | |
| name | string | |
| email | string unique | |
| email_verified_at | timestamp nullable | **認証必須には使わない** |
| password | string | |
| remember_token | string nullable | |
| timestamps | | |

パスワードリセット用の標準テーブル（`password_reset_tokens` 等）も Starter Kit に従う。Social 用テーブルは作らない。

### `projects`

| カラム | 型 | 備考 |
| --- | --- | --- |
| id | bigint PK | |
| user_id | bigint FK | |
| name | string | |
| description | text nullable | |
| start_date | date nullable | |
| due_date | date nullable | |
| status | string | `active` / `completed` / `on_hold` |
| timestamps | | |

削除時: 配下 Todo / Activity Log の `project_id` を NULL（DB `nullOnDelete`）。

### `todos`

| カラム | 型 | 備考 |
| --- | --- | --- |
| id | bigint PK | |
| user_id | bigint FK | |
| project_id | bigint FK nullable | `ON DELETE SET NULL` |
| recurrence_parent_id | bigint FK nullable → todos.id | **UNIQUE**。生成元 Todo。`ON DELETE SET NULL` |
| title | string | |
| memo | text nullable | |
| due_date | date nullable | |
| due_time | time nullable | |
| priority | string | default `medium` |
| is_completed | boolean | default false |
| completed_at | timestamp nullable | Asia/Tokyo 基準で「今日完了」判定 |
| recurrence | string | `none` / `daily` / `weekly` / `monthly` |
| remind_at | datetime nullable | 保持のみ |
| timestamps | | |

#### 繰り返し生成・重複防止（確定）

`complete` 時（`recurrence` が `daily` / `weekly` / `monthly`）:

1. 当該 Todo を完了（`is_completed = true`, `completed_at = now('Asia/Tokyo')` 相当）
2. **既に `recurrence_parent_id = 当該Todo.id` の行が存在するか確認**
   - 存在する → **次回を生成しない**（reopen 後の再 complete 対策）
   - 存在しない → 次回 Todo を 1 件 insert し、その `recurrence_parent_id` に当該 Todo の id を入れる
3. UNIQUE 制約により、同一親からの二重 insert も DB レベルで防ぐ
4. Activity Log は作らない

`reopen` 時:

1. `is_completed = false`, `completed_at = null`
2. **子 Todo（`recurrence_parent_id = 当該.id`）は削除しない**

次回 Todo の内容:

| 項目 | 値 |
| --- | --- |
| 引き継ぎ | user_id, project_id, title, memo, priority, recurrence, due_time |
| due_date | 元 due_date（無ければ完了日）+ 1 day / 1 week / 1 month |
| remind_at | ある場合は同等にずらす |
| recurrence_parent_id | 完了した元 Todo の id |
| is_completed | false |

### `activity_logs`

| カラム | 型 | 備考 |
| --- | --- | --- |
| id | bigint PK | |
| user_id | bigint FK | |
| project_id | bigint FK nullable | `ON DELETE SET NULL` |
| todo_id | bigint FK nullable | `ON DELETE SET NULL` |
| title | string | |
| performed_on | date | |
| performed_at | time nullable | |
| duration_minutes | unsigned int nullable | |
| memo | text nullable | |
| timestamps | | |

### `activity_log_photos`

| カラム | 型 | 備考 |
| --- | --- | --- |
| id | bigint PK | |
| activity_log_id | bigint FK | `ON DELETE CASCADE` |
| disk | string | **初期値 `public`**。将来変更可 |
| path | string | |
| original_name | string nullable | |
| mime_type | string nullable | jpeg / png / webp |
| size_bytes | unsigned int nullable | 最大 5MB 検証用 |
| sort_order | int | |
| timestamps | | |

#### 写真ライフサイクル（確定）

| 操作 | DB | 実ファイル |
| --- | --- | --- |
| アップロード | insert | `Storage::disk($disk)->put(...)` |
| Photo 単体削除 | delete | **同時に削除** |
| Activity Log 削除 | cascade delete photos | **各 path を削除** |
| 編集で写真削除 | delete | **同時に削除** |

制約: 最大 5 枚、5MB/枚、JPEG / PNG / WebP。

---

## 3. 画面用クエリ

基準タイムゾーン: **Asia/Tokyo**。

### Today（表示順・確定）

| 順 | ブロック | 条件 |
| --- | --- | --- |
| 1 | 期限超過 Todo | 未完了 かつ 下記「期限超過判定」を満たす |
| 2 | 今日期限の Todo | 未完了 かつ `due_date = 今日` かつ **期限超過ではない** |
| 3 | 今日完了した Todo | `is_completed` かつ `completed_at` の日付が今日 |
| 4 | 今日の Activity Log | `performed_on = 今日` |
| 5 | 進行中 Project | `status = active` |

`due_date IS NULL` は Today に出さない。

### 期限超過判定（確定）

未完了 Todo について（Asia/Tokyo の今日・現在時刻）:

```
due_date < 今日
  → 超過

due_date == 今日 AND due_time IS NOT NULL AND due_time < 現在時刻
  → 超過

due_date == 今日 AND due_time IS NULL
  → 超過にしない（「今日期限」側）

due_date IS NULL
  → 超過にしない（Today にも出さない）
```

### Calendar

専用テーブルなし。

- 月: 対象月の Todo（`due_date`）と Activity Log（`performed_on`）
- 日: 指定日の上記一覧（`due_time` / `performed_at` は表示補助）

---

## 4. 区分値・完了フロー

### Project status

`active` / `completed` / `on_hold`

### Todo priority / recurrence

`low|medium|high` / `none|daily|weekly|monthly`

### complete

```
1. 完了状態へ更新
2. 繰り返しなら、子未作成のときだけ次回 1 件生成
3. Activity Log は作らない
```

### reopen

```
1. 未完了へ戻す
2. 子 Todo は削除しない
```

---

## 5. Authorization（確定）

| Policy | 要点 |
| --- | --- |
| `ProjectPolicy` | `user_id` 一致 |
| `TodoPolicy` | `user_id` 一致 |
| `ActivityLogPolicy` | `user_id` 一致 |
| `ActivityLogPhotoPolicy` | 親 Log の `user_id` 一致 |

`auth` 必須。`verified` は必須にしない。一覧は常に本人スコープ。

---

## 6. URL 設計

Blade サーバサイド描画。専用 API・SPA は作らない。

### 認証（Starter Kit・Blade）

| メソッド | URL | 用途 |
| --- | --- | --- |
| GET/POST | `/register` | 登録 |
| GET/POST | `/login` | ログイン |
| POST | `/logout` | ログアウト |
| GET/POST | `/forgot-password` | パスワードリセット依頼 |
| GET/POST | `/reset-password/...` | パスワード再設定 |

（パスは Starter Kit の既定に合わせる。メール認証必須・Social は含めない。）

### アプリ本体

| メソッド | URL | 用途 |
| --- | --- | --- |
| GET | `/` | **Today（最重要）** |
| REST | `/projects` | Project CRUD |
| REST | `/todos` | Todo CRUD |
| POST | `/todos/{todo}/complete` | 完了＋条件付き次回生成 |
| POST | `/todos/{todo}/reopen` | reopen（子は残す） |
| REST | `/activity-logs` | Activity Log CRUD |
| POST/DELETE | `/activity-logs/{activity_log}/photos[...]` | 写真（任意分離） |
| GET | `/calendar` | 月表示 |
| GET | `/calendar/day` | 日表示 |
| GET | `/activity-logs/create?todo_id=` | 「活動記録として残す」 |

---

## 7. コントローラ案

| コントローラ | 役割 |
| --- | --- |
| `TodayController` | ホーム集約（完了・記録への導線を短く） |
| `ProjectController` | CRUD |
| `TodoController` | CRUD + complete / reopen |
| `ActivityLogController` | CRUD + 写真（ファイル削除含む） |
| `ActivityLogPhotoController` | 写真分離時 |
| `CalendarController` | 月・日 |

UI は Blade View。JS は Vite で必要最小限。

---

## 8. UI / 実装スタック（確定）

| 層 | 選択 |
| --- | --- |
| View | Blade |
| JS | 最小限 + Vite |
| 使わない | Livewire, Inertia, Vue, React |
| 認証 UI | Starter Kit（Blade） |
| ファイル | `Storage::disk('public')` 初期 |

---

## 9. `document.md` との整合チェック

| 確定事項 | 本設計 | 矛盾 |
| --- | --- | --- |
| Blade 中心 | §8 / ルート | なし |
| Starter Kit + PW リセット | 認証 URL | なし |
| recurrence 重複防止 | `recurrence_parent_id` UNIQUE | なし |
| reopen で子を残す | reopen 仕様 | なし |
| 期限超過ルール | §3 | なし（Today「今日期限」は超過除外と整合） |
| Asia/Tokyo | クエリ節 | なし |
| 写真 public + 実ファイル削除 | photos 節 | なし |
| Today 最重要・表示順 | §3 Today | なし（超過→今日期限の順） |
| Calendar テーブルなし | §3 | なし |
| Extension なし | スコープ外 | なし |

---

## 10. 実装順序案（参考）

1. Starter Kit（Blade）導入・タイムゾーン Asia/Tokyo・認証確認
2. migration / Model / Policy（projects, todos, activity_logs, photos）
3. Project CRUD
4. Todo CRUD + complete / reopen（recurrence）
5. Activity Log CRUD + 写真（アップロード・削除同期）
6. Today（最重要 UI）
7. Calendar（月・日）
8. スマホ導線・バリデーション・認可の通し確認

---

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-08-11 | 初版 |
| 2026-08-11 | 設計レビュー一次反映 |
| 2026-08-11 | **確定版**（Blade / Starter Kit / recurrence_parent_id / 期限超過 / 写真削除 / Today / 実装順序） |
