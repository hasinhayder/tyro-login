{{-- Animated birds background for 'animated-birds' layout --}}
@php
    $birdsColor = config('tyro-login.animated_birds.color', '#f7f2ec');
@endphp
<canvas id="tyro-bird-canvas" data-sky="{{ $birdsColor }}"></canvas>

<script>
    (function () {
        'use strict';

        var canvas = document.getElementById('tyro-bird-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        var SKY_COLOR = canvas.getAttribute('data-sky') || '#f7f2ec';

        var W, H;

        function isDark() {
            return document.documentElement.classList.contains('dark');
        }

        function resize() {
            W = canvas.width = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        // ─── Color palette ────────────────────────────────────
        var COLORS = {
            skyTop: SKY_COLOR,
            skyBottom: SKY_COLOR,
            birdBase: '#2d2a27',
            birdLight: '#4a4440',
        };

        var COLORS_DARK = {
            skyTop: SKY_COLOR,
            skyBottom: SKY_COLOR,
            birdBase: '#e8e2d8',
            birdLight: '#b9b2a6',
        };

        // ─── Bird class ──────────────────────────────────────
        function Bird() {
            this.reset();
        }

        Bird.prototype.reset = function () {
            this.x = Math.random() * W;
            this.y = Math.random() * H;
            var angle = Math.random() * Math.PI * 2;
            var speed = 0.4 + Math.random() * 1.2;
            this.vx = Math.cos(angle) * speed;
            this.vy = Math.sin(angle) * speed * 0.7;
            this.size = 6 + Math.random() * 14;
            this.wingPhase = Math.random() * Math.PI * 2;
            this.wingSpeed = 0.6 + Math.random() * 1.8;
            this.wobbleOffset = Math.random() * 1000;
            this.wobbleAmp = 0.02 + Math.random() * 0.04;
            this.targetAngle = Math.atan2(this.vy, this.vx);
            this.currentAngle = this.targetAngle;
        };

        Bird.prototype.update = function () {
            this.vx += (Math.random() - 0.5) * 0.012;
            this.vy += (Math.random() - 0.5) * 0.008;

            var t = performance.now() * 0.0004 + this.wobbleOffset;
            this.vx += Math.sin(t) * this.wobbleAmp * 0.3;
            this.vy += Math.cos(t * 0.7 + 1.2) * this.wobbleAmp * 0.3;

            var spd = Math.hypot(this.vx, this.vy);
            var maxSpeed = 2.0;
            var minSpeed = 0.35;
            if (spd > maxSpeed) {
                this.vx = (this.vx / spd) * maxSpeed;
                this.vy = (this.vy / spd) * maxSpeed;
            } else if (spd < minSpeed) {
                var boost = (minSpeed / spd) * 0.5 + 0.5;
                this.vx *= boost;
                this.vy *= boost;
            }

            this.x += this.vx;
            this.y += this.vy;

            this.targetAngle = Math.atan2(this.vy, this.vx);
            var diff = this.targetAngle - this.currentAngle;
            while (diff > Math.PI) diff -= Math.PI * 2;
            while (diff < -Math.PI) diff += Math.PI * 2;
            this.currentAngle += diff * 0.08;

            this.wingPhase += this.wingSpeed * 0.025;

            var margin = 80;
            var halfSize = this.size * 1.2;
            if (this.x < -margin - halfSize) this.x = W + margin + halfSize;
            if (this.x > W + margin + halfSize) this.x = -margin - halfSize;
            if (this.y < -margin - halfSize) this.y = H + margin + halfSize;
            if (this.y > H + margin + halfSize) this.y = -margin - halfSize;
        };

        Bird.prototype.draw = function (ctx, palette) {
            var s = this.size;
            var angle = this.currentAngle;
            var wing = Math.sin(this.wingPhase) * 0.55;

            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate(angle);

            var wu = -0.9 - wing * 0.45;
            var wd = 0.9 + wing * 0.45;

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.quadraticCurveTo(s * 0.30, s * (wu * 0.55), s * 0.85, s * wu);
            ctx.quadraticCurveTo(s * 0.55, s * (wu * 0.25), s * 0.35, 0);
            ctx.quadraticCurveTo(s * 0.55, s * (wd * 0.25), s * 0.85, s * wd);
            ctx.quadraticCurveTo(s * 0.30, s * (wd * 0.55), 0, 0);
            ctx.closePath();

            var grad = ctx.createLinearGradient(-s * 0.2, -s * 0.8, s * 0.6, s * 0.8);
            grad.addColorStop(0, palette.birdBase);
            grad.addColorStop(0.6, palette.birdBase);
            grad.addColorStop(1, palette.birdLight);
            ctx.fillStyle = grad;
            ctx.shadowColor = 'rgba(30, 25, 20, 0.06)';
            ctx.shadowBlur = s * 0.4;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = s * 0.08;
            ctx.fill();

            ctx.strokeStyle = 'rgba(45, 42, 39, 0.08)';
            ctx.lineWidth = 0.4;
            ctx.stroke();

            ctx.restore();
        };

        // ─── Flock ────────────────────────────────────────────
        var BIRD_COUNT = 22;
        var birds = [];
        for (var i = 0; i < BIRD_COUNT; i++) {
            birds.push(new Bird());
        }

        // ─── Background ───────────────────────────────────────
        function drawBackground(ctx, w, h, palette) {
            var grad = ctx.createLinearGradient(0, 0, 0, h);
            grad.addColorStop(0, palette.skyTop);
            grad.addColorStop(1, palette.skyBottom);
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, w, h);

            var haze = ctx.createRadialGradient(w * 0.5, h * 0.85, 0, w * 0.5, h * 0.85, h * 0.5);
            haze.addColorStop(0, 'rgba(235, 224, 214, 0.15)');
            haze.addColorStop(1, 'rgba(247, 242, 236, 0)');
            ctx.fillStyle = haze;
            ctx.fillRect(0, 0, w, h);
        }

        // ─── Animation loop ──────────────────────────────────
        function animate() {
            if (W !== canvas.width || H !== canvas.height) {
                resize();
            }

            var palette = isDark() ? COLORS_DARK : COLORS;

            drawBackground(ctx, W, H, palette);

            for (var i = 0; i < birds.length; i++) {
                var bird = birds[i];
                bird.update();
                bird.draw(ctx, palette);
            }

            // very faint overlay for depth
            ctx.fillStyle = 'rgba(247, 242, 236, 0.03)';
            ctx.fillRect(0, 0, W, H);

            requestAnimationFrame(animate);
        }

        requestAnimationFrame(animate);

        // re-seed birds gently on resize
        window.addEventListener('resize', function () {
            for (var b in birds) {
                if (!birds.hasOwnProperty(b)) continue;
                var margin = 100;
                var half = birds[b].size * 1.2;
                if (birds[b].x < -margin - half) birds[b].x = W + margin + half;
                if (birds[b].x > W + margin + half) birds[b].x = -margin - half;
                if (birds[b].y < -margin - half) birds[b].y = H + margin + half;
                if (birds[b].y > H + margin + half) birds[b].y = -margin - half;
            }
        });
    })();
</script>
