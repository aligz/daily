# Audit konsistensi desain — daily

Tanggal: 27 Oktober 2026
Metode: inventaris utility di 65 file `.svelte` (di luar `components/ui/` yang vendored) + ukur computed style di browser pada `/tasks`, `/tasks/calendar`, `/guide`.
Cakupan: tipografi, spacing, radius, warna, ukuran komponen, elevasi, tracking/weight.

Angka di bawah diambil dari kode dan dari DOM, bukan perkiraan.

---

## Ringkasan

1. **Tipografi**: 14 ukuran font ad-hoc (8px–22px) dipakai 154×, plus 140× ukuran bernama. Satu halaman saja (`/tasks/calendar`) memakai **9 ukuran berbeda**.
2. **Spacing**: 115 kombinasi utility spacing berbeda; 6 langkah tidak konsisten (`0.5/1/1.5/2/3/4/6`) dipakai campur tanpa aturan peran.
3. **Radius**: bug di token — `--radius-md` = 2px dan `--radius-sm` = 0px, sementara `rounded` (tanpa suffix) = 4px dan `rounded-lg` = 4px. Jadi "md" lebih tajam dari "lg" cuma beda 2px, dan `rounded` = `lg`. Terukur dalam satu halaman: 0px (toggle), 2px (nav, tombol, wrapper kartu), 4px (panel, card), 12px (auth card).
4. **Warna**: 43 warna palette mentah (`green-500`, `sky-700`, `violet-300`, …) dipakai untuk status, padahal token semantik sudah ada dan dipakai 400+×. Brand juga beda hue antar mode: light `#0d9488` (teal) → dark `#00FFD1` (neon cyan).
5. **Ukuran komponen**: tinggi kontrol 22/24/25/28/32/36/40 px (7 nilai), ikon 10/12/14/16/20 px.

Yang **sudah** konsisten: token semantik dipakai rajin (`text-muted` 156×, `border-border` 67×), spacing mayoritas di 2/4/6/8/12/16, dan tidak ada `tailwind.config.js` — semuanya CSS-first (`@theme`), jadi perbaikan skala cukup di satu file.

---

## Keputusan (disetujui 27 Okt 2026)

1. Base body: **13px** untuk kartu/list (`text-dense`), **14px** untuk form/dialog/guide (`text-sm`).
2. Radius: **chip 2px, panel 4px, dialog 8px**.
3. Brand: **hue disamakan** — light `#0d9488`, dark `#2dd4bf`; neon `#00FFD1` hanya untuk aksen (`--ring`, chart).
4. Kolom board (biru/oranye/hijau): **dipertahankan**.

## Status perbaikan

### Batch 0 — fondasi ✅

`resources/css/app.css`:

- Radius eksplisit: `--radius-sm: 2px`, `--radius-md: 4px`, `--radius-lg: 8px` (sebelumnya `md` = 2px dan `sm` = 0px karena `calc`).
- Token tipografi baru: `--text-3xs` (10), `--text-2xs` (11), `--text-dense` (13), plus line-height untuk `xs`/`sm`. Line-height jadi bagian token, tidak lagi per-usage.
- Token warna status: `--success`, `--info`, `--routine`, `--warning` (light + dark) dan mapping `--color-*` sehingga `text-success`, `bg-routine/10`, dst. tersedia.
- Brand dark: `--primary` `#00FFD1` → `#2dd4bf`; `--ring` tetap neon `#00FFD1`.
- Blok override `--font-sans` (JetBrains Mono) yang tidak pernah berefek dihapus.

`scripts/design-lint.mjs` + `npm run lint:design`:

- error: `text-[Npx]`, spacing `2.5/3.5/5`, `rounded-xl`
- warning: `rounded` polos, warna palette mentah
- baseline saat ditambahkan: 198 error / 312 warning di 37 file.

### Batch 1 — tipografi ✅

154 pemakaian ukuran arbitrer + `text-lg` dimigrasi ke token dengan perubahan maksimal 0.5px per elemen:

| Sebelum | Sesudah |
|---|---|
| 8, 9, 10px | `text-3xs` (10) |
| 10.5, 11px | `text-2xs` (11) |
| 12, 12.5px | `text-xs` (12) |
| 13, 13.5px | `text-dense` (13) |
| 14, 14.5px | `text-sm` (14) |
| 15, 16px | `text-base` (16) |
| 18px (`text-lg`) | `text-base` |
| 22px | `text-xl` (20) |

Sekaligus: spacing `2.5/3.5/5` → `2/3/4` (42 tempat) dan `rounded-xl` → `rounded-lg` (2 tempat), supaya `lint:design` bersih dari error.

Hasil terukur (computed style):

| Halaman | Sebelum | Sesudah |
|---|---|---|
| `/tasks` | 8 ukuran | **6** (10, 11, 12, 14, 16, 24) |
| `/tasks/calendar` | 9 ukuran | **7** (10, 11, 12, 13, 14, 16, 24) |
| `/guide` | 11 ukuran | **7** (11, 12, 13, 14, 16, 20, 30) |

Radius terukur: nav 4px, tombol 4px, chip toggle 2px, kartu 4px, dialog 8px — sesuai keputusan.

`lint:design` sekarang: **0 error**, 312 warning (272 palette, 40 `rounded` polos) → target batch 2–3.

### Batch 2 — radius & spacing ✅

- 40 `rounded` polos dipetakan per peran: chip/link/ikon → `rounded-sm` (2px), panel/menu/kolom → `rounded-md` (4px), bar progres → `rounded-full`.
- Kolom board disamakan dengan board feature-requests: `p-4` → `p-3`, header `px-4 py-2` → `px-3 py-2`, `mb-4` → `mb-3`.

### Batch 3 — warna ✅

- 90 pemakaian palette di komponen status → token: `green` → `success`, `sky` → `info`, `violet` → `routine`, `amber` → `warning`, `red` → `destructive`, termasuk `dark:` ganda yang jadi redundan.
- Priority badge (`low/medium/high/urgent`) → `muted/info/warning/destructive`.
- Status task di halaman report (`backlog/todo/today/done`) → `muted/warning/info/success`.
- `AppearanceTabs`, avatar fallback, "Saved." dan error text ikut token.
- Warna identitas dikumpulkan di `resources/js/lib/board-colors.ts` (kolom board & badge status feature request) dan `lib/users.ts` (avatar per user) — keduanya dikecualikan di `lint:design` dengan alasan tertulis.
- Helper warna duplikat di kedua halaman feature-requests dihapus, sekarang memakai `lib/users.ts` + `lib/initials.ts`.

`lint:design`: **0 error, 0 warning**.

### Batch 4 — ukuran komponen ✅

- Tinggi kontrol diseragamkan ke **24 / 32 / 36 / 40**:
  - `RoutineBar` footer & dialog note `h-7` (28) → `h-8` (32)
  - `TaskViewToggle`, `Guide` ID/EN toggle, tombol "Today" di kalender → `h-6` (24)
  - filter rentang tanggal di `/reports` `py-2` (37) → `h-8` (32)
- Ikon sudah di 12/14/16/20 — tidak perlu diubah.

Hasil ukur (computed, `button` + `a[role=button]`):

| Halaman | Sebelum | Sesudah |
|---|---|---|
| `/tasks` | 32, 40 | 32, 40 |
| `/tasks/calendar` | 23, 24, 28, 32, 40 | **24, 32, 40** |
| `/guide` | 21, 40 | **24, 40** |
| `/reports` | 37, 40 | **32, 40** |
| `/feature-requests`, `/divisions`, `/settings` | 36, 40 | 36, 40 |

### Housekeeping ✅

Dihapus (tidak dipakai rute mana pun, dikonfirmasi lewat `svelte-check` + grep): `AppHeader`, `AppHeaderLayout`, `AppSidebar`, `AppSidebarHeader`, `AppSidebarLayout`, `NavMain`, `NavUser`, `NavFooter`, `PlaceholderPattern`, `AuthSplitLayout`.

Dampak: bundle CSS 107.3 → 104.3 kB, dan allowlist `lint:design` menyusut jadi 2 file (`board-colors.ts`, `users.ts`).

### Permukaan datar (28 Okt)

Shadow dihapus dari semua permukaan konten; elevation hanya untuk overlay:

| Elemen | Sebelum | Sesudah |
|---|---|---|
| `Input` | `shadow-sm` | tanpa shadow (border + focus ring) |
| `Checkbox`, `InputOTP` slot | `shadow-xs` | tanpa shadow |
| `Card` | `shadow-sm` | rata, border saja |
| `Button` (default/secondary/destructive) | `shadow` / `shadow-sm` | tanpa shadow |
| `AppearanceTabs` (pilihan aktif) | `shadow-xs` | `bg-card` saja |
| Kartu task & feature request | `hover:shadow-md` | `hover:border-border` |
| Dialog, sheet, menu, popover, skip link | `shadow-md`/`lg` | tetap (punya fungsi elevasi) |

Diverifikasi lewat computed style: `boxShadow` input/kartu/tombol = `none` (input hanya punya ring fokus saat aktif), di light maupun dark.

### Penjaga otomatis

- `.github/workflows/design.yml` — GitHub Actions menjalankan `node scripts/design-lint.mjs` di setiap push/PR. Tidak butuh `npm ci` karena skripnya cuma membaca file.
- `.githooks/pre-commit` — hook lokal, aktif dengan `git config core.hooksPath .githooks` (sudah dijalankan di clone ini). Lewati dengan `git commit --no-verify`.
- Aturan baru: `shadow-xs|sm|2xl` → warning ("konten rata, shadow hanya untuk overlay").

### Status akhir

| Cek | Hasil |
|---|---|
| `npm run lint:design` | 0 error, 0 warning |
| `svelte-check` | 0 error (13 warning lama di halaman feature-request) |
| Pest | 141 passed, 2 skipped |
| Ukuran font per halaman | 6–7 (dari 8–11) |
| Radius | 2 / 4 / 8 (+ full), tanpa `rounded` polos atau `xl` |
| Tinggi kontrol | 24 / 32 / 36 / 40 |
| Warna palette mentah | 0 di luar `board-colors.ts` & `users.ts` |
| Shadow konten | 0 (hanya overlay) |
| CI/pre-commit | aktif |


---

## 1. Tipografi

### Inventaris

| Ukuran | Pemakaian | Catatan |
|---|---|---|
| 8px | 1 | `feature-requests/Index.svelte:327` (avatar kecil) |
| 9px | 3 | `TaskBadges:89`, `TaskCard:80`, `feature-requests/Index:320` |
| 10px | 10 | eyebrow, badge, label mikro |
| 10.5px | 3 | `UserFilter:53,94`, `Guide:229` |
| 11px | 37 | meta, chip, label kolom |
| 12px | 10 | — |
| 12.5px | 25 | chip kalender, teks board |
| 13px | 20 | list, agenda |
| 13.5px | 21 | body dense |
| 14px | 11 | body |
| 14.5px | 6 | guide body |
| 15px | 5 | guide subhead |
| 16px | 1 | `RoutineBar:185` |
| 22px | 1 | `Guide:124` (h2) |

Ditambah ukuran bernama: `text-sm` 100×, `text-xs` 15×, `text-2xl` 10×, `text-3xl` 6×, `text-xl` 4×, `text-lg` 3×, `text-base` 2×.

Per halaman (computed style):

| Halaman | Ukuran yang muncul |
|---|---|
| `/tasks` | 9, 10, 11, 12, 12.5, 14, 16, 24 |
| `/tasks/calendar` | 10, 11, 12, 12.5, 13, 13.5, 14, 16, 24 |
| `/guide` | 10.5, 11, 12, 12.5, 13, 13.5, 14, 14.5, 15, 22, 30 |

### Masalah

- **Pecahan setengah piksel** (10.5, 12.5, 13.5, 14.5) = 55 pemakaian. Bukan hasil keputusan skala, tapi "kayak pas aja".
- **11px vs 12.5px** mendominasi halaman kalender (50 dan 54 elemen) — dua ukuran ini bersaing untuk peran yang sama (meta kecil), dan 11px di bawah ambang nyaman baca di HP.
- **Line-height ikut acak**: `11px/16.5` (1.5), `12.5px/18.75` (1.5), `14px/17.5` (1.25), `11px/11` (1.0) — karena sebagian pakai `leading-none` untuk badge.
- **Tracking**: 5 nilai untuk label uppercase (`0.08em`, `0.1em`, `0.12em`, `0.14em`, `0.16em`) — 18 pemakaian, tanpa aturan.
- **Weight**: `font-medium` 62×, `font-semibold` 43×, `font-bold` 11× — `bold` dipakai untuk judul halaman, padahal `semibold` juga dipakai untuk judul seksi lain (`DayDetail`, `RoutineBar`).

### Usulan skala (9 langkah, mencakup semua nilai sekarang)

| Token | px | line-height | Peran |
|---|---|---|---|
| `2xs` | 10 | 1.4 | badge mikro, inisial avatar, label teknis |
| `xs` | 11 | 1.45 | eyebrow, meta, label kolom, caption |
| `sm` | 12 | 1.45 | chip kalender, badge status, teks sekunder |
| `base` | 13 | 1.5 | body padat (list, agenda, kartu) |
| `md` | 14 | 1.5 | body default (form, dialog, guide) |
| `lg` | 16 | 1.4 | subhead, nama rutinitas, judul panel |
| `xl` | 20 | 1.3 | judul seksi besar |
| `2xl` | 24 | 1.25 | judul halaman |
| `3xl` | 30 | 1.2 | hero (hanya guide) |

### Pemetaan (perubahan maksimal 0.5px per elemen)

| Sekarang | Jumlah | Jadi |
|---|---|---|
| 8px, 9px, 10px | 14 | **10px** |
| 10.5px, 11px | 40 | **11px** |
| 12px, 12.5px | 35 | **12px** |
| 13px, 13.5px | 41 | **13px** |
| 14px, 14.5px | 17 | **14px** |
| 15px, 16px | 6 | **16px** |
| 22px | 1 | **20px** |
| 24px, 30px | 16 | tetap |

Aturan turunan:

- `bold` hanya untuk `2xl`/`3xl`; seksi pakai `semibold`; tombol/nav pakai `medium`.
- Uppercase label: satu nilai `tracking-[0.08em]`; eyebrow halaman `tracking-[0.12em]`. Hapus 0.1/0.14/0.16.
- Badge jangan pakai `leading-none`; pakai line-height skala (1.4) supaya tidak menempel.

---

## 2. Spacing

### Inventaris

115 kombinasi utility berbeda (`p`, `px`, `py`, `m*`, `gap*`, `space-*`). Yang dominan:

| Utility | Jumlah |
|---|---|
| `gap-2` (8px) | 85 |
| `px-3` (12px) | 37 |
| `py-2` (8px) | 36 |
| `gap-4` (16px) | 27 |
| `mt-1` (4px) | 25 |
| `gap-1.5` (6px) | 24 |
| `px-2` | 23 |
| `gap-1` | 22 |
| `py-1.5` | 22 |
| `px-4` | 17 |
| `gap-6` (24px) | 13 |

Langkah yang dipakai: **0.5, 1, 1.5, 2, 3, 4, 6** plus outlier **2.5 (10px)** 8×, **3.5 (14px)** 10×, **5 (20px)**.

Padding kartu yang terukur di DOM: `12px 36px 4px 12px`, `4px 12px 12px`, `0px` — tiga pola berbeda untuk container yang sederajat.

### Masalah

- Tidak ada aturan "peran → nilai": padding kartu kadang `p-3` (12), kadang `p-2` (8), kadang `px-3 pb-2`.
- `px-3.5`/`py-2.5` dipakai untuk hal yang sama dengan `px-3`/`py-2`.
- Jarak antar-section campur: `gap-2` (8), `gap-3` (12), `gap-4` (16), `gap-6` (24) — tanpa hierarki.

### Usulan

Skala yang **diizinkan**: `0.5, 1, 1.5, 2, 3, 4, 6, 8, 10` (2–40px). Dilarang: `2.5, 3.5, 5`.

Peran tetap:

| Peran | Token | px |
|---|---|---|
| padding dalam chip/badge | `px-1.5 py-0.5` | 6/2 |
| padding kartu dense (task card, list) | `p-3` | 12 |
| padding panel/section | `p-3` / `p-4` | 12–16 |
| padding halaman | `p-4` (mobile) / `p-6` (desktop) | 16/24 |
| jarak antar elemen dalam grup | `gap-1.5` / `gap-2` | 6–8 |
| jarak antar grup | `gap-4` | 16 |
| jarak antar section | `gap-6` | 24 |

Migrasi mekanis: `px-3.5` → `px-3`, `py-2.5` → `py-2`, `p-3.5` → `p-3`, `h-7` (28px, tinggi kontrol) → lihat bagian 5.

---

## 3. Radius

### Akar masalah

```css
--radius: 0.25rem;                     /* 4px */
--radius-lg: var(--radius);            /* 4px */
--radius-md: calc(var(--radius) - 2px);/* 2px */
--radius-sm: calc(var(--radius) - 4px);/* 0px */
```

`rounded-md` jadi **2px** (nyaris tidak terlihat), `rounded-sm` jadi **0px** (tajam), sedangkan `rounded` polos (bawaan Tailwind) tetap **4px** dan `rounded-lg` juga 4px.

Terukur di `/tasks`: nav link **2px**, tombol **2px**, panel/card **4px**, toggle kartu **0px**, dan kartu task punya **2px di dalam 4px** (wrapper `rounded-md` membungkus card `rounded-lg`). `rounded-xl` (12px) muncul 2× (`AuthCardLayout:28`, `TwoFactorSetupModal:186`).

Pemakaian: `rounded` 50×, `rounded-sm` 36×, `rounded-md` 22×, `rounded-lg` 13×, `rounded-full` 19×, `rounded-xl` 2×.

### Masalah

- "sm" lebih tajam dari "md" hanya beda 2px → tidak terbaca sebagai hierarki.
- `rounded` (50×) dan `rounded-lg` (13×) menghasilkan 4px yang sama → satu di antaranya redundan.
- `xl` (12px) cuma 2× dan muncul di halaman auth yang jarang dibuka.

### Usulan

Tetapkan eksplisit 3 tingkat + full:

```css
--radius-sm: 0px;       /* chip, badge, tombol kecil — mempertahankan look "terminal" */
--radius-md: 0.25rem;   /* default: kartu, tombol, input, popover */
--radius-lg: 0.5rem;    /* dialog, sheet, panel besar */
```

- Hapus `rounded` polos dari kode → pakai `rounded-md` (atau `rounded-sm` kalau memang mau tajam).
- `rounded-xl` → `rounded-lg`.
- Kalau mau look tajam total, alternatifnya `--radius: 0px` dan semua ikut. Ini keputusan gaya, bukan teknis.

---

## 4. Warna

### Kondisi sekarang

Token semantik dipakai konsisten: `text-muted-foreground` 156×, `border-border` 67×, `text-destructive` 40×, `bg-accent` 38×, `ring-ring` 24×, `bg-card` 23×.

Tapi status memakai palette mentah: **43 kelas warna palette** (23 hue+shade berbeda), dengan pasangan light/dark manual:

| Status | Light | Dark |
|---|---|---|
| done | `bg-green-500/10`, `text-green-700` | `dark:text-green-300` |
| today | `bg-sky-500/10`, `text-sky-700` | `dark:text-sky-300` |
| routine | `bg-violet-500/10`, `text-violet-600` | `dark:text-violet-300` |
| delayed/molor | `bg-destructive/10`, `text-destructive` | — |
| stale | `bg-amber-500/10`, `text-amber-700` | `dark:text-amber-300` |
| board kolom | `bg-blue-100/50`, `bg-orange-100/50` | `dark:bg-blue-900/20` |

### Masalah

- **Brand beda hue antar mode**: light `#0d9488` (teal-600, muted) vs dark `#00FFD1` (neon cyan). Bukan variasi lightness dari hue yang sama — jadi transisi light↔dark terasa seperti dua produk.
- **Pasangan manual** rawan lupa: satu tempat pakai `dark:text-green-300`, tempat lain `dark:text-green-200` (keduanya ada di kode).
- **Warna palette mentah** bercampur dengan token → warna tidak bisa diubah dari satu tempat, dan kontras tidak terjamin (mis. `text-green-600` di atas `bg-green-500/10`).
- `--chart-*` sudah punya hue sendiri (teal/green/purple/yellow/orange) tapi tidak dipakai di UI.

### Usulan

Tambah token status (light + dark) di `app.css`, lalu ganti palette mentah:

```css
:root {
    --success: #15803d;        /* done */
    --info: #0369a1;           /* today */
    --routine: #7c3aed;        /* rutinitas */
    --warning: #b45309;        /* stale */
    --danger: var(--destructive);
}
.dark {
    --success: #4ade80;
    --info: #7dd3fc;
    --routine: #c4b5fd;
    --warning: #fcd34d;
}
```

Pemakaian jadi `text-success`, `bg-success/10`, `border-success/40` — otomatis benar di dua mode, tanpa `dark:`.

Keputusan yang perlu diambil:

1. **Brand dark**: tetap neon `#00FFD1` (dan ubah light jadi `#0d9488` → `#14b8a6` biar sekeluarga), atau turunkan dark ke `#2dd4bf` (teal-400) supaya dua mode satu hue.
2. **Kolom board**: sekarang biru (today) / oranye (todo) / hijau (done) di header kolom — mau dipertahankan sebagai identitas, atau ikut token status?

---

## 5. Ukuran komponen

Terukur di DOM:

| Kontrol | Tinggi | Sumber |
|---|---|---|
| chip/tombol reorder | 20–24 | `TaskCardMenu` 24, `CalendarMonth` 24, `Guide` toggle 22 |
| tombol kecil | 25–28 | `TaskViewToggle` 25, `RoutineBar` 28 (`h-7`) |
| tombol `sm` | 32 | `Button size="sm"` (h-8) |
| tombol default | 36 | `Button` (h-9) |
| tombol besar / input | 40 | `h-10` |
| header | 64 | `h-16` |

Ikon: `h-2.5` (10), `h-3` (12), `h-3.5` (14), `h-4` (16), `h-5` (20) — 5 ukuran, dipakai campur untuk ikon yang sederajat (mis. chevron 14 vs more-horizontal 16).

### Usulan

Tinggi kontrol: **24 / 32 / 36 / 40** (xs / sm / md / lg). `h-7` → 32 atau 24; `h-6` tetap 24; toggle 22 → 24.

Ikon: **12 / 14 / 16 / 20** (`h-3`/`h-3.5`/`h-4`/`h-5`). `h-2.5` → 12. Aturan: ikon di dalam tombol 32px pakai 14–16; ikon dekoratif kecil 12.

---

## 6. Elevasi, border, transisi

- `shadow` 7×, `shadow-md` 4×, `shadow-lg` 2×, `shadow-sm` 1×, `shadow-xs` 1×, `shadow-none` 2× — 6 level untuk 3 peran (kartu diam, popover, dialog).
- Border: `border` 1px mayoritas; ada `border-2`/`border-l-2` untuk aksen status.
- Transisi: `transition-colors` dominan (bagus), `transition-shadow` 2×, `transition-all` di komponen lama (di luar scope UI baru).

Usulan: 3 level — kartu (`shadow-none` + border), popover (`shadow-md`), dialog (`shadow-lg`). `shadow-sm`/`xs` dihapus.

---

## 7. Outlier prioritas

Tipografi (yang paling terlihat):

- `components/tasks/TaskBadges.svelte:89` — 9px, badge status utama di kartu.
- `pages/feature-requests/Index.svelte:327` — 8px (inisial avatar).
- `pages/feature-requests/Index.svelte:320` — 9px untuk badge "Overdue".
- `components/tasks/TaskCard.svelte:80` (9px), `:88` (10px, referensi mono).
- `components/tasks/TaskHeader.svelte:35`, `CalendarDay.svelte:125`, `DayDetail.svelte:161` — eyebrow 10px dengan 3 tracking berbeda.
- `pages/Guide.svelte:124` (22px), `:192` (15px), `:245` dst (14.5px) — guide satu-satunya pemakai ukuran ini.

Spacing:

- `components/ui` dikecualikan, tapi di app: `RoutineBar.svelte` (`px-2`, `px-1.5`, `py-0.5` campur), `DayDetail.svelte` (11 radius + padding campur), `CalendarMonth.svelte` (`px-3 py-2` header vs `px-2 py-1` sel).

Radius:

- `resources/js/layouts/auth/AuthCardLayout.svelte:28` — `rounded-xl` (12px), satu-satunya kartu yang beda sendiri.
- `components/AppLogo.svelte:5` — `rounded-md` (2px) untuk logo; terlihat seperti kotak tajam.

Warna:

- `pages/feature-requests/Index.svelte` — badge priority pakai `getPriorityBadgeColor()` dan `getUserColor()`; kemungkinan sumber 10+ hue. Fungsi warna ini kandidat pertama dipetakan ke token.
- `pages/tasks/Index.svelte:462-465` — header kolom pakai `bg-blue-100/50`, `bg-orange-100/50`, `text-blue-800`, plus pasangan `dark:` manual.

---

## 8. Rencana migrasi

**Batch 0 — fondasi (tanpa ubah tampilan)**

1. Tambah token di `resources/css/app.css`: `--text-2xs…--text-3xl` (dengan line-height), `--radius-sm/md/lg` eksplisit, token warna status.
2. Perbaiki `--radius-md` yang sekarang 2px.
3. Tambah `scripts/design-lint.mjs`: gagal kalau ada `text-[Npx]`, `px-2.5/3.5/5`, `rounded-xl`, atau warna palette mentah di `components/` & `pages/` (allowlist untuk `ui/`). Ini yang menjaga konsistensi setelah dirapikan.

**Batch 1 — tipografi** (dampak visual terbesar, risiko rendah): ganti 154 pemakaian arbitrary ke token skala, tambah 3 aturan tracking/weight.

**Batch 2 — radius + spacing**: `rounded` → `rounded-md`, `rounded-xl` → `rounded-lg`, `px-3.5` → `px-3`, `py-2.5` → `py-2`, seragamkan padding kartu/panel.

**Batch 3 — warna**: token status, ganti palette mentah, putuskan brand dark.

**Batch 4 — ukuran komponen**: tinggi kontrol 24/32/36/40, ikon 12/14/16/20.

Verifikasi tiap batch: jalankan `design-lint`, `svelte-check`, screenshot `/tasks`, `/tasks/calendar`, `/guide` light + dark, lalu bandingkan angka inventaris (target: 0 ukuran arbitrary, ≤3 radius, 0 warna palette mentah di app code).

---

## 9. Keputusan yang perlu kamu ambil

1. **Ukuran base**: 13px (padat, cocok board) atau 14px (lebih nyaman baca, tapi kartu jadi lebih tinggi)?
2. **Radius**: tetap ada yang tajam (0px untuk chip, 4px untuk panel) atau semua 4px?
3. **Brand dark**: neon `#00FFD1` dipertahankan (dan light dinaikkan ke `#14b8a6`), atau dua mode disamakan ke hue teal?
4. **Kolom board**: biru/oranye/hijau dipertahankan, atau ikut token status?
