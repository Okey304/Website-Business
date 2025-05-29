<?php
require_once 'includes/config.php';
require_once 'includes/db_config.php';
require_once 'includes/functions.php';

// Get professor ID
$professor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch professor details
$stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(c.id) as total_classes,
           GROUP_CONCAT(DISTINCT c.title SEPARATOR '|') as class_titles,
           GROUP_CONCAT(DISTINCT c.id SEPARATOR '|') as class_ids
    FROM professors p
    LEFT JOIN classes c ON p.id = c.professor_id
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch();

if (!$professor) {
    header("Location: index.php");
    exit;
}

$page_title = htmlspecialchars($professor['name']) . " | " . SITE_NAME;

require_once 'includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($professor['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar-circle-lg bg-primary text-white mx-auto mb-3">
                        <?php echo strtoupper(substr($professor['name'], 0, 1)); ?>
                    </div>
                    <h2 class="card-title"><?php echo htmlspecialchars($professor['name']); ?></h2>
                    <p class="text-muted mb-3">
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        <?php echo htmlspecialchars($professor['specialization']); ?>
                    </p>
                    <?php if($professor['total_classes'] > 0): ?>
                        <div class="d-flex justify-content-center">
                            <span class="badge bg-primary rounded-pill">
                                <?php echo $professor['total_classes']; ?> Classes
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">About</h3>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($professor['bio'])); ?></p>

                    <?php if($professor['total_classes'] > 0): ?>
                        <h3 class="card-title mt-4">Classes</h3>
                        <div class="list-group">
                            <?php 
                            $class_titles = explode('|', $professor['class_titles']);
                            $class_ids = explode('|', $professor['class_ids']);
                            for($i = 0; $i < count($class_titles); $i++): 
                            ?>
                                <a href="class_detail.php?id=<?php echo $class_ids[$i]; ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($class_titles[$i]); ?>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle-lg {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 600;
}
</style>

<?php require_once 'includes/footer.php'; ?>
