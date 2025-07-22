### **B. Pengguna Sistem dan Hak Akses**

Sistem ini akan memiliki tiga jenis pengguna dengan tingkat akses yang berbeda untuk menjamin keamanan dan kerahasiaan data.

| No. | Pengguna | Hak Akses dan Wewenang |
| :--- | :--- | :--- |
| 1. | **Admin** | Memiliki hak akses penuh (*full access*) ke semua fitur yang ada pada sistem. <br> • Dapat melakukan operasi pengelolaan data (menambah, mengubah, menghapus) pada data master: Data Guru, Data Jabatan, Data Tunjangan, dan Data Potongan. <br> • Menginput data transaksi bulanan seperti rekap kehadiran guru. <br> • Melakukan validasi dan menjalankan proses perhitungan gaji untuk semua guru. <br> • Melihat dan mencetak semua jenis laporan penggajian. |
| 2. | **Kepala Sekolah** | Memiliki hak akses untuk monitoring dan manajemen tingkat tinggi. <br> • Dapat melihat semua laporan yang dihasilkan oleh sistem, seperti laporan gaji per bulan, per periode, atau per guru. <br> • Memiliki wewenang untuk menambahkan akun admin lainnya. |
| 3. | **Guru** | Memiliki hak akses yang sangat terbatas hanya untuk data pribadi. <br> • Hanya dapat melihat data laporan gajinya sendiri. <br> • Dapat mencetak slip gaji pribadi setiap bulan. |

### **C. Fitur-Fitur Sistem**

Berikut adalah rincian fitur yang akan tersedia untuk setiap pengguna:

**1. Fitur untuk Admin**

  * **Manajemen Pengguna (User)**: Menambah, mengubah, dan menghapus data pengguna (user) sistem, serta mengatur hak aksesnya.
  * **Manajemen Data Guru**: Mengelola data induk guru, termasuk NIPM, nama, jabatan, tanggal masuk, status kawin, dan jumlah anak.
  * **Manajemen Data Jabatan**: Mengelola data jabatan beserta gaji pokok awal dan skema kenaikan gaji per tahun.
  * **Manajemen Tunjangan & Potongan**: Mengatur besaran komponen tunjangan dan potongan.
  * **Pencatatan Kehadiran**: Menginput rekap kehadiran bulanan setiap guru, yang mencakup jumlah keterlambatan, izin, dan alfa.
  * **Proses Perhitungan Gaji**: Fitur inti untuk memproses perhitungan gaji secara otomatis berdasarkan data yang ada.
  * **Pembuatan Laporan**: Menghasilkan dan mencetak laporan penggajian secara kolektif per bulan atau per periode.

**2. Fitur untuk Kepala Sekolah**

  * **Lihat Laporan**: Mengakses semua laporan penggajian yang dihasilkan oleh sistem tanpa bisa mengubah data.
  * **Manajemen Akses Admin**: Menambah akun baru dengan hak akses sebagai admin.

**3. Fitur untuk Guru**

  * **Lihat Riwayat Gaji**: Menampilkan riwayat gaji dari bulan ke bulan.
  * **Cetak Slip Gaji**: Mengunduh dan mencetak slip gaji pribadi dalam format yang telah ditentukan.

### **D. Rancangan Halaman-Halaman Sistem**

Berikut adalah rancangan halaman utama yang akan ada di dalam aplikasi:

1.  **Halaman Login**

      * **Tujuan**: Sebagai gerbang masuk ke sistem untuk semua pengguna sebelum menggunakan aplikasi.
      * **Komponen**:
          * Form input untuk `Username`
          * Form input untuk `Password`
          * Tombol `Login`
      * **Referensi**: Gambar 3. 4. Rancangan Masukan Data Login.

2.  **Halaman Kelola Data (Guru, Jabatan, User, dll.)**

      * **Tujuan**: Menampilkan daftar data dalam bentuk tabel dan menyediakan fungsi untuk mengelolanya.
      * **Komponen**:
          * Tombol `Tambah Data` untuk membuka form input.
          * Fitur `Pencarian` untuk mencari data spesifik.
          * Tabel data dengan kolom-kolom yang relevan (misal: NIPM, Nama, Jabatan, dll. untuk data guru).
          * Kolom `Aksi` pada setiap baris data, berisi tombol `Edit` dan `Hapus`.
      * **Referensi**: Gambar 3.12 (Output User), 3.13 (Output Guru), 3.14 (Output Jabatan), 3.15 (Output Rekap Kehadiran).

3.  **Halaman Form Input (Guru, Jabatan, Kehadiran, dll.)**

      * **Tujuan**: Halaman khusus untuk memasukkan atau mengubah data.
      * **Komponen**:
          * Berbagai field input sesuai dengan data yang akan dimasukkan (misal: NIPM, Nama, Jabatan, Tanggal Masuk, dll. untuk form guru).
          * Tombol `Simpan` dan `Batal`.
      * **Referensi**: Gambar 3.5 (Input Guru), 3.6 (Input Jabatan), 3.7 (Input Kehadiran).

4.  **Halaman Laporan Penggajian**

      * **Tujuan**: Menampilkan data hasil perhitungan gaji semua guru dalam satu periode.
      * **Komponen**:
          * Filter berdasarkan `Nama`, `Bulan`, dan `Tahun`.
          * Tabel laporan yang menampilkan rincian: Nama, Gaji Pokok, Tunjangan, Potongan, Gaji Kotor, dan Gaji Bersih.
          * Tombol untuk melihat detail atau mencetak.
      * **Referensi**: Gambar 3.16 (Output Data Gaji) dan 3.17 (Output Penggajian Perperiode).

5.  **Halaman Slip Gaji (Untuk Guru)**

      * **Tujuan**: Menampilkan rincian gaji untuk satu guru pada satu periode tertentu dalam format yang siap cetak.
      * **Komponen**:
          * Kop surat sekolah (Yayasan Pimpinan Daerah Muhammadiyah (PDM) Kab. Bantul SD Unggulan Muhammadiyah Kretek).
          * Detail identitas guru (Nama, NIPM, Jabatan).
          * Periode penggajian (Bulan Penggajian dan Masa Kerja).
          * Rincian Penghasilan (Gaji Pokok, Tunjangan Beras, Tunjangan Kehadiran, dll.) dan Potongan (BPJS, Infak).
          * Total `Gaji Kotor`, Total Potongan, dan `Gaji Bersih`.
          * Kolom tanda tangan untuk `Penerima` dan `Bendahara Sekolah`.
      * **Referensi**: Gambar 3.18 Rancangan Output Slip Gaji.

