# Bug Fix: Perhitungan Potongan Gaji

## Masalah yang Ditemukan

1. **Inkonsistensi perhitungan potongan** antara `proses_gaji.php` dan `get_payroll_details.php`
2. **BPJS dikali 2** saat edit data karena perhitungan yang berbeda
3. **Tidak ada fungsi helper** untuk memastikan konsistensi perhitungan

## Penyebab Bug

### Di `proses_gaji.php` (sebelum perbaikan):
```php
// Menggunakan max() untuk nilai minimum
$gaji['potongan_bpjs'] = max($gaji['gaji_pokok'] * ($persentase_bpjs / 100), 50000);
$gaji['infak'] = max($gaji['gaji_pokok'] * ($persentase_infak / 100), 25000);
```

### Di `get_payroll_details.php` (sebelum perbaikan):
```php
// Tidak menggunakan max(), hanya perhitungan persentase
$response['potongan_bpjs'] = $gaji_pokok * ($persentase_bpjs / 100);
$response['infak'] = $gaji_pokok * ($persentase_infak / 100);
```

## Solusi yang Diterapkan

### 1. Menambahkan Helper Functions di `includes/functions.php`

```php
/**
 * Menghitung potongan berdasarkan persentase dari gaji pokok.
 */
function calculate_potongan($gaji_pokok, $persentase_bpjs = 2, $persentase_infak = 2)
{
    return [
        'potongan_bpjs' => $gaji_pokok * ($persentase_bpjs / 100),
        'infak' => $gaji_pokok * ($persentase_infak / 100)
    ];
}

/**
 * Menghitung tunjangan kehadiran berdasarkan jumlah keterlambatan.
 */
function calculate_tunjangan_kehadiran($jml_terlambat)
{
    if ($jml_terlambat > 5) {
        return 0;
    }
    return 100000 - ($jml_terlambat * 5000);
}

/**
 * Menghitung tunjangan anak berdasarkan jumlah anak.
 */
function calculate_tunjangan_anak($jml_anak, $max_anak = 2, $tunjangan_per_anak = 100000)
{
    $jml_anak_tunjangan = min((int)$jml_anak, $max_anak);
    return $jml_anak_tunjangan * $tunjangan_per_anak;
}
```

### 2. Menyeragamkan Perhitungan di Kedua File

**Setelah perbaikan di kedua file:**
```php
// Menggunakan fungsi helper yang sama
$potongan = calculate_potongan($gaji_pokok, $persentase_bpjs, $persentase_infak);
$gaji['potongan_bpjs'] = $potongan['potongan_bpjs'];
$gaji['infak'] = $potongan['infak'];
```

### 3. Menghapus Logika max() yang Inconsistent

- Menghapus penggunaan `max()` di `proses_gaji.php`
- Memastikan kedua file menggunakan perhitungan persentase yang sama
- Menggunakan helper functions untuk konsistensi

## Hasil Perbaikan

✅ **Konsistensi perhitungan** antara input data baru dan edit data  
✅ **Tidak ada lagi BPJS dikali 2** saat edit  
✅ **Helper functions** untuk memudahkan maintenance  
✅ **Dokumentasi yang jelas** untuk setiap perhitungan  

## Testing

1. **Input data gaji baru** - perhitungan potongan konsisten
2. **Edit data gaji** - tidak ada perubahan nilai potongan yang tidak diinginkan
3. **API response** - sama dengan perhitungan server-side

## File yang Diperbaiki

- `includes/functions.php` - Menambahkan helper functions
- `pages/proses_gaji.php` - Menggunakan helper functions
- `api/get_payroll_details.php` - Menggunakan helper functions yang sama 