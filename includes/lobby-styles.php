.section-label {
    text-align:center; font-family:'Orbitron',monospace;
    font-size:.65rem; letter-spacing:2px; color:var(--text-muted); margin-bottom:.8rem;
}
.code-input {
    text-transform:uppercase; letter-spacing:4px; text-align:center;
    font-family:'Orbitron',monospace; font-size:1.2rem;
}
.lobby-card {
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:16px; padding:2rem; box-shadow:var(--shadow-purple);
}
.lobby-code-wrap { display:flex; align-items:center; justify-content:center; gap:.8rem; margin-bottom:.5rem; }
.lobby-code {
    font-family:'Orbitron',monospace; font-size:2.2rem; font-weight:900;
    letter-spacing:8px; color:var(--purple-bright);
    text-shadow:0 0 20px rgba(124,77,255,.4);
    padding:.3rem .6rem;
    background:rgba(124,77,255,.07); border:1px solid rgba(124,77,255,.2); border-radius:10px;
}
.copy-btn {
    background:rgba(45,31,94,.6); border:1px solid var(--border); border-radius:8px;
    padding:.5rem .7rem; font-size:1.1rem; cursor:pointer;
    color:var(--text-dim); transition:all .2s; line-height:1;
}
.copy-btn:hover { border-color:var(--purple-mid); color:var(--text-main); }
.player-list { display:flex; flex-direction:column; gap:.75rem; margin-bottom:1.5rem; }
.player-slot {
    display:flex; align-items:center; gap:.9rem;
    padding:.85rem 1rem; border-radius:12px; border:1px solid; transition:all .3s;
}
.player-slot.filled  { background:rgba(124,77,255,.1); border-color:rgba(124,77,255,.35); }
.player-slot.waiting { background:rgba(45,31,94,.3); border-color:var(--border); border-style:dashed; }
.slot-avatar {
    width:42px; height:42px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-family:'Orbitron',monospace; font-weight:700; font-size:.9rem;
    background:rgba(124,77,255,.2); border:2px solid rgba(124,77,255,.4);
    color:var(--purple-bright); flex-shrink:0;
}
.slot-avatar.ghost { background:rgba(45,31,94,.4); border-color:var(--border); color:var(--text-muted); }
.slot-info { flex:1; }
.slot-name { font-weight:700; font-size:.95rem; color:var(--text-main); }
.slot-role { font-size:.75rem; color:var(--text-muted); margin-top:.1rem; }
.slot-status { font-size:1.1rem; flex-shrink:0; }
.slot-status.ready { color:#00c853; }
.pulse-dot {
    display:inline-block; width:10px; height:10px;
    background:var(--accent-cyan); border-radius:50%;
    animation:pulse-anim 1.2s ease-in-out infinite;
}
@keyframes pulse-anim { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.7)} }
.waiting-msg {
    text-align:center; padding:.9rem;
    background:rgba(0,229,255,.06); border:1px solid rgba(0,229,255,.2);
    border-radius:10px; color:var(--accent-cyan); font-size:.9rem; margin-bottom:.75rem;
}
.btn-success {
    background:linear-gradient(135deg,#00c853,#00a843) !important;
    border-color:#00c853 !important; color:#fff !important; font-weight:700 !important;
}
.btn-success:disabled {
    opacity:.45 !important; cursor:not-allowed !important;
    background:rgba(45,31,94,.5) !important;
    border-color:var(--border) !important; color:var(--text-muted) !important;
}
.mt-2 { margin-top:.6rem; }
