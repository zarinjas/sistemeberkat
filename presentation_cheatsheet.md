# e-BERKAT — Cheat Sheet Pembentangan

> **Audience:** AJK & Pengerusi BERKAT, JANM
> **Format:** Demo live, tanpa slide
> **Anggaran masa:** 10–15 minit + Q&A

---

## PEMBUKAAN (30s)

- Salam Pengerusi & AJK
- "Prototaip **e-BERKAT Smart Concierge**" — modenkan ekosistem BERKAT
- Demo ikut **3 sudut**: Ahli → Admin → Superadmin

---

## 1) AHLI BERKAT *(login: applicant)*

| Ciri | Point untuk sebut |
|---|---|
| Papan pemuka peribadi | Senarai permohonan + pengumuman + notifikasi |
| **Kad Keahlian Digital** | Lengkap dengan **QR code** — ganti kad fizikal |
| **Dompet Dokumen** | Upload **sekali** → guna semula untuk permohonan akan datang |
| **Smart Stepper** | Borang berperingkat — mesra warga emas |
| Garis masa status | Pemohon cakna kemajuan tanpa telefon pejabat |
| **PDF download** | Salinan rasmi permohonan |
| Pusat Maklumat | Infografik, Undang-Undang, AJK, Panduan |

---

## 2) PENTADBIR *(login: admin)*

| Ciri | Point untuk sebut |
|---|---|
| Dashboard KPI | Baharu / Pending / Lulus + **Queue Pressure** indicator |
| **Approval Queue** | **Tag keutamaan automatik** — kes kritikal didahulukan |
| Buka permohonan | Semak butiran + dokumen + kemaskini status |
| **E-mel Blast** | Hantar pengumuman pukal — preview dulu sebelum hantar |
| Pengurusan Bayaran | Senarai layak + eksport rekod bayaran |
| **Laporan Analitik** | Taburan kategori bantuan + volum cawangan + trend |
| Lihat ahli berdaftar | Profil ahli untuk semakan |

---

## 3) SUPERADMIN *(login: superadmin)*

### Ciri SIGNATURE — beri penekanan extra

**A. Pembina Borang Dinamik (Form Schema Builder)**
- Cipta / Duplicate / Draf / **Terbit** / Arkib templat bantuan
- Skim baru → tak perlu tunggu developer
- *Contoh: "Bantuan Banjir 2026" boleh dibina sendiri*

**B. Log Jejak Audit (untuk JANM — paling penting!)**
- Rekod automatik: **perubahan peranan**, **log masuk**, **operasi sistem**
- Boleh tapis ikut: pengguna / tarikh / peranan
- **Eksport CSV & PDF** — untuk pengauditan rasmi
- Statistik visual

### Ciri sokongan

| Ciri | Point untuk sebut |
|---|---|
| Pengurusan Pengguna | Daftar ahli, tukar peranan |
| **Import CSV** | Pendaftaran ahli pukal |
| Eksport ahli | Pelaporan |
| Hero Settings | Imej & slogan halaman utama |
| Poster Pengumuman | Papan pemuka ahli |
| Kandungan Info Center | Undang-undang, AJK, infografik |
| Pengurusan Panduan | Cipta / sunting / terbit / arkib |

---

## 4) KESELAMATAN (30s sahaja)

- **RBAC** 3 lapis: Ahli / Admin / Superadmin
- **Policies terperinci** — contoh: Superadmin **tak boleh padam Superadmin lain**
- **Log audit kekal** — tak boleh diubah
- Pengesahan e-mel

---

## PENUTUP — 4 PEMBAHARUAN

1. **Memudahkan ahli** — Dompet Dokumen, smart stepper, kad QR, status telus
2. **Memperkasa pentadbir** — Queue automatik, e-mel blast, laporan analitik
3. **Memberi autonomi pengurusan** — Borang dinamik, pengurusan kandungan tanpa kod
4. **Memperkukuh pengauditan** — Log lengkap, boleh eksport CSV/PDF

> *"Masih prototaip — mengalu-alukan pandangan dan cadangan."*

---

## CHEAT — Jawapan Soalan Lazim

| Soalan | Jawapan ringkas |
|---|---|
| Bila boleh deploy? | Prototaip — perlu fasa pengukuhan sebelum production |
| Berapa kos? | Bergantung skop deployment & infrastruktur |
| Data security? | Laravel security standard + audit logging + RBAC |
| Boleh integrate sistem JANM? | Boleh dikaji — API-ready architecture |
| Mobile app? | Web responsive, boleh ditambah app jika perlu |
| Backup data? | Mengikut polisi infrastruktur deployment |

---

## TIPS MASA PRESENT

- Pandang Pengerusi masa intro & penutup
- **Pace lambat** — beri masa point sink in
- Beri penekanan extra pada: **Form Builder** + **Audit Trail**
- Glitch teknikal? Jangan panik: *"Mohon maaf, ini prototaip — kita teruskan."*
- Jangan baca skrip — guna sebagai panduan sahaja

---

## URUTAN LOGIN SEBELUM PRESENT

1. Bukak 3 tab browser (atau private windows)
2. **Tab 1:** Login applicant
3. **Tab 2:** Login admin
4. **Tab 3:** Login superadmin
5. Pastikan dashboard load elok sebelum mula
6. Pastikan ada **sample data** — beberapa permohonan dalam queue
