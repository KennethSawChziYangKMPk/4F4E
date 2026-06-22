<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// ==========================================
// 🧠 FORMULA DATABASE
// ==========================================
$formulas = [
    1 => [
        'title' => 'Chapter 1: Numerical Solution',
        'list' => [
            [
                'name' => 'Newton-Raphson Method',
                'math' => '$$x_{n+1} = x_n - \frac{f(x_n)}{f\'(x_n)}$$',
                'meaning' => 'Used to find successively better approximations to the roots of a real-valued function. Here, $n = 1, 2, 3, \dots$',
                'example' => 'Find the root of $f(x) = x^2 - 5 = 0$ starting with $x_0 = 2$. <br> $x_1 = 2 - \frac{2^2 - 5}{2(2)} = 2 - \frac{-1}{4} = 2.25$'
            ]
        ]
    ],
    2 => [
        'title' => 'Chapter 2: Integration',
        'list' => [
            [
                'name' => 'Basic Power Rule',
                'math' => '$$\int x^{n}dx = \frac{x^{n+1}}{n+1} + c$$ <br> $$\int(ax+b)^{n}dx = \frac{(ax+b)^{n+1}}{a(n+1)} + c$$',
                'meaning' => 'Used for integrating standard polynomials and linear expressions. Note: $n \neq -1$.',
                'example' => '$$\int x^3 dx = \frac{x^4}{4} + c$$'
            ],
            [
                'name' => 'Integration of Exponential Functions',
                'math' => '$$\int e^{bx+d}dx = \frac{e^{bx+d}}{b} + c$$ <br> $$\int a^{bx+d}dx = \frac{a^{bx+d}}{b \ln a} + c$$',
                'meaning' => 'Rules for integrating base $e$ and base $a$ exponential functions.',
                'example' => '$$\int e^{3x+2} dx = \frac{e^{3x+2}}{3} + c$$'
            ],
            [
                'name' => 'Integration of Rational Functions',
                'math' => '$$\int \frac{1}{x}dx = \ln|x| + c$$ <br> $$\int \frac{1}{ax+b}dx = \frac{1}{a}\ln|ax+b| + c$$',
                'meaning' => 'Used when integrating rational functions where the denominator is a linear function.',
                'example' => '$$\int \frac{1}{4x-1} dx = \frac{1}{4}\ln|4x-1| + c$$'
            ],
            [
                'name' => 'Integration of Trigonometric Functions',
                'math' => '$$\int \sin(ax+b)dx = -\frac{1}{a}\cos(ax+b) + c$$ <br> $$\int \cos(ax+b)dx = \frac{1}{a}\sin(ax+b) + c$$ <br> $$\int \sec^{2}(ax+b)dx = \frac{1}{a}\tan(ax+b) + c$$',
                'meaning' => 'Standard integrals for sine, cosine, and secant squared functions.',
                'example' => '$$\int \cos(2x) dx = \frac{1}{2}\sin(2x) + c$$'
            ],
            [
                'name' => 'Double Angle Formulae (For Integration)',
                'math' => '$$\sin^{2}x = \frac{1-\cos 2x}{2}$$ <br> $$\cos^{2}x = \frac{1+\cos 2x}{2}$$',
                'meaning' => 'Used to replace $\sin^2(ax)$ and $\cos^2(ax)$ before attempting to integrate them.',
                'example' => '$$\int \sin^2(x) dx = \int \frac{1-\cos(2x)}{2} dx = \frac{x}{2} - \frac{\sin(2x)}{4} + c$$'
            ],
            [
                'name' => 'Integration by Substitution',
                'math' => '$$\int f[g(x)] g\'(x)dx = \int f(u)du$$',
                'meaning' => 'Let $u = g(x)$ and $du = g\'(x)dx$ to simplify complex integrals.',
                'example' => 'For $\int 2x \cos(x^2) dx$, let $u = x^2$. Then $du = 2x dx$. <br> $\int \cos(u) du = \sin(u) + c = \sin(x^2) + c$'
            ],
            [
                'name' => 'Integration by Parts',
                'math' => '$$\int u\,dv = uv - \int v\,du$$',
                'meaning' => 'Priority to choose $u$ follows LPET: Logarithm, Polynomial, Exponential, Trigonometry.',
                'example' => 'For $\int x e^x dx$, let $u = x$ and $dv = e^x dx$. <br> $x e^x - \int e^x dx = x e^x - e^x + c$'
            ],
            [
                'name' => 'Definite Integrals Property',
                'math' => '$$\int_{a}^{c}f(x)dx = \int_{a}^{b}f(x)dx + \int_{b}^{c}f(x)dx$$',
                'meaning' => 'Where $a \le b \le c$. The integral can be split into adjacent intervals.',
                'example' => '$$\int_0^5 x^2 dx = \int_0^2 x^2 dx + \int_2^5 x^2 dx$$'
            ],
            [
                'name' => 'Area Enclosed by a Curve',
                'math' => '$$A = \int_{a}^{b} f(x)dx \quad \text{(x-axis)}$$ <br> $$A = \int_{c}^{d} g(y)dy \quad \text{(y-axis)}$$ <br> $$A = \int_{a}^{b} [f(x) - g(x)]dx \quad \text{(between curves)}$$',
                'meaning' => 'Formulas to calculate the area under a curve or between two continuous functions.',
                // FIXED: LaTeX bracket spacing with \left[ and \right]
                'example' => 'Area under $y=x^2$ from $x=0$ to $2$: <br> $A = \int_0^2 x^2 dx = \left[ \frac{x^3}{3} \right]_0^2 = \frac{8}{3}$'
            ],
            [
                'name' => 'Volume of a Solid of Revolution',
                'math' => '$$V = \pi \int_{a}^{b} [f(x)]^2 dx \quad \text{(x-axis)}$$ <br> $$V = \pi \int_{c}^{d} [f(y)]^2 dy \quad \text{(y-axis)}$$ <br> $$V = \pi \int_{a}^{b} [(f(x))^2 - (g(x))^2] dx$$',
                'meaning' => 'Used to calculate the volume generated by revolving a region about an axis.',
                // FIXED: LaTeX bracket spacing with \left[ and \right]
                'example' => 'Revolving $y=\sqrt{x}$ about the x-axis from $0$ to $4$: <br> $V = \pi \int_0^4 (\sqrt{x})^2 dx = \pi \left[ \frac{x^2}{2} \right]_0^4 = 8\pi$'
            ],
            [
                'name' => 'Trapezoidal Rule',
                'math' => '$$\int_{a}^{b} f(x) dx \approx \frac{h}{2} \left[ (y_0 + y_n) + 2(y_1 + y_2 + \dots + y_{n-1}) \right]$$',
                'meaning' => 'Estimates the area under a curve as the sum of trapeziums, where $h = \frac{b-a}{n}$.',
                'example' => 'Estimate $\int_0^2 x^2 dx$ with $n=2$ strips ($h=1$). <br> $\frac{1}{2} [(0 + 4) + 2(1)] = \frac{1}{2}[6] = 3$'
            ]
        ]
    ],
    3 => [
        'title' => 'Chapter 3: First Order Differential Equations',
        'list' => [
            [
                'name' => 'General Solution',
                'math' => '$$\frac{dy}{dx} = f(x) \implies y = \int f(x) dx + C$$',
                'meaning' => 'The general solution of a differential equation contains an arbitrary constant $C$.',
                'example' => 'If $\frac{dy}{dx} = 3x^2$, the general solution is $y = x^3 + C$.'
            ],
            [
                'name' => 'Particular Solution',
                'math' => '$$y = F(x) + C \xrightarrow{\text{substitute initial values}} y = F(x) + k$$',
                'meaning' => 'Contains a specified initial value and no arbitrary constant. Solved by substituting $x$ and $y$ to find $C$.',
                'example' => 'Given $y = x^3 + C$ and $y(1) = 5$. <br> $5 = 1^3 + C \implies C = 4$. Solution: $y = x^3 + 4$'
            ],
            [
                'name' => 'Separable Variables',
                'math' => '$$\frac{dy}{dx} = f(x)g(y) \implies \int \frac{1}{g(y)} dy = \int f(x) dx$$',
                'meaning' => 'Used when the differential equation can be algebraically separated into $x$ terms on one side and $y$ terms on the other.',
                'example' => '$\frac{dy}{dx} = xy \implies \int \frac{1}{y} dy = \int x dx \implies \ln|y| = \frac{x^2}{2} + C$'
            ],
            [
                'name' => 'Linear First-Order Differential Equations',
                'math' => '$$\frac{dy}{dx} + P(x)y = Q(x)$$ <br> $$v(x) = e^{\int P(x)dx}$$ <br> $$v(x)y = \int v(x)Q(x)dx$$',
                'meaning' => 'Solved by finding the integrating factor $v(x)$ and multiplying it throughout the equation.',
                // FIXED: Completed the equation to find y
                'example' => '$\frac{dy}{dx} + \frac{1}{x}y = 2$. $P(x) = \frac{1}{x}$. <br> Integrating factor $v(x) = e^{\int \frac{1}{x} dx} = e^{\ln x} = x$. <br> Multiply by $x$: $x\frac{dy}{dx} + y = 2x \implies \frac{d}{dx}(xy) = 2x$. <br> Integrate both sides: $xy = x^2 + C \implies y = x + \frac{C}{x}$'
            ],
            [
                'name' => 'Population Growth Model',
                'math' => '$$\frac{dy}{dt} = ky \implies y = Ae^{kt}$$',
                'meaning' => 'Used when the rate of change of a population is proportional to the current population. $k$ is the constant of proportionality.',
                'example' => 'Bacteria grows at $\frac{dP}{dt} = 0.05P$. <br> The population at any time $t$ is $P(t) = P_0 e^{0.05t}$.'
            ]
        ]
    ],
    5 => [
        'title' => 'Chapter 5: Vectors',
        'list' => [
            [
                'name' => '1. Position Vector',
                'math' => '$$\vec{v} = \begin{pmatrix} x \\ y \\ z \end{pmatrix} = \langle x, y, z \rangle = x\hat{i} + y\hat{j} + z\hat{k}$$',
                'meaning' => 'Represents a position in 3D space. Can be written as a column matrix, with angle brackets, or using i, j, k components.',
                'example' => 'Point $A(1, -2, 3)$ has position vector $\vec{OA} = \langle 1, -2, 3 \rangle = \hat{i} - 2\hat{j} + 3\hat{k}$'
            ],
            [
                'name' => '2. Magnitude of a Vector',
                'math' => '$$|\vec{v}| = \sqrt{x^2 + y^2 + z^2}$$',
                'meaning' => 'Calculates the exact length (or magnitude) of the vector from the origin.',
                'example' => 'If $\vec{v} = \langle 3, 4, 0 \rangle$, then $|\vec{v}| = \sqrt{3^2 + 4^2 + 0^2} = 5$'
            ],
            [
                'name' => '3. Unit Vector',
                'math' => '$$\hat{u} = \frac{\vec{v}}{|\vec{v}|}$$',
                'meaning' => 'A vector with a magnitude of exactly 1 that points in the exact same direction as $\vec{v}$.',
                'example' => 'If $\vec{v} = \langle 3, 0, 4 \rangle$, its unit vector is $\frac{1}{5}\langle 3, 0, 4 \rangle = \langle 0.6, 0, 0.8 \rangle$'
            ],
            [
                'name' => '4. Addition & Subtraction',
                'math' => '$$\vec{a} \pm \vec{b} = \langle a_1 \pm b_1, \ a_2 \pm b_2, \ a_3 \pm b_3 \rangle$$',
                'meaning' => 'Algebraically, you simply add or subtract the corresponding $x$, $y$, and $z$ components of the two vectors.',
                'example' => '$\langle 1, 2, 3 \rangle + \langle 4, 5, 6 \rangle = \langle 1+4, 2+5, 3+6 \rangle = \langle 5, 7, 9 \rangle$'
            ],
            [
                'name' => '5. Direction Cosines & Angles',
                'math' => '$$\cos \alpha = \frac{x}{|\vec{v}|}, \quad \cos \beta = \frac{y}{|\vec{v}|}, \quad \cos \gamma = \frac{z}{|\vec{v}|}$$',
                'meaning' => 'The cosines of the angles ($\alpha, \beta, \gamma$) the vector makes with the x, y, and z axes. Note: $\cos^2\alpha + \cos^2\beta + \cos^2\gamma = 1$.',
                'example' => 'For $\vec{v} = \langle 1, 2, 2 \rangle$ (magnitude $3$), $\cos\alpha = \frac{1}{3}, \cos\beta = \frac{2}{3}, \cos\gamma = \frac{2}{3}$'
            ],
            [
                'name' => '6. Scalar (Dot) Product',
                'math' => '$$\vec{a} \cdot \vec{b} = |\vec{a}| |\vec{b}| \cos \theta = a_1 b_1 + a_2 b_2 + a_3 b_3$$',
                'meaning' => 'Results in a scalar number. $\vec{a} \cdot \vec{b} = 0$ if perpendicular. $\vec{a} \cdot \vec{b} = |\vec{a}||\vec{b}|$ if parallel.',
                'example' => '$\langle 1, 2, 3 \rangle \cdot \langle 4, -5, 6 \rangle = (1)(4) + (2)(-5) + (3)(6) = 4 - 10 + 18 = 12$'
            ],
            [
                'name' => '7. Properties of Scalar Product',
                'math' => '$$\vec{a} \cdot \vec{a} = |\vec{a}|^2 \quad \text{and} \quad \vec{a} \cdot \vec{b} = \vec{b} \cdot \vec{a}$$',
                'meaning' => 'The dot product is commutative. Multiplying a unit vector by itself equals 1 (e.g., $\hat{i} \cdot \hat{i} = 1$).',
                'example' => 'If $|\vec{a}| = 4$, then $\vec{a} \cdot \vec{a} = 4^2 = 16$'
            ],
            [
                'name' => '8. Vector (Cross) Product',
                'math' => '$$\vec{a} \times \vec{b} = \begin{vmatrix} \hat{i} & \hat{j} & \hat{k} \\ a_1 & a_2 & a_3 \\ b_1 & b_2 & b_3 \end{vmatrix}$$',
                'meaning' => 'Results in a new perpendicular vector. Area of parallelogram = $|\vec{a} \times \vec{b}|$.',
                'example' => '$\langle 1, 0, 0 \rangle \times \langle 0, 1, 0 \rangle = \langle 0, 0, 1 \rangle \implies \hat{i} \times \hat{j} = \hat{k}$'
            ],
            [
                'name' => '9. Equation of a Line',
                'math' => '$$\vec{r} = \vec{a} + t\vec{v} \quad \text{(Vector)}$$ <br> $$x = x_0 + tv_1, \quad y = y_0 + tv_2, \quad z = z_0 + tv_3 \quad \text{(Parametric)}$$ <br> $$\frac{x - x_0}{v_1} = \frac{y - y_0}{v_2} = \frac{z - z_0}{v_3} \quad \text{(Cartesian)}$$',
                'meaning' => '$\vec{a}$ (or $x_0, y_0, z_0$) is a specific point on the line, and $\vec{v} = \langle v_1, v_2, v_3 \rangle$ is the direction vector.',
                'example' => 'Line through point $(1, 2, 3)$ with direction $\langle 4, 5, 6 \rangle$: <br> $\vec{r} = \langle 1, 2, 3 \rangle + t\langle 4, 5, 6 \rangle$'
            ],
            [
                'name' => '10. Equation of a Plane',
                'math' => '$$\vec{r} \cdot \vec{n} = \vec{a} \cdot \vec{n} \quad \text{(Vector)}$$ <br> $$ax + by + cz = d \quad \text{(Cartesian)}$$',
                'meaning' => '$\vec{a}$ is a known point on the plane, and $\vec{n} = \langle a, b, c \rangle$ is the normal vector (perpendicular to the plane).',
                'example' => 'Plane through $(1, 1, 1)$ with normal vector $\langle 2, -1, 3 \rangle$: <br> $2(x-1) - 1(y-1) + 3(z-1) = 0 \implies 2x - y + 3z = 4$'
            ]
        ]
    ],
    8 => [
        'title' => 'Chapter 8: Random Variable',
        'list' => [
            [
                'name' => '1 & 2. Basic Probability Conditions',
                'math' => '$$\text{Discrete:} \quad 0 \le P(X = x) \le 1 \quad \text{and} \quad \sum P(X = x) = 1$$ <br> $$\text{Continuous:} \quad f(x) \ge 0 \quad \text{and} \quad \int_{-\infty}^{\infty} f(x) dx = 1$$',
                'meaning' => 'Fundamental conditions for discrete and continuous probability distributions.',
                'example' => 'Rolling a fair die: $P(X=x) = \frac{1}{6}$. The sum of all $6$ outcomes is $6 \times \frac{1}{6} = 1$'
            ],
            [
                'name' => '3. Cumulative Distribution Function (CDF), $F(x)$',
                'math' => '$$F(x) = P(X \le x) = \sum_{t \le x} P(X = t) \quad \text{(Discrete)}$$ <br> $$F(x) = P(X \le x) = \int_{-\infty}^{x} f(t) dt \quad \text{(Continuous)}$$',
                'meaning' => 'Calculates the probability that the random variable takes a value less than or equal to $x$.',
                'example' => 'For a 6-sided die, $F(2) = P(X \le 2) = P(X=1) + P(X=2) = \frac{2}{6} = \frac{1}{3}$'
            ],
            [
                'name' => '4. Median ($m$)',
                'math' => '$$F(m) \ge 0.5 \quad \text{(Discrete - smallest } x \text{)}$$ <br> $$\int_{-\infty}^{m} f(x) dx = F(m) = 0.5 \quad \text{(Continuous)}$$',
                'meaning' => 'The value $m$ that divides the probability distribution perfectly in half.',
                'example' => 'If $f(x) = 0.1$ for $0 \le x \le 10$, then $\int_0^m 0.1 dx = 0.5 \implies 0.1m = 0.5 \implies m = 5$'
            ],
            [
                'name' => '5. Expected Value / Mean ($\mu$)',
                'math' => '$$E(X) = \sum x \cdot P(X = x) \quad \text{(Discrete)}$$ <br> $$E(X) = \int_{-\infty}^{\infty} x \cdot f(x) dx \quad \text{(Continuous)}$$',
                'meaning' => 'The long-run average value of the random variable.',
                'example' => 'For a fair coin ($0$=Tails, $1$=Heads), $E(X) = 0(0.5) + 1(0.5) = 0.5$'
            ],
            [
                'name' => '6. Variance ($Var(X)$ or $\sigma^2$)',
                'math' => '$$Var(X) = E(X^2) - [E(X)]^2$$',
                'meaning' => 'Measures the spread of the data. To find $E(X^2)$, square the $x$ inside your Expected Value formula.',
                'example' => 'If $E(X) = 3$ and $E(X^2) = 11$, then $Var(X) = 11 - 3^2 = 2$'
            ],
            [
                'name' => '7. Standard Deviation ($\sigma$)',
                'math' => '$$\sigma = \sqrt{Var(X)}$$',
                'meaning' => 'The square root of the variance, representing the average distance from the mean.',
                'example' => 'If $Var(X) = 4$, then the standard deviation $\sigma = \sqrt{4} = 2$'
            ],
            [
                'name' => '8. Properties of Expected Value and Variance',
                'math' => '$$E(aX + b) = aE(X) + b$$ <br> $$Var(aX + b) = a^2Var(X)$$',
                'meaning' => 'Rules for linear transformations, where $a$ and $b$ are constants. Notice that adding a constant $b$ does not affect the variance!',
                'example' => 'If $E(X) = 5$ and $Var(X) = 2$, then $E(3X+1) = 3(5)+1 = 16$ and $Var(3X+1) = 3^2(2) = 18$'
            ]
        ]
    ],
    9 => [
        'title' => 'Chapter 9: Special Probability Distributions',
        'list' => [
            [
                'name' => '1. Binomial Distribution ($X \sim B(n, p)$)',
                'math' => '<div>$$P(X = r = \binom{n}{r} p^r (1-p)^{n-r}$$</div> <div>$$\mu = np$$</div> <div>$$\sigma^2 = npq$$</div>',
                'meaning' => 'Models the number of successes ($r$) in a fixed number of independent trials ($n$).',
                'example' => 'Flipping a fair coin 3 times. Chance of exactly 2 heads: <br> $P(X = 2) = \binom{3}{2} (0.5)^2 (0.5)^1 = 0.375$'
            ],
            [
                'name' => '2. Binomial Symmetry',
                'math' => '<div>$$P(X = x) = P(Y = n - x)$$</div> <div>$$p_{new} = 1 - p_{old}$$</div>',
                'meaning' => 'Useful for looking up values in statistical tables when $p > 0.5$.',
                'example' => 'If $X \sim B(10, 0.8)$, $P(X=8) = P(Y=2)$ where $Y \sim B(10, 0.2)$'
            ],
            [
                'name' => '3. Poisson Distribution ($X \sim P_o(\lambda)$)',
                'math' => '<div>$$P(X = r) = \frac{e^{-\lambda} \lambda^r}{r!}$$</div> <div>$$\text{Mean} = \text{Variance} = \lambda$$</div>',
                'meaning' => 'Models events in a fixed interval. $\lambda$ is the average rate.',
                'example' => 'If $\lambda=5$, $P(X=3) = \frac{e^{-5} 5^3}{3!} \approx 0.140$'
            ],
            [
                'name' => '4. Standard Normal Distribution ($Z$)',
                'math' => '$$Z = \frac{X - \mu}{\sigma}$$',
                'meaning' => 'Converts $X \sim N(\mu, \sigma^2)$ into $Z \sim N(0, 1)$ for Z-table use.',
                'example' => 'If $X \sim N(50, 16)$, find $P(X < 56)$. <br> $Z = \frac{56 - 50}{\sqrt{16}} = 1.5$'
            ],
            [
                'name' => '5. Normal Approximation to Binomial (Continuity Correction)',
                'math' => '<div>$$X \sim B(n, p) \approx Y \sim N(np, npq)$$</div>
                            <hr style="border:0; border-top:1px solid #ddd; margin:10px 0;">
                            <div style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">The Boundary Rule:</div>
                            <div>$$P(X = a) \Rightarrow P(a - 0.5 < Y < a + 0.5)$$</div>
                            <div>$$P(a \le X \le b) \Rightarrow P(a - 0.5 < Y < b + 0.5)$$</div>
                            <div>$$P(X \ge a) \Rightarrow P(Y > a - 0.5)$$</div>
                            <div>$$P(X \le a) \Rightarrow P(Y < a + 0.5)$$</div>',
                'meaning' => 'Find the included integers, then expand by 0.5 outwards.',
                'example' => 'If $X \sim B(100, 0.5)$, approximate $P(X > 55)$. <br> Included integers start at 56, expand: $P(Y > 55.5)$.'
            ]
        ]
    ]
];

// Check if a user clicked a specific chapter
$active_chapter = isset($_GET['chapter']) ? (int)$_GET['chapter'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Formulae</title>
    
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        window.MathJax = {
            tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] },
            chtml: {
               displayAlign: 'center',
               scale: 1.1
            }
        };
    </script>
    <link rel="stylesheet" href="/assets/css/interior.css">
    
    <style>
        /* Grid Layout (Matches chapters.php) */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; margin-bottom: 40px; }
        .chapter-card { display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .chapter-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 229, 255, 0.15); }
        .chapter-card h3 { margin-top: 0; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-family: 'Inter'; font-size: 20px;}
        
        .btn-view { display: inline-block; margin-top: auto; background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 12px; text-decoration: none; border-radius: 8px; font-weight: 600; width: 100%; text-align: center; box-sizing: border-box; transition: all 0.2s; }
        .btn-view:hover { background: #00e5ff; color: #0b0f19; }
        
        .btn-back { display: inline-block; margin-bottom: 25px; color: #00e5ff; text-decoration: none; font-weight: 600; }
        .btn-back:hover { text-decoration: underline; text-shadow: 0 0 10px rgba(0,229,255,0.4); }
        
        /* Formula Detail Layout */
        .formula-box { margin-bottom: 24px; border-left: 4px solid #00e5ff; padding: 30px; border-radius: 12px;}
        .formula-name { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 15px; font-family: 'Inter'; letter-spacing: -0.5px;}
        .formula-math { 
            font-size: 20px; 
            margin: 20px 0; 
            padding: 25px; 
            background: rgba(0,0,0,0.3); 
            border-radius: 8px; 
            text-align: center; 
            color: #fff;
            
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow-x: auto;
        }
        .formula-meaning { color: #cbd5e1; line-height: 1.6; font-size: 15px; }
        
        .formula-example { 
            margin-top: 20px; 
            background: rgba(245, 158, 11, 0.05); 
            padding: 15px 20px; 
            border-radius: 8px; 
            border-left: 4px solid #f59e0b; 
            font-size: 15px;
            color: #e2e8f0;
        }

        h2.dark-title { margin-top: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}

        @media (max-width: 768px) {
            h2.dark-title { font-size: 28px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <?php if (!$active_chapter || !isset($formulas[$active_chapter])): ?>
        
        <div class="page-header">
            <div class="page-title">
                <h1>Subject Formulae</h1>
                <p>Select a chapter to review its key mathematical equations.</p>
            </div>
        </div>
        
        <div class="grid-container">
            <?php foreach ($formulas as $chap_id => $data): ?>
                <div class="chapter-card glass-panel" style="padding: 25px;">
                    <h3><?php echo htmlspecialchars($data['title']); ?></h3>
                    <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">Contains <?php echo count($data['list']); ?> formulas</p>
                    <a href="formulae.php?chapter=<?php echo $chap_id; ?>" class="btn-view">View Formulae ➔</a>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        
        <a href="formulae.php" class="btn-back">← Back to Chapters</a>
        <h2 class="dark-title" style="margin-bottom: 30px;"><?php echo htmlspecialchars($formulas[$active_chapter]['title']); ?></h2>
        
        <?php foreach ($formulas[$active_chapter]['list'] as $f): ?>
            <div class="formula-box glass-panel">
                <div class="formula-name"><?php echo htmlspecialchars($f['name']); ?></div>
                <div class="formula-math">
                    <?php echo $f['math']; ?>
                </div>
                <div class="formula-meaning">
                    <strong>Meaning:</strong> <?php echo $f['meaning']; ?>
                </div>
                <?php if (!empty($f['example'])): ?>
                <div class="formula-example">
                    <strong>Example:</strong> <?php echo $f['example']; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
    <?php endif; ?>

</div>

</body>
</html>
