-- ============================================================
-- Migration: tambah kolom moves dan attempts ke tabel scores
-- Jalankan sebagai: sqlplus kebox/kebox123@localhost:1521/orclpdb
-- ============================================================

ALTER TABLE scores ADD moves    NUMBER(10) DEFAULT 0 NOT NULL;
ALTER TABLE scores ADD attempts NUMBER(3)  DEFAULT 0 NOT NULL;

-- Index opsional untuk query leaderboard yang order by moves/attempts
CREATE INDEX idx_scores_moves    ON scores(game_type, game_level, moves)    TABLESPACE kebox_idx;
CREATE INDEX idx_scores_attempts ON scores(game_type, game_level, attempts) TABLESPACE kebox_idx;

COMMIT;
