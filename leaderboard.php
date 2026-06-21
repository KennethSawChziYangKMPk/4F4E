<?php
session_start();
require 'config.php'; // Load central Supabase connection

// SECURITY: Kick out anyone who isn't logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

try {
    // Grab the chapter from the URL (Defaults to 1 if missing)
    $chapter = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 1;

    // Fetch the top 10 AND the current user's rank using a Window Function
    $sql = "WITH RankedScores AS (
                SELECT username, 
                       MAX(score) as max_score, 
                       MIN(time_taken_seconds) as fastest_time,
                       RANK() OVER (ORDER BY MAX(score) DESC, MIN(time_taken_seconds) ASC) as rank
                FROM quiz_history 
                WHERE chapter = ? 
                GROUP BY username
            )
            SELECT * FROM RankedScores 
            WHERE rank <= 10 OR username = ?
            ORDER BY rank ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$chapter, $username]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate the data for easy display
    $top_10 = [];
    $my_data = null;

    foreach ($results as $row) {
        if ($row['rank'] <= 10) {
            $top_10[] = $row;
        }
        if ($row['username'] === $username) {
            $my_data = $row;
        }
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
    <title>Leaderboard - Chapter <?php echo htmlspecialchars($chapter); ?></title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .header-text h2 { color: #ffffff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px; margin: 0 0 5px 0; }
        .header-text p { color: #94a3b8; font-size: 16px; margin: 0; }
        
        .back-btn { background: rgba(255,255,255,0.05); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; transition: all 0.2s ease; white-space: nowrap; }
        .back-btn:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 15px rgba(0,229,255,0.4); }

        .table-wrapper { width: 100%; overflow-x: auto; background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .table-container { padding: 25px; min-width: 600px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.05); color: #e2e8f0; padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid rgba(255,255,255,0.1); text-transform: uppercase; font-size: 13px; letter-spacing: 1px;}
        td { padding: 16px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; font-size: 15px;}
        tr:hover td { background: rgba(255,255,255,0.02); }
        tr:last-child td { border-bottom: none; }
        
        .rank-col { font-weight: 700; font-size: 18px; text-align: center; width: 60px; color: #fff; }
        .gold .rank-col { color: #f59e0b; font-size: 24px; text-shadow: 0 0 15px rgba(245,158,11,0.5); }
        .silver .rank-col { color: #cbd5e1; font-size: 22px; text-shadow: 0 0 15px rgba(203,213,225,0.5); }
        .bronze .rank-col { color: #d97706; font-size: 20px; text-shadow: 0 0 15px rgba(217,119,6,0.5); }
        
        .gold td { background: rgba(245,158,11,0.05); }
        
        .my-row td { background: rgba(0,229,255,0.1); font-weight: 600; color: #00e5ff; border-top: 1px solid rgba(0,229,255,0.2); border-bottom: 1px solid rgba(0,229,255,0.2);}
        .my-row:hover td { background: rgba(0,229,255,0.15); }
        
        .separator-row td { text-align: center; color: #64748b; font-size: 24px; letter-spacing: 5px; padding: 10px; background: transparent !important; border: none;}
        
        .empty-state { color: #94a3b8; font-style: italic; font-size: 16px; text-align: center; padding: 40px; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .header-text h2 { font-size: 26px; }
            .table-container { padding: 15px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="header">
        <div class="header-text">
            <h2>🏆 Chapter <?php echo htmlspecialchars($chapter); ?> Top Scholars</h2>
            <p>See who is leading the pack for this chapter.</p>
        </div>
        <div>
            <a href="chapters.php" class="back-btn">← Back to Chapters</a>
        </div>
    </div>

    <div class="table-wrapper glass-panel">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="rank-col">Rank</th>
                        <th>Student</th>
                        <th>Best Score</th>
                        <th>Fastest Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($top_10) > 0): ?>
                        <?php 
                        foreach($top_10 as $row): 
                            $class = '';
                            $rank = $row['rank'];
                            
                            if($rank == 1) $class = 'gold';
                            elseif($rank == 2) $class = 'silver';
                            elseif($rank == 3) $class = 'bronze';
                            
                            // Highlight if this row belongs to the logged-in user
                            if ($row['username'] === $username) {
                                $class .= ' my-row'; 
                            }
                        ?>
                        <tr class="<?php echo trim($class); ?>">
                            <td class="rank-col">
                                <?php 
                                    if($rank == 1) echo '🥇'; 
                                    elseif($rank == 2) echo '🥈'; 
                                    elseif($rank == 3) echo '🥉'; 
                                    else echo $rank; 
                                ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if($row['username'] === $username) echo " (You)"; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['max_score']); ?> / 10</td>
                            <td><?php echo htmlspecialchars($row['fastest_time']); ?>s</td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php 
                        // If the user has taken the quiz, but is NOT in the top 10, pin their rank at the bottom
                        if ($my_data && $my_data['rank'] > 10): 
                        ?>
                            <tr class="separator-row"><td colspan="4">&bull; &bull; &bull;</td></tr>
                            <tr class="my-row">
                                <td class="rank-col"><?php echo htmlspecialchars($my_data['rank']); ?></td>
                                <td><?php echo htmlspecialchars($my_data['username']); ?> (You)</td>
                                <td><?php echo htmlspecialchars($my_data['max_score']); ?> / 10</td>
                                <td><?php echo htmlspecialchars($my_data['fastest_time']); ?>s</td>
                            </tr>
                        <?php endif; ?>

                        <?php 
                        // If the user hasn't attempted this chapter yet
                        if (!$my_data): 
                        ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #e67e22; background: #fff3e0; font-weight: bold;">
                                    You haven't attempted this chapter yet! Take the quiz to get ranked.
                                </td>
                            </tr>
                        <?php endif; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">No scores yet! Take the quiz and claim 1st place!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
