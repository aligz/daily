# Public Feature Request Form — Implementation Plan

> **Status:** selesai diimplementasikan 28 Okt 2026. Plan ini menggantikan draft share-link (dibatalkan) dan draft `/form` (dibatalkan).

**Goal:** Form feature request di `/feature-requests/create` bisa dibuka guest (user divisi) tanpa akun. Pengirim memilih sendiri divisinya dan mengisi 9 field. Priority & deadline hanya muncul untuk user yang login; guest tidak melihatnya dan nilainya disimpan null sampai tim mengisi lewat panel Triage. Guest yang membuka `/` langsung mendarat di board dan bisa menekan **New Request**.

**Architecture:** Satu halaman form, dua kondisi (`auth.user`). Tidak ada tabel baru: `feature_requests` dapat 4 kolom teks, dan `user_id` / `priority` / `deadline` jadi nullable. Pembatas akses tetap di level jaringan (IP wifi kantor).

**Tech Stack:** Laravel 12, PHP 8.4, Inertia v2, Svelte 5 (runes), Tailwind v4, Pest 4, Wayfinder.

---

## 1. Keputusan final

| # | Keputusan |
|---|---|
| 1 | **Satu halaman `/feature-requests/create`** untuk guest dan user login, dibedakan lewat `auth.user` |
| 2 | **Divisi dipilih pengirim**; tidak ada link per divisi |
| 3 | **`user_id` nullable**: guest = null, user login = id-nya |
| 4 | **`priority` & `deadline` nullable**, hanya dirender untuk user login |
| 5 | **Triage di halaman detail**: status + priority + deadline + notes |
| 6 | **Guest di `/` → board**; halaman Welcome dihapus |
| 7 | **Tombol New Request tampil untuk semua**, selalu ke `/create` |
| 8 | **Email & phone tetap opsional**; 4 field baru + Description wajib |

**Non-goals:** token/link generator, QR code, expiry, notifikasi email/WA, upload, approval workflow, CAPTCHA.

---

## 2. Data

### Migrasi A — 4 kolom baru

`change_reason`, `proposed_action`, `effect_on_organization`, `effect_of_not_approving` (text, nullable, setelah `description`). Nullable di DB untuk data lama; validasi form tetap wajib.

### Migrasi B — kolom opsional

`user_id`, `deadline`, `priority` jadi nullable. `priority` diubah dari enum ke `VARCHAR(20)`; nilai tetap dijaga validasi `in:low,medium,high,urgent`. FK `user_id` tidak diubah aksinya.

### Invariant

1. Guest submit → `user_id = null`, `priority = null`, `deadline = null`, `status = 'new'`.
2. `priority`/`deadline` yang dikirim guest diabaikan (tidak ada di aturan validasi).
3. User login wajib mengisi priority + deadline saat submit.
4. 5 field teks wajib: Description, Change reason, Proposed action, Effect on organization, Effect of NOT approving.
5. Request tanpa priority/deadline tampil dengan label **Unset** / **No deadline**; detail menampilkan **Not set yet**.
6. Request tanpa `user` ditandai `isPublic = true` dan reporter-nya **Public form**.

---

## 3. Route & controller

```php
// Publik
Route::get('feature-requests/create', [FeatureRequestController::class, 'create'])->name('feature-requests.create');
Route::post('feature-requests', [FeatureRequestController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('feature-requests.store');
```

Route `create` didaftarkan sebelum `feature-requests/{feature_request}`.

`store` menyusun aturan validasi secara kondisional:

- semua field umum wajib (termasuk Description + 4 field baru);
- kalau `$request->user()` ada, tambah `priority` (required, in) dan `deadline` (required, date, after_or_equal:today);
- `user_id = $request->user()?->id`; `priority`/`deadline` di-`??= null`;
- redirect ke halaman detail request yang baru dibuat.

`index`/`show` memakai eager load `user:id,name` + `division:id,name` supaya email user tidak ikut terkirim ke guest. `show` mengirim `isPublic`.

`update` menambah aturan untuk 4 field baru (`nullable|string`), `priority` (`sometimes|nullable|in:...`), dan `deadline` (`sometimes|nullable|date` — `after_or_equal` dihapus supaya request overdue tetap bisa diubah statusnya).

`/` → auth: `tasks.index`; guest: `feature-requests.index`.

---

## 4. UX

### 4a. Form `/create`

`AppLayout`, container `max-w-2xl`, `Card`.

| Section | Field |
|---|---|
| — | Division \*, PIC Name \*, PIC Email, PIC Phone |
| Request | Title \*, Description \* |
| Change | Change reason \*, Proposed action \* |
| Impact | Effect on the organization \*, Effect of NOT approving this change \* |
| Triage (login only) | Priority \*, Deadline \* |
| Guest footer | "Priority and deadline are set by the team after review." |

- `Textarea` component baru (`resources/js/components/ui/textarea`) dipakai untuk 5 field panjang.
- Validasi inline per field; tombol `Submit Request` disable saat processing.
- Guest dan user login melihat field yang sama, kecuali blok Triage.
- Submit sukses → halaman detail request (guest melihat versi read-only).

### 4b. Board

- Tombol **New Request** tampil untuk semua, href `/feature-requests/create`.
- Kartu null-safe:
  - priority null → badge **Unset**;
  - deadline null → **No deadline** + ikon jam;
  - `user` null → ikon globe + **Public form**;
  - `isOverdue` mengembalikan false untuk deadline null.
- Guest tetap tanpa drag, Edit, Delete, atau Update Status.

### 4c. Detail

- Kartu **Change Request** (Change reason, Proposed action, Effect on the organization, Effect of NOT approving) hanya dirender kalau minimal satu terisi.
- Priority null → **Not set**; deadline null → **Not set yet**.
- Header & Request Info: **Submitted via public form** kalau `user` null.
- Panel kanan **Triage** (login only): Status, Priority (opsi "Not set"), Deadline, Notes → `Save triage`.

### 4d. Edit

- 4 textarea baru (opsional), `Textarea` component.
- Priority punya opsi "Not set"; deadline tidak wajib.
- Nilai null tidak lagi bikin crash (`deadline ? ... : ''`).
- `useForm` dibungkus `untrack()` supaya `svelte-check` bersih (0 warning).

### 4e. Landing

- Guest `/` → board; `Welcome.svelte` dihapus; import `Fortify\Features` dihapus.
- Guide EN/ID diperbarui: siapa pun di jaringan kantor bisa submit lewat New Request; priority/deadline diisi setelahnya.

---

## 5. Keamanan & edge case

| Kasus | Perilaku |
|---|---|
| Guest submit | Boleh, `user_id` null, CSRF tetap berlaku, `throttle:30,1` |
| Guest menyelipkan priority/deadline | Diabaikan; tersimpan null |
| Akses dari luar wifi kantor | Ditolak di proxy (di luar scope kode) |
| Request lama tanpa 4 field | Tetap render; kartu Change Request tidak muncul |
| Edit request overdue | Bisa disimpan (validasi tanggal dilonggarkan di update) |
| Guest membuka detail | Read-only; tidak ada panel Triage |
| User internal dihapus | Request ikut cascade (perilaku lama) |

---

## 6. Tests

`tests/Feature/PublicFeatureRequestFormTest.php` (14 test)
- guest buka form; guest submit lengkap; user login tercatat + priority/deadline; nilai triage dari guest diabaikan; redirect ke detail; 8 field wajib (dataset); divisi harus ada.

`tests/Feature/FeatureRequestBoardTest.php` (6 test)
- guest baca board & detail tanpa email; request tanpa user ditandai public; guest tidak bisa update/delete; triage mengisi 4 nilai; update parsial tetap jalan.

`tests/Feature/ExampleTest.php`
- guest `/` → board; login `/` → tasks.

---

## 7. Verifikasi

- `php artisan test` → **163 passed, 2 skipped**.
- `npm run check` → **0 error, 0 warning**.
- `node scripts/design-lint.mjs` → **0/0**.
- `npm run build` → sukses.
- Browser (guest): `/` → board; New Request → form; submit → detail "Submitted via public form"; board menampilkan Unset / No deadline / Public form.
- Browser (login): form menampilkan Priority + Deadline; panel Triage menyimpan priority high + deadline; board berubah dari Unset → High.
- 390px: `scrollWidth` 390, card 358, textarea 308, tanpa overflow.
