<?php
session_start();
require 'config.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$chapter = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 1;

// === NEW: Give the student the VIP Ticket so submit_quiz.php accepts their answers! ===
$_SESSION['active_quiz'] = true; 

// === THE RESTART BUTTON LOGIC ===
if (isset($_GET['restart']) && $_GET['restart'] == '1') {
    // Delete the saved progress from the database
    $stmt = $pdo->prepare("DELETE FROM quiz_progress WHERE username = ? AND chapter = ?");
    $stmt->execute([$username, $chapter]);
    
    // Redirect back to the quiz to generate a fresh set
    header("Location: quiz.php?chapter=" . $chapter);
    exit();
}
// =====================================

// 1. Check for existing progress
$check_sql = "SELECT * FROM quiz_progress WHERE username = ? AND chapter = ?";
$stmt = $pdo->prepare($check_sql);
$stmt->execute([$username, $chapter]);
$progress = $stmt->fetch();

if ($progress) {
    // --- RESTORED CODE: Fetch the saved questions ---
    $saved_ids = $progress['question_ids'];
    
    // Convert the string of IDs into an array
    $id_array = explode(',', $saved_ids);
    
    // Build a standard SQL CASE statement to force the exact order
    $order_cases = "";
    foreach ($id_array as $index => $id) {
        $order_cases .= "WHEN question_id = " . (int)$id . " THEN $index ";
    }

    // Fetch questions using the dynamically built CASE rule (Grabs the 'hint' column too!)
    $sql = "SELECT * FROM question WHERE question_id IN ($saved_ids) ORDER BY CASE $order_cases END";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $questions = $stmt->fetchAll();
    
    // --- TIMER RESUME LOGIC ---
    if ($progress['start_time'] <= 0) {
        // It was paused! The negative number is our exact elapsed seconds.
        $elapsed_seconds = abs($progress['start_time']);
        
        // Calculate a new "fake" start time so the timer runs normally if they just refresh the page
        $quiz_start_time = time() - $elapsed_seconds;
        
        // Update the database back to a normal timestamp so it keeps ticking
        $update_time_sql = "UPDATE quiz_progress SET start_time = ? WHERE username = ? AND chapter = ?";
        $pdo->prepare($update_time_sql)->execute([$quiz_start_time, $username, $chapter]);
    } else {
        // They didn't pause, they just hit refresh on their browser
        $quiz_start_time = $progress['start_time'];
    } 
}
else {
    // 3. New Quiz: Fetch 10 random questions directly
    // SMART FIX: Auto-detect Database type (MySQL for localhost, PostgreSQL for Supabase)
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $rand_func = ($driver === 'pgsql') ? 'RANDOM()' : 'RAND()';
    
    $sql = "SELECT * FROM question WHERE chapter = ? ORDER BY $rand_func LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$chapter]);
    $questions = $stmt->fetchAll();

    if ($questions) {
        // Extract the IDs in the EXACT random order they were just pulled
        $random_ids = array_column($questions, 'question_id');
        $ids_string = implode(',', $random_ids);
        
        $quiz_start_time = time();

        // Save that exact sequence to the database
        $save_sql = "INSERT INTO quiz_progress (username, chapter, question_ids, start_time) VALUES (?, ?, ?, ?)";
        $pdo->prepare($save_sql)->execute([$username, $chapter, $ids_string, $quiz_start_time]);
    } else {
        $questions = [];
        $quiz_start_time = time(); // Prevent undefined variable if no questions exist
    }
}

$total_questions = count($questions);
$elapsed_seconds = time() - $quiz_start_time;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter <?php echo $chapter; ?> Quiz</title>
    
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
      window.MathJax = { tex: { inlineMath: [['\\(', '\\)'], ['$$', '$$']] } };
    </script>

    <link rel="stylesheet" href="/Ken Project/assets/css/interior.css">
    <style>
        /* Mobile Menu Button */
        .menu-btn {
            display: none;
            background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.2); padding: 10px 15px; 
            font-size: 16px; cursor: pointer; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-family: 'Inter';
        }

        /* === HEADER === */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { color: #ffffff; margin: 0; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        
        .timer-badge { background: rgba(0,0,0,0.4); color: #00e5ff; padding: 10px 20px; border-radius: 8px; font-family: 'SFMono-Regular', Consolas, monospace; font-size: 22px; font-weight: bold; letter-spacing: 2px; border: 1px solid rgba(0,229,255,0.2); box-shadow: 0 0 15px rgba(0,229,255,0.05) inset;}
        .btn-exit { color: #ef4444; text-decoration: none; font-weight: 600; font-size: 16px; white-space: nowrap; padding: 10px 15px; border-radius: 8px; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.2); transition: all 0.2s;}
        .btn-exit:hover { background: rgba(239,68,68,0.15); box-shadow: 0 0 15px rgba(239,68,68,0.2); }

        /* === QUIZ CARD UI === */
        .quiz-card { padding: 40px; }
        
        .question-slide { display: none; }
        .question-slide.active { display: block; animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .progress-text { display: flex; justify-content: space-between; align-items: center; color: #94a3b8; font-weight: 600; margin-bottom: 20px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        
        /* HINT BUTTON STYLES */
        .btn-hint { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); padding: 6px 14px; border-radius: 6px; font-family: 'Inter'; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 13px;}
        .btn-hint:hover { background: rgba(245, 158, 11, 0.2); box-shadow: 0 0 10px rgba(245, 158, 11, 0.2); }
        .btn-hint:disabled { background: rgba(255, 255, 255, 0.05); color: #64748b; border-color: rgba(255, 255, 255, 0.1); cursor: not-allowed; box-shadow: none; }

        .question-text { 
            font-size: 22px; color: #ffffff; margin-bottom: 30px; line-height: 1.6; font-weight: 500; 
            overflow-x: auto; padding-bottom: 10px;
        }
        
        .options-container { display: flex; flex-direction: column; gap: 15px; margin-bottom: 40px; }
        .option-label {
            display: block; padding: 20px 25px; background: rgba(0,0,0,0.2); 
            border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; cursor: pointer;
            transition: all 0.2s ease; font-size: 18px; color: #e2e8f0;
            overflow-x: auto; font-family: 'Inter';
        }
        .option-label:hover { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.2); }
        .option-label.selected { background: rgba(0, 229, 255, 0.08); border-color: #00e5ff; font-weight: 600; color: #fff; box-shadow: 0 0 15px rgba(0,229,255,0.1); }
        .option-label input { display: none; }

        /* === NAVIGATION BUTTONS === */
        .nav-buttons { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 30px; gap: 15px; }
        
        .btn { padding: 15px 30px; border-radius: 8px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.2s; text-align: center; font-family: 'Inter'; text-transform: uppercase; letter-spacing: 1px;}
        .btn-prev { background: rgba(255,255,255,0.05); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.1);}
        .btn-prev:hover { background: rgba(255,255,255,0.1); }
        .btn-next { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); flex-grow: 1; max-width: 250px; }
        .btn-next:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 20px rgba(0,229,255,0.4); }

        .btn-submit-locked { background: rgba(255,255,255,0.05); color: #64748b; cursor: not-allowed; flex-grow: 1; border: 1px solid rgba(255,255,255,0.05);}
        .btn-submit-ready { background: rgba(16, 185, 129, 0.1); color: #10b981; cursor: pointer; border: 1px solid rgba(16, 185, 129, 0.3); flex-grow: 1;}
        .btn-submit-ready:hover { background: #10b981; color: #fff; box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
        
        .hidden { visibility: hidden; } 
        .empty-state { text-align: center; padding: 40px; color: #64748b; font-style: italic; font-size: 16px;}

        /* === HINT MODAL UI === */
        .hint-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px);
            display: none; justify-content: center; align-items: center; z-index: 1000;
            opacity: 0; transition: opacity 0.3s ease;
        }
        .hint-modal-overlay.show { display: flex; opacity: 1; }
        
        .hint-modal-content {
            background: #0f172a; padding: 25px; border-radius: 12px; width: 90%; max-width: 800px;
            border: 1px solid rgba(0, 229, 255, 0.3); box-shadow: 0 0 30px rgba(0,229,255,0.1);
            position: relative; transform: translateY(20px); transition: transform 0.3s ease;
        }
        .hint-modal-overlay.show .hint-modal-content { transform: translateY(0); }

        .close-hint-btn {
            position: absolute; top: 15px; right: 20px; font-size: 24px; color: #94a3b8;
            cursor: pointer; transition: 0.2s;
        }
        .close-hint-btn:hover { color: #fff; }

        @media (max-width: 768px) {
            .header { flex-wrap: wrap; gap: 15px; justify-content: center; text-align: center;}
            .header h2 { font-size: 26px; width: 100%; }
            .quiz-card { padding: 25px; }
            .question-text { font-size: 18px; }
            .option-label { font-size: 16px; padding: 15px 20px; }
            .nav-buttons { flex-direction: column-reverse; } 
            .btn { width: 100%; max-width: 100%; }
            .btn-prev.hidden { display: none; } 
            .progress-text { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar" style="background: rgba(10, 15, 30, 0.8) !important; border-right: 1px solid rgba(255, 255, 255, 0.05) !important;">
    <p style="color: #e2e8f0; font-family: 'Inter'; font-weight: 600;">Welcome, <strong style="color: #00e5ff;"><?php echo htmlspecialchars($username); ?></strong></p>
    <hr style="border-color: rgba(255,255,255,0.05); margin-bottom: 20px; width: 100%;">
    
    <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; margin-top: 40px; padding: 20px; background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
        <span style="font-size: 40px; text-shadow: 0 0 20px rgba(0,229,255,0.5);">🧠</span>
        <span style="font-weight: 700; font-size: 16px; color: #00e5ff; font-family: 'Inter'; letter-spacing: 0.5px; text-transform: uppercase;">Focus Mode Active</span>
        <span style="font-size: 13px; color: #94a3b8; line-height: 1.5;">Navigation restricted to prevent accidental progress loss.</span>
    </div>

    <button onclick="toggleSidebar()" style="margin-top: auto; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; padding: 12px; border-radius: 8px; cursor: pointer; display: none; font-weight: 600;" class="close-sidebar-btn">Close Menu</button>
</div>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="header">
        <h2>Chapter <?php echo $chapter; ?> Quiz</h2>
        <div id="stopwatch" class="timer-badge">00:00</div>
        <div style="display: flex; gap: 10px;">
            <a href="quiz.php?chapter=<?php echo $chapter; ?>&restart=1" class="btn-exit" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);">↻ Restart</a>
            <a href="#" onclick="pauseQuiz(); return false;" class="btn-exit">⏸ Pause & Exit</a>
        </div>
    </div>

    <div class="quiz-card glass-panel">
        <?php if($total_questions > 0): ?>
            <form id="quizForm" action="submit_quiz.php" method="POST">
                <input type="hidden" name="chapter" value="<?php echo $chapter; ?>">
                <input type="hidden" name="start_time" value="<?php echo $quiz_start_time; ?>">

                <?php 
                $q_num = 1;
                foreach($questions as $index => $row): 
                    $activeClass = ($index === 0) ? 'active' : '';
                ?>
                    <div class="question-slide <?php echo $activeClass; ?>" id="slide-<?php echo $index; ?>">
                        
                        <div class="progress-text">
                            <span>Question <?php echo $q_num; ?> of <?php echo $total_questions; ?></span>
                            <button type="button" class="btn-hint" onclick="triggerHint()">💡 Hint (<span class="hint-count-display">2</span> Left)</button>
                            <span class="live-tracker" style="color: #e74c3c;">Answered: 0 / <?php echo $total_questions; ?></span>
                        </div>
                        
                        <div class="question-text">
                            <?php echo $row['question_text']; ?>
                        </div>

                        <div class="specific-hint-data" style="display: none;">
                            <?php echo empty($row['hint']) ? "No specific hint provided for this question. Remember your fundamental formulas!" : $row['hint']; ?>
                        </div>

                        <div class="options-container">
                            <label class="option-label" onclick="selectOption(this)">
                                <input type="radio" name="q<?php echo $row['question_id']; ?>" value="A" required> 
                                A) <?php echo $row['option_a']; ?>
                            </label>
                            <label class="option-label" onclick="selectOption(this)">
                                <input type="radio" name="q<?php echo $row['question_id']; ?>" value="B" required> 
                                B) <?php echo $row['option_b']; ?>
                            </label>
                            <label class="option-label" onclick="selectOption(this)">
                                <input type="radio" name="q<?php echo $row['question_id']; ?>" value="C" required> 
                                C) <?php echo $row['option_c']; ?>
                            </label>
                            <label class="option-label" onclick="selectOption(this)">
                                <input type="radio" name="q<?php echo $row['question_id']; ?>" value="D" required> 
                                D) <?php echo $row['option_d']; ?>
                            </label>
                        </div>

                        <div class="nav-buttons">
                            <button type="button" class="btn btn-prev <?php echo ($index == 0) ? 'hidden' : ''; ?>" onclick="changeSlide(-1)">
                                ← Previous
                            </button>

                            <?php if ($q_num < $total_questions): ?>
                                <button type="button" class="btn btn-next" onclick="changeSlide(1)">Next →</button>
                            <?php else: ?>
                                <button type="submit" id="submitBtn" class="btn btn-submit-locked" disabled>
                                    Answer <?php echo $total_questions; ?> questions to submit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    $q_num++;
                endforeach; 
                ?>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <h2>No questions yet!</h2>
                <p>Looks like the content team is still working on this chapter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="formulaModal" class="hint-modal-overlay">
    <div class="hint-modal-content">
        <span class="close-hint-btn" onclick="closeHintModal()">&times;</span>
        <h3 style="color: #fff; margin-top: 0; font-family: 'Inter';">💡 Question Hint</h3>
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">Use your hints wisely. You cannot get them back by refreshing.</p>
        
        <div id="dynamicHintBody" style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 20px; font-size: 18px; color: #00e5ff; border: 1px solid rgba(255,255,255,0.05); overflow-x: auto; line-height: 1.6;">
            </div>
    </div>
</div>

<style>
    @media (max-width: 768px) {
        .close-sidebar-btn { display: block !important; }
    }
</style>

<script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("active");
    }

    // --- BULLETPROOF TIMER & HINT LOGIC ---
    const timeStorageKey = 'quiz_time_<?php echo $username; ?>_chap_<?php echo $chapter; ?>';
    const hintStorageKey = 'quiz_hints_<?php echo $username; ?>_chap_<?php echo $chapter; ?>';
    const timerElement = document.getElementById('stopwatch');

    // If it's a new quiz, clear the old saved answers, saved time, AND reset hints to 2
    let isNewQuiz = <?php echo $progress ? 'false' : 'true'; ?>;
    if (isNewQuiz) {
        localStorage.removeItem('quiz_answers_<?php echo $username; ?>_chap_<?php echo $chapter; ?>');
        localStorage.removeItem(timeStorageKey);
        localStorage.setItem(hintStorageKey, '2'); // Give them 2 fresh hints
    }

    // --- HINT SYSTEM LOGIC ---
    let hintsRemaining = parseInt(localStorage.getItem(hintStorageKey) || '2');

    function updateHintButtonsUI() {
        const hintDisplays = document.querySelectorAll('.hint-count-display');
        const hintButtons = document.querySelectorAll('.btn-hint');

        hintDisplays.forEach(el => el.innerText = hintsRemaining);

        if (hintsRemaining <= 0) {
            hintButtons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '🔒 No Hints Left';
            });
        }
    }

    function triggerHint() {
        if (hintsRemaining > 0) {
            // 1. Deduct a hint
            hintsRemaining--;
            localStorage.setItem(hintStorageKey, hintsRemaining);
            updateHintButtonsUI();
            
            // 2. Grab the specific hint for the currently active slide
            const activeSlide = document.querySelector('.question-slide.active');
            const specificHint = activeSlide.querySelector('.specific-hint-data').innerHTML;
            
            // 3. Inject it into the modal
            document.getElementById('dynamicHintBody').innerHTML = specificHint;

            // 4. Force MathJax to render the new math formulas inside the modal!
            if (window.MathJax) {
                MathJax.typesetPromise([document.getElementById('dynamicHintBody')]).catch((err) => console.log(err.message));
            }
            
            // 5. Show the modal
            const modal = document.getElementById('formulaModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }
    }

    function closeHintModal() {
        const modal = document.getElementById('formulaModal');
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300); // Wait for fade out animation
    }

    // Initialize the buttons on load
    updateHintButtonsUI();

    // 1. Pull the time from the browser first. If empty, fallback to PHP.
    let storedTime = localStorage.getItem(timeStorageKey);
    let seconds = storedTime ? parseInt(storedTime) : <?php echo (int)$elapsed_seconds; ?>;
    
    function updateTimerDisplay() {
        let mins = Math.floor(seconds / 60);
        let secs = seconds % 60;
        timerElement.innerText = (mins < 10 ? "0" + mins : mins) + ":" + (secs < 10 ? "0" + secs : secs);
    }

    updateTimerDisplay(); 

    // 2. Fix the "Slower" bug using accurate system time tracking
    let lastTickTime = Date.now();

    setInterval(() => {
        let currentTime = Date.now();
        let timeDifference = Math.floor((currentTime - lastTickTime) / 1000);
        
        if (timeDifference >= 1) {
            seconds += timeDifference;
            lastTickTime = currentTime - ((currentTime - lastTickTime) % 1000); 
            updateTimerDisplay();
            localStorage.setItem(timeStorageKey, seconds);
        }
    }, 1000);

    function pauseQuiz() {
        window.location.href = "pause.php?chapter=<?php echo $chapter; ?>&time=" + seconds;
    }

    // --- SLIDE PAGINATION LOGIC ---
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.question-slide');

    function changeSlide(direction) {
        slides[currentSlideIndex].classList.remove('active');
        currentSlideIndex += direction;
        slides[currentSlideIndex].classList.add('active');
    }

    // --- OPTION SELECTION & AUTO-SAVE LOGIC ---
    const totalQuestions = <?php echo (int)$total_questions; ?>;
    const answerStorageKey = 'quiz_answers_<?php echo $username; ?>_chap_<?php echo $chapter; ?>';

    function selectOption(clickedLabel) {
        const container = clickedLabel.closest('.options-container');
        const allLabels = container.querySelectorAll('.option-label');
        allLabels.forEach(label => label.classList.remove('selected'));
        clickedLabel.classList.add('selected');

        const radio = clickedLabel.querySelector('input[type="radio"]');
        radio.checked = true; 

        let savedAnswers = JSON.parse(localStorage.getItem(answerStorageKey) || '{}');
        savedAnswers[radio.name] = radio.value;
        localStorage.setItem(answerStorageKey, JSON.stringify(savedAnswers));

        updateProgressUI();
    }

    function updateProgressUI() {
        const form = document.getElementById('quizForm');
        const answeredCount = form.querySelectorAll('input[type="radio"]:checked').length;

        const trackers = document.querySelectorAll('.live-tracker');
        trackers.forEach(tracker => {
            tracker.innerText = `Answered: ${answeredCount} / ${totalQuestions}`;
            if(answeredCount === totalQuestions) {
                tracker.style.color = '#2ecc71'; 
            }
        });

        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            if (answeredCount === totalQuestions) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-submit-locked');
                submitBtn.classList.add('btn-submit-ready');
                submitBtn.innerText = "Submit Quiz ✓";
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-submit-ready');
                submitBtn.classList.add('btn-submit-locked');
                let remaining = totalQuestions - answeredCount;
                submitBtn.innerText = `Answer ${remaining} more to submit`;
            }
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        let savedAnswers = JSON.parse(localStorage.getItem(answerStorageKey) || '{}');
        for (let qName in savedAnswers) {
            let radio = document.querySelector(`input[name="${qName}"][value="${savedAnswers[qName]}"]`);
            if (radio) {
                let label = radio.closest('.option-label');
                const container = label.closest('.options-container');
                const allLabels = container.querySelectorAll('.option-label');
                allLabels.forEach(l => l.classList.remove('selected'));
                label.classList.add('selected');
                radio.checked = true;
            }
        }
        updateProgressUI();
    });
    // --- PREVENT DOUBLE/CANCEL SUBMISSION LOOP ---
    document.getElementById('quizForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        
        // Change text to show it's working
        btn.innerText = "Submitting... ⏳";
        btn.classList.remove('btn-submit-ready');
        btn.classList.add('btn-submit-locked');
        
        // Disable the button just after the form triggers its submit action
        setTimeout(() => {
            btn.disabled = true;
        }, 10);
    });
</script>

</body>
</html>
