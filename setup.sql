-- ============================================================
-- KeBox Game - Oracle Database Full Setup Script
-- ============================================================
-- PENTING - Urutan eksekusi:
--   STEP 1: Jalankan BAGIAN 1 (DBA Setup) sebagai SYSDBA
--   STEP 2: Jalankan BAGIAN 2-6 sebagai kebox
--   STEP 3: Jalankan BAGIAN 7 sebagai SYSDBA
-- ============================================================
-- ROOT CAUSE ERROR SEBELUMNYA:
--   "level" dan "role" adalah RESERVED WORD Oracle
--   Sudah diganti: level -> word_level / game_level / level_label
--                  role  -> user_role
-- ============================================================


-- ============================================================
-- BAGIAN 1: DBA SETUP
-- Jalankan sebagai: sqlplus sys/password as sysdba
-- ============================================================

-- 1.1 Tablespace data utama
CREATE TABLESPACE kebox_tbs
    DATAFILE 'kebox_data01.dbf'
    SIZE 50M
    AUTOEXTEND ON NEXT 10M MAXSIZE 200M
    EXTENT MANAGEMENT LOCAL
    SEGMENT SPACE MANAGEMENT AUTO;

-- 1.2 Tablespace khusus index
CREATE TABLESPACE kebox_idx
    DATAFILE 'kebox_idx01.dbf'
    SIZE 20M
    AUTOEXTEND ON NEXT 5M MAXSIZE 100M
    EXTENT MANAGEMENT LOCAL
    SEGMENT SPACE MANAGEMENT AUTO;

-- 1.3 Buat user Oracle
CREATE USER kebox IDENTIFIED BY kebox123    -- pake C## prefix untuk user biasa, nanti jadi C##kebox
    DEFAULT TABLESPACE kebox_tbs
    TEMPORARY TABLESPACE TEMP
    QUOTA UNLIMITED ON kebox_tbs
    QUOTA UNLIMITED ON kebox_idx;

-- 1.4 Buat role
CREATE ROLE kebox_app_role;         -- pake C## prefix untuk role biasa
CREATE ROLE kebox_readonly_role;    -- ini juga

-- 1.5 Privilege dasar ke user
GRANT CONNECT, RESOURCE TO kebox;
GRANT CREATE SESSION TO kebox;
GRANT CREATE VIEW TO kebox;
GRANT CREATE SEQUENCE TO kebox;
GRANT CREATE TRIGGER TO kebox;
GRANT kebox_app_role TO kebox;


-- ============================================================
-- BAGIAN 2: DROP TABEL LAMA (jika ada)
-- Jalankan sebagai: sqlplus kebox/kebox123@localhost:1521/orclpdb(sesuaikan dengan service kalian)
-- ============================================================

BEGIN EXECUTE IMMEDIATE 'DROP TABLE game_sessions CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE scores CASCADE CONSTRAINTS';        EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE puzzle_levels CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE words CASCADE CONSTRAINTS';         EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE users CASCADE CONSTRAINTS';         EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP VIEW vw_leaderboard_word';   EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP VIEW vw_leaderboard_puzzle'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP VIEW vw_user_stats';         EXCEPTION WHEN OTHERS THEN NULL; END;
/


-- ============================================================
-- BAGIAN 3: BUAT TABEL
-- ============================================================

-- 3.1 Tabel USERS
CREATE TABLE users (
    id          NUMBER GENERATED ALWAYS AS IDENTITY,
    username    VARCHAR2(50)  NOT NULL,
    email       VARCHAR2(100) NOT NULL,
    password    VARCHAR2(255) NOT NULL,
    user_role   VARCHAR2(10)  DEFAULT 'user' NOT NULL,
    created_at  DATE          DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_users             PRIMARY KEY (id),
    CONSTRAINT uq_users_username    UNIQUE (username),
    CONSTRAINT uq_users_email       UNIQUE (email),
    CONSTRAINT chk_users_role       CHECK (user_role IN ('user', 'admin')),
    CONSTRAINT chk_users_uname_len  CHECK (LENGTH(username) >= 3),
    CONSTRAINT chk_users_email_fmt  CHECK (email LIKE '%@%.%')
) TABLESPACE kebox_tbs;

-- 3.2 Tabel WORDS (kata untuk Word Game)
CREATE TABLE words (
    id          NUMBER GENERATED ALWAYS AS IDENTITY,
    word        VARCHAR2(10)  NOT NULL,
    word_level  VARCHAR2(10)  NOT NULL,
    created_at  DATE          DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_words             PRIMARY KEY (id),
    CONSTRAINT uq_words_per_level   UNIQUE (word, word_level),
    CONSTRAINT chk_words_level      CHECK (word_level IN ('easy', 'medium', 'hard')),
    CONSTRAINT chk_words_easy_len   CHECK (word_level != 'easy'   OR LENGTH(word) = 4),
    CONSTRAINT chk_words_med_len    CHECK (word_level != 'medium' OR LENGTH(word) = 5),
    CONSTRAINT chk_words_hard_len   CHECK (word_level != 'hard'   OR LENGTH(word) = 6),
    CONSTRAINT chk_words_alpha      CHECK (REGEXP_LIKE(word, '^[A-Z]+$'))
) TABLESPACE kebox_tbs;

-- 3.3 Tabel PUZZLE_LEVELS
CREATE TABLE puzzle_levels (
    id           NUMBER GENERATED ALWAYS AS IDENTITY,
    level_num    NUMBER(3)    NOT NULL,
    grid_size    NUMBER(2)    NOT NULL,
    level_label  VARCHAR2(50),
    label        VARCHAR2(100),
    created_at   DATE         DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_puzzle_levels       PRIMARY KEY (id),
    CONSTRAINT uq_puzzle_level_num    UNIQUE (level_num),
    CONSTRAINT chk_puzzle_level_range CHECK (level_num BETWEEN 1 AND 99),
    CONSTRAINT chk_puzzle_grid_range  CHECK (grid_size BETWEEN 2 AND 10)
) TABLESPACE kebox_tbs;

-- 3.4 Tabel SCORES
CREATE TABLE scores (
    id          NUMBER GENERATED ALWAYS AS IDENTITY,
    user_id     NUMBER        NOT NULL,
    game_type   VARCHAR2(10)  NOT NULL,
    game_level  VARCHAR2(30),
    score       NUMBER(10)    DEFAULT 0 NOT NULL,
    duration    NUMBER(10)    DEFAULT 0 NOT NULL,
    created_at  DATE          DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_scores          PRIMARY KEY (id),
    CONSTRAINT fk_scores_user     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_scores_type    CHECK (game_type IN ('word', 'puzzle')),
    CONSTRAINT chk_scores_score   CHECK (score >= 0),
    CONSTRAINT chk_scores_dur     CHECK (duration >= 0)
) TABLESPACE kebox_tbs;

-- 3.5 Tabel GAME_SESSIONS (2-player online)
CREATE TABLE game_sessions (
    id           VARCHAR2(6)   NOT NULL,
    game_type    VARCHAR2(10)  NOT NULL,
    player1_id   NUMBER,
    player2_id   NUMBER,
    sess_status  VARCHAR2(20)  DEFAULT 'waiting' NOT NULL,
    session_data CLOB,
    created_at   DATE          DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_game_sessions   PRIMARY KEY (id),
    CONSTRAINT fk_sess_player1    FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_sess_player2    FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_sess_type      CHECK (game_type IN ('word', 'puzzle')),
    CONSTRAINT chk_sess_status    CHECK (sess_status IN ('waiting', 'playing', 'finished')),
    CONSTRAINT chk_sess_diff_plyr CHECK (player1_id != player2_id OR player2_id IS NULL)
) TABLESPACE kebox_tbs;


-- ============================================================
-- BAGIAN 4: INDEX
-- ============================================================

-- Index tabel users
CREATE INDEX idx_users_username ON users(username)  TABLESPACE kebox_idx;
CREATE INDEX idx_users_email    ON users(email)     TABLESPACE kebox_idx;
CREATE INDEX idx_users_role     ON users(user_role) TABLESPACE kebox_idx;

-- Index tabel words
CREATE INDEX idx_words_level    ON words(word_level) TABLESPACE kebox_idx;
CREATE INDEX idx_words_word     ON words(word)       TABLESPACE kebox_idx;

-- Index tabel puzzle_levels
CREATE INDEX idx_puzzle_lvlnum  ON puzzle_levels(level_num) TABLESPACE kebox_idx;

-- Index tabel scores
CREATE INDEX idx_scores_user    ON scores(user_id)              TABLESPACE kebox_idx;
CREATE INDEX idx_scores_type    ON scores(game_type)            TABLESPACE kebox_idx;
CREATE INDEX idx_scores_date    ON scores(created_at)           TABLESPACE kebox_idx;
CREATE INDEX idx_scores_usrtype ON scores(user_id, game_type)   TABLESPACE kebox_idx;

-- Index tabel game_sessions
CREATE INDEX idx_sess_status    ON game_sessions(sess_status)   TABLESPACE kebox_idx;
CREATE INDEX idx_sess_p1        ON game_sessions(player1_id)    TABLESPACE kebox_idx;
CREATE INDEX idx_sess_p2        ON game_sessions(player2_id)    TABLESPACE kebox_idx;


-- ============================================================
-- BAGIAN 5: VIEW
-- ============================================================

CREATE OR REPLACE VIEW vw_leaderboard_word AS
SELECT
    u.username,
    MAX(s.score)    AS best_score,
    COUNT(s.id)     AS total_games,
    ROUND(AVG(s.score), 0) AS avg_score,
    MIN(s.duration) AS best_time_sec
FROM scores s
JOIN users u ON s.user_id = u.id
WHERE s.game_type = 'word'
GROUP BY u.username
ORDER BY best_score DESC;
/

CREATE OR REPLACE VIEW vw_leaderboard_puzzle AS
SELECT
    u.username,
    s.game_level,
    MAX(s.score)    AS best_score,
    COUNT(s.id)     AS total_games,
    MIN(s.duration) AS best_time_sec
FROM scores s
JOIN users u ON s.user_id = u.id
WHERE s.game_type = 'puzzle'
GROUP BY u.username, s.game_level
ORDER BY best_score DESC;
/

CREATE OR REPLACE VIEW vw_user_stats AS
SELECT
    u.id,
    u.username,
    u.email,
    u.user_role,
    u.created_at,
    NVL(w.total_games, 0)   AS word_total_games,
    NVL(w.best_score,  0)   AS word_best_score,
    NVL(p.total_games, 0)   AS puzzle_total_games,
    NVL(p.best_score,  0)   AS puzzle_best_score
FROM users u
LEFT JOIN (
    SELECT user_id, COUNT(*) AS total_games, MAX(score) AS best_score
    FROM scores WHERE game_type = 'word' GROUP BY user_id
) w ON u.id = w.user_id
LEFT JOIN (
    SELECT user_id, COUNT(*) AS total_games, MAX(score) AS best_score
    FROM scores WHERE game_type = 'puzzle' GROUP BY user_id
) p ON u.id = p.user_id;
/


-- ============================================================
-- BAGIAN 6 (SEED DATA): INSERT DATA AWAL
-- ============================================================

-- Admin default (password: admin123)
-- GANTI hash ini dengan: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
INSERT INTO users (username, email, password, user_role)
VALUES ('admin', 'admin@kebox.id',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin');

-- Kata Easy (4 huruf)
INSERT INTO words (word, word_level) VALUES ('TREE', 'easy');
INSERT INTO words (word, word_level) VALUES ('MOON', 'easy');
INSERT INTO words (word, word_level) VALUES ('FISH', 'easy');
INSERT INTO words (word, word_level) VALUES ('LION', 'easy');
INSERT INTO words (word, word_level) VALUES ('WIND', 'easy');
INSERT INTO words (word, word_level) VALUES ('ROCK', 'easy');
INSERT INTO words (word, word_level) VALUES ('STAR', 'easy');
INSERT INTO words (word, word_level) VALUES ('FROG', 'easy');
INSERT INTO words (word, word_level) VALUES ('SHIP', 'easy');
INSERT INTO words (word, word_level) VALUES ('RAIN', 'easy');
INSERT INTO words (word, word_level) VALUES ('FIRE', 'easy');
INSERT INTO words (word, word_level) VALUES ('BIRD', 'easy');
INSERT INTO words (word, word_level) VALUES ('SAND', 'easy');
INSERT INTO words (word, word_level) VALUES ('KING', 'easy');
INSERT INTO words (word, word_level) VALUES ('BEAR', 'easy');
INSERT INTO words (word, word_level) VALUES ('WOLF', 'easy');
INSERT INTO words (word, word_level) VALUES ('DEER', 'easy');
INSERT INTO words (word, word_level) VALUES ('MINT', 'easy');
INSERT INTO words (word, word_level) VALUES ('GOLD', 'easy');
INSERT INTO words (word, word_level) VALUES ('SNOW', 'easy');
INSERT INTO words (word, word_level) VALUES ('LAMP', 'easy');
INSERT INTO words (word, word_level) VALUES ('BELL', 'easy');
INSERT INTO words (word, word_level) VALUES ('RING', 'easy');
INSERT INTO words (word, word_level) VALUES ('MILK', 'easy');
INSERT INTO words (word, word_level) VALUES ('CAKE', 'easy');
INSERT INTO words (word, word_level) VALUES ('FORK', 'easy');
INSERT INTO words (word, word_level) VALUES ('DUCK', 'easy');
INSERT INTO words (word, word_level) VALUES ('ROAD', 'easy');

-- Kata Medium (5 huruf)
INSERT INTO words (word, word_level) VALUES ('APPLE', 'medium');
INSERT INTO words (word, word_level) VALUES ('GRASS', 'medium');
INSERT INTO words (word, word_level) VALUES ('CLOUD', 'medium');
INSERT INTO words (word, word_level) VALUES ('BRAVE', 'medium');
INSERT INTO words (word, word_level) VALUES ('SNAKE', 'medium');
INSERT INTO words (word, word_level) VALUES ('PLANT', 'medium');
INSERT INTO words (word, word_level) VALUES ('RIVER', 'medium');
INSERT INTO words (word, word_level) VALUES ('SWEET', 'medium');
INSERT INTO words (word, word_level) VALUES ('STONE', 'medium');
INSERT INTO words (word, word_level) VALUES ('LIGHT', 'medium');
INSERT INTO words (word, word_level) VALUES ('CHAIR', 'medium');
INSERT INTO words (word, word_level) VALUES ('WATER', 'medium');
INSERT INTO words (word, word_level) VALUES ('SMILE', 'medium');
INSERT INTO words (word, word_level) VALUES ('HOUSE', 'medium');
INSERT INTO words (word, word_level) VALUES ('BREAD', 'medium');
INSERT INTO words (word, word_level) VALUES ('CROWN', 'medium');
INSERT INTO words (word, word_level) VALUES ('HEART', 'medium');
INSERT INTO words (word, word_level) VALUES ('MOUSE', 'medium');
INSERT INTO words (word, word_level) VALUES ('TRAIN', 'medium');
INSERT INTO words (word, word_level) VALUES ('SWORD', 'medium');
INSERT INTO words (word, word_level) VALUES ('HORSE', 'medium');
INSERT INTO words (word, word_level) VALUES ('PLANE', 'medium');
INSERT INTO words (word, word_level) VALUES ('BRAIN', 'medium');
INSERT INTO words (word, word_level) VALUES ('FRUIT', 'medium');
INSERT INTO words (word, word_level) VALUES ('DREAM', 'medium');
INSERT INTO words (word, word_level) VALUES ('SHEEP', 'medium');
INSERT INTO words (word, word_level) VALUES ('GRAPE', 'medium');
INSERT INTO words (word, word_level) VALUES ('SPOON', 'medium');

-- Kata Hard (6 huruf)
INSERT INTO words (word, word_level) VALUES ('BANANA', 'hard');
INSERT INTO words (word, word_level) VALUES ('TUNNEL', 'hard');
INSERT INTO words (word, word_level) VALUES ('SILVER', 'hard');
INSERT INTO words (word, word_level) VALUES ('BUTTON', 'hard');
INSERT INTO words (word, word_level) VALUES ('POETRY', 'hard');
INSERT INTO words (word, word_level) VALUES ('ROCKET', 'hard');
INSERT INTO words (word, word_level) VALUES ('MONKEY', 'hard');
INSERT INTO words (word, word_level) VALUES ('FUTURE', 'hard');
INSERT INTO words (word, word_level) VALUES ('CANDLE', 'hard');
INSERT INTO words (word, word_level) VALUES ('DRAGON', 'hard');
INSERT INTO words (word, word_level) VALUES ('PEPPER', 'hard');
INSERT INTO words (word, word_level) VALUES ('LADDER', 'hard');
INSERT INTO words (word, word_level) VALUES ('GUITAR', 'hard');
INSERT INTO words (word, word_level) VALUES ('SPRING', 'hard');
INSERT INTO words (word, word_level) VALUES ('ORANGE', 'hard');
INSERT INTO words (word, word_level) VALUES ('POCKET', 'hard');
INSERT INTO words (word, word_level) VALUES ('PLANET', 'hard');
INSERT INTO words (word, word_level) VALUES ('MARKET', 'hard');
INSERT INTO words (word, word_level) VALUES ('BRIDGE', 'hard');
INSERT INTO words (word, word_level) VALUES ('GARDEN', 'hard');
INSERT INTO words (word, word_level) VALUES ('ANIMAL', 'hard');
INSERT INTO words (word, word_level) VALUES ('BOTTLE', 'hard');
INSERT INTO words (word, word_level) VALUES ('SHADOW', 'hard');
INSERT INTO words (word, word_level) VALUES ('STREAM', 'hard');
INSERT INTO words (word, word_level) VALUES ('CASTLE', 'hard');
INSERT INTO words (word, word_level) VALUES ('POWDER', 'hard');
INSERT INTO words (word, word_level) VALUES ('FLOWER', 'hard');
INSERT INTO words (word, word_level) VALUES ('WINDOW', 'hard');

-- Level Puzzle (12 level)
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (1,  2, 'Level 1 (2x2)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (2,  3, 'Level 2 (3x3)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (3,  3, 'Level 3 (3x3)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (4,  4, 'Level 4 (4x4)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (5,  4, 'Level 5 (4x4)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (6,  5, 'Level 6 (5x5)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (7,  5, 'Level 7 (5x5)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (8,  6, 'Level 8 (6x6)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (9,  7, 'Level 9 (7x7)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (10, 8, 'Level 10 (8x8)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (11, 9, 'Level 11 (9x9)');
INSERT INTO puzzle_levels (level_num, grid_size, level_label) VALUES (12, 10, 'Level 12 (10x10)');

COMMIT;


-- ============================================================
-- BAGIAN 7: GRANT PRIVILEGE (jalankan sebagai SYSDBA)
-- ============================================================

GRANT SELECT, INSERT, UPDATE, DELETE ON kebox.users         TO kebox_app_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON kebox.words         TO kebox_app_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON kebox.puzzle_levels TO kebox_app_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON kebox.scores        TO kebox_app_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON kebox.game_sessions TO kebox_app_role;
GRANT SELECT ON kebox.vw_leaderboard_word   TO kebox_app_role;
GRANT SELECT ON kebox.vw_leaderboard_puzzle TO kebox_app_role;
GRANT SELECT ON kebox.vw_user_stats         TO kebox_app_role;

GRANT SELECT ON kebox.users         TO kebox_readonly_role;
GRANT SELECT ON kebox.words         TO kebox_readonly_role;
GRANT SELECT ON kebox.puzzle_levels TO kebox_readonly_role;
GRANT SELECT ON kebox.scores        TO kebox_readonly_role;
GRANT SELECT ON kebox.vw_user_stats TO kebox_readonly_role;


-- ============================================================
-- VERIFIKASI (jalankan untuk mengecek hasil)
-- ============================================================
-- SELECT table_name FROM user_tables ORDER BY table_name;
-- SELECT index_name, table_name FROM user_indexes ORDER BY table_name;
-- SELECT constraint_name, constraint_type, table_name FROM user_constraints WHERE table_name NOT LIKE 'SYS%' ORDER BY table_name;
-- SELECT COUNT(*) FROM words;
-- SELECT COUNT(*) FROM puzzle_levels;
-- SELECT * FROM vw_user_stats;
