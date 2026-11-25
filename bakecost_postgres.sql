-- =====================================================
-- BAKECOST (Baking Converter & Cost Calculator)
-- Skema PostgreSQL (Neon) — hasil konversi dari bakecost.sql
-- + bakecost_migration_v2.sql (kolom produksi.tipe).
--
-- Perbedaan utama vs MySQL:
--   AUTO_INCREMENT            -> SERIAL
--   ENUM(...)                 -> VARCHAR + CHECK
--   ON UPDATE CURRENT_TIMESTAMP -> trigger set_updated_at()
--   Waktu default disimpan dalam zona Asia/Jakarta (TIMESTAMP tanpa zona),
--   supaya tampilan tanggal sama seperti versi MySQL lokal.
-- Aman dijalankan ulang (IF NOT EXISTS / OR REPLACE).
-- =====================================================

-- 1. Pengguna (login / multi-user)
CREATE TABLE IF NOT EXISTS pengguna (
  id            SERIAL PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(10)  NOT NULL DEFAULT 'user'
                CHECK (role IN ('admin', 'user')),
  created_at    TIMESTAMP(0) DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta')
);

-- 2. Database bahan baku
CREATE TABLE IF NOT EXISTS bahan_baku (
  id               SERIAL PRIMARY KEY,
  pengguna_id      INT NOT NULL REFERENCES pengguna(id) ON DELETE CASCADE,
  nama             VARCHAR(100) NOT NULL,
  kategori         VARCHAR(50),
  satuan           VARCHAR(10) NOT NULL DEFAULT 'g'
                   CHECK (satuan IN ('g', 'kg', 'ml', 'l', 'pcs', 'butir')),
  jumlah_beli      NUMERIC(12,2) NOT NULL,
  harga_beli       NUMERIC(14,2) NOT NULL,
  harga_per_satuan NUMERIC(14,4) GENERATED ALWAYS AS (harga_beli / jumlah_beli) STORED,
  updated_at       TIMESTAMP(0) DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta'),
  UNIQUE (pengguna_id, nama)
);

-- Pengganti "ON UPDATE CURRENT_TIMESTAMP" milik MySQL
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at := CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta';
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_bahan_baku_updated_at ON bahan_baku;
CREATE TRIGGER trg_bahan_baku_updated_at
  BEFORE UPDATE ON bahan_baku
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- 3. Manajemen resep
CREATE TABLE IF NOT EXISTS resep (
  id            SERIAL PRIMARY KEY,
  pengguna_id   INT NOT NULL REFERENCES pengguna(id) ON DELETE CASCADE,
  nama          VARCHAR(150) NOT NULL,
  jumlah_hasil  NUMERIC(10,2) NOT NULL,
  satuan_hasil  VARCHAR(30) NOT NULL DEFAULT 'pcs',
  deskripsi     TEXT,
  dibuat_via_ai BOOLEAN NOT NULL DEFAULT FALSE,
  created_at    TIMESTAMP(0) DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta')
);

CREATE TABLE IF NOT EXISTS resep_bahan (
  id        SERIAL PRIMARY KEY,
  resep_id  INT NOT NULL REFERENCES resep(id) ON DELETE CASCADE,
  bahan_id  INT NOT NULL REFERENCES bahan_baku(id),
  jumlah    NUMERIC(12,3) NOT NULL,
  catatan   VARCHAR(150),
  UNIQUE (resep_id, bahan_id)
);

-- 4. Packaging
CREATE TABLE IF NOT EXISTS packaging (
  id             SERIAL PRIMARY KEY,
  pengguna_id    INT NOT NULL REFERENCES pengguna(id) ON DELETE CASCADE,
  nama           VARCHAR(100) NOT NULL,
  harga_per_unit NUMERIC(12,2) NOT NULL
);

-- 5. Kalkulator HPP & biaya produksi
CREATE TABLE IF NOT EXISTS produksi (
  id                 SERIAL PRIMARY KEY,
  resep_id           INT NOT NULL REFERENCES resep(id) ON DELETE CASCADE,
  jumlah_produksi    NUMERIC(10,2) NOT NULL,
  faktor_konversi    NUMERIC(10,4) NOT NULL DEFAULT 1,
  biaya_bahan        NUMERIC(14,2) NOT NULL DEFAULT 0,
  biaya_packaging    NUMERIC(14,2) NOT NULL DEFAULT 0,
  biaya_tenaga_kerja NUMERIC(14,2) NOT NULL DEFAULT 0,
  biaya_overhead     NUMERIC(14,2) NOT NULL DEFAULT 0,
  hpp_total          NUMERIC(14,2) NOT NULL DEFAULT 0,
  hpp_per_unit       NUMERIC(14,2) NOT NULL DEFAULT 0,
  margin_persen      NUMERIC(5,2),
  harga_jual         NUMERIC(14,2),
  tipe               VARCHAR(10) NOT NULL DEFAULT 'biaya'
                     CHECK (tipe IN ('konversi', 'biaya')),
  created_at         TIMESTAMP(0) DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta')
);

CREATE TABLE IF NOT EXISTS produksi_packaging (
  produksi_id  INT NOT NULL REFERENCES produksi(id) ON DELETE CASCADE,
  packaging_id INT NOT NULL REFERENCES packaging(id),
  jumlah       INT NOT NULL,
  PRIMARY KEY (produksi_id, packaging_id)
);

-- 6. Bake Loss
CREATE TABLE IF NOT EXISTS bake_loss (
  id            SERIAL PRIMARY KEY,
  resep_id      INT NOT NULL REFERENCES resep(id) ON DELETE CASCADE,
  berat_sebelum NUMERIC(10,2) NOT NULL,
  berat_sesudah NUMERIC(10,2) NOT NULL,
  persen_loss   NUMERIC(6,2) GENERATED ALWAYS AS
                  ((berat_sebelum - berat_sesudah) / berat_sebelum * 100) STORED,
  catatan       VARCHAR(200),
  tanggal       DATE DEFAULT ((CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta')::date)
);

-- Index untuk foreign key yang sering dipakai di WHERE / JOIN
CREATE INDEX IF NOT EXISTS idx_bahan_baku_pengguna ON bahan_baku(pengguna_id);
CREATE INDEX IF NOT EXISTS idx_resep_pengguna      ON resep(pengguna_id);
CREATE INDEX IF NOT EXISTS idx_resep_bahan_bahan   ON resep_bahan(bahan_id);
CREATE INDEX IF NOT EXISTS idx_packaging_pengguna  ON packaging(pengguna_id);
CREATE INDEX IF NOT EXISTS idx_produksi_resep      ON produksi(resep_id);
CREATE INDEX IF NOT EXISTS idx_bake_loss_resep     ON bake_loss(resep_id);
