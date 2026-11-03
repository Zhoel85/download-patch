-- 091 VERIFY
SELECT 'elearning_bsi_portable_batch' AS object_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS result
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'elearning_bsi_portable_batch'
UNION ALL
SELECT 'elearning_bsi_generated_content',
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'elearning_bsi_generated_content';
