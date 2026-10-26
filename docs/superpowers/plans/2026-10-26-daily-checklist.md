# Daily Checklist — Implementation Plan

> **Status:** disetujui 26 Okt 2026. UI tinggal di **kalender**, tidak ada halaman `/checklist`.
> **Mockup:** [`docs/mockups/checklist-on-calendar.html`](../../mockups/checklist-on-calendar.html) · screenshot di [`docs/mockups/screenshots/`](../../mockups/screenshots)

**Goal:** Setiap user punya daftar **routine harian** yang selalu sama tiap hari, bisa dicentang setelah dikerjakan, bisa di-skip, dan punya satu catatan bebas per hari untuk mencatat kendala. Head bisa melihat rutinitas developer-nya (read-only).

**Architecture:** Entitas baru **terpisah dari `tasks`** — routine bukan pekerjaan ad-hoc, jadi tidak masuk kolom kanban, metrik molor, atau riwayat task. Tiga tabel: `routines` (definisi per user), `routine_entries` (status per routine per tanggal), `routine_notes` (catatan per user per tanggal). Semua interaksi tinggal di halaman kalender (`/tasks/calendar`) lewat bar **Rutinitas Kamu**; tidak ada halaman baru. Hari dihitung dengan zona `Asia/Jakarta`. Tidak ada cron: "hari ini" dihitung per request.

**Tech Stack:** Laravel 12, PHP 8.4, Inertia v2, Svelte 5 (runes), TypeScript, Tailwind v4, Pest 4, Wayfinder. Dev & test lewat Docker (`daily-app`, DB test `daily_test`).

---

## 0. Keputusan yang sudah dikunci

| # | Keputusan | Alasan |
|---|---|---|
| 1 | **Tanpa halaman `/checklist`** — semua di `/tasks/calendar` | Satu halaman untuk "hari ini" dan "bulan ini"; tidak menambah nav item |
| 2 | **Tabel terpisah dari `tasks`** | Routine tidak boleh merusak metrik molor/kalender task |
| 3 | **Bar "Rutinitas Kamu" selalu hari ini** | Navigasi bulan tidak boleh mengubah konteks check-in; tanggal lampau read-only |
| 4 | **Arsip, bukan hapus** | Riwayat tetap utuh dan angka historis tidak berubah |
| 5 | **Tanpa restore di v1** | Restore mengubah angka masa lalu secara retroaktif (lihat §1) |
| 6 | **Role global `head` / `developer`** | Divisi tidak dipakai di modul task/checklist; divisi cuma master feature request |
| 7 | **Head read-only** | Angka rekap harus jujur; aksi tetap di tangan pemilik routine |
| 8 | **Opsi A di grid kalender** — indikator `4/5` per tanggal | Task tetap dominan; riwayat kebaca tanpa alat baru |
| 9 | **English copy**, format tanggal `id-ID` | Konsisten dengan app |

**Asumsi:**
1. Routine personal, dibuat sendiri oleh user — bukan dibagikan admin.
2. Routine tidak membuat/mengubah task board.
3. Tidak ada hukuman visual merah untuk hari yang bolong.
4. ≤ 20 routine per user, jadi hitungan di PHP aman tanpa query berat.
5. Tidak ada backfill data lama.

---

## 1. Domain Model

```
users 1 ──< routines 1 ──< routine_entries
  │
  └──< routine_notes
```

| Tabel | Kolom penting | Catatan |
|---|---|---|
| `routines` | `id`, `user_id`, `title`, `position`, `starts_on` (date), `archived_on` (date, nullable), timestamps | Definisi routine. Tidak pernah di-hard-delete dari UI. |
| `routine_entries` | `id`, `routine_id`, `check_date` (date), `status` (`done`\|`skipped`), `checked_at`, timestamps | Satu baris per routine per hari. **Tidak ada baris = pending**. Unique `(routine_id, check_date)`. |
| `routine_notes` | `id`, `user_id`, `note_date` (date), `body` (text), timestamps | Maks satu catatan per user per hari. Unique `(user_id, note_date)`. |

### Kelayakan routine pada suatu tanggal

```
routine aktif pada tanggal d  ⟺  starts_on ≤ d  AND  (archived_on IS NULL OR d < archived_on)
```

- `starts_on` / `archived_on` disimpan sebagai **tanggal lokal WIB**, bukan diturunkan dari `created_at` UTC — supaya routine yang dibuat jam 06:00 WIB tanggal 27 tidak terbaca "mulai tanggal 26".
- `archived_on` adalah **hari pertama routine tidak dihitung lagi**. Arsip hari ini → langsung hilang dari bar hari ini.

### Kalau routine diarsipkan, riwayatnya bagaimana?

- Entry **sebelum** `archived_on` tetap ada dan tetap dihitung di hari-hari itu.
- Tanggal `archived_on` dan sesudahnya tidak menghitung routine itu sama sekali — denominator progres ikut turun, jadi `4/5` di hari lampau tidak berubah jadi `4/4`.
- Buka tanggal lampau → routine masih muncul lengkap dengan ✓/⊘ waktu itu.
- Section `Archived routines` menampilkan definisinya, bukan entry-nya.
- Hard delete hanya terjadi kalau **user-nya** dihapus (FK cascade).

**Kenapa tanpa restore:** restore membuat routine "aktif kembali" untuk masa lalu saat dia sebenarnya absen, sehingga streak dan persentase yang sudah lewat berubah. Kalau nanti butuh rehat sementara (cuti 2 minggu lalu lanjut), solusinya tabel `routine_pauses` per interval — bukan restore.

### State

| State | Representasi | Arti |
|---|---|---|
| `pending` | tidak ada baris entry | Belum dikerjakan |
| `done` | `status = 'done'` | Sudah dikerjakan |
| `skipped` | `status = 'skipped'` | Sengaja dilewati |
| `archived` | `archived_on ≤ hari ini` | Tidak dihitung lagi, riwayat tetap ada |

### Invariant

1. User hanya menyentuh routine & entry miliknya → `403`.
2. Check-in hanya untuk **hari ini atau kemarin** (WIB); tanggal lain → `422`.
3. `pending` = hapus baris entry, bukan status ketiga.
4. Routine terarsip tidak bisa dicentang lagi.
5. Catatan kosong = hapus baris.
6. Progres harian = `(done + skipped) / total routine aktif hari itu`.

---

## 1b. Role & akses head

```
users.role  →  'developer' (default)  |  'head'
```

Satu kolom string + cast ke `App\Enums\UserRole`. `role` **tidak** masuk `$fillable` supaya form profile/register tidak bisa menaikkan diri jadi head.

| Aksi | Developer | Head |
|---|---|---|
| Lihat & kelola rutinitas sendiri | ✅ | ✅ |
| Lihat rutinitas hari ini milik developer lain | ❌ (bar tetap milik sendiri) | ✅ read-only |
| Lihat entry & catatan historis developer lain | ❌ | ✅ |
| Centang / ubah / arsip rutinitas orang lain | ❌ 403 | ❌ 403 |
| Rekap progres tim di filter user | ❌ | ✅ |

### Policy

```php
// app/Policies/ChecklistPolicy.php
final class ChecklistPolicy
{
    public function view(User $viewer, User $owner): bool
    {
        return $viewer->id === $owner->id || $viewer->isHead();
    }

    public function manage(User $viewer, User $owner): bool
    {
        return $viewer->id === $owner->id;
    }
}
```

Didaftarkan di `AppServiceProvider@boot`:

```php
Gate::policy(User::class, ChecklistPolicy::class);
```

`view` dipakai saat menentukan owner bar & data historis; `manage` dipakai semua endpoint tulis. Developer yang membuka data orang lain → **403**.

### Cara menjadikan user head

Sekali saja, tanpa command/UI:

```bash
docker exec -u www-data -e HOME=/tmp daily-app \
  php artisan tinker --execute="App\Models\User::where('email', 'aji@daily.test')->update(['role' => 'head']);"
```

```sql
UPDATE users SET role = 'head' WHERE email = 'aji@daily.test';
```

Nilai harus lowercase; enum cast akan error kalau salah, supaya tidak diam-diam jadi developer. `-e HOME=/tmp` perlu karena `www-data` tidak bisa menulis ke `/var/www/.config/psysh` di container ini.

---

## 2. UX Spec — semuanya di kalender

### Bar "Rutinitas Kamu" (desktop)

Di atas grid kalender, **selalu menampilkan hari ini**, tidak ikut navigasi bulan.

```
┌ Rutinitas Kamu · hari ini ───────────────────────────── 4/5  🔥 4 ──────┐
│ ☑ Review pull request       ☑ Deploy staging        ⊘ Update changelog  │
│ ☐ Cek error log 24 jam      + Tambah routine        [✎ Catatan]         │
└─────────────────────────────────────────────────────────────────────────┘
```

- Dua kolom di desktop, satu kolom di mobile; item berupa baris dengan checkbox.
- Tap baris = `done` ⇄ `pending`. `⋯` per item = `Skip` / `Edit` / `Archive`.
- `+ Tambah routine` membuka dialog judul (maks 255 char).
- `✎ Catatan` membuka popover textarea; kalau terisi, tampil potongan teks di bar.
- `4/5` = `(done + skipped) / total`; `🔥 4` = streak.
- Semua item `done` → bar tetap tampil dengan `5/5` + `All done for today.`

### Mode head

Filter user yang sudah ada di kalender dipakai ulang:

```
[ Semua user ▾ ]
  Semua user          ← bar menampilkan rutinitas kamu
  Rina      4/5
  Bagas     2/5
  Sari      5/5
```

- Progress per developer hanya muncul untuk head.
- Pilih Rina → bar berubah jadi `Rutinitas Rina` + ikon lock, **read-only**: checkbox, `+ Tambah`, `⋯`, dan `Save note` tidak dirender.
- Grid & panel detail ikut menampilkan rutinitas Rina (riwayatnya).
- `Semua user` → bar selalu milik sendiri, bukan agregat.

### Grid kalender (opsi A)

```
┌──────────────────────┐
│ 23        hari ini   │
│ ▶ Mockup calendar    │
│ ▶ Filter laporan     │
│ +1 lagi              │
│ ▣ ███████████ 4/5    │  ← ungu = rutinitas
└──────────────────────┘
```

- Satu baris tambahan di kaki sel: ikon `▣`, bar 3px, angka `4/5`.
- Hanya muncul kalau tanggal itu punya routine aktif (sebelum routine dibuat → tidak ada).
- `title` + `aria-label` menyebut `3 selesai, 1 skip, 1 belum`.

### Panel detail harian

Blok `Checklist` di atas seksi task: progress bar, baris status (`✓` done, `⊘` skipped, `○` pending), dan kutipan catatan. Read-only untuk semua tanggal — penulisan hanya dari bar (hari ini).

### Mobile

- Grid disembunyikan, agenda tampil seperti sekarang.
- Bar "Rutinitas Kamu" jadi kartu penuh di atas agenda, tetap hari ini.
- Agenda per tanggal menampilkan baris `▣ 4/5 · "catatan…"`.

### Board chip

Di header board (`/tasks`): chip `Rutinitas 3/5` → link ke kalender. Read-only, tidak bisa dicentang dari board. Hanya kalau user punya routine aktif hari ini.

### Copy (English)

| Konsep | Label |
|---|---|
| Judul bar | `Your routine` + `· today` |
| Mode head | `Rina's routine` + `· today`, tooltip `Read-only` |
| Tambah | `Add routine` |
| Dialog | `Routine name` / `Save routine` |
| Item action | `Skip` / `Edit` / `Archive` |
| Status | `Done` / `Skipped` / `Pending` |
| Catatan | `Note for today` + placeholder `Anything blocking you today?` |
| Progres | `4 of 5 done` |
| Streak | `4-day streak` |
| Arsip | `Archived routines` (collapsed, read-only) |
| Empty | `No routines yet. Add the first thing you do every day.` |
| Semua beres | `All done for today.` |
| Read-only | `Read-only` |

### Aksesibilitas

- Checkbox `<input type="checkbox">` asli, `aria-label` = judul routine.
- Tombol skip `aria-pressed`; menu `⋯` punya `aria-haspopup`/`aria-expanded`.
- Ringkasan progres `aria-live="polite"`.
- Fokus terlihat; target tap ≥ 44px di mobile.
- Kontras teks status ≥ 4.5:1 (light & dark).

---

## 3. Data & API

### Route (tidak ada halaman baru)

```php
// routes/web.php — dalam group auth + verified, di atas resource tasks
Route::post('routines', [RoutineController::class, 'store'])->name('routines.store');
Route::patch('routines/{routine}', [RoutineController::class, 'update'])->name('routines.update');
Route::patch('routines/{routine}/position', [RoutineController::class, 'reorder'])->name('routines.reorder');
Route::delete('routines/{routine}', [RoutineController::class, 'archive'])->name('routines.destroy');

Route::patch('checklist/{routine}', [ChecklistController::class, 'check'])->name('checklist.check');
Route::put('checklist/note', [ChecklistController::class, 'saveNote'])->name('checklist.note.update');
```

Semua `redirect()->back()` — karena bar ada di halaman kalender, user tetap di sana.

### Query param kalender

| Param | Validasi | Default |
|---|---|---|
| `month` | `nullable`, `date_format:Y-m` | bulan berjalan (WIB) |
| `user_id` | `nullable`, `integer`, `exists:users,id` | semua user |
| `date` | *client-side* | hari ini |

### Props tambahan di `TaskCalendarController@index`

```jsonc
{
  "checklist": {
    "owner": { "id": 1, "name": "Aji" },
    "read_only": false,
    "date": "2026-10-26",
    "items": [
      { "id": 12, "title": "Review pull request", "position": 1.0,
        "status": "done", "checked_at": "2026-10-26T01:12:00Z" }
    ],
    "note": { "body": "Deploy staging kena timeout…", "updated_at": "2026-10-26T01:20:00Z" },
    "stats": { "total": 5, "done": 3, "skipped": 1, "pending": 1, "percent": 80, "streak": 4 }
  },
  "checklist_by_date": {
    "2026-10-23": { "total": 5, "done": 3, "skipped": 1, "pending": 1, "note": "…" }
  },
  "checklist_team": [
    { "id": 3, "name": "Rina", "done": 4, "skipped": 0, "total": 5 }
  ],
  "archived_routines": [
    { "id": 7, "title": "Old routine", "archived_on": "2026-09-30" }
  ]
}
```

- `checklist` = hari ini, milik `user_id` yang difilter (atau diri sendiri kalau `All users`).
- `checklist_by_date` = semua tanggal di grid, untuk indikator `4/5`.
- `checklist_team` hanya dikirim ke head.
- `read_only` = `owner.id !== auth()->id()`.

### Query

```php
$tz      = config('tasks.display_timezone');
$today   = CarbonImmutable::now($tz)->toDateString();
$ownerId = $filters['user_id'] ?? auth()->id();
$viewer  = auth()->user();

$readOnly = $ownerId !== $viewer->id;
abort_if($readOnly && ! $viewer->isHead(), 403);

$routines = Routine::query()
    ->where('user_id', $ownerId)
    ->orderBy('position')
    ->get();

$entries = RoutineEntry::query()
    ->whereIn('routine_id', $routines->pluck('id'))
    ->whereBetween('check_date', [$gridStart, $gridEnd])
    ->get()
    ->groupBy(fn ($e) => $e->check_date->toDateString());

$notes = RoutineNote::query()
    ->where('user_id', $ownerId)
    ->whereBetween('note_date', [$gridStart, $gridEnd])
    ->get()
    ->keyBy(fn ($n) => $n->note_date->toDateString());
```

Tiga query tambahan, tidak ada N+1. `isActiveOn()` dipakai untuk menghitung total per tanggal.

### Endpoint check

```php
$validated = $request->validate([
    'date'   => ['required', 'date_format:Y-m-d'],
    'status' => ['required', Rule::in(['done', 'skipped', 'pending'])],
]);

Gate::authorize('manage', $routine->user);

$today     = CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
$yesterday = CarbonImmutable::parse($today)->subDay()->toDateString();

abort_unless(in_array($validated['date'], [$today, $yesterday], true), 422);
abort_if(! $routine->isActiveOn($validated['date']), 422);

// idempoten; unique(routine_id, check_date) jadi jaring pengaman
```

### Endpoint catatan

```php
$validated = $request->validate(['body' => ['nullable', 'string', 'max:2000']]);
$body = trim((string) ($validated['body'] ?? ''));

if ($body === '') {
    RoutineNote::where('user_id', auth()->id())->whereDate('note_date', $today)->delete();
} else {
    RoutineNote::updateOrCreate(
        ['user_id' => auth()->id(), 'note_date' => $today],
        ['body' => $body]
    );
}
```

### Migration

```php
// users
$table->string('role')->default('developer')->index();

// routines
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->string('title');
$table->float('position')->default(0);
$table->date('starts_on');
$table->date('archived_on')->nullable();
$table->timestamps();
$table->index(['user_id', 'archived_on']);

// routine_entries
$table->id();
$table->foreignId('routine_id')->constrained()->cascadeOnDelete();
$table->date('check_date');
$table->string('status', 16);
$table->timestamp('checked_at')->nullable();
$table->timestamps();
$table->unique(['routine_id', 'check_date']);
$table->index(['check_date', 'status']);

// routine_notes
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->date('note_date');
$table->text('body');
$table->timestamps();
$table->unique(['user_id', 'note_date']);
```

### Config `config/checklist.php`

```php
return [
    'max_routines'         => 20,
    'note_max_length'      => 2000,
    'streak_lookback_days' => 90,
    'history_days'         => 90,
];
```

---

## 4. File Map

| File | Perubahan |
|---|---|
| `database/migrations/2026_10_27_000000_add_role_to_users_table.php` | Create |
| `database/migrations/2026_10_27_000001_create_routines_table.php` | Create |
| `database/migrations/2026_10_27_000002_create_routine_entries_table.php` | Create |
| `database/migrations/2026_10_27_000003_create_routine_notes_table.php` | Create |
| `app/Enums/UserRole.php` | Create |
| `app/Models/User.php` | Modify — cast `role`, `isHead()`, `routines()` |
| `app/Models/Routine.php` | Create — `isActiveOn()`, `scopeActiveOn()`, relasi |
| `app/Models/RoutineEntry.php` | Create |
| `app/Models/RoutineNote.php` | Create |
| `app/Policies/ChecklistPolicy.php` | Create |
| `app/Providers/AppServiceProvider.php` | Modify — registrasi policy |
| `app/Actions/Checklist/ComputeChecklistStats.php` | Create — progres, 7 hari, streak |
| `app/Http/Controllers/TaskCalendarController.php` | Modify — props checklist |
| `app/Http/Controllers/RoutineController.php` | Create |
| `app/Http/Controllers/ChecklistController.php` | Create — check + note |
| `app/Http/Controllers/TaskController.php` | Modify — chip `routine` di board |
| `routes/web.php` | Modify — 6 route |
| `config/checklist.php` | Create |
| `database/factories/RoutineFactory.php` | Create |
| `database/factories/RoutineEntryFactory.php` | Create |
| `resources/js/components/checklist/RoutineBar.svelte` | Create |
| `resources/js/components/checklist/RoutineMenu.svelte` | Create |
| `resources/js/components/checklist/RoutineDialog.svelte` | Create |
| `resources/js/components/checklist/RoutineNote.svelte` | Create |
| `resources/js/pages/tasks/Calendar.svelte` | Modify — bar + state |
| `resources/js/components/tasks/CalendarDay.svelte` | Modify — indikator `4/5` |
| `resources/js/components/tasks/DayDetail.svelte` | Modify — blok checklist |
| `resources/js/components/tasks/CalendarAgenda.svelte` | Modify — baris checklist |
| `resources/js/pages/tasks/Index.svelte` | Modify — chip rutinitas |
| `resources/js/types/tasks.ts` | Modify — tipe checklist |
| `resources/js/components/AppSidebar.svelte` | Tidak berubah |
| `tests/Feature/UserRoleTest.php` | Create |
| `tests/Feature/RoutineModelTest.php` | Create |
| `tests/Feature/RoutineTest.php` | Create |
| `tests/Feature/ChecklistCheckInTest.php` | Create |
| `tests/Feature/ChecklistNoteTest.php` | Create |
| `tests/Feature/ChecklistStatsTest.php` | Create |
| `tests/Feature/ChecklistAccessTest.php` | Create |
| `tests/Feature/CalendarRoutineTest.php` | Create |

---

## 5. Phase 0 — Fondasi + role (1 hari) — ✅ SELESAI (26 Okt 2026)

**Tujuan:** skema, model, enum, policy, factory, dan test invariant berdiri. Belum ada route/UI.

### Task 0.1 — Migration

- [x] 4 migration: role, routines, routine_entries, routine_notes.
- [x] Index & unique persis §3.
- [x] `down()` aman (drop index lalu kolom untuk `users.role`).

### Task 0.2 — Model

- [x] `UserRole` enum + `label()`.
- [x] `User`: cast `role`, `isHead()`, `routines()`; `role` **tidak** fillable.
- [x] `Routine`: fillable, cast `starts_on`/`archived_on`, `user()`, `entries()`, `isActiveOn()`, `scopeActiveOn()`.
- [x] `RoutineEntry`: cast `check_date`, `checked_at`, relasi `routine()`.
- [x] `RoutineNote`: cast `note_date`.

### Task 0.3 — Policy + config + factory

- [x] `ChecklistPolicy` (`view`, `manage`) + registrasi `Gate::policy(User::class, ...)`.
- [x] `config/checklist.php`.
- [x] `RoutineFactory` (state `archived()`), `RoutineEntryFactory`.
- [x] `UserFactory`: default `developer`, state `head()`.

### Task 0.4 — Test

- [x] `UserRoleTest`: default developer; cast enum; `isHead()`; mass-assignment ditolak; query builder update jalan.
- [x] `RoutineModelTest`: `isActiveOn()` 6 kasus; `scopeActiveOn()` konsisten; unique entry; cascade user → routines/entries/notes.

### Acceptance Phase 0

```bash
docker exec -u www-data daily-app php artisan test --filter="UserRole|RoutineModel"
docker exec -u www-data daily-app php artisan test
docker exec -u www-data daily-app php artisan migrate
docker exec -u www-data daily-app ./vendor/bin/pint --test
```

- [x] Migration jalan di MySQL dev tanpa error.
- [x] `isActiveOn()` benar untuk: sebelum `starts_on`, hari `starts_on`, di tengah, sehari sebelum arsip, hari arsip, setelah arsip, `archived_on` null.
- [x] Hapus user → routines, entries, notes ikut terhapus.

**Hasil verifikasi (26 Okt 2026):**

```
php artisan test ......... 96 passed, 2 skipped (489 assertions)
pint --test .............. 105 files PASS
php artisan migrate ...... 4 migration DONE di DB dev
tables ................... routines, routine_entries, routine_notes, users.role
```

---

## 6. Phase 1 — Bar + CRUD routine (1,5 hari)

### Task 1.1 — Data untuk kalender

- [ ] `TaskCalendarController`: owner, read_only, items hari ini, note, stats, `checklist_by_date`, `archived_routines`.
- [ ] Head: `checklist_team` (progress hari ini per developer) + option di filter.
- [ ] Developer yang `user_id`-nya orang lain → 403.

### Task 1.2 — Bar

- [ ] `RoutineBar.svelte`: header (`Your routine · today`, `4/5`, `🔥 4`), grid 2 kolom item.
- [ ] Item: checkbox, judul, menu `⋯`, state done (strikethrough).
- [ ] Empty state + `Add routine`.
- [ ] Mode read-only: label owner, lock, tanpa kontrol tulis.
- [ ] `RoutineDialog.svelte` untuk tambah/edit judul.
- [ ] `Archived routines` collapsible, read-only.

### Task 1.3 — CRUD

- [ ] `RoutineController@store`: judul wajib ≤255, maks 20 routine aktif, `starts_on` = hari ini, `position` = max+1.
- [ ] `update`: judul saja; `403` kalau bukan miliknya.
- [ ] `reorder`: validasi `position` numerik.
- [ ] `archive`: `archived_on` = hari ini; `404` kalau sudah diarsip.
- [ ] Semua endpoint pakai `Gate::authorize('manage', $routine->user)`.

### Verification Phase 1

```bash
docker exec -u www-data daily-app php artisan test --filter="RoutineTest|CalendarRoutine"
docker exec -u www-data daily-app ./vendor/bin/pint --test
npm run check
```

- [ ] Bar tampil di kalender, tambah/edit/arsip jalan.
- [ ] Arsip menghilangkan routine dari bar hari ini; entry lama tetap ada.
- [ ] Filter ke developer lain sebagai developer → 403.

---

## 7. Phase 2 — Centang, skip, catatan, streak (1 hari)

### Task 2.1 — Endpoint check

- [ ] `PATCH /checklist/{routine}` `{date, status}`.
- [ ] Guard: `manage`, tanggal ∈ {hari ini, kemarin}, routine aktif pada tanggal itu.
- [ ] `done`/`skipped` → `updateOrCreate`; `pending` → `delete`.
- [ ] Anti-duplikat: `unique` + retry sekali saat `23000`.

### Task 2.2 — UI

- [ ] Checkbox → `router.patch` (`preserveScroll`, `preserveState`), optimistic + revert on error.
- [ ] Menu `⋯`: `Skip` / `Mark as pending` / `Edit` / `Archive`.
- [ ] `RoutineNote`: popover textarea, counter, `Save note`, feedback `Saved`.
- [ ] Progres & streak update tanpa reload penuh.

### Task 2.3 — Streak

- [ ] `ComputeChecklistStats`: progres, 7 hari, streak (lookback 90 hari).
- [ ] Streak: hari dihitung kalau semua routine yang aktif hari itu `done`/`skipped`; hari ini yang belum selesai dilewati; hari lampau yang bolong memutus; hari tanpa routine memutus.

### Task 2.4 — Test

- [ ] done dua kali idempoten; pending menghapus baris; skipped dihitung selesai.
- [ ] Tanggal besok / 2 hari lalu → 422.
- [ ] Routine orang lain → 403.
- [ ] Routine terarsip → 422.
- [ ] Catatan: upsert, trim, kosong → hapus, >2000 → 422.

---

## 8. Phase 3 — Riwayat di grid + panel + mobile (1 hari)

### Task 3.1 — Grid

- [ ] `CalendarDay`: baris `▣ bar 4/5` di kaki sel, ungu, `title` + `aria-label`.
- [ ] Tidak render apa pun kalau tanggal itu tanpa routine aktif.
- [ ] Hari dengan 3 chip + rollback tidak overflow (tes 112px).

### Task 3.2 — Panel & agenda

- [ ] `DayDetail`: blok checklist (progress, item, catatan) di atas seksi task.
- [ ] `CalendarAgenda`: baris `▣ 4/5 · "catatan"`, clamp 1 baris.
- [ ] Tanggal lampau read-only; hari ini juga read-only di panel (tulis hanya dari bar).

### Task 3.3 — Test + browser

- [ ] `CalendarRoutineTest`: data per tanggal, user filter, head vs developer, streak sama dengan bar.
- [ ] Browser: indikator muncul di tanggal yang benar, tidak ada console error.

---

## 9. Phase 4 — Head mode, board chip, polish (½ hari)

### Task 4.1 — Head

- [ ] Filter user menampilkan `Rina 4/5` untuk head.
- [ ] Pilih developer → bar read-only + grid/detail milik developer.
- [ ] Developer yang mencoba `?user_id=` orang lain → 403.

### Task 4.2 — Board chip

- [ ] `TaskController@index`: kirim `routine` ringkas (done/total) untuk hari ini.
- [ ] `Index.svelte`: chip `Rutinitas 3/5` → link `tasks.calendar`; tidak render kalau `total = 0`.

### Task 4.3 — Polish + docs

- [ ] Mobile: bar di atas agenda, target tap ≥44px, tidak overflow.
- [ ] Dark mode & kontras.
- [ ] Screenshot ke `docs/mockups/screenshots/`.
- [ ] Update README (kalau ada daftar fitur).
- [ ] `npm run check`, `pint`, full test.

---

## 10. Test Plan

| File | Isi |
|---|---|
| `UserRoleTest` | default developer; cast enum; `isHead()`; bukan fillable; query builder update |
| `RoutineModelTest` | `isActiveOn` 6 kasus; scope konsisten; unique entry; cascade |
| `RoutineTest` | create (validasi, maks 20, posisi); update; archive; reorder; 403 |
| `ChecklistCheckInTest` | done/skipped/pending; idempoten; kemarin boleh; besok & lampau jauh 422; arsip 422; 403 |
| `ChecklistNoteTest` | upsert, trim, kosong → hapus, max 2000, hanya hari ini |
| `ChecklistStatsTest` | progres; 7 hari; streak 8 skenario |
| `ChecklistAccessTest` | developer 403; head read-only; head tidak bisa menulis milik orang lain; `team` hanya head |
| `CalendarRoutineTest` | props per tanggal; filter; indikator; angka sama dengan bar |

**8 skenario streak:**
1. 3 hari beruntun selesai → 3.
2. Hari ini belum selesai, 2 hari sebelumnya selesai → 2.
3. Hari ini selesai → ikut dihitung.
4. Satu `pending` di hari lampau → berhenti.
5. `skipped` dihitung selesai.
6. Routine baru hari ini → tidak memutus streak kemarin.
7. Routine diarsipkan 3 hari lalu → tidak dihitung setelahnya, tetap sebelumnya.
8. Tidak punya routine → 0 (tanpa division by zero).

---

## 11. Out of Scope

- Reminder / notifikasi / Telegram / email.
- Jadwal per hari (Senin–Jumat) atau frekuensi mingguan.
- Catatan per routine (v1 per hari).
- Restore/unarchive routine (butuh `routine_pauses`).
- Head mengedit/mencentang rutinitas developer.
- Role per divisi.
- Komentar head di catatan developer.
- Export CSV/PDF.
- Drag-and-drop urutan routine (v1 tombol naik/turun).

---

## 12. Risiko & mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Streak salah karena routine baru/arsip | Angka tidak masuk akal | `starts_on`/`archived_on` + 8 test |
| Dua klik cepat → entry ganda | Data kotor | Unique + `updateOrCreate` + retry |
| Tab terbuka melewati tengah malam | Check masuk tanggal salah | Client kirim `date`; server hanya terima hari ini/kemarin |
| Kalender terlalu ramai | Sulit dibaca | Bar ringkas + indikator 1 baris; detail di panel |
| Check-in tersembunyi di halaman sekunder | Rutinitas terlupa | Chip di board + bar selalu di atas |
| Optimistic state bentrok dengan reload | UI lompat | `preserveState` + revert on error |

---

## 13. Estimasi

| Phase | Isi | Estimasi |
|---|---|---|
| 0 | Skema, model, role, policy, test | 1 hari |
| 1 | Bar + CRUD routine + filter user | 1,5 hari |
| 2 | Centang, skip, catatan, streak | 1 hari |
| 3 | Riwayat di grid, panel, mobile | 1 hari |
| 4 | Head mode, board chip, polish | ½ hari |
| **Total** | | **±5 hari** |
