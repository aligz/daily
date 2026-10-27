# Review UI — Web Interface Guidelines

Tanggal: 27 Oktober 2026
Sumber aturan: `vercel-labs/web-interface-guidelines` (`command.md`, diambil ulang saat review)
Cakupan: seluruh `resources/js` (pages, components, layouts) + `resources/css/app.css` + `resources/views/app.blade.php`
Metode: baca kode baris-per-baris, lalu verifikasi klaim berisiko di browser (Chrome/CDP, sesi login asli)

Keterangan tingkat:
- **P0** — menghalangi (a11y/fungsional), user tidak bisa pakai / screen reader tidak dapat info
- **P1** — serius (UX/state/feedback)
- **P2** — polish (tipografi, copy, konsistensi)

---

## Status perbaikan — batch 1 (dikerjakan 27 Okt 2026)

Sudah diperbaiki:

| Temuan | Perbaikan |
|---|---|
| `DialogContent:27` tanpa trap/Escape/label | aksi `overlay()` baru (`lib/overlay.ts`): fokus awal, Tab/shift-Tab terkurung, Escape, scroll lock, fokus balik ke trigger; `aria-labelledby`/`aria-describedby` otomatis dari `DialogTitle`/`DialogDescription` (id digenerate `Dialog.svelte`) |
| `DialogContent:17` backdrop jadi stop Tab | `tabindex="-1"`, tidak lagi di urutan Tab |
| `SheetContent:59` tanpa semantik dialog | `role="dialog"` + `aria-modal` + `aria-label` + `overlay()` + `overscroll-contain`; transisi `fly` dimatikan saat `prefers-reduced-motion` |
| `TaskCard:47` nested interactive | kartu jadi `<article>` + `.role=button` tunggal tanpa tombol di dalamnya; menu aksi dipindah keluar (overlay terpisah) |
| `TaskCard:41` Space tidak ditangani | `Enter` dan `Space` dua-duanya membuka dialog |
| `Index:454` pindah kolom hanya drag | menu per kartu (`TaskCardMenu.svelte`): "Move to Backlog/Todo/Today/Done" + "Move up/down" + "Edit details", lengkap dengan navigasi panah, Home/End, Escape, fokus masuk/keluar |
| `TaskCard:95` tombol hover-only | tombol menu selalu terlihat (opacity 70%), tetap jelas saat fokus & di touch |
| `AppHeaderKanbanLayout:82` nav meluber di HP | nav inline `hidden md:flex`; di bawah `md` pakai tombol hamburger + drawer (Sheet) → `scrollWidth` = 390 di viewport 390 |
| `AppContent:22` / `AppShell:21` tanpa skip link | `<main id="main">` + skip link yang muncul saat fokus (`top-[-80px]` → `focus:top-2`) |
| `AppHeaderKanbanLayout:86` tanpa `aria-current` | `aria-current="page"` di nav desktop & drawer |
| `AppHeaderKanbanLayout:101` tombol avatar tanpa label | `aria-label="Open user menu"` |
| `AppHeaderKanbanLayout:125` `<Link><Button>` | "Log in" jadi `<Link>` yang di-style seperti tombol |
| `Checkbox:10` ("Remember me" tidak pernah terkirim) | `<input type="hidden" name value={value ?? 'on'} disabled={!checked}>` → terkirim `remember=on` hanya saat dicentang |

Bukti verifikasi (Chrome/CDP, sesi login asli):

- Dialog: `aria-labelledby` → "Create Task", fokus awal di field Title, Tab dari elemen terakhir balik ke pertama (dan sebaliknya), Escape menutup, `body.overflow` pulih ke `visible`, fokus balik ke tombol pemicu.
- Drawer HP (390px): `role=dialog`, `aria-label="Main navigation"`, fokus awal di link "Tasks", Escape menutup dan fokus balik ke hamburger; `document.scrollWidth` 390 (sebelumnya 535).
- Menu kartu: item disabled dilewati, ArrowDown/End/Escape bekerja, "Move to Today" benar-benar memindahkan kartu (dicek dari kolom DOM), lalu task uji dihapus lagi.
- Remember me: tidak dicentang → `remember` absen dari `FormData`; dicentang → `remember=on`; submit login berhasil ke `/tasks`.
- `svelte-check` 0 error (13 warning lama di halaman feature-requests), ESLint bersih untuk file yang diubah, Pest `141 passed, 2 skipped`.

Belum dikerjakan (menunggu batch berikutnya): `aria-live` untuk `InputError`, `aria-current` di breadcrumb, menu `UserFilter`/`RoutineBar` diganti ke `dropdown-menu`, `confirm()` → dialog, ukuran teks 9–11px, `"..."` → `"…"`, `prefers-reduced-motion` global, `color-scheme`/`theme-color`, key pada `{#each}`, pagination board.


---

## P0 — Blocking

### Modal & overlay

- `components/ui/dialog/DialogContent.svelte:27` — `role="dialog"` + `aria-modal="true"` (baris 28) tapi tanpa focus trap, tanpa Escape, tanpa `aria-labelledby`/`aria-describedby`, body tidak di-lock. Fokus tetap di belakang → SR & keyboard user "hilang". Fix: pindahkan fokus ke panel saat open, trap Tab, Escape → close, `aria-labelledby` dari `DialogTitle`, `overflow: hidden` di `<body>`.
- `components/ui/dialog/DialogContent.svelte:17` — backdrop `<button aria-label="Close">` seukuran layar jadi stop Tab pertama dan tidak punya gaya fokus. Fix: jadikan `<div>` + listener, atau `tabindex="-1"`.
- `components/ui/sheet/SheetContent.svelte:59` — panel drawer tanpa `role="dialog"`, tanpa `aria-modal`, tanpa Escape, tanpa `overscroll-behavior: contain`. (Dipakai `AppHeader.svelte`, yang ternyata dead code — lihat catatan; tetap relevan kalau drawer dipakai lagi.)
- `components/ui/dialog/DialogTitle.svelte:1` — `h2` tidak diberi `id`, jadi tidak bisa dipakai sebagai `aria-labelledby`.

### Task board

- `components/tasks/TaskCard.svelte:47` — `role="button"` pada wrapper yang berisi `<button>` lain (reorder). Nested interactive = HTML tidak valid, SR bingung, Tab dua kali. Fix: wrapper jadi `<article>`, aksi utama jadi `<button>`/`<a>` di dalamnya.
- `components/tasks/TaskCard.svelte:41` — `role="button"` hanya menangani `Enter`, bukan `Space`. Fix: tambah `Space` (dan `preventDefault`) atau pakai elemen native.
- `pages/tasks/Index.svelte:454` — pindah kolom **hanya** lewat HTML5 drag-and-drop (`ondrop`). DnD tidak jalan di touch dan tidak bisa keyboard → di HP task tidak bisa dipindah sama sekali. Fix: menu "Move to…" per kartu + `role="status"` announcement.
- `pages/tasks/Index.svelte:438` — submit tanpa indikator proses (hanya `disabled`), tidak ada `aria-busy`.
- `components/tasks/TaskCard.svelte:95` — tombol reorder `opacity-0 group-hover:opacity-100`; keyboard user bisa Tab ke tombol yang tidak terlihat, dan di touch tidak pernah muncul. Fix: `group-focus-within:opacity-100` + selalu tampil di coarse pointer.

### Mobile header

- `layouts/app/AppHeaderKanbanLayout.svelte:82` — 4 item nav + logo + avatar satu baris tanpa wrap/scroll. Terukur di 390px: nav 411px (kanan x=495), item terakhir (Divisions) di luar layar, dan `document.scrollWidth` = 535 → seluruh halaman ikut scroll horizontal. `overflow-x-hidden` di `components/AppContent.svelte:134` cuma menutup konten, bukan header. Fix: hamburger + sheet, atau `flex-wrap` di nav.
- `resources/js/components/AppContent.svelte:22` — `<main>` tanpa `id`, `components/AppShell.svelte:21` tanpa skip link → tidak ada "Skip to content" (aturan a11y dasar).

### Form & feedback

- `components/InputError.svelte:13` — error validasi `<p>` biasa: tanpa `role="alert"`/`aria-live`, tanpa `id` → tidak diumumkan SR dan input tidak punya `aria-describedby`/`aria-invalid`. Ini systemic: kena semua form auth, settings, tasks, routine, feature-requests.
- `pages/feature-requests/Show.svelte:245` — form status + notes **tidak merender `$form.errors` sama sekali** → validasi gagal secara senyap.
- `pages/settings/TwoFactor.svelte:96` — "Disable 2FA" langsung submit, tanpa konfirmasi/undo (aksi destruktif).
- `pages/auth/TwoFactorChallenge.svelte:88` — input recovery code tanpa `<Label>`/`aria-label`; `:53` input OTP punya `id` tapi tidak ada label yang menunjuk.
- `components/ui/checkbox/Checkbox.svelte:45` — hidden input `name`/`value` **selalu** dirender dan `value` tidak diisi dari `Login.svelte:77`, jadi `remember` terkirim sebagai string kosong walau kotak dicentang: "Remember me" tidak pernah aktif. Fix: `value={value ?? 'on'}` + `disabled={!checked}` supaya hanya terkirim saat dicentang. ✅ sudah diperbaiki.
- `components/checklist/RoutineBar.svelte:271` — menu custom `role="menu"` tanpa navigasi panah, tanpa Escape, fokus tidak pindah ke item; label item `:292` memakai `item.status` (data server) padahal status optimistis sudah berubah → setelah "Skip", menu masih menampilkan "Skip".
- `components/tasks/UserFilter.svelte:66` — dropdown custom tanpa `role="menu"`/`menuitem`, tanpa keyboard, tanpa fokus-return; item terpilih hanya dibedakan warna (`:87`). Padahal `ui/dropdown-menu` sudah ada — pakai itu.
- `components/AppearanceTabs.svelte:28` — 3 tombol tema tanpa `aria-pressed`/`radiogroup` (pilihan hanya warna) dan tanpa `focus-visible` ring (`:27`, karena bukan `ui/Button`).

---

## P1 — Serius

### Navigasi & state

- `layouts/app/AppHeaderKanbanLayout.svelte:86` — nav aktif hanya warna, tanpa `aria-current="page"`. Verifikasi browser di `/tasks`: Tasks/Feature Requests/Reports/Divisions semuanya `aria-current=null`, hanya toggle Board yang `page`.
- `layouts/app/AppHeaderKanbanLayout.svelte:101` — tombol avatar (menu user) tanpa `aria-label`; nama aksesibelnya cuma inisial ("TS").
- `layouts/app/AppHeaderKanbanLayout.svelte:125` — `<Link><Button>Log in</Button></Link>` = nested interactive, 2 tab stop.
- `components/Breadcrumbs.svelte:25` — `BreadcrumbPage` = `<span>` tanpa `aria-current="page"`; `:29` link jatuh ke `href="#"` kalau `href` kosong.
- `pages/tasks/Calendar.svelte:36` — `selectedDate` hanya `$state`, tidak di URL → deep link/refresh/back tidak membawa hari yang dipilih (month & user_id sudah di URL).
- `pages/feature-requests/Index.svelte:50` — search/division/priority di `$state`, tidak di query param → hilang saat refresh/back.
- `pages/report/Index.svelte:106` — filter rentang tanggal berupa `<button onclick={router.get}>` (navigasi via onClick) tanpa `aria-pressed`/`aria-current`.

### Interaksi & affordance

- `components/tasks/CalendarMonth.svelte:139` — hint `← → change month` tidak ada implementasinya (panah justru memindah hari). False affordance.
- `components/tasks/CalendarMonth.svelte:115` — baris nama hari `<div>` terpisah dari grid sel; grid tidak punya `role="grid"`/`<table>` → SR tidak tahu "Senin kolom 3".
- `pages/tasks/Index.svelte:240` — `confirm()` native untuk hapus task (tanpa undo, unstyled); sama di `components/checklist/RoutineBar.svelte:107`.
- `pages/tasks/Index.svelte:322` — error inline ada, tapi tidak diumumkan & tidak terhubung ke input (`aria-describedby`).
- `pages/feature-requests/{Create,Edit,Show}.svelte` — `<a>` membungkus `<Button>` (`Create:53`, `Create:199`, `Edit:69`, `Edit:245`, `Show:133`, `Index:206`) → invalid nesting, 2 tab stop. Pakai `Link` yang *styled* seperti tombol.
- `pages/divisions/Index.svelte:177` & `:184` — tombol ikon (Pencil/Trash2) tanpa `aria-label`; sama `feature-requests/Create.svelte:53`, `Edit.svelte:70`, `Show.svelte:121`.
- `pages/feature-requests/Index.svelte:279` — kartu `role="button"` + navigasi via `router.visit`, hanya `Enter` (tanpa `Space`), dan drag-only untuk ubah status (`:264`).
- `pages/settings/Profile.svelte:57` — input `value={user.name}` tanpa `bind:` → edit bisa "balik sendiri" saat props refresh.
- `pages/settings/Password.svelte:40` — `resetOnError` mengosongkan 3 field password saat validasi gagal → user mengetik ulang semuanya.

### Informasi yang hanya bisa diakses mouse

- `components/tasks/TaskBadges.svelte:90` — detail badge hanya di `title` (non-focusable) → tidak ada di touch/keyboard.
- `components/tasks/CalendarDay.svelte:144` — judul task di-truncate, teks penuh hanya di `title` → di HP tidak terbaca.
- `components/tasks/DayDetail.svelte:200` — sama; `:257` bahkan menampilkan catatan internal **"Counts come from task_histories."** (nama tabel DB bocor ke UI).
- `components/tasks/TaskBadges.svelte:81` — `slice(0, 2)` membuang badge ke-3+ tanpa indikator "lainnya".

### Struktur heading

- `pages/feature-requests/Index.svelte:268` — `h1` → `h3` (lompat, tanpa `h2`).
- `components/tasks/DayDetail.svelte:86` & `:89` — label panel ("Day detail", tanggal) `<div>`, bukan heading; `components/checklist/RoutineBar.svelte:181` & `:185` ("Routine"/"Your routine") juga `<span>`.
- `pages/report/Index.svelte`, `pages/divisions/Index.svelte` — hanya `h1`; judul kartu bukan heading → outline halaman kosong.

---

## P2 — Tipografi, copy, konsistensi

### Teks

- 53 pemakaian ukuran ≤11px di source (37× `11px`, 10× `10px`, 3× `9px`). Terukur di DOM `/tasks`: 7 elemen 9px + 1 elemen 10px. Contoh `TaskBadges.svelte:89` (9px), `TaskCard.svelte:84` (11px), `TaskHeader.svelte:35` (10px). Tidak ada type scale — semua `text-[Npx]` ad-hoc.
- `"..."` → `"…"`: `pages/tasks/Index.svelte:370`, `feature-requests/Index.svelte:223`, `Create.svelte:160` & `:177`, `Edit.svelte:177`, `Show.svelte` (placeholder notes), `RoutineBar.svelte:355`.
- Apostrof lurus di prosa: `pages/tasks/Index.svelte:302` ("you're"), `Login.svelte:95` ("Don't").
- Label simbol saja dibacakan SR: `◇` (`TaskBadges.svelte:67`, `CalendarDay.svelte:147`, `DayDetail.svelte:219`), `▶` (`TaskBadges.svelte:75`), `↩` (`CalendarAgenda.svelte:61`), `🔥` (`RoutineBar.svelte:205`), `▣` (`Index.svelte:277`). Fix: `<span aria-hidden>` + teks/`sr-only`.
- `feature-requests/Index.svelte:253`, `report/Index.svelte:211,216,223,229`, `divisions/Index.svelte:172` — angka tanpa `tabular-nums` (kolom angka bergeser).

### Layout teks

- `components/tasks/CalendarAgenda.svelte:94,108,121,134` — `truncate` pada flex child tanpa `min-w-0` → truncate tidak pernah aktif, judul panjang mendorong jam keluar.
- `pages/feature-requests/Index.svelte:300`, `pages/report/Index.svelte:205` — pola sama (email/deskripsi panjang).
- `components/tasks/TaskCard.svelte:64`, `feature-requests/Show.svelte:126` & `:173` — tanpa `break-words`/`line-clamp`; judul atau string tanpa spasi menembus kartu.
- `pages/Welcome.svelte:51` — `<br />` paksa di prosa, pecah di 335px.

### Copy

- Tombol generik: `divisions/Index.svelte:138` ("Save"/"Update"), `Profile.svelte:98` ("Save"), `TwoFactorChallenge.svelte:68` ("Continue"), `TwoFactor.svelte:65` ("Continue Setup").
- Link tidak deskriptif: `Profile.svelte:84` ("Click here to resend the verification email").
- `pages/tasks/Index.svelte:302` — "Click save when you're done." (instruksi redundant).
- Copy pasif: `Password.svelte:33`, `TwoFactor.svelte:59` & `:87`.
- Placeholder mengulang label tanpa contoh: `Profile.svelte:60` & `:75`, `Password.svelte:52,65,78`, `divisions/Index.svelte:125`, `Create.svelte:105` & `:146`.

---

## Tema & sistem

- `resources/css/app.css` — tidak ada blok `@media (prefers-reduced-motion: reduce)` padahal ada: animasi masuk 750ms bertahap (`pages/Welcome.svelte:46`), `fly` transisi drawer (`SheetContent.svelte:66`), spinner, `tw-animate-css`. Grep: 0 hit di seluruh `resources/`.
- `pages/Welcome.svelte:109` + 13 tempat lain — `transition-all` → `transition-[transform,opacity]`. Juga `components/TwoFactorRecoveryCodes.svelte:73`.
- `resources/views/app.blade.php:6` — tidak ada `<meta name="theme-color">` (light/dark) → chrome browser HP tidak match.
- `resources/views/app.blade.php:2` — `color-scheme` hanya di-set via JS setelah mount (`lib/theme.svelte.ts:40`) → paint pertama pakai scrollbar/control native terang. Fix: `:root { color-scheme: light }` / `.dark { color-scheme: dark }` di CSS.
- `resources/css/app.css:89-95` — blok `--font-sans` berisi stack JetBrains Mono **tidak berefek**: `@theme inline` meng-inline font stack ke utility `font-sans`, jadi `var()` tidak dipakai (verifikasi browser: body = Instrument Sans). JetBrains Mono juga tidak pernah di-load. Hapus atau ubah jadi `font-mono` yang memang dipakai.
- `resources/css/app.css` — tidak ada `touch-action: manipulation` / `-webkit-tap-highlight-color` base rule; tap target kecil: `TaskViewToggle.svelte:17` (terukur 68×25 & 84×25 — lolos minimum 24px tapi jauh di bawah 44px), `TaskCard.svelte:104/116` (20×20), `Guide.svelte:169/180` (≈22px), `RoutineBar.svelte:258` (≈18px).
- `components/tasks/TaskViewToggle.svelte:32` & `:40`, `TaskBadges`/`Calendar`/`RoutineBar` — ikon dekoratif tanpa `aria-hidden="true"` (lucide merender `<svg>` polos).
- `pages/Guide.svelte:133` & `:209` — ikon dekoratif tanpa `aria-hidden`.

---

## Performa & SSR

- `app/Http/Controllers/TaskController.php:22` — `Task::with(['user','history'])->get()` memuat **semua** task + seluruh history ke props board; `feature-requests/Index.svelte:258` dan `report/Index.svelte:193` juga tanpa pagination. Data sekarang kecil (4 task), tapi >50 baris akan berat dan tidak divirtualisasi.
- `components/tasks/DayDetail.svelte:26` — `new Map(...)` tiap render (kecil, aman).
- `config/inertia.php:19` — `ssr.enabled = true` tapi tidak ada proses SSR yang jalan (tidak ada node di container) → saat ini CSR. Kalau SSR dinyalakan, semua `new Date(...).toLocaleDateString` di `feature-requests/{Index,Show}.svelte` dan `lib/date.ts` berisiko hydration mismatch (render server beda TZ).
- `lib/date.ts:22-59` — `'id-ID'` hardcoded di 6 fungsi; `feature-requests/Show.svelte:92` & `pages/tasks/Index.svelte:174,393` menduplikasi format yang sama. Konsolidasikan ke `lib/date.ts` supaya satu tempat kalau nanti UI di-i18n-kan.

---

## Klaim yang saya buang setelah verifikasi

- **"Logout 405"** — tidak benar. Wayfinder `logout()` mengembalikan `{url, method:'post'}`; Inertia `Link` mendeteksi `isUrlMethodPair` (`node_modules/@inertiajs/svelte/dist/components/Link.svelte:8`) sehingga mengirim POST. `GET /logout` memang 405, tapi UI tidak pernah melakukan GET.
- **"Body render JetBrains Mono / font salah"** — tidak benar; computed `font-family` di browser = Instrument Sans. Yang benar: blok override di `app.css:89` dead code.
- **"AppHeader.svelte bermasalah"** — `AppHeader.svelte`, `AppSidebar.svelte`, `AppHeaderLayout`, `AppSidebarLayout` tidak dipakai layout mana pun (dead code). Temuan di sana tidak berdampak ke user; kandidat dihapus.
- **"`toLocaleDateString` membuang jam"** — di V8 tetap menampilkan jam (`27 Okt 2026, 10.35`). Yang layak diperbaiki hanya API-nya (`toLocaleString`) dan duplikasi format.

---

## Yang sudah sesuai guideline (jangan diubah)

- `ui/button/Button.svelte:20` — `focus-visible:ring-2` + `disabled` state konsisten.
- `ui/alert/Alert.svelte` — sudah `role="alert"` (jadi `AlertError` aman).
- `ui/breadcrumb/Breadcrumb.svelte` — `<nav aria-label="breadcrumb">`.
- `ui/avatar/AvatarImage.svelte` — `alt` diteruskan; `AppHeaderKanbanLayout:107` mengirim `alt={user.name}`.
- `components/tasks/TaskViewToggle.svelte:23` — `<nav aria-label>` + `aria-current="page"`.
- `components/tasks/CalendarDay.svelte:106` — `aria-label` sel berisi jumlah today/done/rollback/planned + rutinitas.
- `components/tasks/CalendarDay.svelte:101` — fokus keyboard terlihat (`focus-visible:ring-inset`) dan panah memindah hari.
- `components/checklist/RoutineBar.svelte:199` — `aria-live="polite"` untuk progres rutinitas; `:213` `role="progressbar"` + `aria-valuenow` (kurang `aria-label`).
- Empty state ada dan jelas: `Calendar.svelte:140`, `CalendarAgenda:43`, `divisions:199`, `RoutineBar:337`.
- `pages/Guide.svelte:240` dst — `scroll-mt-24` di semua anchor section; TOC `<nav aria-label>`; toggle bahasa `role="group"` + `aria-pressed`.
- `components/checklist/RoutineBar.svelte:411` — arsip pakai `<details>/<summary>` (native, keyboard-accessible).

---

## Urutan perbaikan yang saya sarankan

1. **Modal** (`DialogContent`) — trap + Escape + `aria-labelledby` + scroll lock. Satu perbaikan, kena semua dialog.
2. **Task move** — menu "Move to…" per kartu (`TaskCard`), plus Space di `role="button"`.
3. **Mobile nav** — hamburger/sheet, atau wrap; sekarang halaman Reports/Divisions tidak terjangkau di HP.
4. **Error & status** — `InputError` jadi `role="alert"` + `aria-describedby`; tambah `role="status"` untuk "Saved."; render `$form.errors` di `feature-requests/Show`.
5. **Label ikon** — `aria-label` di semua tombol ikon; `aria-current` di nav.
6. **Checkbox `name`/`value`** — perbaiki "Remember me" (bug fungsional).
7. **Reduced motion + `color-scheme` + `theme-color`** — tiga baris CSS/meta.
8. **Tipografi** — skala huruf (hentikan 9–11px ad-hoc), `tabular-nums` di tabel/angka, `"…"`.
