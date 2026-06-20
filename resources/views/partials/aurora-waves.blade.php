{{-- Aurora waves background for 'aurora-waves' layout --}}
@php
    $auroraColor = config('tyro-login.aurora_waves.color', '#0b1020');
    $auroraSpeed = config('tyro-login.aurora_waves.speed', 1);
    $auroraIntensity = config('tyro-login.aurora_waves.intensity', 0.5);
@endphp
<canvas id="tyro-aurora-canvas"
        data-color="{{ $auroraColor }}"
        data-speed="{{ $auroraSpeed }}"
        data-intensity="{{ $auroraIntensity }}"></canvas>

<script>
    (function () {
        'use strict';

        var canvas = document.getElementById('tyro-aurora-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        var W, H, DPR;

        function isDark() {
            return document.documentElement.classList.contains('dark');
        }

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
            return { r: 11, g: 16, b: 32 };
        }

        function rgbToHsl(r, g, b) {
            r /= 255; g /= 255; b /= 255;
            var max = Math.max(r, g, b), min = Math.min(r, g, b);
            var h, s, l = (max + min) / 2;
            if (max === min) { h = s = 0; }
            else {
                var d = max - min;
                s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    default: h = (r - g) / d + 4;
                }
                h /= 6;
            }
            return { h: h * 360, s: s, l: l };
        }

        var base = parseColor(canvas.getAttribute('data-color'));
        var baseHsl = rgbToHsl(base.r, base.g, base.b);
        var SPEED = parseFloat(canvas.getAttribute('data-speed')) || 1;
        var INTENSITY = parseFloat(canvas.getAttribute('data-intensity'));
        if (isNaN(INTENSITY)) INTENSITY = 0.5;
        INTENSITY = Math.max(0, Math.min(1, INTENSITY));

        // ─── Ribbons ─────────────────────────────────────────
        var HUE_OFFSETS = [-40, -15, 20, 45, 70];
        var ribbons = HUE_OFFSETS.map(function (off, i) {
            return {
                hue: (baseHsl.h + off + 360) % 360,
                sat: 0.6,
                light: 0.5,
                yBase: 0.1 + (i / Math.max(1, HUE_OFFSETS.length - 1)) * 0.8,
                amp: 0.05 + Math.random() * 0.04,
                freq: 0.0008 + Math.random() * 0.0012,
                phase: Math.random() * Math.PI * 2,
                speed: (0.00012 + Math.random() * 0.0001) * SPEED,
                thickness: 0.28 + Math.random() * 0.14
            };
        });

        function drawBackground() {
            ctx.globalCompositeOperation = 'source-over';
            // Full-height color gradient derived from the palette so the whole
            // screen is colorful (never bare dark) - ribbons animate on top.
            var grad = ctx.createLinearGradient(0, 0, 0, H);
            var stops = HUE_OFFSETS;
            for (var i = 0; i < stops.length; i++) {
                var hue = (baseHsl.h + stops[i] + 360) % 360;
                var t = i / Math.max(1, stops.length - 1);
                grad.addColorStop(t, 'hsl(' + hue + ',55%,18%)');
            }
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, W, H);
        }

        function drawRibbon(ribbon, time) {
            var centerY = ribbon.yBase * H;
            var ampPx = ribbon.amp * H;
            var thickPx = ribbon.thickness * H;
            var alphaBase = (0.10 + INTENSITY * 0.22);

            var grad = ctx.createLinearGradient(0, centerY - thickPx, 0, centerY + thickPx);
            var top = 'hsla(' + ribbon.hue + ',' + (ribbon.sat * 100) + '%,' + (ribbon.light * 100) + '%,0)';
            var mid = 'hsla(' + ribbon.hue + ',' + (ribbon.sat * 100) + '%,' + (ribbon.light * 100) + '%,' + alphaBase + ')';
            grad.addColorStop(0, top);
            grad.addColorStop(0.5, mid);
            grad.addColorStop(1, top);

            ctx.beginPath();
            ctx.moveTo(0, centerY + thickPx);
            for (var x = 0; x <= W; x += 10) {
                var y = centerY + Math.sin(x * ribbon.freq + ribbon.phase + time * ribbon.speed) * ampPx;
                ctx.lineTo(x, y);
            }
            ctx.lineTo(W, centerY + thickPx);
            for (var x = W; x >= 0; x -= 10) {
                var y2 = centerY + Math.sin(x * ribbon.freq + ribbon.phase + time * ribbon.speed) * ampPx - thickPx;
                ctx.lineTo(x, y2);
            }
            ctx.closePath();

            ctx.globalCompositeOperation = 'lighter';
            ctx.fillStyle = grad;
            ctx.shadowColor = 'hsla(' + ribbon.hue + ',' + (ribbon.sat * 100) + '%,' + (ribbon.light * 100) + '%,0.35)';
            ctx.shadowBlur = 48;
            ctx.fill();
            ctx.shadowBlur = 0;
        }

        function animate() {
            if (canvas.width !== W * DPR || canvas.height !== H * DPR) resize();
            var time = performance.now();

            drawBackground();
            for (var i = 0; i < ribbons.length; i++) {
                drawRibbon(ribbons[i], time);
            }
            ctx.globalCompositeOperation = 'source-over';

            requestAnimationFrame(animate);
        }

        requestAnimationFrame(animate);
    })();
</script>
