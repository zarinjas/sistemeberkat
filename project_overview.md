# Gambaran Keseluruhan Projek e-BERKAT

## Pengenalan
Sistem **e-BERKAT Smart Concierge** merupakan sebuah prototaip aplikasi web yang dibangunkan untuk memodenkan dan mendigitalkan aliran kerja pengurusan bantuan JANM BERKAT. Projek ini dibina menggunakan teknologi terkini seperti **Laravel 13** untuk logik pengurusan belakang (backend), **Inertia.js + Vue 3** untuk antaramuka interaktif (frontend), serta **Tailwind CSS** bagi tujuan reka bentuk gaya.

## Fungsi dan Ciri-Ciri Utama Sistem

### 1. Pengurusan Pemohon (Applicant)
- **Muat Naik Dokumen Sokongan:** Pemohon boleh memuat naik dokumen-dokumen sokongan semasa proses penghantaran permohonan.
- **Aliran Permohonan Pintar:** Proses permohonan dilaksanakan menggunakan langkah-langkah borang pintar bersiri (*dynamic smart stepper*).
- **Papan Pemuka Pemohon:** Memaparkan status permohonan dalam bentuk garis masa (*timeline view*) supaya pemohon sentiasa cakna tentang kemajuan permohonan kelulusan mereka.

### 2. Pengurusan Pentadbir (Admin & Superadmin)
- **Giliran Kelulusan (Approval Queue):** Pentadbir boleh memantau permohonan dengan tag keutamaan pra-pemarkahan (*pre-scoring priority tags*) untuk memproses bantuan yang lebih kritikal terlebih dahulu.
- **Penapisan Berasaskan Cawangan (*Branch-Based Filtering*):** Pentadbir hanya boleh melihat, mengurus, dan meluluskan permohonan serta pembayaran daripada pemohon di bawah cawangan tugasan mereka sendiri.
- **Papan Pemuka Laporan:** Laporan taburan bantuan mengikut kategori dan volum statistik mengikut cawangan bagi memudahkan analisis pengurusan.
- **Pembina Borang Dinamik:** Superadmin boleh mencipta, menduplikasi, mengubah, menyimpan draf, mengarkib, dan menerbitkan templat skim borang bantuan baharu melalui ciri *Form Schema*.

### 3. Kawalan Akses Berasaskan Peranan (RBAC - Role Based Access Control)
Sistem membezakan keupayaan pengguna berasaskan 3 peranan (roles) utama:
- **Pemohon (Applicant):** Menghantar permohonan dan menguruskan draf serta dokumen profil diri sendiri.
- **Pentadbir (Admin):** Menyemak dan meluluskan permohonan, memulakan serta mengesahkan proses pembayaran (konsep semakan *maker-checker* bagi pembayaran).
- **Superadmin:** Akses tanpa batasan untuk menguruskan templat borang bantuan, mentadbir pengguna, melihat semua cawangan dan menjejaki laporan keselamatan sistem.

### 4. Polisi Kebenaran Berlapis (*Fine-Tuned Permission System*)
Sistem tidak sekadar melihat pada peranan semata-mata, sebaliknya menggunakan **Laravel Policies** dan **Middleware** yang terperinci ke atas sesuatu tindakan seperti:
- **`AidApplicationPolicy`**: Mengawal ketat fungsi kemaskini borang, penyemakan bantuan, dan pemprosesan baucar bayaran bagi sesuatu aplikasi spesifik.
- **`FormSchemaPolicy`**: Mengehadkan penerbitan dan pengarkiban borang hanya kepada Superadmin sahaja.
- **`UserPolicy`**: Memastikan peranan keselamatan terjaga (contohnya, melindungi Superadmin daripada dibuang jawatan).

### 5. Log Jejak Audit (*Audit Trail & Dashboard*)
- Semua perubahan penting seperti perubahan status peranan pengguna didokumentasikan di bawah Sejarah Audit (Role Change History).
- Sistem ini mempunyai papan pemuka khas yang komprehensif bagi menjejak log jejak audit seperti "Siapa mengubah status siapa".
- Log audit ini menyokong ciri carian, penapisan (mengikut pengguna/tarikh/peranan) dan turut menyediakan fasiliti menjana/eksport log statistik ke dalam format **CSV** dan bentuk laporan profesional **PDF**.

## Reka Bentuk & Infrastruktur Teknikal
- **Penyimpanan:** Prototaip setakat ini menyokong pemfailan dokumen secara terus menerusi *storage local disk* standard dari Laravel.
- **Sistem Masa Nyata (Real-time):** Sistem ini telah dilengkapkan dengan tetapan masa nyata berasaskan peninjauan automatik (*polling-ready*) pada bahagian UI untuk menyediakan proses kemaskini maklumat kelancaran.

## Ringkasan
Secara tuntasnya, projek e-BERKAT bukan sekadar berfungsi sebagai pendigitalan permohonan kertas. Ia membawa pembaharuan pengurusan dari sudut memudahkan proses permohonan pemohon, meningkatkan kawalan integriti dengan pelaksanaan modul kelulusan berperingkat (*maker-checker* bayaran dan cawangan), serta memperkukuh pematuhan menerusi log jejak audit yang selamat dan komprehensif.
