<?php
/**
 * Student Company-wise Questions: company.php
 * Filter and practice questions specific to IT recruiters (TCS, Infosys, Wipro, Capgemini, Accenture).
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

// --- CSRF Token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Student ID from session only ---
$student_id = (int) $_SESSION['student_id'];

// --- Detect if new columns exist (graceful degradation) ---
$has_new_cols = false;
$col_check = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_questions' AND COLUMN_NAME = 'category'");
if ($col_check && mysqli_num_rows($col_check) > 0) {
    $has_new_cols = true;
}

$has_bookmarks_table = false;
$tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'student_question_bookmarks'");
if ($tbl_check && mysqli_num_rows($tbl_check) > 0) {
    $has_bookmarks_table = true;
}

$has_progress_table = false;
$tbl_check2 = mysqli_query($conn, "SHOW TABLES LIKE 'student_question_progress'");
if ($tbl_check2 && mysqli_num_rows($tbl_check2) > 0) {
    $has_progress_table = true;
}

// ========================================
// AJAX POST Handler (bookmark / progress)
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['ajax_action'] ?? '';
    $question_id = isset($_POST['question_id']) ? (int) $_POST['question_id'] : 0;

    if ($question_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid question ID']);
        exit;
    }

    // Verify question exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM company_questions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $question_id);
    mysqli_stmt_execute($stmt);
    $qr = mysqli_stmt_get_result($stmt);
    if (!$qr || mysqli_num_rows($qr) === 0) {
        echo json_encode(['success' => false, 'error' => 'Question not found']);
        exit;
    }
    mysqli_stmt_close($stmt);

    switch ($action) {
        case 'toggle_bookmark':
            if (!$has_bookmarks_table) {
                echo json_encode(['success' => false, 'error' => 'Bookmarks not available. Run migration first.']);
                exit;
            }
            // Check if already bookmarked
            $stmt = mysqli_prepare($conn, "SELECT id FROM student_question_bookmarks WHERE student_id = ? AND question_id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $student_id, $question_id);
            mysqli_stmt_execute($stmt);
            $br = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($br) > 0) {
                // Remove bookmark
                $del = mysqli_prepare($conn, "DELETE FROM student_question_bookmarks WHERE student_id = ? AND question_id = ?");
                mysqli_stmt_bind_param($del, 'ii', $student_id, $question_id);
                mysqli_stmt_execute($del);
                mysqli_stmt_close($del);
                echo json_encode(['success' => true, 'bookmarked' => false]);
            } else {
                // Add bookmark
                $ins = mysqli_prepare($conn, "INSERT INTO student_question_bookmarks (student_id, question_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($ins, 'ii', $student_id, $question_id);
                mysqli_stmt_execute($ins);
                mysqli_stmt_close($ins);
                echo json_encode(['success' => true, 'bookmarked' => true]);
            }
            mysqli_stmt_close($stmt);
            exit;

        case 'toggle_solved':
            if (!$has_progress_table) {
                echo json_encode(['success' => false, 'error' => 'Progress tracking not available. Run migration first.']);
                exit;
            }
            $status_input = $_POST['status'] ?? '';
            $allowed_statuses = ['attempted', 'solved', 'remove'];
            if (!in_array($status_input, $allowed_statuses, true)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
                exit;
            }

            if ($status_input === 'remove') {
                $del = mysqli_prepare($conn, "DELETE FROM student_question_progress WHERE student_id = ? AND question_id = ?");
                mysqli_stmt_bind_param($del, 'ii', $student_id, $question_id);
                mysqli_stmt_execute($del);
                mysqli_stmt_close($del);
                echo json_encode(['success' => true, 'status' => 'none']);
            } else {
                // Upsert
                $stmt2 = mysqli_prepare($conn, "SELECT id FROM student_question_progress WHERE student_id = ? AND question_id = ?");
                mysqli_stmt_bind_param($stmt2, 'ii', $student_id, $question_id);
                mysqli_stmt_execute($stmt2);
                $pr = mysqli_stmt_get_result($stmt2);
                if (mysqli_num_rows($pr) > 0) {
                    $upd = mysqli_prepare($conn, "UPDATE student_question_progress SET status = ? WHERE student_id = ? AND question_id = ?");
                    mysqli_stmt_bind_param($upd, 'sii', $status_input, $student_id, $question_id);
                    mysqli_stmt_execute($upd);
                    mysqli_stmt_close($upd);
                } else {
                    $ins = mysqli_prepare($conn, "INSERT INTO student_question_progress (student_id, question_id, status) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($ins, 'iis', $student_id, $question_id, $status_input);
                    mysqli_stmt_execute($ins);
                    mysqli_stmt_close($ins);
                }
                mysqli_stmt_close($stmt2);
                echo json_encode(['success' => true, 'status' => $status_input]);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
}

// ========================================
// Page Data Queries (all prepared statements)
// ========================================

// Whitelist inputs
$allowed_difficulties = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
$allowed_categories = ['Programming and DSA', 'Database and SQL', 'Core CS', 'Advanced Topics', 'Scenario-based'];

// Get filter params
$selected_company = trim($_GET['company'] ?? '');
$selected_category = trim($_GET['category'] ?? '');
$selected_difficulty = trim($_GET['difficulty'] ?? '');
$search_query = trim($_GET['q'] ?? '');
$view_mode = trim($_GET['view'] ?? ''); // 'bookmarked' or 'all'

// Validate filters
if ($selected_difficulty !== '' && !in_array($selected_difficulty, $allowed_difficulties, true)) {
    $selected_difficulty = '';
}
if ($selected_category !== '' && !in_array($selected_category, $allowed_categories, true)) {
    $selected_category = '';
}
if (!in_array($view_mode, ['bookmarked', 'all', ''], true)) {
    $view_mode = '';
}

// Company meta info for cards
$company_meta = [
    'TCS' => ['color' => '#1a73e8', 'icon' => 'bi-cpu', 'desc' => 'Tata Consultancy Services — India\'s largest IT services company. Known for NQT-based hiring with aptitude, coding, and technical rounds.'],
    'Infosys' => ['color' => '#0066b3', 'icon' => 'bi-diagram-3', 'desc' => 'Global leader in consulting and digital services. InfyTQ platform, pseudocode rounds, and structured technical interviews.'],
    'Wipro' => ['color' => '#44167e', 'icon' => 'bi-lightning', 'desc' => 'IT services and consulting major. Elite NLTH coding rounds, networking, and OS-focused technical interviews.'],
    'Accenture' => ['color' => '#a100ff', 'icon' => 'bi-arrow-up-right-circle', 'desc' => 'Global professional services company. Coding assessments, logical reasoning, and agile/SDLC interview patterns.'],
    'Cognizant' => ['color' => '#0033a0', 'icon' => 'bi-grid-3x3-gap', 'desc' => 'Technology and consulting corporation. GenC hiring with OOP deep-dives, database normalization, and coding rounds.'],
    'Capgemini' => ['color' => '#0070ad', 'icon' => 'bi-globe2', 'desc' => 'French multinational IT services company. Game-based assessments, pseudocode evaluation, and design pattern discussions.'],
    'HCLTech' => ['color' => '#00649b', 'icon' => 'bi-hdd-network', 'desc' => 'India-based IT services and consulting. Systems programming, networking, debugging, and infrastructure-focused rounds.'],
    'Tech Mahindra' => ['color' => '#e30613', 'icon' => 'bi-phone-landscape', 'desc' => 'IT subsidiary of the Mahindra Group. Full-stack concepts, API design, and database optimization interviews.'],
    'Amazon' => ['color' => '#ff9900', 'icon' => 'bi-box-seam', 'desc' => 'FAANG giant with rigorous SDE hiring. Leadership principles, scalable system design, DP, and advanced DSA.'],
    'Microsoft' => ['color' => '#00a4ef', 'icon' => 'bi-windows', 'desc' => 'Big Tech with deep engineering culture. Graph problems, OOP design, concurrency, and system architecture rounds.'],
    'Google' => ['color' => '#4285f4', 'icon' => 'bi-search', 'desc' => 'World\'s top tech company. Hard algorithmic challenges, distributed systems, optimization, and scalability design.'],
    'IBM' => ['color' => '#054ada', 'icon' => 'bi-server', 'desc' => 'Enterprise technology leader. Data structures, cloud concepts, enterprise patterns, and algorithm optimization.'],
    'Deloitte' => ['color' => '#86bc25', 'icon' => 'bi-briefcase', 'desc' => 'Big Four consulting firm. Enterprise architecture, database design, testing strategies, and consulting scenarios.'],
    'Oracle' => ['color' => '#f80000', 'icon' => 'bi-database', 'desc' => 'Database and cloud technology giant. Complex SQL, database internals, B-tree indexing, transactions, and JVM concepts.'],
];

// Fetch all distinct companies from DB
$comp_res = mysqli_query($conn, "SELECT DISTINCT company_name FROM company_questions ORDER BY company_name ASC");
$db_companies = [];
while ($row = mysqli_fetch_assoc($comp_res)) {
    $db_companies[] = $row['company_name'];
}

// If no company selected, default to first or empty
if ($selected_company === '' && !empty($db_companies)) {
    // Show company cards view (no company selected)
    $show_company_list = true;
} else {
    $show_company_list = false;
    // Validate selected company exists
    if (!in_array($selected_company, $db_companies, true) && $selected_company !== '') {
        $selected_company = $db_companies[0] ?? 'TCS';
    }
}

// --- Fetch questions for selected company (prepared stmt) ---
$questions = [];
$where_clauses = [];
$bind_types = '';
$bind_values = [];

if (!$show_company_list) {
    $where_clauses[] = "cq.company_name = ?";
    $bind_types .= 's';
    $bind_values[] = $selected_company;
}

if ($selected_category !== '' && $has_new_cols) {
    $where_clauses[] = "cq.category = ?";
    $bind_types .= 's';
    $bind_values[] = $selected_category;
}

if ($selected_difficulty !== '' && $has_new_cols) {
    $where_clauses[] = "cq.difficulty = ?";
    $bind_types .= 's';
    $bind_values[] = $selected_difficulty;
}

if ($search_query !== '') {
    $where_clauses[] = "(cq.question LIKE ? OR cq.answer LIKE ? OR cq.company_name LIKE ?)";
    $bind_types .= 'sss';
    $search_like = '%' . $search_query . '%';
    $bind_values[] = $search_like;
    $bind_values[] = $search_like;
    $bind_values[] = $search_like;
}

// Bookmarked view
if ($view_mode === 'bookmarked' && $has_bookmarks_table) {
    $where_clauses[] = "cq.id IN (SELECT question_id FROM student_question_bookmarks WHERE student_id = ?)";
    $bind_types .= 'i';
    $bind_values[] = $student_id;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Build SELECT with optional new columns
if ($has_new_cols) {
    $select_cols = "cq.id, cq.company_name, cq.topic, cq.question, cq.answer, cq.difficulty, cq.question_type, cq.explanation, cq.programming_language, cq.time_complexity, cq.space_complexity, cq.sample_input, cq.sample_output, cq.source_type, cq.category";
} else {
    $select_cols = "cq.id, cq.company_name, cq.topic, cq.question, cq.answer, 'Beginner' as difficulty, NULL as question_type, NULL as explanation, NULL as programming_language, NULL as time_complexity, NULL as space_complexity, NULL as sample_input, NULL as sample_output, 'practice' as source_type, cq.topic as category";
}

$sql = "SELECT $select_cols FROM company_questions cq $where_sql ORDER BY cq.company_name ASC, cq.id ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $questions[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// --- Fetch student bookmarks ---
$student_bookmarks = [];
if ($has_bookmarks_table) {
    $stmt = mysqli_prepare($conn, "SELECT question_id FROM student_question_bookmarks WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $br = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($br)) {
        $student_bookmarks[$row['question_id']] = true;
    }
    mysqli_stmt_close($stmt);
}

// --- Fetch student progress ---
$student_progress = [];
if ($has_progress_table) {
    $stmt = mysqli_prepare($conn, "SELECT question_id, status FROM student_question_progress WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $pr = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($pr)) {
        $student_progress[$row['question_id']] = $row['status'];
    }
    mysqli_stmt_close($stmt);
}

// --- Compute per-company stats ---
$company_stats = [];
$stats_query = "SELECT company_name, COUNT(*) as total FROM company_questions GROUP BY company_name";
$sr = mysqli_query($conn, $stats_query);
while ($row = mysqli_fetch_assoc($sr)) {
    $company_stats[$row['company_name']] = ['total' => (int)$row['total'], 'solved' => 0, 'attempted' => 0];
}

if ($has_progress_table) {
    $stmt = mysqli_prepare($conn, "SELECT cq.company_name, sp.status, COUNT(*) as cnt FROM student_question_progress sp JOIN company_questions cq ON sp.question_id = cq.id WHERE sp.student_id = ? GROUP BY cq.company_name, sp.status");
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $pr = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($pr)) {
        if (isset($company_stats[$row['company_name']])) {
            $company_stats[$row['company_name']][$row['status']] = (int)$row['cnt'];
        }
    }
    mysqli_stmt_close($stmt);
}

// --- Overall stats ---
$total_questions = array_sum(array_column($company_stats, 'total'));
$total_solved = array_sum(array_column($company_stats, 'solved'));
$total_attempted = array_sum(array_column($company_stats, 'attempted'));
$total_bookmarked = count($student_bookmarks);

// --- Category counts for current view ---
$category_counts = [];
foreach ($questions as $q) {
    $cat = $q['category'] ?? 'Uncategorized';
    if (!isset($category_counts[$cat])) $category_counts[$cat] = 0;
    $category_counts[$cat]++;
}

// --- Difficulty counts for current view ---
$difficulty_counts = [];
foreach ($questions as $q) {
    $diff = $q['difficulty'] ?? 'Beginner';
    if (!isset($difficulty_counts[$diff])) $difficulty_counts[$diff] = 0;
    $difficulty_counts[$diff]++;
}

$page_title = $show_company_list ? "Company-wise Interview Prep" : htmlspecialchars($selected_company) . " Interview Questions";
$page_heading = "Company-wise Placement Questions";
$current_page = "company";

require_once '../includes/student_header.php';
?>

<!-- ============================= SCOPED STYLES ============================= -->
<style>
/* Company Questions - Scoped Styles (cq- prefix) */
.cq-hero {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 60%, #084298 100%);
    border-radius: 12px;
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.cq-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.cq-hero h4 { font-weight: 700; margin-bottom: 6px; position: relative; z-index: 1; }
.cq-hero p { opacity: 0.85; margin-bottom: 0; position: relative; z-index: 1; }

/* Stats bar */
.cq-stats-bar {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.cq-stat-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
    flex: 1;
    min-width: 150px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}
.cq-stat-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.cq-stat-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.cq-stat-icon.blue { background: #e7f1ff; color: #0d6efd; }
.cq-stat-icon.green { background: #d1e7dd; color: #198754; }
.cq-stat-icon.orange { background: #fff3cd; color: #fd7e14; }
.cq-stat-icon.pink { background: #f8d7da; color: #dc3545; }
.cq-stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; line-height: 1; }
.cq-stat-label { font-size: 0.8rem; color: #64748b; margin-top: 2px; }

/* Search & Filter Bar */
.cq-filter-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

/* Company Cards Grid */
.cq-company-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.cq-company-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: block;
}
.cq-company-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #0d6efd;
    color: inherit;
    text-decoration: none;
}
.cq-company-card.cq-active {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13,110,253,0.25);
}
.cq-company-badge {
    width: 52px; height: 52px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 800; color: #fff;
    margin-bottom: 14px; letter-spacing: -0.5px;
}
.cq-company-card h6 { font-weight: 700; font-size: 1rem; margin-bottom: 6px; color: #1e293b; }
.cq-company-card .cq-card-desc { font-size: 0.8rem; color: #64748b; line-height: 1.4; margin-bottom: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.cq-progress-info { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: #64748b; margin-bottom: 6px; }
.cq-progress-bar-wrap { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.cq-progress-bar-fill { height: 100%; border-radius: 3px; transition: width 0.4s ease; }

/* Question Cards */
.cq-question-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 16px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.cq-question-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
.cq-question-card.cq-solved { border-left: 4px solid #198754; }
.cq-question-card.cq-attempted { border-left: 4px solid #fd7e14; }
.cq-question-card.cq-bookmarked-card { border-left: 4px solid #0d6efd; }

.cq-q-header {
    padding: 16px 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    user-select: none;
}
.cq-q-header:hover { background: #f8fafc; }
.cq-q-num {
    background: #e7f1ff;
    color: #0d6efd;
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
}
.cq-q-title { font-weight: 600; font-size: 0.95rem; color: #1e293b; flex-grow: 1; line-height: 1.45; }
.cq-q-meta { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
.cq-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600;
}
.cq-badge-beginner { background: #d1e7dd; color: #0f5132; }
.cq-badge-intermediate { background: #fff3cd; color: #664d03; }
.cq-badge-advanced { background: #f8d7da; color: #842029; }
.cq-badge-expert { background: #6f42c1; color: #fff; }
.cq-badge-type { background: #e7f1ff; color: #0d6efd; }
.cq-badge-source { background: #f0f0f0; color: #555; }
.cq-badge-category { background: #e8f5e9; color: #2e7d32; }

.cq-q-actions {
    display: flex; gap: 6px; flex-shrink: 0; align-items: center;
}
.cq-action-btn {
    border: none; background: none; padding: 6px 8px; border-radius: 6px;
    cursor: pointer; font-size: 1.1rem; transition: all 0.2s;
    color: #94a3b8;
}
.cq-action-btn:hover { background: #f1f5f9; color: #0d6efd; }
.cq-action-btn.cq-bookmarked { color: #0d6efd; }
.cq-action-btn.cq-is-solved { color: #198754; }
.cq-action-btn.cq-is-attempted { color: #fd7e14; }
.cq-expand-icon { transition: transform 0.3s; font-size: 1rem; color: #94a3b8; }
.cq-expand-icon.cq-expanded { transform: rotate(180deg); }

/* Answer/Detail Panel */
.cq-answer-panel {
    display: none;
    border-top: 1px solid #e2e8f0;
    padding: 0;
}
.cq-answer-panel.cq-open { display: block; }

.cq-answer-section {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
}
.cq-answer-section:last-child { border-bottom: none; }
.cq-section-title {
    font-weight: 700; font-size: 0.85rem; color: #0d6efd;
    margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    text-transform: uppercase; letter-spacing: 0.3px;
}
.cq-section-content {
    font-size: 0.9rem; color: #334155; line-height: 1.7;
    white-space: pre-line;
}
.cq-code-block {
    background: #1e293b; color: #e2e8f0; border-radius: 8px;
    padding: 16px; font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.85rem; line-height: 1.6; overflow-x: auto;
    margin: 8px 0; position: relative;
    white-space: pre; tab-size: 4;
}
.cq-code-label {
    position: absolute; top: 8px; right: 10px;
    background: rgba(255,255,255,0.1); color: #94a3b8;
    padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;
}
.cq-code-tabs { display: flex; gap: 0; margin-bottom: 0; }
.cq-code-tab {
    padding: 8px 16px; font-size: 0.8rem; font-weight: 600;
    border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b;
    cursor: pointer; border-bottom: none; border-radius: 6px 6px 0 0;
    transition: all 0.2s;
}
.cq-code-tab.cq-tab-active { background: #1e293b; color: #e2e8f0; border-color: #1e293b; }
.cq-code-tab-content { display: none; }
.cq-code-tab-content.cq-tab-visible { display: block; }

.cq-complexity-box {
    display: flex; gap: 16px; flex-wrap: wrap;
}
.cq-complexity-item {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 10px 16px; flex: 1; min-width: 140px;
}
.cq-complexity-item strong { color: #1e293b; font-size: 0.8rem; display: block; margin-bottom: 2px; }
.cq-complexity-item code { font-size: 1rem; color: #0d6efd; font-weight: 700; }

.cq-io-box {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}
.cq-io-item {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;
}
.cq-io-item strong { font-size: 0.8rem; color: #64748b; display: block; margin-bottom: 6px; }
.cq-io-item pre { margin: 0; font-size: 0.85rem; color: #1e293b; white-space: pre-wrap; }

/* Back button */
.cq-back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    color: #0d6efd; font-weight: 600; font-size: 0.9rem;
    text-decoration: none; margin-bottom: 16px; padding: 6px 12px;
    border-radius: 6px; transition: background 0.2s;
}
.cq-back-btn:hover { background: #e7f1ff; color: #0a58ca; }

/* Responsive */
@media (max-width: 991.98px) {
    .cq-company-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
    .cq-stats-bar { gap: 10px; }
    .cq-stat-item { min-width: 120px; padding: 12px 14px; }
    .cq-io-box { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
    .cq-hero { padding: 20px; }
    .cq-company-grid { grid-template-columns: 1fr; }
    .cq-stat-item { min-width: 100%; }
    .cq-q-header { padding: 12px 14px; flex-wrap: wrap; }
    .cq-answer-section { padding: 12px 14px; }
    .cq-code-block { font-size: 0.78rem; padding: 12px; }
    .cq-complexity-box { flex-direction: column; }
}

/* Animations */
@keyframes cqFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.cq-fade-in { animation: cqFadeIn 0.3s ease forwards; }

/* Empty state */
.cq-empty-state {
    text-align: center; padding: 60px 20px; color: #94a3b8;
}
.cq-empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }
.cq-empty-state h5 { color: #64748b; font-weight: 600; }

/* Toast notification */
.cq-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    background: #1e293b; color: #fff; padding: 12px 20px;
    border-radius: 8px; font-size: 0.85rem; font-weight: 500;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    transform: translateY(100px); opacity: 0;
    transition: all 0.3s ease;
}
.cq-toast.cq-toast-show { transform: translateY(0); opacity: 1; }
</style>

<!-- ============================= HERO BANNER ============================= -->
<div class="cq-hero">
    <h4><i class="bi bi-buildings-fill me-2"></i>Company-wise Interview Preparation</h4>
    <p>Master interview patterns of top recruiters. Practice company-specific questions across coding, SQL, core CS, system design, and scenario-based rounds.</p>
</div>

<!-- ============================= STATS BAR ============================= -->
<div class="cq-stats-bar">
    <div class="cq-stat-item">
        <div class="cq-stat-icon blue"><i class="bi bi-collection"></i></div>
        <div>
            <div class="cq-stat-value"><?php echo $total_questions; ?></div>
            <div class="cq-stat-label">Total Questions</div>
        </div>
    </div>
    <div class="cq-stat-item">
        <div class="cq-stat-icon green"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="cq-stat-value"><?php echo $total_solved; ?></div>
            <div class="cq-stat-label">Solved</div>
        </div>
    </div>
    <div class="cq-stat-item">
        <div class="cq-stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="cq-stat-value"><?php echo $total_attempted; ?></div>
            <div class="cq-stat-label">Attempted</div>
        </div>
    </div>
    <div class="cq-stat-item">
        <div class="cq-stat-icon pink"><i class="bi bi-bookmark-star"></i></div>
        <div>
            <div class="cq-stat-value"><?php echo $total_bookmarked; ?></div>
            <div class="cq-stat-label">Bookmarked</div>
        </div>
    </div>
</div>

<?php if ($show_company_list): ?>
<!-- ============================= COMPANY CARDS VIEW ============================= -->

<!-- Search Bar -->
<div class="cq-filter-bar">
    <form method="GET" action="company.php" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search questions across all companies..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
        </div>
        <?php if ($has_new_cols): ?>
        <div class="col-6 col-md-2">
            <select name="category" class="form-select form-select-sm">
                <option value="">All Categories</option>
                <?php foreach ($allowed_categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="difficulty" class="form-select form-select-sm">
                <option value="">All Levels</option>
                <?php foreach ($allowed_difficulties as $diff): ?>
                    <option value="<?php echo htmlspecialchars($diff); ?>" <?php echo $selected_difficulty === $diff ? 'selected' : ''; ?>><?php echo htmlspecialchars($diff); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search me-1"></i>Search</button>
            <?php if ($search_query || $selected_category || $selected_difficulty): ?>
                <a href="company.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Quick View Tabs -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="company.php" class="btn btn-sm <?php echo $view_mode === '' && !$search_query ? 'btn-primary' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-grid-3x3-gap me-1"></i>All Companies
    </a>
    <?php if ($has_bookmarks_table): ?>
    <a href="company.php?view=bookmarked" class="btn btn-sm <?php echo $view_mode === 'bookmarked' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
        <i class="bi bi-bookmark-star me-1"></i>Bookmarked (<?php echo $total_bookmarked; ?>)
    </a>
    <?php endif; ?>
</div>

<?php if ($search_query || $view_mode === 'bookmarked'): ?>
    <!-- Show search/bookmark results -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <?php if ($view_mode === 'bookmarked'): ?>
                <i class="bi bi-bookmark-star-fill text-primary me-2"></i>Your Bookmarked Questions
            <?php else: ?>
                <i class="bi bi-search text-primary me-2"></i>Results for "<?php echo htmlspecialchars($search_query); ?>"
            <?php endif; ?>
        </h5>
        <span class="badge bg-secondary-subtle text-secondary"><?php echo count($questions); ?> found</span>
    </div>
    <?php if (empty($questions)): ?>
        <div class="cq-empty-state">
            <i class="bi bi-inbox"></i>
            <h5><?php echo $view_mode === 'bookmarked' ? 'No bookmarked questions yet' : 'No questions match your search'; ?></h5>
            <p><?php echo $view_mode === 'bookmarked' ? 'Bookmark questions while practicing to see them here.' : 'Try a different search term or remove filters.'; ?></p>
        </div>
    <?php else: ?>
        <?php
        $num = 0;
        foreach ($questions as $item):
            $num++;
            $qid = (int)$item['id'];
            $is_bookmarked = isset($student_bookmarks[$qid]);
            $progress_status = $student_progress[$qid] ?? 'none';
            $card_class = '';
            if ($progress_status === 'solved') $card_class = 'cq-solved';
            elseif ($progress_status === 'attempted') $card_class = 'cq-attempted';
            elseif ($is_bookmarked) $card_class = 'cq-bookmarked-card';
            $diff_class = 'cq-badge-' . strtolower($item['difficulty'] ?? 'beginner');
        ?>
            <?php echo cq_render_question_card($item, $num, $is_bookmarked, $progress_status, $card_class, $diff_class, true); ?>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else: ?>
    <!-- Company Cards Grid -->
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-buildings me-2 text-primary"></i>Select a Company to Begin</h6>
    <div class="cq-company-grid">
        <?php foreach ($db_companies as $comp):
            $meta = $company_meta[$comp] ?? ['color' => '#6c757d', 'icon' => 'bi-building', 'desc' => 'Interview preparation questions for ' . $comp];
            $stats = $company_stats[$comp] ?? ['total' => 0, 'solved' => 0, 'attempted' => 0];
            $progress_pct = $stats['total'] > 0 ? round(($stats['solved'] / $stats['total']) * 100) : 0;
            $initials = '';
            $words = explode(' ', $comp);
            foreach ($words as $w) { $initials .= strtoupper($w[0] ?? ''); }
            $initials = substr($initials, 0, 3);
        ?>
        <a href="company.php?company=<?php echo urlencode($comp); ?>" class="cq-company-card cq-fade-in">
            <div class="cq-company-badge" style="background: <?php echo htmlspecialchars($meta['color']); ?>">
                <?php echo htmlspecialchars($initials); ?>
            </div>
            <h6><?php echo htmlspecialchars($comp); ?></h6>
            <div class="cq-card-desc"><?php echo htmlspecialchars($meta['desc']); ?></div>
            <div class="cq-progress-info">
                <span><?php echo $stats['total']; ?> questions</span>
                <span><?php echo $stats['solved']; ?>/<?php echo $stats['total']; ?> solved</span>
            </div>
            <div class="cq-progress-bar-wrap">
                <div class="cq-progress-bar-fill" style="width: <?php echo $progress_pct; ?>%; background: <?php echo htmlspecialchars($meta['color']); ?>"></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php else: ?>
<!-- ============================= COMPANY DETAIL VIEW ============================= -->

<!-- Back Button -->
<a href="company.php" class="cq-back-btn"><i class="bi bi-arrow-left"></i>All Companies</a>

<?php
$meta = $company_meta[$selected_company] ?? ['color' => '#6c757d', 'icon' => 'bi-building', 'desc' => 'Interview preparation questions.'];
$stats = $company_stats[$selected_company] ?? ['total' => 0, 'solved' => 0, 'attempted' => 0];
$progress_pct = $stats['total'] > 0 ? round(($stats['solved'] / $stats['total']) * 100) : 0;
$initials = '';
$words = explode(' ', $selected_company);
foreach ($words as $w) { $initials .= strtoupper($w[0] ?? ''); }
$initials = substr($initials, 0, 3);
$remaining = $stats['total'] - $stats['solved'] - $stats['attempted'];
if ($remaining < 0) $remaining = 0;
?>

<!-- Company Header -->
<div class="cq-filter-bar mb-3">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="cq-company-badge" style="background: <?php echo htmlspecialchars($meta['color']); ?>; margin-bottom: 0;">
            <?php echo htmlspecialchars($initials); ?>
        </div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($selected_company); ?></h5>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($meta['desc']); ?></p>
        </div>
    </div>
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="d-flex gap-3 small">
                <span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i><?php echo $stats['solved']; ?> Solved</span>
                <span class="text-warning fw-bold"><i class="bi bi-hourglass me-1"></i><?php echo $stats['attempted']; ?> Attempted</span>
                <span class="text-muted fw-bold"><i class="bi bi-circle me-1"></i><?php echo $remaining; ?> Remaining</span>
            </div>
            <div class="cq-progress-bar-wrap mt-1">
                <div class="cq-progress-bar-fill" style="width: <?php echo $progress_pct; ?>%; background: <?php echo htmlspecialchars($meta['color']); ?>"></div>
            </div>
        </div>
        <div class="col-12 col-md-8">
            <form method="GET" action="company.php" class="row g-2">
                <input type="hidden" name="company" value="<?php echo htmlspecialchars($selected_company); ?>">
                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search questions..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                <?php if ($has_new_cols): ?>
                <div class="col-auto">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($allowed_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="difficulty" class="form-select form-select-sm">
                        <option value="">All Levels</option>
                        <?php foreach ($allowed_difficulties as $diff): ?>
                            <option value="<?php echo htmlspecialchars($diff); ?>" <?php echo $selected_difficulty === $diff ? 'selected' : ''; ?>><?php echo htmlspecialchars($diff); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i></button>
                    <?php if ($search_query || $selected_category || $selected_difficulty): ?>
                        <a href="company.php?company=<?php echo urlencode($selected_company); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Difficulty & Category Quick Filters -->
<?php if ($has_new_cols && !empty($questions)): ?>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <?php foreach ($difficulty_counts as $diff => $cnt): ?>
        <span class="cq-badge <?php
            echo match($diff) {
                'Beginner' => 'cq-badge-beginner',
                'Intermediate' => 'cq-badge-intermediate',
                'Advanced' => 'cq-badge-advanced',
                'Expert' => 'cq-badge-expert',
                default => 'cq-badge-type'
            };
        ?>"><?php echo htmlspecialchars($diff); ?>: <?php echo $cnt; ?></span>
    <?php endforeach; ?>
    <span class="text-muted">|</span>
    <?php foreach ($category_counts as $cat => $cnt): ?>
        <span class="cq-badge cq-badge-category"><?php echo htmlspecialchars($cat); ?>: <?php echo $cnt; ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Questions List -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold text-dark mb-0">
        Questions
        <?php if ($search_query): ?>
            matching "<?php echo htmlspecialchars($search_query); ?>"
        <?php endif; ?>
    </h6>
    <span class="badge bg-secondary-subtle text-secondary"><?php echo count($questions); ?> questions</span>
</div>

<?php if (empty($questions)): ?>
    <div class="cq-empty-state">
        <i class="bi bi-inbox"></i>
        <h5>No questions found</h5>
        <p>
            <?php if ($search_query || $selected_category || $selected_difficulty): ?>
                Try removing some filters or search for different keywords.
            <?php else: ?>
                No questions available for <?php echo htmlspecialchars($selected_company); ?> yet. Questions can be added via the Admin Panel.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <?php
    $num = 0;
    foreach ($questions as $item):
        $num++;
        $qid = (int)$item['id'];
        $is_bookmarked = isset($student_bookmarks[$qid]);
        $progress_status = $student_progress[$qid] ?? 'none';
        $card_class = '';
        if ($progress_status === 'solved') $card_class = 'cq-solved';
        elseif ($progress_status === 'attempted') $card_class = 'cq-attempted';
        elseif ($is_bookmarked) $card_class = 'cq-bookmarked-card';
        $diff_class = 'cq-badge-' . strtolower($item['difficulty'] ?? 'beginner');
    ?>
        <?php echo cq_render_question_card($item, $num, $is_bookmarked, $progress_status, $card_class, $diff_class, false); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>

<!-- Toast notification container -->
<div id="cqToast" class="cq-toast"></div>

</main>
</div>
</div>

<?php
// ========================================
// Helper: Render a question card
// ========================================
function cq_render_question_card($item, $num, $is_bookmarked, $progress_status, $card_class, $diff_class, $show_company) {
    $qid = (int)$item['id'];
    $question_text = htmlspecialchars($item['question'] ?? '');
    $answer_text = htmlspecialchars($item['answer'] ?? '');
    $topic = htmlspecialchars($item['topic'] ?? '');
    $difficulty = htmlspecialchars($item['difficulty'] ?? 'Beginner');
    $question_type = htmlspecialchars($item['question_type'] ?? '');
    $explanation = htmlspecialchars($item['explanation'] ?? '');
    $prog_lang = htmlspecialchars($item['programming_language'] ?? '');
    $time_c = htmlspecialchars($item['time_complexity'] ?? '');
    $space_c = htmlspecialchars($item['space_complexity'] ?? '');
    $sample_in = htmlspecialchars($item['sample_input'] ?? '');
    $sample_out = htmlspecialchars($item['sample_output'] ?? '');
    $source_type = $item['source_type'] ?? 'practice';
    $category = htmlspecialchars($item['category'] ?? '');
    $company = htmlspecialchars($item['company_name'] ?? '');

    $source_label = ($source_type === 'verified_previous_year') ? 'Verified Previous Year' : 'Company-style Practice';

    $bookmark_class = $is_bookmarked ? 'cq-bookmarked' : '';
    $solved_class = '';
    if ($progress_status === 'solved') $solved_class = 'cq-is-solved';
    elseif ($progress_status === 'attempted') $solved_class = 'cq-is-attempted';

    $solved_icon = 'bi-circle';
    $solved_title = 'Mark as attempted';
    if ($progress_status === 'solved') { $solved_icon = 'bi-check-circle-fill'; $solved_title = 'Solved — click to unmark'; }
    elseif ($progress_status === 'attempted') { $solved_icon = 'bi-hourglass-split'; $solved_title = 'Attempted — click to mark solved'; }

    $html = '<div class="cq-question-card ' . $card_class . ' cq-fade-in" data-qid="' . $qid . '">';

    // Header
    $html .= '<div class="cq-q-header" onclick="cqToggleAnswer(' . $qid . ')">';
    $html .= '<div class="cq-q-num">' . $num . '</div>';
    $html .= '<div class="flex-grow-1">';
    $html .= '<div class="cq-q-title">' . nl2br($question_text) . '</div>';
    $html .= '<div class="cq-q-meta">';
    if ($show_company && $company) {
        $html .= '<span class="cq-badge cq-badge-type"><i class="bi bi-building"></i> ' . $company . '</span>';
    }
    if ($topic) $html .= '<span class="cq-badge cq-badge-type"><i class="bi bi-tag"></i> ' . $topic . '</span>';
    $html .= '<span class="cq-badge ' . $diff_class . '">' . $difficulty . '</span>';
    if ($question_type) $html .= '<span class="cq-badge cq-badge-type">' . $question_type . '</span>';
    if ($category) $html .= '<span class="cq-badge cq-badge-category">' . $category . '</span>';
    $html .= '<span class="cq-badge cq-badge-source">' . htmlspecialchars($source_label) . '</span>';
    $html .= '</div></div>';

    // Action buttons
    $html .= '<div class="cq-q-actions">';
    $html .= '<button class="cq-action-btn ' . $bookmark_class . '" onclick="event.stopPropagation(); cqToggleBookmark(' . $qid . ', this)" title="Bookmark"><i class="bi ' . ($is_bookmarked ? 'bi-bookmark-fill' : 'bi-bookmark') . '"></i></button>';
    $html .= '<button class="cq-action-btn ' . $solved_class . '" onclick="event.stopPropagation(); cqToggleSolved(' . $qid . ', this)" title="' . $solved_title . '"><i class="bi ' . $solved_icon . '"></i></button>';
    $html .= '<i class="bi bi-chevron-down cq-expand-icon" id="cq-expand-' . $qid . '"></i>';
    $html .= '</div></div>';

    // Answer Panel
    $html .= '<div class="cq-answer-panel" id="cq-answer-' . $qid . '">';

    // Main Answer
    if ($answer_text) {
        $html .= '<div class="cq-answer-section">';
        $html .= '<div class="cq-section-title"><i class="bi bi-lightbulb"></i> Answer / Solution</div>';

        // Check if answer contains code blocks marked with ``` or language-specific sections
        $raw_answer = $item['answer'] ?? '';
        $html .= '<div class="cq-section-content">' . cq_format_answer($raw_answer) . '</div>';
        $html .= '</div>';
    }

    // Explanation
    if ($explanation) {
        $html .= '<div class="cq-answer-section">';
        $html .= '<div class="cq-section-title"><i class="bi bi-info-circle"></i> Detailed Explanation</div>';
        $raw_exp = $item['explanation'] ?? '';
        $html .= '<div class="cq-section-content">' . cq_format_answer($raw_exp) . '</div>';
        $html .= '</div>';
    }

    // Sample I/O
    if ($sample_in || $sample_out) {
        $html .= '<div class="cq-answer-section">';
        $html .= '<div class="cq-section-title"><i class="bi bi-terminal"></i> Sample Input / Output</div>';
        $html .= '<div class="cq-io-box">';
        if ($sample_in) {
            $html .= '<div class="cq-io-item"><strong>Input</strong><pre>' . $sample_in . '</pre></div>';
        }
        if ($sample_out) {
            $html .= '<div class="cq-io-item"><strong>Output</strong><pre>' . $sample_out . '</pre></div>';
        }
        $html .= '</div></div>';
    }

    // Complexity
    if ($time_c || $space_c) {
        $html .= '<div class="cq-answer-section">';
        $html .= '<div class="cq-section-title"><i class="bi bi-speedometer2"></i> Complexity Analysis</div>';
        $html .= '<div class="cq-complexity-box">';
        if ($time_c) {
            $html .= '<div class="cq-complexity-item"><strong>Time Complexity</strong><code>' . $time_c . '</code></div>';
        }
        if ($space_c) {
            $html .= '<div class="cq-complexity-item"><strong>Space Complexity</strong><code>' . $space_c . '</code></div>';
        }
        $html .= '</div></div>';
    }

    $html .= '</div>'; // end answer panel
    $html .= '</div>'; // end card

    return $html;
}

// ========================================
// Helper: Format answer text with code blocks
// ========================================
function cq_format_answer($text) {
    // First, escape HTML
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Convert ```lang ... ``` code blocks
    $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function($matches) {
        $lang = $matches[1] ? htmlspecialchars($matches[1]) : 'code';
        $code = $matches[2];
        return '<div class="cq-code-block"><span class="cq-code-label">' . $lang . '</span>' . $code . '</div>';
    }, $text);

    // Convert single backtick inline code
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    // Bold text **text**
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

    // Convert \n to <br> for remaining text (but not inside code blocks)
    $text = nl2br($text);

    return $text;
}
?>

<!-- ============================= SCOPED SCRIPT ============================= -->
<script>
// Company Questions - Scoped JS
const CQ_CSRF = <?php echo json_encode($csrf_token); ?>;

function cqToggleAnswer(qid) {
    const panel = document.getElementById('cq-answer-' + qid);
    const icon = document.getElementById('cq-expand-' + qid);
    if (!panel) return;
    panel.classList.toggle('cq-open');
    if (icon) icon.classList.toggle('cq-expanded');
}

function cqShowToast(msg, type) {
    const toast = document.getElementById('cqToast');
    toast.textContent = msg;
    toast.style.background = type === 'success' ? '#198754' : type === 'error' ? '#dc3545' : '#1e293b';
    toast.classList.add('cq-toast-show');
    setTimeout(() => toast.classList.remove('cq-toast-show'), 2500);
}

function cqToggleBookmark(qid, btn) {
    const fd = new FormData();
    fd.append('ajax_action', 'toggle_bookmark');
    fd.append('question_id', qid);
    fd.append('csrf_token', CQ_CSRF);

    fetch('company.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const icon = btn.querySelector('i');
                if (data.bookmarked) {
                    btn.classList.add('cq-bookmarked');
                    icon.className = 'bi bi-bookmark-fill';
                    cqShowToast('Question bookmarked!', 'success');
                } else {
                    btn.classList.remove('cq-bookmarked');
                    icon.className = 'bi bi-bookmark';
                    cqShowToast('Bookmark removed', 'info');
                }
                cqUpdateStatCount('bookmarked', data.bookmarked ? 1 : -1);
            } else {
                cqShowToast(data.error || 'Error', 'error');
            }
        })
        .catch(() => cqShowToast('Network error', 'error'));
}

function cqToggleSolved(qid, btn) {
    const icon = btn.querySelector('i');
    let currentStatus = 'none';
    if (btn.classList.contains('cq-is-solved')) currentStatus = 'solved';
    else if (btn.classList.contains('cq-is-attempted')) currentStatus = 'attempted';

    // Cycle: none -> attempted -> solved -> none
    let newStatus;
    if (currentStatus === 'none') newStatus = 'attempted';
    else if (currentStatus === 'attempted') newStatus = 'solved';
    else newStatus = 'remove';

    const fd = new FormData();
    fd.append('ajax_action', 'toggle_solved');
    fd.append('question_id', qid);
    fd.append('status', newStatus);
    fd.append('csrf_token', CQ_CSRF);

    fetch('company.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = btn.closest('.cq-question-card');
                btn.classList.remove('cq-is-solved', 'cq-is-attempted');
                card.classList.remove('cq-solved', 'cq-attempted');

                if (data.status === 'solved') {
                    btn.classList.add('cq-is-solved');
                    icon.className = 'bi bi-check-circle-fill';
                    btn.title = 'Solved — click to unmark';
                    card.classList.add('cq-solved');
                    cqShowToast('Marked as solved!', 'success');
                } else if (data.status === 'attempted') {
                    btn.classList.add('cq-is-attempted');
                    icon.className = 'bi bi-hourglass-split';
                    btn.title = 'Attempted — click to mark solved';
                    card.classList.add('cq-attempted');
                    cqShowToast('Marked as attempted', 'info');
                } else {
                    icon.className = 'bi bi-circle';
                    btn.title = 'Mark as attempted';
                    cqShowToast('Progress cleared', 'info');
                }
            } else {
                cqShowToast(data.error || 'Error', 'error');
            }
        })
        .catch(() => cqShowToast('Network error', 'error'));
}

function cqUpdateStatCount(type, delta) {
    // Update the stats bar numbers in the DOM
    const statItems = document.querySelectorAll('.cq-stat-item');
    const labels = { 'bookmarked': 'Bookmarked', 'solved': 'Solved', 'attempted': 'Attempted' };
    statItems.forEach(item => {
        const label = item.querySelector('.cq-stat-label');
        if (label && label.textContent.trim() === labels[type]) {
            const valueEl = item.querySelector('.cq-stat-value');
            if (valueEl) {
                let val = parseInt(valueEl.textContent) || 0;
                val += delta;
                if (val < 0) val = 0;
                valueEl.textContent = val;
            }
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
