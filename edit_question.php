<?php
session_start();
require 'config.php'; // Your Supabase connection

$message = "";

// 1. Check if an ID was passed in the URL
if (!isset($_GET['question_id']) || empty($_GET['question_id'])) {
    die("❌ Error: No question ID provided. <a href='import.php'>Go back</a>");
}

$question_id = $_GET['question_id'];

// 2. Handle the Form Submission (When admin clicks "Save Changes")
if (isset($_POST['update_question'])) {
    $chapter = $_POST['chapter'];
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_option = $_POST['correct_option'];
    $method_text = $_POST['method']; // The method field
    $hint_text = $_POST['hint']; // The NEW hint field

    try {
        // FIXED: Added 'hint = ?' to the SQL query
        $update_stmt = $pdo->prepare("UPDATE question SET chapter = ?, question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, method = ?, hint = ? WHERE question_id = ?");
        $update_stmt->execute([$chapter, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $method_text, $hint_text, $question_id]);
        
        $message = "<div class='alert success'>✅ Question updated successfully! <a href='import.php' style='color:#155724; text-decoration:underline;'>Return to Admin Dashboard</a></div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>❌ Error updating question. (Did you remember to add the 'hint' column to your Supabase table?) <br><br>Details: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 3. Fetch the current question data to pre-fill the form
try {
    $fetch_stmt = $pdo->prepare("SELECT * FROM question WHERE question_id = ?");
    $fetch_stmt->execute([$question_id]);
    $q = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$q) {
        die("❌ Error: Question not found in the database. <a href='import.php'>Go back</a>");
    }
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4 Flat 4 Everyone - Edit Question</title>
    <link rel="stylesheet" href="/Ken Project/assets/css/interior.css">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <style>
        body { font-family: 'Inter', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 40px; color: #fff; min-height: 100vh;}
        .edit-container { max-width: 900px; margin: 0 auto; padding: 40px; border-top: 4px solid #00e5ff;}
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .header h2 { margin: 0; color: #fff; font-size: 28px; letter-spacing: -0.5px; font-weight: 700;}
        .btn-back { background: rgba(255,255,255,0.05); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.1); padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; transition: all 0.2s;}
        .btn-back:hover { background: rgba(255,255,255,0.1); color: #fff;}
        
        /* Form Styles */
        .form-group { margin-bottom: 25px; }
        label { display: block; font-weight: 600; margin-bottom: 10px; color: #94a3b8; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        input[type="text"], input[type="number"], select, textarea { 
            width: 100%; padding: 15px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; 
            box-sizing: border-box; font-family: 'Inter'; color: #fff; font-size: 15px; transition: all 0.3s;
        }
        select option { background: #0b0f19; color: #fff; }
        textarea { resize: vertical; min-height: 120px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.1); }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: rgba(0,0,0,0.2); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px;}
        
        .btn-save { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 15px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%; font-size: 16px; margin-top: 10px; transition: all 0.2s; text-transform: uppercase; letter-spacing: 1px; font-family: 'Inter';}
        .btn-save:hover { background: #10b981; color: #fff; box-shadow: 0 0 20px rgba(16,185,129,0.4); }

        /* Preview Box Styles */
        .preview-box { background: rgba(0,0,0,0.4); border: 1px dashed rgba(255,255,255,0.2); padding: 20px; border-radius: 8px; margin-top: 10px; min-height: 40px; color: #fff; font-family: 'Inter';}
        .preview-box-small { background: rgba(0,0,0,0.4); border: 1px dashed rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; margin-top: 10px; min-height: 25px; font-size: 15px; color: #fff;}
        .preview-header { font-size: 11px; text-transform: uppercase; color: #00e5ff; font-weight: 700; margin-bottom: 10px; display: block; letter-spacing: 1px;}

        /* Alerts */
        .alert { padding: 15px; margin-bottom: 25px; border-radius: 8px; font-weight: 600; }
        .success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .error { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    </style>
</head>
<body>

<div class="edit-container glass-panel">
    <div class="header">
        <h2>✏️ Edit Question #<?php echo htmlspecialchars($q['question_id']); ?></h2>
        <a href="import.php" class="btn-back">🔙 Back</a>
    </div>

    <?php echo $message; ?>

    <form method="POST" action="edit_question.php?question_id=<?php echo htmlspecialchars($question_id); ?>">
        
        <div class="form-group">
            <label for="chapter">Chapter</label>
            <input type="text" id="chapter" name="chapter" value="<?php echo htmlspecialchars($q['chapter']); ?>" required>
        </div>

        <div class="form-group">
            <label for="question_text">Question Text</label>
            <textarea id="question_text" name="question_text" required oninput="updatePreview('question_text', 'preview_question')"><?php echo htmlspecialchars($q['question_text']); ?></textarea>
            <div class="preview-box">
                <span class="preview-header">Live Preview</span>
                <div id="preview_question"><?php echo htmlspecialchars($q['question_text']); ?></div>
            </div>
        </div>

        <div class="form-group">
            <label for="method">Method / Solution (Optional)</label>
            <textarea id="method" name="method" placeholder="Show the step-by-step working here..." oninput="updatePreview('method', 'preview_method')"><?php echo htmlspecialchars($q['method'] ?? ''); ?></textarea>
            <div class="preview-box">
                <span class="preview-header">Live Preview</span>
                <div id="preview_method"><?php echo htmlspecialchars($q['method'] ?? ''); ?></div>
            </div>
        </div>

        <div class="form-group">
            <label for="hint">Specific Hint (Optional)</label>
            <textarea id="hint" name="hint" placeholder="Provide a helpful formula or hint for this question..." oninput="updatePreview('hint', 'preview_hint')"><?php echo htmlspecialchars($q['hint'] ?? ''); ?></textarea>
            <div class="preview-box">
                <span class="preview-header">Live Preview</span>
                <div id="preview_hint"><?php echo htmlspecialchars($q['hint'] ?? ''); ?></div>
            </div>
        </div>

        <div class="options-grid">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="option_a">Option A</label>
                <input type="text" id="option_a" name="option_a" value="<?php echo htmlspecialchars($q['option_a']); ?>" required oninput="updatePreview('option_a', 'preview_a')">
                <div class="preview-box-small" id="preview_a"><?php echo htmlspecialchars($q['option_a']); ?></div>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="option_b">Option B</label>
                <input type="text" id="option_b" name="option_b" value="<?php echo htmlspecialchars($q['option_b']); ?>" required oninput="updatePreview('option_b', 'preview_b')">
                <div class="preview-box-small" id="preview_b"><?php echo htmlspecialchars($q['option_b']); ?></div>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="option_c">Option C</label>
                <input type="text" id="option_c" name="option_c" value="<?php echo htmlspecialchars($q['option_c']); ?>" required oninput="updatePreview('option_c', 'preview_c')">
                <div class="preview-box-small" id="preview_c"><?php echo htmlspecialchars($q['option_c']); ?></div>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="option_d">Option D</label>
                <input type="text" id="option_d" name="option_d" value="<?php echo htmlspecialchars($q['option_d']); ?>" required oninput="updatePreview('option_d', 'preview_d')">
                <div class="preview-box-small" id="preview_d"><?php echo htmlspecialchars($q['option_d']); ?></div>
            </div>
        </div>

        <div class="form-group">
            <label for="correct_option">Correct Option</label>
            <select id="correct_option" name="correct_option" required>
                <option value="A" <?php if ($q['correct_option'] == 'A') echo 'selected'; ?>>A</option>
                <option value="B" <?php if ($q['correct_option'] == 'B') echo 'selected'; ?>>B</option>
                <option value="C" <?php if ($q['correct_option'] == 'C') echo 'selected'; ?>>C</option>
                <option value="D" <?php if ($q['correct_option'] == 'D') echo 'selected'; ?>>D</option>
            </select>
        </div>

        <button type="submit" name="update_question" class="btn-save">💾 Save Changes</button>
    </form>
</div>

<script>
    function updatePreview(inputId, previewId) {
        var text = document.getElementById(inputId).value;
        var preview = document.getElementById(previewId);
        
        // Update the text inside the specific preview box
        preview.innerHTML = text;
        
        // Tell MathJax to re-render the math in that specific box
        if (window.MathJax) {
            MathJax.typesetPromise([preview]).catch(function (err) {
                console.log('MathJax error: ' + err.message);
            });
        }
    }
</script>

</body>
</html>
