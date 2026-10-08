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
    <link rel="stylesheet" href="assets/css/login.css">
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
