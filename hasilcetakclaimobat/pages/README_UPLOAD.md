# Upload Berkas Pemeriksaan - SIMRS KHANZA

## Folder yang Diperlukan

Pastikan folder-folder berikut ada di direktori `/webapps/`:

1. hasilpemeriksaaneeg/
2. hasilpemeriksaanhba1c/
3. hasilpemeriksaanmmse/
4. hasilpemeriksaanecho/
5. hasilpemeriksaanechopediatrik/
6. hasilpemeriksaanekg/
7. hasilpemeriksaanoct/
8. hasilpemeriksaanslitlamp/
9. hasilpemeriksaantreadmill/
10. hasilpemeriksaanusg/
11. hasilpemeriksaanusggynecologi/
12. hasilpemeriksaanusgneonatus/
13. hasilpemeriksaanusgurologi/
14. hasilpemeriksaanendoskopifaringlaring/
15. hasilpemeriksaanendoskopihidung/
16. hasilpemeriksaanendoskopitelinga/

## File yang Dibuat

1. **upload_berkas.php** - Handler untuk upload berkas pemeriksaan
2. **previewriwayat.php** (diupdate) - Form upload terintegrasi

## Cara Menggunakan

1. Pilih nomor rawat dari dropdown
2. Pilih jenis berkas yang akan diupload
3. Pilih file (JPG, PNG, PDF, DOC, DOCX max 5MB)
4. Klik "Upload Berkas"
5. File akan disimpan ke folder sesuai jenis berkas
6. Data akan tersimpan di tabel `berkas_digital_perawatan`

## Struktur Database

Tabel: `berkas_digital_perawatan`
- no_rawat (VARCHAR)
- kode (VARCHAR) - Kode jenis berkas (EEG, HBA1C, MMSE, dll)
- lokasi_file (VARCHAR) - Path relative file

## Kode Berkas

- EEG - Electroencephalography
- HBA1C - Hemoglobin A1c
- MMSE - Mini-Mental State Examination
- ECHO - Ekokardiografi
- ECHOPED - ECHO Pediatrik
- EKG - Elektrokardiogram
- OCT - Optical Coherence Tomography
- SLITLAMP - Slit Lamp
- TREADMILL - Treadmill
- USG - USG
- USGGYN - USG Gynecologi
- USGNEO - USG Neonatus
- USGURO - USG Urologi
- ENDOFAR - Endoskopi Faring Laring
- ENDOHID - Endoskopi Hidung
- ENDOTEL - Endoskopi Telinga
