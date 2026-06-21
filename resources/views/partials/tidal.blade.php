{{-- Tidal background for 'tidal' layout: flowing waves + bubbles --}}
@php
    $tidalColor = config('tyro-login.tidal.color', '#1f7a8c');
    $tidalSpeed = config('tyro-login.tidal.speed', 1);
    $tidalBubbles = config('tyro-login.tidal.bubbles', true);
@endphp
<canvas id="tyro-tidal-canvas"
        data-color="{{ $tidalColor }}"
        data-speed="{{ $tidalSpeed }}"
        data-bubbles="{{ $tidalBubbles ? '1' : '0' }}"></canvas>

<script>
    (function () {
        'use strict';

        var canvas = document.getElementById('tyro-tidal-canvas');
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
        window.addEventListener('resize', resize);
        resize();

        // ─── Color parsing (hex / rgb / rgba) ────────────────
        function parseColor(input) {
            var s = (input || '').trim();
            var m;
            if (s.charAt(0) === '#') {
                var hex = s.slice(1);
                if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
                var num = parseInt(hex, 16);
                return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
            }
            m = s.match(/rgba?\(([^)]+)\)/i);
            if (m) {
                var parts = m[1].split(',').map(function (p) { return parseFloat(p); });
                return { r: parts[0] || 0, g: parts[1] || 0, b: parts[2] || 0 };
            }
            return { r: 31, g: 122, b: 140 };
        }

        function mix(a, b, t) {
            return {
                r: Math.round(a.r + (b.r - a.r) * t),
                g: Math.round(a.g + (b.g - a.g) * t),
                b: Math.round(a.b + (b.b - a.b) * t)
            };
        }
        function rgba(c, a) { return 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',' + a + ')'; }
        function rgb(c) { return 'rgb(' + c.r + ',' + c.g + ',' + c.b + ')'; }

        var WHITE = { r: 255, g: 255, b: 255 };
        var BLACK = { r: 0, g: 0, b: 0 };
        var base = parseColor(canvas.getAttribute('data-color'));
        var SPEED = parseFloat(canvas.getAttribute('data-speed')) || 1;
        var BUBBLES = canvas.getAttribute('data-bubbles') === '1';

        // Derived palette from a single color
        var skyTop = mix(base, WHITE, 0.9);
        var skyMid = mix(base, WHITE, 0.82);
        var skyBottom = mix(base, WHITE, 0.7);

        // Wave layers: from light (top) to deep (bottom)
        var layers = [
            { c: mix(base, WHITE, 0.45), a: 0.22, amp: 60, len: 0.008, speed: 0.0004, yOff: 0.55 },
            { c: base, a: 0.30, amp: 80, len: 0.006, speed: 0.00055, yOff: 0.66 },
            { c: mix(base, BLACK, 0.4), a: 0.55, amp: 100, len: 0.0045, speed: 0.0007, yOff: 0.78 },
            { c: mix(base, BLACK, 0.72), a: 0.85, amp: 120, len: 0.0035, speed: 0.00085, yOff: 0.9 }
        ];

        function drawWave(layer, time) {
            var baseY = H * layer.yOff;
            ctx.beginPath();
            ctx.moveTo(0, H);
            ctx.lineTo(0, baseY);

            var step = 6;
            for (var x = 0; x <= W; x += step) {
                var y = baseY
                    + Math.sin(x * layer.len + time * layer.speed * SPEED) * layer.amp
                    + Math.sin(x * layer.len * 2.3 + time * layer.speed * 1.6 * SPEED) * layer.amp * 0.3;
                ctx.lineTo(x, y);
            }

            ctx.lineTo(W, H);
            ctx.closePath();

            var grad = ctx.createLinearGradient(0, baseY - layer.amp, 0, H);
            grad.addColorStop(0, rgba(layer.c, layer.a));
            grad.addColorStop(1, rgb(layer.c));
            ctx.fillStyle = grad;
            ctx.fill();
        }

        // ─── Bubbles ─────────────────────────────────────────
        var bubbles = [];
        function Bubble() { this.reset(true); }
        Bubble.prototype.reset = function (initial) {
            this.x = Math.random() * W;
            this.y = initial ? Math.random() * H : H + 20;
            this.r = 2 + Math.random() * 6;
            this.vy = -(0.2 + Math.random() * 0.6);
            this.vx = (Math.random() - 0.5) * 0.3;
            this.wobble = Math.random() * Math.PI * 2;
            this.alpha = 0.15 + Math.random() * 0.3;
        };
        Bubble.prototype.update = function () {
            this.wobble += 0.02;
            this.y += this.vy;
            this.x += this.vx + Math.sin(this.wobble) * 0.4;
            if (this.y < -10) this.reset(false);
            if (this.x < -10) this.x = W + 10;
            if (this.x > W + 10) this.x = -10;
        };
        Bubble.prototype.draw = function (ctx) {
            var r = Math.max(0.5, this.r);
            ctx.beginPath();
            ctx.arc(this.x, this.y, r, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(255, 255, 255, ' + this.alpha + ')';
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.fillStyle = 'rgba(255, 255, 255, ' + (this.alpha * 0.25) + ')';
            ctx.fill();
        };

        if (BUBBLES) {
            for (var i = 0; i < 40; i++) bubbles.push(new Bubble());
        }

        // ─── Animation loop ──────────────────────────────────
        function animate(time) {
            if (canvas.width !== W * DPR || canvas.height !== H * DPR) resize();

            // sky gradient background
            var bg = ctx.createLinearGradient(0, 0, 0, H);
            bg.addColorStop(0, rgb(skyTop));
            bg.addColorStop(0.5, rgb(skyMid));
            bg.addColorStop(1, rgb(skyBottom));
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, W, H);

            // soft sun glow
            var sun = ctx.createRadialGradient(W * 0.5, H * 0.3, 0, W * 0.5, H * 0.3, H * 0.5);
            sun.addColorStop(0, 'rgba(255, 255, 255, 0.5)');
            sun.addColorStop(1, 'rgba(255, 255, 255, 0)');
            ctx.fillStyle = sun;
            ctx.fillRect(0, 0, W, H);

            // waves back to front
            for (var li = 0; li < layers.length; li++) {
                drawWave(layers[li], time);
            }

            // bubbles on top
            for (var bi = 0; bi < bubbles.length; bi++) {
                bubbles[bi].update();
                bubbles[bi].draw(ctx);
            }

            requestAnimationFrame(animate);
        }

        requestAnimationFrame(animate);
    })();
</script>
