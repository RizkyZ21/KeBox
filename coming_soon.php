<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Coming Soon — KeBox</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:          #07040f;
    --purple:      #7c4dff;
    --purple-dim:  #3b2a6e;
    --purple-glow: #a57eff;
    --cyan:        #00e5ff;
    --text:        #e8e0ff;
    --muted:       #5a4a8a;
  }

  html, body {
    min-height: 100vh;
    background: var(--bg);
    color: var(--text);
    font-family: 'Rajdhani', sans-serif;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* ── CANVAS BACKGROUND ── */
  canvas {
    position: fixed;
    inset: 0;
    z-index: 0;
    opacity: 0.5;
  }

  /* ── RADIAL GLOW ── */
  .glow-bg {
    position: fixed;
    inset: 0;
    z-index: 1;
    background:
      radial-gradient(ellipse 70% 60% at 50% 50%, rgba(124,77,255,0.12) 0%, transparent 70%),
      radial-gradient(ellipse 40% 40% at 20% 80%, rgba(0,229,255,0.05) 0%, transparent 60%);
    pointer-events: none;
  }

  /* ── MAIN CONTENT ── */
  .stage {
    position: relative;
    z-index: 10;
    text-align: center;
    padding: 2rem;
    animation: fadeIn 1.2s ease both;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* ── LOGO ── */
  .logo {
    font-family: 'Orbitron', monospace;
    font-size: clamp(2.5rem, 8vw, 5rem);
    font-weight: 900;
    letter-spacing: 6px;
    background: linear-gradient(135deg, #fff 0%, var(--purple-glow) 50%, var(--cyan) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: none;
    animation: fadeIn 1s ease 0.2s both;
  }

  /* ── COMING SOON TEXT ── */
  .coming-label {
    font-family: 'Orbitron', monospace;
    font-size: clamp(0.55rem, 2vw, 0.85rem);
    letter-spacing: 8px;
    color: var(--cyan);
    text-transform: uppercase;
    margin: 0.6rem 0 2.5rem;
    opacity: 0.8;
    animation: fadeIn 1s ease 0.4s both;
  }

  /* ── BIG TITLE ── */
  .big-title {
    font-family: 'Orbitron', monospace;
    font-size: clamp(2rem, 9vw, 6.5rem);
    font-weight: 900;
    line-height: 1;
    color: #fff;
    text-shadow: 0 0 60px rgba(124,77,255,0.4), 0 0 120px rgba(124,77,255,0.15);
    animation: fadeIn 1s ease 0.6s both;
    position: relative;
  }

  .big-title .line2 {
    display: block;
    background: linear-gradient(90deg, var(--purple-glow), var(--cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* ── DIVIDER ── */
  .divider {
    width: 120px;
    height: 2px;
    margin: 2rem auto;
    background: linear-gradient(90deg, transparent, var(--purple), var(--cyan), var(--purple), transparent);
    animation: fadeIn 1s ease 0.8s both;
  }

  /* ── SUBTITLE ── */
  .subtitle {
    font-size: clamp(0.9rem, 2.5vw, 1.1rem);
    color: var(--muted);
    letter-spacing: 1px;
    max-width: 380px;
    margin: 0 auto 3rem;
    line-height: 1.7;
    font-weight: 300;
    animation: fadeIn 1s ease 1s both;
  }

  /* ── COUNTDOWN ── */
  .countdown-wrap {
    display: flex;
    justify-content: center;
    gap: clamp(0.8rem, 3vw, 2rem);
    margin-bottom: 3rem;
    animation: fadeIn 1s ease 1.1s both;
  }

  .cd-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
  }

  .cd-num {
    font-family: 'Orbitron', monospace;
    font-size: clamp(2rem, 6vw, 3.5rem);
    font-weight: 700;
    color: #fff;
    background: rgba(124,77,255,0.12);
    border: 1px solid rgba(124,77,255,0.3);
    border-radius: 12px;
    width: clamp(70px, 15vw, 100px);
    height: clamp(70px, 15vw, 100px);
    display: flex;
    align-items: center;
    justify-content: center;
    text-shadow: 0 0 20px rgba(124,77,255,0.5);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
  }

  .cd-num::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(255,255,255,0.04) 0%, transparent 50%);
    border-radius: 12px;
  }

  .cd-lbl {
    font-size: 0.65rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--muted);
    font-weight: 600;
  }

  .cd-sep {
    font-family: 'Orbitron', monospace;
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    color: var(--purple);
    align-self: center;
    margin-bottom: 1.4rem;
    animation: blink 1s step-end infinite;
  }

  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

  /* ── BACK BUTTON ── */
  .back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.8rem;
    border-radius: 10px;
    background: transparent;
    border: 1.5px solid rgba(124,77,255,0.5);
    color: var(--purple-glow);
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.25s;
    animation: fadeIn 1s ease 1.3s both;
  }

  .back-btn:hover {
    background: rgba(124,77,255,0.15);
    border-color: var(--purple);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(124,77,255,0.25);
  }

  /* ── FLOATING PARTICLES (CSS) ── */
  .particles {
    position: fixed;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    overflow: hidden;
  }

  .particle {
    position: absolute;
    border-radius: 50%;
    animation: floatUp linear infinite;
    opacity: 0;
  }

  @keyframes floatUp {
    0%   { transform: translateY(100vh) scale(0); opacity: 0; }
    10%  { opacity: 1; }
    90%  { opacity: 0.6; }
    100% { transform: translateY(-10vh) scale(1.2); opacity: 0; }
  }
</style>
</head>
<body>


<div class="stage">
  <div class="logo">KeBox</div>
  <div class="coming-label">— Segera Hadir —</div>

  <div class="big-title">
    Coming<br>
    <span class="line2">Soon</span>
  </div>

  <div class="divider"></div>

  <p class="subtitle">Fitur baru sedang dalam pengembangan.<br>Nantikan update selanjutnya!</p>


  <a href="dashboard.php" class="back-btn">← Kembali ke Beranda</a>
</div>

<script>
// ── CANVAS STAR FIELD
const canvas = document.getElementById('bg');
const ctx = canvas.getContext('2d');
let stars = [];

function resize() {
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;
  initStars();
}

function initStars() {
  stars = Array.from({ length: 140 }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 1.2 + 0.2,
    a: Math.random(),
    da: (Math.random() - 0.5) * 0.008,
    dx: (Math.random() - 0.5) * 0.1,
    dy: (Math.random() - 0.5) * 0.1,
  }));
}

function drawStars() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  stars.forEach(s => {
    s.a += s.da;
    if (s.a <= 0 || s.a >= 1) s.da *= -1;
    s.x += s.dx;
    s.y += s.dy;
    if (s.x < 0) s.x = canvas.width;
    if (s.x > canvas.width) s.x = 0;
    if (s.y < 0) s.y = canvas.height;
    if (s.y > canvas.height) s.y = 0;
    ctx.beginPath();
    ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
    ctx.fillStyle = `rgba(200, 180, 255, ${s.a})`;
    ctx.fill();
  });
  requestAnimationFrame(drawStars);
}

window.addEventListener('resize', resize);
resize();
drawStars();

// ── FLOATING PARTICLES
const pContainer = document.getElementById('particles');
const colors = ['rgba(124,77,255,', 'rgba(0,229,255,', 'rgba(165,126,255,'];

for (let i = 0; i < 18; i++) {
  const p = document.createElement('div');
  p.className = 'particle';
  const size = Math.random() * 4 + 2;
  const color = colors[Math.floor(Math.random() * colors.length)];
  p.style.cssText = `
    width:${size}px; height:${size}px;
    left:${Math.random()*100}%;
    background:${color}${Math.random()*0.6+0.2});
    animation-duration:${Math.random()*12+8}s;
    animation-delay:${Math.random()*10}s;
    box-shadow:0 0 ${size*3}px ${color}0.5);
  `;
  pContainer.appendChild(p);
}
</script>
</body>
</html>