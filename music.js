// ── KeBox Background Music Player ─────────────────────────────────
(function () {
    const STORAGE_KEY  = 'kebox_music_on';
    const TIME_KEY     = 'kebox_music_time';
    const TIME_INT_KEY = 'kebox_music_time_at';
    const MUSIC_FILE   = '/kebox/assets/music/bgm.mp3';

    let audio  = null;
    let btn    = null;
    let isOn   = localStorage.getItem(STORAGE_KEY) !== 'false'; // default ON
    let timeSaveIv = null;

    // ── Audio instance ──────────────────────────────────────────────
    function getAudio() {
        if (!audio) {
            audio = new Audio(MUSIC_FILE);
            audio.loop   = true;
            audio.volume = 0.05;
            audio.addEventListener('error', () => {
                console.warn('KeBox BGM: file tidak ditemukan di', MUSIC_FILE);
            });
        }
        return audio;
    }

    // ── Simpan posisi tiap 500ms supaya tab baru bisa lanjut ────────
    function startSavingTime() {
        if (timeSaveIv) return;
        timeSaveIv = setInterval(() => {
            if (audio && !audio.paused) {
                localStorage.setItem(TIME_KEY,     audio.currentTime.toFixed(2));
                localStorage.setItem(TIME_INT_KEY, Date.now().toString());
            }
        }, 500);
    }

    function stopSavingTime() {
        clearInterval(timeSaveIv);
        timeSaveIv = null;
    }

    // ── Hitung posisi real saat tab baru dibuka ─────────────────────
    function getSyncedTime(duration) {
        const savedTime  = parseFloat(localStorage.getItem(TIME_KEY)  || '0');
        const savedAt    = parseInt(localStorage.getItem(TIME_INT_KEY) || '0', 10);
        if (!savedAt || !duration) return savedTime;
        const elapsed = (Date.now() - savedAt) / 1000; // detik berlalu sejak terakhir disimpan
        return (savedTime + elapsed) % duration;       // loop-aware
    }

    // ── Play dengan posisi tersinkron ───────────────────────────────
    function play() {
        const a = getAudio();
        const tryPlay = () => {
            // Set posisi tersinkron sebelum play
            if (a.duration) {
                a.currentTime = getSyncedTime(a.duration);
            }
            a.play().catch(() => {});
            startSavingTime();
        };

        if (a.readyState >= 1) { // metadata sudah ada
            tryPlay();
        } else {
            a.addEventListener('loadedmetadata', tryPlay, { once: true });
            a.load();
        }
    }

    function pause() {
        if (audio) {
            // Simpan posisi terakhir sebelum pause
            localStorage.setItem(TIME_KEY,     audio.currentTime.toFixed(2));
            localStorage.setItem(TIME_INT_KEY, Date.now().toString());
            audio.pause();
        }
        stopSavingTime();
    }

    // ── Sync UI tombol ──────────────────────────────────────────────
    function syncBtn() {
        if (!btn) return;
        if (isOn) {
            btn.textContent = '🎵';
            btn.title       = 'Matikan musik';
            btn.classList.add('music-on');
            btn.classList.remove('music-off');
        } else {
            btn.textContent = '🔇';
            btn.title       = 'Nyalakan musik';
            btn.classList.remove('music-on');
            btn.classList.add('music-off');
        }
    }

    // ── Toggle on/off ───────────────────────────────────────────────
    function toggle() {
        isOn = !isOn;
        localStorage.setItem(STORAGE_KEY, isOn);
        isOn ? play() : pause();
        syncBtn();
    }

    // ── Init ────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        btn = document.getElementById('music-toggle-btn');
        if (!btn) return;
        btn.addEventListener('click', toggle);
        syncBtn();

        if (isOn) {
            // Autoplay butuh interaksi user pertama — coba langsung, kalau gagal tunggu klik
            play();
            // Fallback: coba lagi di klik pertama jika autoplay diblok
            const tryOnce = () => { if (isOn) play(); document.removeEventListener('click', tryOnce); };
            document.addEventListener('click', tryOnce, { once: true });
        }
    });

    // ── Simpan posisi saat tab ditutup/pindah ───────────────────────
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && audio && !audio.paused) {
            localStorage.setItem(TIME_KEY,     audio.currentTime.toFixed(2));
            localStorage.setItem(TIME_INT_KEY, Date.now().toString());
        }
    });

    window.addEventListener('beforeunload', () => {
        if (audio && !audio.paused) {
            localStorage.setItem(TIME_KEY,     audio.currentTime.toFixed(2));
            localStorage.setItem(TIME_INT_KEY, Date.now().toString());
        }
    });
})();
