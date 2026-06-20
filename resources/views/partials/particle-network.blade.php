{{-- Particle network background for 'particle-network' layout --}}
@php
    $particleColor = config('tyro-login.particle_network.color', '#0f172a');
    $particleDensity = (int) config('tyro-login.particle_network.density', 80);
    $particleLink = (int) config('tyro-login.particle_network.link_distance', 130);
    $particleInteractive = config('tyro-login.particle_network.interactive', true);
@endphp
<canvas id="tyro-particle-canvas"
        data-color="{{ $particleColor }}"
        data-density="{{ $particleDensity }}"
        data-link="{{ $particleLink }}"
        data-interactive="{{ $particleInteractive ? '1' : '0' }}"></canvas>

<script>
    (function () {
        'use strict';

        var canvas = document.getElementById('tyro-particle-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        var W, H, DPR;

        function resize() {
            DPR = Math.min(window.devicePixelRatio || 1, 2);
            W = window.innerWidth;
            H = window.innerHeight;
            canvas.width = W * DPR;
            canvas.height = H * DPR;
            canvas.style.width = W + 'px';
            canvas.style.height = H + 'px';
            ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
        }
        window.addEventListener('resize', function () { resize(); seed(); });
        resize();

        // ─── Color parsing ───────────────────────────────────
        function parseColor(input) {
            var s = (input || '').trim();
            if (s.charAt(0) === '#') {
                var hex = s.slice(1);
                if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
                var num = parseInt(hex, 16);
                return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
            }
            var m = s.match(/rgba?\(([^)]+)\)/i);
            if (m) {
                var parts = m[1].split(',').map(function (p) { return parseFloat(p); });
                return { r: parts[0] || 0, g: parts[1] || 0, b: parts[2] || 0 };
            }
            return { r: 15, g: 23, b: 42 };
        }

        var base = parseColor(canvas.getAttribute('data-color'));
        var DENSITY = parseInt(canvas.getAttribute('data-density'), 10) || 80;
        var LINK_DIST = parseInt(canvas.getAttribute('data-link'), 10) || 130;
        var INTERACTIVE = canvas.getAttribute('data-interactive') === '1';

        // Decide node color: bright relative to base luminance
        function luminance(c) { return 0.2126 * c.r + 0.7152 * c.g + 0.0722 * c.b; }
        var nodeTone = luminance(base) > 140 ? '0,0,0' : '255,255,255';

        var particles = [];
        var mouse = { x: -9999, y: -9999, active: false };

        function seed() {
            var count = Math.round(DENSITY * (W * H) / (1280 * 720));
            if (count < 20) count = 20;
            particles = [];
            for (var i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * W,
                    y: Math.random() * H,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    r: 1 + Math.random() * 1.6
                });
            }
        }
        seed();

        if (INTERACTIVE) {
            window.addEventListener('mousemove', function (e) {
                mouse.x = e.clientX;
                mouse.y = e.clientY;
                mouse.active = true;
            });
            window.addEventListener('mouseleave', function () { mouse.active = false; });
        }

        function drawBackground() {
            ctx.fillStyle = 'rgb(' + base.r + ',' + base.g + ',' + base.b + ')';
            ctx.fillRect(0, 0, W, H);
        }

        function animate() {
            if (canvas.width !== W * DPR || canvas.height !== H * DPR) { resize(); }
            drawBackground();

            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                p.x += p.vx;
                p.y += p.vy;

                if (p.x < 0 || p.x > W) p.vx *= -1;
                if (p.y < 0 || p.y > H) p.vy *= -1;

                if (INTERACTIVE && mouse.active) {
                    var dx = p.x - mouse.x, dy = p.y - mouse.y;
                    var dist = Math.hypot(dx, dy);
                    if (dist < 120) {
                        var force = (120 - dist) / 120;
                        p.x += (dx / dist) * force * 1.5;
                        p.y += (dy / dist) * force * 1.5;
                    }
                }

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(' + nodeTone + ',0.85)';
                ctx.fill();
            }

            for (var a = 0; a < particles.length; a++) {
                for (var b = a + 1; b < particles.length; b++) {
                    var pa = particles[a], pb = particles[b];
                    var ddx = pa.x - pb.x, ddy = pa.y - pb.y;
                    var d = Math.hypot(ddx, ddy);
                    if (d < LINK_DIST) {
                        var alpha = (1 - d / LINK_DIST) * 0.5;
                        ctx.strokeStyle = 'rgba(' + nodeTone + ',' + alpha + ')';
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(pa.x, pa.y);
                        ctx.lineTo(pb.x, pb.y);
                        ctx.stroke();
                    }
                }
            }

            if (INTERACTIVE && mouse.active) {
                for (var m = 0; m < particles.length; m++) {
                    var pm = particles[m];
                    var mdx = pm.x - mouse.x, mdy = pm.y - mouse.y;
                    var md = Math.hypot(mdx, mdy);
                    if (md < LINK_DIST) {
                        var ma = (1 - md / LINK_DIST) * 0.6;
                        ctx.strokeStyle = 'rgba(' + nodeTone + ',' + ma + ')';
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(pm.x, pm.y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.stroke();
                    }
                }
            }

            requestAnimationFrame(animate);
        }

        requestAnimationFrame(animate);
    })();
</script>
