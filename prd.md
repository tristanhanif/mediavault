# PERANCANGAN SPESIFIKASI PRODUK & TEKNIS (PRD)
## MEDIAVAULT - DIGITAL ASSET MANAGEMENT SYSTEM

---

## 1. IDENTITAS & TUJUAN APLIKASI

* **Nama Produk:** MediaVault
* **Jenis & Skala Produk:** Platform Pengelolaan Aset Digital (*Digital Asset Management* / DAM) yang dirancang untuk kebutuhan organisasi, namun terbuka dan dapat diakses secara publik.
* **Sistem Registrasi:** *Self-Registration* (Pengguna dapat mendaftarkan akun secara mandiri tanpa memerlukan persetujuan manual dari admin).
* **Alur Akses (Authentication Flow):** Proteksi akses ketat tanpa halaman *landing page* publik. Seluruh pengguna yang mengakses aplikasi wajib melakukan *login* atau *register* terlebih dahulu sebelum diarahkan ke halaman *dashboard* utama.

---

## 2. ATURAN PENGELOLAAN MEDIA

* **Batas Ukuran File:** Maksimal **20 MB** per file.
* **Pengunggahan Banyak File (Multi-Upload):** Mendukung pengunggahan banyak file sekaligus (*batch upload*), dengan pengujian validasi batas maksimal 20 MB pada setiap file individual.
* **Penanganan Duplikat Nama File:** Diperbolehkan. Sistem mengimplementasikan penamaan otomatis inkremental berbasis indeks angka jika terdapat bentrokan nama file (contoh: `keuangan.pdf` menjadi `keuangan (1).pdf`).
* **Integritas & Keamanan Aset:** File bersifat *immutable* (tidak dapat diubah, baik konten maupun nama file) setelah berhasil diunggah demi menjaga keabsahan dan keaslian aset organisasi.
* **Mekanisme Penghapusan:** File yang dihapus tidak langsung hilang permanen dari sistem, melainkan masuk ke dalam **Recycle Bin** (*soft delete*).
* **Format File yang Didukung:**
  * **Dokumen:** PDF, DOCS, DOCX, TXT.
  * **Gambar:** Seluruh format gambar standar (PNG, JPG, JPEG, WEBP, GIF, dll.).
  * **Video:** Seluruh format video standar (MP4, MKV, AVI, MOV, dll.).

---

## 3. SISTEM FOLDER & KATEGORI

* **Kedalaman Subfolder:** Dibatasi maksimal **3 level subfolder** dari direktori utama untuk menjaga efisiensi serta performa pencarian database (*query recursive*).
* **Fleksibilitas Lokasi File:** File dapat diletakkan di direktori paling luar (*root*) maupun di dalam folder dan subfolder.
* **Sistem Kategori:** Disesuaikan berdasarkan preferensi pengguna (opsional/dikosongkan), dengan fokus utama pengelompokan menggunakan struktur folder dan pencarian metadata.
* **Sistem Label (Tag Management):** Pengguna bebas membuat dan menyematkan tag (*user-defined tags*) dengan relasi fleksibel (*many-to-many*) untuk mempermudah pencarian aset lintas folder.
* **Perpindahan Objek (Move Operation):** Baik file maupun folder dapat dipindahkan posisinya secara dinamis ke direktori target lainnya.

---

## 4. INTEGRASI CLOUDINARY & INFRASTRUKTUR DATABASE

* **Akun Storage:** Menggunakan satu akun Cloudinary terpusat milik organisasi/pengembang.
* **Isolasi Folder Penyimpanan:** Setiap pengguna memiliki folder penyimpanan terisolasi di Cloudinary berbasis ID unik pengguna.
* **Format Penamaan `public_id`:** `mediavault/users/{user_id}/{timestamp}_{random_string}_{original_name}` untuk menjamin keunikan dan privasi aset.
* **Sinkronisasi & Penanganan Kegagalan (Rollback):** Jika proses unggah ke Cloudinary berhasil namun penyimpanan metadata ke database MySQL gagal, sistem secara otomatis mengeksekusi proses *rollback* (menghapus file terkait dari Cloudinary) dan mengembalikan notifikasi pesan kegagalan ke pengguna.
* **Optimasi Media:** Tidak ada proses kompresi atau transformasi otomatis (*stored as-is*).

---

## 5. KEAMANAN & HAK AKSES

* **Privasi URL Media:** Bersifat **private** (memerlukan sesi login dan otorisasi aplikasi untuk mengakses aset).
* **Hak Akses Admin:** Pengguna dengan role **Admin** memiliki kewenangan penuh untuk memonitor, mengelola, dan melihat seluruh file pengguna di dalam organisasi.
* **Otorisasi Berbagi Media (Media Sharing):** Tautan berbagi media bersifat privat dan mewajibkan penerima akses untuk melakukan *login* ke aplikasi.
* **Masa Berlaku Akses Berbagi:** Berlaku permanen tanpa batasan waktu otomatis, hingga pemilik file mencabut akses tersebut.
* **Pencabutan Akses (Revoke Sharing):** Pemilik file memiliki kontrol penuh untuk membatalkan atau mencabut tautan akses berbagi kapan saja.

---

## 6. DAFTAR MODUL & FITUR UTAMA

| No. | Modul | Fitur & Deskripsi Fungsional |
| :-: | :--- | :--- |
| **1** | **Authentication** | Register, Login, Logout, Password Hashing |
| **2** | **Authorization** | Role Management (Admin & User), Middleware, Access Control |
| **3** | **Dashboard** | Total Media, Total Images, Documents, Videos, Storage Usage, Recent Uploads |
| **4** | **Media Management** | Upload (Max 20MB, Multi-upload), Rename System (Incremental), Delete (Recycle Bin), Bulk Delete, Download, Media Details |
| **5** | **Media Preview** | Image Preview, Video Player, PDF Viewer internal |
| **6** | **Folder Management** | Create Folder, Rename, Delete, Move Media/Folder, Subfolder (Max 3 Level) |
| **7** | **Category Management** | Create, Edit, Delete, Assign Category |
| **8** | **Tag Management** | Create Tag, Edit Tag, Delete Tag, Assign Multiple Tags |
| **9** | **Search & Filter** | Search by Name, Filter by Type, Date, Folder, Category, Tags |
| **10** | **Sorting** | Sort by Name, File Size, Upload Date |
| **11** | **User Management** | User List, User Details, Change Role, Deactivate Account (Khusus Admin) |
| **12** | **Activity Log** | Record Upload, Delete, Rename, and Other User Activities |
| **13** | **Media Sharing** | Generate Private Share Link, Manage Sharing Access (Revoke Access) |