<?php
/**
 * Formulario de autenticación (login) del sistema ODF Tracker.
 *
 * Es la primera barrera de seguridad: sólo envía las credenciales por POST a
 * api/auth_login.php acompañadas del token CSRF. Si ya existe una sesión
 * activa, redirige directamente al dashboard operativo.
 */
session_start();
require_once 'classes/Csrf.php';
if (isset($_SESSION['usuario'])) {

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ODF Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            background-color: #050b14;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Abstract glowing background lines */
        .bg-lines {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                linear-gradient(90deg, #050b14 21px, transparent 1%) center,
                linear-gradient(#050b14 21px, transparent 1%) center,
                rgba(0, 162, 255, 0.08);
            background-size: 22px 22px;
            z-index: 0;
            pointer-events: none;
            animation: grid-flow 5s linear infinite;
        }

        .login-container {
            position: relative;
            z-index: 3;
            background: rgba(4, 15, 30, 0.7);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(0, 162, 255, 0.3);
            box-shadow: 
                0 0 20px rgba(0, 162, 255, 0.1),
                inset 0 0 20px rgba(0, 162, 255, 0.05);
            backdrop-filter: blur(10px);
            animation: login-in 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        /* Sci-fi decorative borders */
        .login-container::before, .login-container::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            border-color: #00a2ff;
            border-style: solid;
        }
        
        .login-container::before {
            top: -1px; left: -1px;
            border-width: 2px 0 0 2px;
            border-top-left-radius: 12px;
        }
        
        .login-container::after {
            bottom: -1px; right: -1px;
            border-width: 0 2px 2px 0;
            border-bottom-right-radius: 12px;
        }

        @keyframes grid-flow {
            from { background-position: 0px 0px, 0px 0px; }
            to   { background-position: 22px 22px, 22px 22px; }
        }

        /* Capa canvas: malla de enlaces opticos animada (nodos + pulsos de luz) */
        #tech-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        /* Orbes de luz corporativa en deriva lenta */
        .glow-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            pointer-events: none;
            opacity: 0.62;
        }

        .glow-orb--cyan {
            width: 46vmax; height: 46vmax;
            top: -18vmax; left: -14vmax;
            background: radial-gradient(circle, rgba(0, 162, 255, 0.45), rgba(0, 162, 255, 0) 68%);
            animation: orb-drift-a 24s ease-in-out infinite alternate;
        }

        .glow-orb--teal {
            width: 38vmax; height: 38vmax;
            right: -12vmax; bottom: -16vmax;
            background: radial-gradient(circle, rgba(0, 255, 214, 0.25), rgba(0, 255, 214, 0) 68%);
            animation: orb-drift-b 30s ease-in-out infinite alternate;
        }

        @keyframes orb-drift-a {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to   { transform: translate3d(5vmax, 4vmax, 0) scale(1.18); }
        }

        @keyframes orb-drift-b {
            from { transform: translate3d(0, 0, 0) scale(1.12); }
            to   { transform: translate3d(-6vmax, -4vmax, 0) scale(0.94); }
        }

        @keyframes login-in {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .bg-lines, .glow-orb, .login-container { animation: none; }
        }

        .title {
            font-family: 'Orbitron', sans-serif;
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 162, 255, 0.8);
        }

        .title span {
            color: #00a2ff;
        }

        .input-group-custom {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group-custom label {
            color: #00a2ff;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: block;
            font-weight: 500;
        }

        .form-control-neon {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 162, 255, 0.4);
            color: #fff;
            border-radius: 4px;
            padding: 12px 15px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-control-neon:focus {
            outline: none;
            border-color: #00a2ff;
            background: rgba(0, 162, 255, 0.05);
            box-shadow: 0 0 15px rgba(0, 162, 255, 0.4);
        }

        .form-control-neon::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .btn-neon {
            background: transparent;
            color: #00a2ff;
            border: 1px solid #00a2ff;
            padding: 12px;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            border-radius: 4px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .btn-neon:disabled {
            opacity: 0.75;
            cursor: progress;
        }

        .btn-neon:focus-visible {
            outline: 2px solid #7ce0ff;
            outline-offset: 2px;
        }

        .btn-neon .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
            vertical-align: -0.15em;
        }

        .btn-neon:hover {
            background: #00a2ff;
            color: #000;
            box-shadow: 0 0 20px rgba(0, 162, 255, 0.6);
        }

        .alert-neon {
            background: rgba(255, 51, 102, 0.1);
            border: 1px solid #ff3366;
            color: #ff3366;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
            box-shadow: 0 0 10px rgba(255, 51, 102, 0.2);
        }
    </style>
</head>
<body>

<canvas id="tech-bg" aria-hidden="true"></canvas>
<div class="glow-orb glow-orb--cyan" aria-hidden="true"></div>
<div class="glow-orb glow-orb--teal" aria-hidden="true"></div>
<div class="bg-lines" aria-hidden="true"></div>

<div class="login-container">
    <h2 class="title">ODF <span>Tracker</span></h2>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert-neon">
            <i class="bi bi-exclamation-triangle me-2"></i>Credenciales inválidas. Acceso denegado.
        </div>
    <?php endif; ?>

    <form action="api/auth_login.php" method="POST" id="formLogin">
        <?= Csrf::field() ?>

        <div class="input-group-custom">
            <label for="correo">Usuario / Email</label>
            <input type="email" class="form-control-neon" id="correo" name="correo" placeholder="admin@telefonica.com" required>
        </div>
        
        <div class="input-group-custom">
            <label for="password">Contraseña de Acceso</label>
            <input type="password" class="form-control-neon" id="password" name="password" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn-neon" id="btnIngresar">
            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>INGRESAR
        </button>
    </form>
</div>

<script>
    /* Fondo tecnologico animado: malla de enlaces opticos con pulsos de luz y trafico de datos */
    (function () {
        'use strict';

        const canvas = document.getElementById('tech-bg');
        if (!canvas || !canvas.getContext) { return; }

        const ctx = canvas.getContext('2d');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const LINK_DIST = 168;
        const MAX_PULSES = 6;
        const mouse = { x: -9999, y: -9999, active: false };

        let width = 0;
        let height = 0;
        let frame = 0;
        let rafId = null;
        let nodes = [];
        let pulses = [];

        function rand(min, max) { return Math.random() * (max - min) + min; }

        function buildNodes() {
            const total = Math.max(46, Math.min(130, Math.round((width * height) / 14500)));
            nodes = [];
            for (let i = 0; i < total; i++) {
                nodes.push({
                    x: rand(0, width),
                    y: rand(0, height),
                    vx: rand(-0.3, 0.3),
                    vy: rand(-0.3, 0.3),
                    r: rand(0.9, 2.1)
                });
            }
            pulses = [];
        }

        function resize() {
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            width = canvas.clientWidth;
            height = canvas.clientHeight;
            canvas.width = Math.round(width * dpr);
            canvas.height = Math.round(height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            buildNodes();
            render();
        }

        function spawnPulse() {
            if (pulses.length >= MAX_PULSES || nodes.length < 2) { return; }
            const from = nodes[Math.floor(Math.random() * nodes.length)];
            let to = null;
            let best = LINK_DIST;
            for (let i = 0; i < nodes.length; i++) {
                const n = nodes[i];
                if (n === from) { continue; }
                const d = Math.hypot(n.x - from.x, n.y - from.y);
                if (d < best) { best = d; to = n; }
            }
            if (to) { pulses.push({ from: from, to: to, t: 0, speed: rand(0.01, 0.024) }); }
        }

        function move() {
            frame++;
            for (let i = 0; i < nodes.length; i++) {
                const n = nodes[i];
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < -30) { n.x = width + 30; } else if (n.x > width + 30) { n.x = -30; }
                if (n.y < -30) { n.y = height + 30; } else if (n.y > height + 30) { n.y = -30; }
            }
            if (frame % 42 === 0) { spawnPulse(); }
        }

        function render() {
            ctx.clearRect(0, 0, width, height);

            // Enlaces de la malla de fibra
            for (let i = 0; i < nodes.length; i++) {
                const a = nodes[i];
                for (let j = i + 1; j < nodes.length; j++) {
                    const b = nodes[j];
                    const dx = a.x - b.x;
                    const dy = a.y - b.y;
                    const d2 = dx * dx + dy * dy;
                    if (d2 > LINK_DIST * LINK_DIST) { continue; }
                    const alpha = (1 - Math.sqrt(d2) / LINK_DIST) * 0.42;
                    ctx.strokeStyle = 'rgba(0, 162, 255, ' + alpha.toFixed(3) + ')';
                    ctx.lineWidth = 0.7;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }

            // Halo e hilos reactivos al cursor
            if (mouse.active) {
                const halo = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 170);
                halo.addColorStop(0, 'rgba(0, 162, 255, 0.16)');
                halo.addColorStop(1, 'rgba(0, 162, 255, 0)');
                ctx.fillStyle = halo;
                ctx.beginPath();
                ctx.arc(mouse.x, mouse.y, 170, 0, Math.PI * 2);
                ctx.fill();

                for (let i = 0; i < nodes.length; i++) {
                    const n = nodes[i];
                    const d = Math.hypot(n.x - mouse.x, n.y - mouse.y);
                    if (d > 180) { continue; }
                    ctx.strokeStyle = 'rgba(120, 225, 255, ' + ((1 - d / 180) * 0.45).toFixed(3) + ')';
                    ctx.lineWidth = 0.8;
                    ctx.beginPath();
                    ctx.moveTo(mouse.x, mouse.y);
                    ctx.lineTo(n.x, n.y);
                    ctx.stroke();
                }
            }

            // Pulsos de luz viajando por los enlaces (trafico optico activo)
            for (let k = pulses.length - 1; k >= 0; k--) {
                const p = pulses[k];
                p.t += p.speed;
                if (p.t >= 1) { pulses.splice(k, 1); continue; }
                const tail = Math.max(0, p.t - 0.25);
                const x1 = p.from.x + (p.to.x - p.from.x) * tail;
                const y1 = p.from.y + (p.to.y - p.from.y) * tail;
                const x2 = p.from.x + (p.to.x - p.from.x) * p.t;
                const y2 = p.from.y + (p.to.y - p.from.y) * p.t;
                const grad = ctx.createLinearGradient(x1, y1, x2, y2);
                grad.addColorStop(0, 'rgba(160, 240, 255, 0)');
                grad.addColorStop(1, 'rgba(160, 240, 255, 0.9)');
                ctx.strokeStyle = grad;
                ctx.lineWidth = 1.7;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.stroke();
                ctx.fillStyle = 'rgba(225, 250, 255, 0.95)';
                ctx.beginPath();
                ctx.arc(x2, y2, 1.9, 0, Math.PI * 2);
                ctx.fill();
            }

            // Conectores opticos (nodos)
            for (let i = 0; i < nodes.length; i++) {
                const n = nodes[i];
                ctx.fillStyle = 'rgba(0, 162, 255, 0.55)';
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                ctx.fill();
                ctx.fillStyle = 'rgba(190, 240, 255, 0.85)';
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r * 0.45, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function loop() {
            move();
            render();
            rafId = window.requestAnimationFrame(loop);
        }

        function start() {
            if (rafId === null && !reduceMotion) { rafId = window.requestAnimationFrame(loop); }
        }

        function stop() {
            if (rafId !== null) { window.cancelAnimationFrame(rafId); rafId = null; }
        }
        window.addEventListener('resize', resize);
        window.addEventListener('pointermove', function (e) {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
            mouse.active = true;
        });
        document.addEventListener('mouseleave', function () { mouse.active = false; });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { stop(); } else { start(); }
        });

        resize();
        if (reduceMotion) { render(); } else { start(); }

        // Anti doble envio: estado de carga del boton INGRESAR
        const form = document.getElementById('formLogin');
        const btn = document.getElementById('btnIngresar');
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm me-2';
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                btn.insertBefore(spinner, btn.firstChild);
            });
        }
    })();
</script>
</body>
</html>
