-- =====================================================
-- bakecost_migration_v2.sql
-- Tambahkan kolom `tipe` ke tabel produksi.
-- Jalankan sekali setelah import bakecost.sql utama.
-- =====================================================

ALTER TABLE produksi
    ADD COLUMN tipe ENUM('konversi','biaya') NOT NULL DEFAULT 'biaya'
    AFTER harga_jual;

