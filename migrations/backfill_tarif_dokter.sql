-- Backfill `tarif` for doctors that don't have it set
-- Preview affected rows:
SELECT id, nama, spesialisasi, tarif FROM dokter WHERE tarif IS NULL;

-- Preview calculated tarif (based on specialization) before applying:
SELECT id, nama, spesialisasi,
CASE
  WHEN LOWER(spesialisasi) LIKE '%penyakit dalam%' THEN 180000
  WHEN LOWER(spesialisasi) LIKE '%anak%' THEN 150000
  WHEN LOWER(spesialisasi) LIKE '%bedah%' THEN 300000
  WHEN LOWER(spesialisasi) LIKE '%kandungan%' OR LOWER(spesialisasi) LIKE '%obgyn%' THEN 250000
  WHEN LOWER(spesialisasi) LIKE '%jantung%' OR LOWER(spesialisasi) LIKE '%kardiologi%' THEN 350000
  WHEN LOWER(spesialisasi) LIKE '%oftalmologi%' OR LOWER(spesialisasi) LIKE '%mata%' THEN 200000
  WHEN LOWER(spesialisasi) LIKE '%ortopedi%' OR LOWER(spesialisasi) LIKE '%traumatologi%' THEN 220000
  WHEN LOWER(spesialisasi) LIKE '%umum%' THEN 150000
  ELSE 150000
END AS calculated_tarif
FROM dokter
WHERE tarif IS NULL;

-- Apply update: set tarif only where currently NULL
START TRANSACTION;
UPDATE dokter
SET tarif = CASE
  WHEN LOWER(spesialisasi) LIKE '%penyakit dalam%' THEN 180000
  WHEN LOWER(spesialisasi) LIKE '%anak%' THEN 150000
  WHEN LOWER(spesialisasi) LIKE '%bedah%' THEN 300000
  WHEN LOWER(spesialisasi) LIKE '%kandungan%' OR LOWER(spesialisasi) LIKE '%obgyn%' THEN 250000
  WHEN LOWER(spesialisasi) LIKE '%jantung%' OR LOWER(spesialisasi) LIKE '%kardiologi%' THEN 350000
  WHEN LOWER(spesialisasi) LIKE '%oftalmologi%' OR LOWER(spesialisasi) LIKE '%mata%' THEN 200000
  WHEN LOWER(spesialisasi) LIKE '%ortopedi%' OR LOWER(spesialisasi) LIKE '%traumatologi%' THEN 220000
  WHEN LOWER(spesialisasi) LIKE '%umum%' THEN 150000
  ELSE 150000
END
WHERE tarif IS NULL;
COMMIT;

-- Verify results
SELECT id, nama, spesialisasi, tarif FROM dokter WHERE tarif IS NOT NULL ORDER BY id;