// Mathematical Universe Background Animation

document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById('math-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const formulasContainer = document.getElementById('formula-nodes');

    // --- State & Config ---
    let width, height;
    let particles = [];
    let formulaNodes = [];
    
    const mouse = { x: -1000, y: -1000, active: false };
    const MOUSE_RADIUS = 150;
    const MAX_PARTICLES = window.innerWidth < 768 ? 40 : 80;
    const MAX_FORMULAS = window.innerWidth < 768 ? 15 : 25;
    const CONNECTION_DIST = 140;

    // Pre-U Math Formulas mapping
    const latexFormulas = [
        "\\frac{d}{dx}(x^n) = nx^{n-1}",
        "\\int_a^b f(x)\\,dx",
        "\\lim_{x \\to a} f(x)",
        "f(x) = ax^2 + bx + c",
        "x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}",
        "\\log_a(x)",
        "\\sin^2\\theta + \\cos^2\\theta = 1",
        "\\tan\\theta = \\frac{\\sin\\theta}{\\cos\\theta}",
        "\\vec{v} = a\\hat{\\imath} + b\\hat{\\jmath}",
        "P(A|B) = \\frac{P(A \\cap B)}{P(B)}",
        "\\sum_{n=1}^{\\infty} a_n",
        "\\begin{bmatrix} a & b \\\\ c & d \\end{bmatrix}",
        "\\int e^x dx = e^x + C",
        "e^{i\\pi} + 1 = 0"
    ];

    const symbols = ['π', '∑', '∫', '√', 'θ', 'Δ', '∞', 'λ', 'dx', 'dy', 'α', 'β', 'γ', '±'];

    // --- Resize handler ---
    function resize() {
        width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        canvas.width = width;
        canvas.height = height;
    }
    window.addEventListener('resize', resize);
    resize();

    // --- Particle Class (Canvas) ---
    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 0.4;
            this.vy = (Math.random() - 0.5) * 0.4;
            this.size = Math.random() * 1.5 + 0.5;
            this.symbol = Math.random() > 0.65 ? symbols[Math.floor(Math.random() * symbols.length)] : null;
            this.opacity = Math.random() * 0.4 + 0.1;
        }

        update() {
            // Mouse repulsion
            if (mouse.active) {
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < MOUSE_RADIUS) {
                    let force = (MOUSE_RADIUS - distance) / MOUSE_RADIUS;
                    // Push away
                    this.vx -= (dx / distance) * force * 0.05;
                    this.vy -= (dy / distance) * force * 0.05;
                }
            }

            // Friction & natural movement
            this.vx *= 0.98;
            this.vy *= 0.98;
            this.vx += (Math.random() - 0.5) * 0.02;
            this.vy += (Math.random() - 0.5) * 0.02;

            // Speed limits
            let speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
            if (speed > 1.2) {
                this.vx = (this.vx / speed) * 1.2;
                this.vy = (this.vy / speed) * 1.2;
            }

            this.x += this.vx;
            this.y += this.vy;

            // Wrap edges smoothly
            if (this.x < 0) this.x = width;
            if (this.x > width) this.x = 0;
            if (this.y < 0) this.y = height;
            if (this.y > height) this.y = 0;
        }

        draw() {
            ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
            if (this.symbol) {
                ctx.font = "italic 14px 'Times New Roman', serif";
                ctx.fillText(this.symbol, this.x, this.y);
            } else {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }
    }

    // --- DOM Formula Node Class ---
    class FormulaNode {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 0.15;
            this.vy = (Math.random() - 0.5) * 0.15;
            this.latex = latexFormulas[Math.floor(Math.random() * latexFormulas.length)];
            
            this.element = document.createElement('div');
            this.element.className = 'formula-node';
            
            if (typeof katex !== 'undefined') {
                try {
                    katex.render(this.latex, this.element);
                } catch(e) {
                    this.element.innerText = this.latex;
                }
            } else {
                this.element.innerText = this.latex;
            }
            
            let scale = Math.random() * 0.4 + 0.8; 
            this.element.style.opacity = Math.random() * 0.3 + 0.1;
            this.element.style.transform = `translate(${this.x}px, ${this.y}px) scale(${scale})`;
            
            formulasContainer.appendChild(this.element);
        }

        update() {
             if (mouse.active) {
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < MOUSE_RADIUS * 1.2) {
                    let force = (MOUSE_RADIUS * 1.2 - distance) / (MOUSE_RADIUS * 1.2);
                    this.vx -= (dx / distance) * force * 0.01;
                    this.vy -= (dy / distance) * force * 0.01;
                }
            }

            this.vx *= 0.99;
            this.vy *= 0.99;
            this.vx += (Math.random() - 0.5) * 0.005;
            this.vy += (Math.random() - 0.5) * 0.005;

            let speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
            if (speed > 0.4) {
                this.vx = (this.vx / speed) * 0.4;
                this.vy = (this.vy / speed) * 0.4;
            }

            this.x += this.vx;
            this.y += this.vy;
            
            // Wrap gently
            if (this.x < -150) this.x = width + 50;
            if (this.x > width + 150) this.x = -150;
            if (this.y < -150) this.y = height + 50;
            if (this.y > height + 150) this.y = -150;

            this.element.style.transform = `translate(${this.x}px, ${this.y}px)`;
        }
    }

    // --- Events ---
    window.addEventListener('mousemove', (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        mouse.active = true;
    });

    window.addEventListener('mouseout', () => {
        mouse.active = false;
    });

    let ripples = [];
    window.addEventListener('mousedown', (e) => {
        if(e.target.closest('.login-card')) return; // Don't ripple hard when clicking form

        ripples.push({ x: e.clientX, y: e.clientY, radius: 0, opacity: 1 });
        
        // Scatter nearby canvas particles
        particles.forEach(p => {
            let dx = p.x - e.clientX;
            let dy = p.y - e.clientY;
            let dist = Math.sqrt(dx*dx + dy*dy);
            if(dist < 150) {
                p.vx += (dx/dist) * 4;
                p.vy += (dy/dist) * 4;
            }
        });
    });

    // --- Init & Loop ---
    function init() {
        // Delay slightly so Katex has time to load via CDN
        setTimeout(() => {
            resize(); // Force recalculate bounds just before spawning
            for (let i = 0; i < MAX_PARTICLES; i++) particles.push(new Particle());
            for (let i = 0; i < MAX_FORMULAS; i++) formulaNodes.push(new FormulaNode());
            animate();
        }, 300);
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);

        // Update & draw particles
        particles.forEach(p => {
            p.update();
            p.draw();
        });

        // Draw network connections
        ctx.lineWidth = 1;
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                let dx = particles[i].x - particles[j].x;
                let dy = particles[i].y - particles[j].y;
                let dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < CONNECTION_DIST) {
                    let opacity = (1 - (dist / CONNECTION_DIST)) * 0.2; // soft line
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(0, 229, 255, ${opacity})`;
                    ctx.stroke();
                }
            }
        }

        // Draw connections to mouse
        if (mouse.active) {
            particles.forEach(p => {
                let dx = p.x - mouse.x;
                let dy = p.y - mouse.y;
                let dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < MOUSE_RADIUS) {
                    let opacity = (1 - (dist / MOUSE_RADIUS)) * 0.4;
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = `rgba(0, 229, 255, ${opacity})`;
                    ctx.stroke();
                }
            });
        }

        // Ripples
        for (let i = ripples.length - 1; i >= 0; i--) {
            let r = ripples[i];
            r.radius += 4;
            r.opacity -= 0.015;
            
            ctx.beginPath();
            ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
            ctx.strokeStyle = `rgba(0, 229, 255, ${r.opacity})`;
            ctx.lineWidth = 2;
            ctx.stroke();

            if (r.opacity <= 0) {
                ripples.splice(i, 1);
            }
        }

        // DOM nodes
        formulaNodes.forEach(f => f.update());

        requestAnimationFrame(animate);
    }

    init();
});
