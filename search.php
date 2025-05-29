<?php
require_once 'includes/config.php';
require_once 'includes/db_config.php';
require_once 'includes/functions.php';

$page_title = "Search Results | " . SITE_NAME;
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'all';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : 'all';

// Get all unique categories for filter
$categories_stmt = $pdo->query("SELECT DISTINCT category FROM classes ORDER BY category");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Define search types
$search_types = [
    'all' => 'All Content',
    'classes' => 'Classes',
    'professors' => 'Professors',
    'messages' => 'Messages'
];

require_once 'includes/header.php';
?>

<section class="search-section py-5 bg-light">
    <div class="container">
        <!-- Search Form -->
        <div class="search-form bg-white p-4 rounded shadow-sm mb-4">
            <form action="search.php" method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control" 
                               placeholder="Search anything..." 
                               value="<?php echo htmlspecialchars($search_query); ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <?php foreach($search_types as $value => $label): ?>
                            <option value="<?php echo $value; ?>" 
                                <?php echo $type === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select" <?php echo $type !== 'all' && $type !== 'classes' ? 'disabled' : ''; ?>>
                        <option value="all">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <div class="search-results">
            <?php if(!empty($search_query)): ?>
                <h2 class="mb-4">
                    Search Results 
                    <small class="text-muted">for "<?php echo htmlspecialchars($search_query); ?>"</small>
                    <?php if($type !== 'all'): ?>
                        <span class="badge bg-primary"><?php echo $search_types[$type]; ?></span>
                    <?php endif; ?>
                    <?php if($category !== 'all' && ($type === 'all' || $type === 'classes')): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($category); ?></span>
                    <?php endif; ?>
                </h2>
                
                <?php
                try {
                    $total_results = 0;
                    $results = [];

                    // Search Classes
                    if($type === 'all' || $type === 'classes') {
                        $where_conditions = [];
                        $params = [];
                        
                        $where_conditions[] = "(c.title LIKE :query OR c.description LIKE :query)";
                        $params['query'] = "%$search_query%";
                        
                        if($category !== 'all') {
                            $where_conditions[] = "c.category = :category";
                            $params['category'] = $category;
                        }
                        
                        $where_clause = implode(' AND ', $where_conditions);
                        
                        $stmt = $pdo->prepare("
                            SELECT c.*, p.name as professor_name, p.specialization,
                                   'class' as result_type 
                            FROM classes c
                            LEFT JOIN professors p ON c.professor_id = p.id
                            WHERE $where_clause
                            ORDER BY c.title
                        ");
                        $stmt->execute($params);
                        $class_results = $stmt->fetchAll();
                        $results = array_merge($results, $class_results);
                    }

                    // Search Professors
                    if($type === 'all' || $type === 'professors') {
                        $stmt = $pdo->prepare("
                            SELECT *, 'professor' as result_type 
                            FROM professors 
                            WHERE name LIKE :query 
                            OR specialization LIKE :query 
                            OR bio LIKE :query
                            ORDER BY name
                        ");
                        $stmt->execute(['query' => "%$search_query%"]);
                        $professor_results = $stmt->fetchAll();
                        $results = array_merge($results, $professor_results);
                    }

                    // Search Messages
                    if($type === 'all' || $type === 'messages') {
                        $stmt = $pdo->prepare("
                            SELECT *, 'message' as result_type 
                            FROM messages 
                            WHERE name LIKE :query 
                            OR email LIKE :query 
                            OR message LIKE :query
                            ORDER BY created_at DESC
                        ");
                        $stmt->execute(['query' => "%$search_query%"]);
                        $message_results = $stmt->fetchAll();
                        $results = array_merge($results, $message_results);
                    }

                    $total_results = count($results);
                    
                    if($total_results > 0): ?>
                        <p class="text-muted mb-4"><?php echo $total_results; ?> results found</p>
                        <div class="row g-4">
                            <?php foreach($results as $result): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 shadow-sm hover-card">
                                        <div class="card-body">
                                            <?php if($result['result_type'] === 'class'): ?>
                                                <!-- Class Result -->
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($result['title']); ?></h5>
                                                    <span class="badge bg-primary">Class</span>
                                                </div>
                                                <?php if(!empty($result['professor_name'])): ?>
                                                    <p class="text-muted small mb-2">
                                                        <i class="bi bi-person-video3"></i>
                                                        <?php echo htmlspecialchars($result['professor_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="card-text text-muted"><?php echo substr(htmlspecialchars($result['description']), 0, 100) . '...'; ?></p>
                                                <a href="class_detail.php?id=<?php echo $result['id']; ?>" class="btn btn-outline-primary stretched-link">
                                                    View Class <i class="bi bi-arrow-right"></i>
                                                </a>

                                            <?php elseif($result['result_type'] === 'professor'): ?>
                                                <!-- Professor Result -->
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($result['name']); ?></h5>
                                                    <span class="badge bg-info">Professor</span>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    <i class="bi bi-mortarboard"></i>
                                                    <?php echo htmlspecialchars($result['specialization']); ?>
                                                </p>
                                                <p class="card-text text-muted"><?php echo substr(htmlspecialchars($result['bio']), 0, 100) . '...'; ?></p>
                                                <a href="professor_detail.php?id=<?php echo $result['id']; ?>" class="btn btn-outline-info stretched-link">
                                                    View Profile <i class="bi bi-arrow-right"></i>
                                                </a>

                                            <?php elseif($result['result_type'] === 'message'): ?>
                                                <!-- Message Result -->
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($result['name']); ?></h5>
                                                    <span class="badge bg-warning text-dark">Message</span>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    <i class="bi bi-envelope"></i>
                                                    <?php echo htmlspecialchars($result['email']); ?>
                                                </p>
                                                <p class="card-text text-muted"><?php echo substr(htmlspecialchars($result['message']), 0, 100) . '...'; ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($result['created_at'])); ?>
                                                    </small>
                                                    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                                        <a href="admin/manage_messages.php?id=<?php echo $result['id']; ?>" class="btn btn-outline-warning stretched-link">
                                                            View Message <i class="bi bi-arrow-right"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No results found matching your search criteria.
                            <a href="search.php" class="alert-link">Clear search</a>
                        </div>
                    <?php endif;
                    
                } catch(PDOException $e) {
                    echo display_error("An error occurred while searching. Please try again.");
                    error_log("Search error: " . $e->getMessage());
                }
                ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-search display-1 text-muted mb-3"></i>
                    <h3>Start Your Search</h3>
                    <p class="text-muted">Search across classes, professors, and messages</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.hover-card {
    transition: transform 0.2s ease-in-out;
}
.hover-card:hover {
    transform: translateY(-5px);
}
.search-section {
    min-height: calc(100vh - 200px);
}
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}
</style>
</section>

<?php require_once 'includes/footer.php'; ?>