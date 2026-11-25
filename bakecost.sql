-- =====================================================
-- BAKECOST (Baking Converter & Cost Calculator)
-- Skema database MySQL (Lengkap & Siap Import)
-- =====================================================

CREATE DATABASE IF NOT EXISTS bakecost
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bakecost;

-- 1. Pengguna (login / multi-user)
CREATE TABLE IF NOT EXISTS pengguna (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Database bahan baku
CREATE TABLE IF NOT EXISTS bahan_baku (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  pengguna_id      INT NOT NULL,
  nama             VARCHAR(100) NOT NULL,
  kategori         VARCHAR(50),
  satuan           ENUM('g','kg','ml','l','pcs','butir') NOT NULL DEFAULT 'g',
  jumlah_beli      DECIMAL(12,2) NOT NULL,
  harga_beli       DECIMAL(14,2) NOT NULL,
  harga_per_satuan DECIMAL(14,4) GENERATED ALWAYS AS (harga_beli / jumlah_beli) STORED,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE,
  UNIQUE (pengguna_id, nama)
);

-- 3. Manajemen resep
CREATE TABLE IF NOT EXISTS resep (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  pengguna_id   INT NOT NULL,
  nama          VARCHAR(150) NOT NULL,
  jumlah_hasil  DECIMAL(10,2) NOT NULL,
  satuan_hasil  VARCHAR(30) NOT NULL DEFAULT 'pcs',
  deskripsi     TEXT,
  dibuat_via_ai BOOLEAN NOT NULL DEFAULT FALSE,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS resep_bahan (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  resep_id  INT NOT NULL,
  bahan_id  INT NOT NULL,
  jumlah    DECIMAL(12,3) NOT NULL,
  catatan   VARCHAR(150),
  FOREIGN KEY (resep_id) REFERENCES resep(id) ON DELETE CASCADE,
  FOREIGN KEY (bahan_id) REFERENCES bahan_baku(id),
  UNIQUE (resep_id, bahan_id)
);

-- 4. Packaging
CREATE TABLE IF NOT EXISTS packaging (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pengguna_id    INT NOT NULL,
  nama           VARCHAR(100) NOT NULL,
  harga_per_unit DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- 5. Kalkulator HPP & biaya produksi
CREATE TABLE IF NOT EXISTS produksi (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  resep_id           INT NOT NULL,
  jumlah_produksi    DECIMAL(10,2) NOT NULL,
  faktor_konversi    DECIMAL(10,4) NOT NULL DEFAULT 1,
  biaya_bahan        DECIMAL(14,2) NOT NULL DEFAULT 0,
  biaya_packaging    DECIMAL(14,2) NOT NULL DEFAULT 0,
  biaya_tenaga_kerja DECIMAL(14,2) NOT NULL DEFAULT 0,
  biaya_overhead     DECIMAL(14,2) NOT NULL DEFAULT 0,
  hpp_total          DECIMAL(14,2) NOT NULL DEFAULT 0,
  hpp_per_unit       DECIMAL(14,2) NOT NULL DEFAULT 0,
  margin_persen      DECIMAL(5,2),
  harga_jual         DECIMAL(14,2),
  tipe               ENUM('konversi','biaya') NOT NULL DEFAULT 'biaya',
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resep_id) REFERENCES resep(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS produksi_packaging (
  produksi_id  INT NOT NULL,
  packaging_id INT NOT NULL,
  jumlah       INT NOT NULL,
  PRIMARY KEY (produksi_id, packaging_id),
  FOREIGN KEY (produksi_id)  REFERENCES produksi(id)  ON DELETE CASCADE,
  FOREIGN KEY (packaging_id) REFERENCES packaging(id)
);

-- 6. Bake Loss
CREATE TABLE IF NOT EXISTS bake_loss (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  resep_id      INT NOT NULL,
  berat_sebelum DECIMAL(10,2) NOT NULL,
  berat_sesudah DECIMAL(10,2) NOT NULL,
  persen_loss   DECIMAL(6,2) GENERATED ALWAYS AS
                  ((berat_sebelum - berat_sesudah) / berat_sebelum * 100) STORED,
  catatan       VARCHAR(200),
  tanggal       DATE DEFAULT (CURRENT_DATE),
  FOREIGN KEY (resep_id) REFERENCES resep(id) ON DELETE CASCADE
);

