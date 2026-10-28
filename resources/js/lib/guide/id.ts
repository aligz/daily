import type { GuideCopy } from './types';

export const id: GuideCopy = {
    languageLabel: 'Bahasa panduan',
    tocLabel: 'Di halaman ini',
    eyebrow: 'Panduan',
    title: 'Cara pakai Daily',
    intro: 'Tiga tempat, satu kebiasaan: rencanakan hari, kerjakan, lalu lihat ke belakang. Halaman ini menjelaskan tiap layar dengan bahasa sehari-hari. Baca sekali, lalu balik lagi kalau ada yang terasa asing.',
    openBoard: 'Buka board',
    openCalendar: 'Buka kalender',
    summary: [
        {
            title: 'Board',
            body: 'Tempat kerja menunggu dan bergerak. Kartu digeser di sini.',
        },
        {
            title: 'Kalender',
            body: 'Apa yang benar-benar terjadi tiap hari. Terisi sendiri.',
        },
        {
            title: 'Rutinitas',
            body: 'Beberapa hal yang kamu lakukan tiap hari. Sekali klik untuk centang.',
        },
    ],
    toc: [
        { id: 'board', title: 'Board' },
        { id: 'cards', title: 'Membaca kartu' },
        { id: 'calendar', title: 'Kalender' },
        { id: 'routine', title: 'Rutinitas kamu' },
        { id: 'team', title: 'Kerja bareng tim' },
        { id: 'habits', title: 'Kebiasaan yang membantu' },
        { id: 'questions', title: 'Jawaban cepat' },
    ],
    board: {
        title: 'Board',
        intro: 'Board adalah tempat kerja bergerak. Tiap kolom adalah satu tahap; kartu berjalan dari kiri ke kanan makin dekat ke selesai. Satu kartu milik satu orang, dan hanya orang itu yang bisa memindahkannya.',
        columns: [
            {
                name: 'Planning',
                what: 'Hal yang layak dikerjakan, tapi belum dijanjikan.',
                how: 'Simpan ide di sini tanpa harus berkomitmen.',
            },
            {
                name: 'Todo',
                what: 'Kerja yang sudah disepakati, menunggu giliran.',
                how: 'Ini antreanmu. Ambil dari sini kalau Today kosong.',
            },
            {
                name: 'Today',
                what: 'Yang sedang kamu kerjakan sekarang.',
                how: 'Cukup satu sampai tiga kartu yang realistis selesai hari ini.',
            },
            {
                name: 'Done',
                what: 'Kerja selesai, dikelompokkan per tanggal selesai.',
                how: 'Geser kartu ke sini kalau benar-benar sudah beres.',
            },
        ],
        movingTitle: 'Memindahkan kerja',
        moving: [
            {
                lead: 'Geser',
                body: 'kartu ke kolom lain. Perubahan langsung tersimpan begitu kartunya dilepas.',
            },
            {
                lead: 'Urutan',
                body: 'Planning dan Todo selalu diurutkan dari yang terbaru, jadi kartu yang baru kamu tambahkan langsung ada di paling atas.',
            },
            {
                lead: 'Klik',
                body: 'kartu untuk membukanya, mengubah detail, atau menghapusnya. Kartu orang lain hanya bisa dibaca.',
            },
        ],
        addingTitle: 'Menambah task',
        addingIntro:
            'Pakai tombol New Task di kanan atas. Hanya judul yang wajib diisi.',
        fields: [
            {
                label: 'Title',
                text: 'hasil akhirnya, bukan kegiatannya. “Rilis perbaikan login”, bukan “Lihat login”.',
            },
            {
                label: 'Reference',
                text: 'nomor tiket kalau ada, misalnya JIRA-123. Muncul di kartu dan di kalender.',
            },
            {
                label: 'Planned for',
                text: 'tanggal mulai yang kamu rencanakan, opsional. Ini tidak memindahkan kartu ke Today, dan tanggal yang sudah lewat tidak bisa dipilih.',
            },
            {
                label: 'Description',
                text: 'detail apa pun yang nanti kamu sendiri butuhkan.',
            },
        ],
        resetTitle: 'Tiap tengah malam (WIB)',
        resetBody:
            'App memindahkan semua yang masih tersisa di Today kembali ke Todo. Supaya Today tetap bermakna, dan kalender mencatat perpindahannya jadi kamu bisa lihat apa yang tertunda.',
    },
    cards: {
        title: 'Membaca kartu',
        intro: 'Chip kecil menjelaskan kenapa sebuah kartu perlu dilihat. Maksimal dua chip per kartu. Arahkan kursor ke chip untuk angka detailnya.',
        legend: [
            {
                chip: '▶ Focus today',
                meaning: 'Kartu sedang ada di kolom Today.',
            },
            {
                chip: '◇ Plan 12 Nov',
                meaning:
                    'Tanggal kamu berencana mulai. Ikut tampil di kalender.',
            },
            {
                chip: 'Stale 21d',
                meaning:
                    'Tidak ada yang menyentuhnya sejak lama (default 14 hari). Pecah, jadwalkan, atau buang.',
            },
            {
                chip: '4× today · 3↩',
                meaning:
                    'Kartu ini bolak-balik: sudah tiga kali atau lebih masuk Today, atau dua kali atau lebih balik ke Todo. Tandanya mungkin terlalu besar.',
            },
            {
                chip: '✓ Done 26 Oct',
                meaning: 'Kapan task-nya selesai.',
            },
        ],
        footnote:
            '“Stale” menghitung hari tanpa perubahan. “Delayed” menghitung keluar-masuk Today dan balik ke Todo — sekarang 3 kali masuk atau 2 kali balik.',
    },
    calendar: {
        title: 'Kalender',
        intro: 'Kalender ditulis oleh board, jadi kamu tidak perlu mengisi dua kali. Geser kartu dan centang rutinitas; harinya terisi sendiri. Pindah ke kalender lewat toggle Board / Calendar di kanan atas halaman Tasks.',
        monthTitle: 'Membaca satu bulan',
        month: [
            'Minggu dimulai hari Senin. Hari ini disorot, akhir pekan diberi warna beda.',
            'Satu hari menampilkan maksimal tiga entri: garis biru berarti kartu masuk Today, hijau berarti selesai, ◇ putus-putus berarti rencana. Angka kecil di kanan atas adalah jumlah entri hari itu.',
            'Garis merah di tepi atas plus “↩ n rolled back” berarti ada kerja yang balik ke Todo hari itu.',
            'Garis ungu di bawah adalah progres rutinitas hari itu.',
        ],
        dayTitle: 'Membuka satu hari',
        day: 'Klik satu hari untuk membuka Day detail di kanan. Isinya dikelompokkan jadi Today entries, Completed, Planned, dan Rolled back, lengkap dengan nama, inisial, nomor referensi, dan jam. Task yang sudah dihapus tetap muncul, dicoret dan ditandai “(deleted)”. Kalau hari itu ada aktivitas rutinitas, bloknya muncul di atas beserta catatan harinya.',
        boxes: [
            {
                title: 'Empat angka di atas',
                body: 'Completed this month, Today entries, Rolled back, dan Most delayed — cek kesehatan bulan dalam sekali lihat, bukan papan skor.',
            },
            {
                title: 'Berpindah bulan',
                body: 'Pakai panah untuk bulan sebelumnya atau berikutnya, dan Today untuk kembali. Kalau satu hari sedang dipilih, tombol panah di keyboard bisa menyusuri grid.',
            },
        ],
        footer: 'Di HP, grid diganti daftar Agenda berisi hari-hari yang sama, urut dari awal bulan. Tidak ada yang bisa diedit di halaman ini — ini catatan. Kalau ada yang terasa salah, perbaiki kartunya di board.',
    },
    routine: {
        title: 'Rutinitas kamu',
        intro: 'Rutinitas adalah daftar pendek hal yang kamu lakukan tiap hari: standup, review pull request, cek alert, tulis update. Daftarnya sama setiap hari, sekali klik untuk centang. Letaknya di bar ungu di atas halaman kalender, dan selalu menampilkan hari ini — bulan apa pun yang sedang kamu lihat.',
        blocks: [
            {
                title: 'Centang dan batal centang',
                body: 'Sekali klik berarti selesai. Klik kotak yang sama lagi, kembali jadi belum.',
            },
            {
                title: 'Skip kalau hari itu tidak berlaku',
                body: 'Buka ⋯ lalu pilih Skip — untuk cuti, libur, atau hari kamu terhalang. Tetap dihitung beres, diberi tanda “skipped”, dan tidak memutus streak.',
            },
            {
                title: 'Satu catatan per hari',
                body: 'Pakai Note untuk hal yang menghalangi hari itu. Satu baris per hari, tampil di bar dan di tanggal itu di kalender — jadi “nunggu staging” tetap kelihatan berbulan-bulan kemudian.',
            },
            {
                title: 'Tambah, ganti nama, urutkan, arsipkan',
                body: 'Add routine ada di bawah bar dan mulai dihitung hari ini. ⋯ bisa dipakai untuk mengubah nama, memindah urutan, atau mengarsipkan. Arsip menyembunyikannya mulai hari ini dan riwayatnya tetap ada; belum ada tombol batal arsip di versi ini.',
            },
        ],
        footnotes: [
            {
                lead: '🔥 streak',
                body: 'menghitung hari berturut-turut saat semua rutinitas yang aktif hari itu sudah dicentang atau di-skip. Hari ini tidak memutus streak selama harinya masih berjalan.',
            },
            {
                lead: 'n/m',
                body: 'artinya sudah beres dari total. Rutinitas yang belum disentuh dihitung belum, makanya hari baru mulai dari 0.',
            },
            {
                body: 'Bar ini hanya untuk hari ini; hari-hari lalu adalah catatan. Dan rutinitas tidak pernah muncul di board — kebiasaan bukan tiket.',
            },
        ],
    },
    team: {
        title: 'Kerja bareng tim',
        intro: 'Filter di kanan atas beralih antara All users dan satu orang. Di board, kartu orang lain disembunyikan; di kalender, hari, angka, dan bar rutinitas ikut menyempit.',
        items: [
            {
                lead: 'Head',
                body: 'juga melihat progres rutinitas tiap developer di samping namanya, dan bisa membuka rutinitas orang lain untuk dibaca. Tampilannya terkunci: head tidak bisa mencentang untuk orang lain, supaya catatannya tetap jujur.',
            },
            {
                lead: 'Yang lain',
                body: 'tetap bisa memfilter tampilan, tapi bar rutinitasnya selalu milik sendiri.',
            },
            {
                lead: 'Reports',
                body: 'menampilkan kerja yang selesai per orang dan rata-rata waktu dari sebuah task diambil sampai selesai, untuk 7 sampai 90 hari terakhir.',
            },
            {
                lead: 'Feature Requests',
                body: 'adalah daftar terpisah untuk permintaan dari divisi lain, dengan prioritas dan deadline sendiri. Bukan bagian dari board harianmu.',
            },
        ],
    },
    habits: {
        title: 'Kebiasaan yang membantu',
        intro: 'App ini sengaja dibuat sederhana. Enam kebiasaan ini yang membuatnya berguna, bukan cuma jadi tab lain.',
        items: [
            {
                lead: 'Jaga Today tetap kecil.',
                body: 'Satu sampai tiga kartu yang benar-benar bisa selesai. Today itu janji, bukan daftar keinginan.',
            },
            {
                lead: 'Rapikan sebelum pulang.',
                body: 'Yang selesai ke Done, yang belum balik ke Todo. Besok mulai dari keadaan bersih, dan kalender tetap jujur.',
            },
            {
                lead: 'Rencanakan besok sebelum tutup laptop.',
                body: 'Isi “Planned for” di kartu yang akan kamu mulai. Kamu besok tidak perlu mengingat-ingat.',
            },
            {
                lead: 'Taruh nomor tiket di Reference.',
                body: 'Kode pendek seperti JIRA-123 bikin kartunya gampang dicari nanti, termasuk di kalender.',
            },
            {
                lead: 'Centang rutinitas sebelum pulang.',
                body: 'Tidak sempat? Pakai Skip dan tulis catatannya. Skip itu jujur; diam saja tidak.',
            },
            {
                lead: 'Percaya sama chip-nya.',
                body: 'Stale atau delayed bukan omelan. Itu tanda untuk memecah task, menjadwalkannya, atau membuangnya.',
            },
        ],
    },
    questions: {
        title: 'Jawaban cepat',
        items: [
            {
                q: 'Kartu yang sedang saya kerjakan hilang dari Today.',
                a: 'Tengah malam (WIB) app memindahkan sisa Today kembali ke Todo. Tidak ada yang hilang — kartunya ada di Todo, dan kalender mencatat perpindahan itu sebagai “rolled back”.',
            },
            {
                q: 'Kenapa saya tidak bisa memindah atau mencentang ini?',
                a: 'Kamu hanya bisa memindahkan kartu sendiri. Kotak rutinitas milik pemiliknya; head bisa membuka rutinitas orang lain, tapi hanya untuk dilihat.',
            },
            {
                q: 'Kenapa tanggal yang sudah lewat tidak bisa dipilih?',
                a: '“Planned for” untuk rencana ke depan, jadi tanggal lampau dimatikan. Kalau ada konteks lama yang perlu disimpan, taruh di description.',
            },
            {
                q: 'Task sudah saya hapus, kenapa masih ada di kalender?',
                a: 'Kalender menyimpan riwayat. Task yang dihapus tetap tampil, dicoret dan ditandai “(deleted)”, supaya hari-hari lalu tetap akurat.',
            },
            {
                q: 'Rutinitas saya di mana?',
                a: 'Di halaman kalender, di bar ungu atas angka-angka bulanan. Bukan di board.',
            },
            {
                q: 'Rutinitas yang sudah diarsipkan bisa dikembalikan?',
                a: 'Belum di versi ini. Tambahkan lagi sebagai rutinitas baru; entri lamanya tetap terlihat di hari-hari sebelumnya.',
            },
            {
                q: 'Skip bikin streak putus?',
                a: 'Tidak. Selesai dan skip sama-sama dihitung beres. Yang memutus hanya hari yang tidak disentuh sama sekali.',
            },
            {
                q: 'Kenapa saya lihat 0/3 padahal belum melakukan apa-apa?',
                a: 'Rutinitas yang belum disentuh dihitung belum selesai. Angkanya terisi sambil kamu mencentang.',
            },
        ],
        outro: 'Masih ragu soal sesuatu? Tanya di grup tim, atau buka board dan lihat satu kartu asli — sebagian besar jawabannya kelihatan di sana.',
    },
};
