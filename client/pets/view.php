<?php
require_once '../../config/init.php';
requireClient(); // Ensure only logged-in clients can access

$pageTitle = 'View Pet - ' . SITE_NAME;

// 1. Get Pet ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlash('Invalid pet ID provided.', 'danger');
    redirect('/client/pets/index.php'); // Redirect to pets list if ID is missing or invalid
}

$petId = (int)$_GET['id'];
$ownerId = $_SESSION['owner_id'];

// 2. Fetch Pet Data from Database, ensuring it belongs to the logged-in owner
try {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = :id AND owner_id = :owner_id AND is_active = 1");
    $stmt->execute(['id' => $petId, 'owner_id' => $ownerId]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        setFlash('Pet not found or you do not have permission to view it, or it has been deactivated.', 'danger');
        redirect('/client/pets/index.php'); // Redirect if pet doesn't exist or doesn't belong to owner
    }

    // Set page title with pet's name
    $pageTitle = $pet['name'] . ' - View Pet - ' . SITE_NAME;

} catch (PDOException $e) {
    // Log the error for debugging, but show a generic message to the user
    // error_log("Failed to fetch pet details: " . $e->getMessage());
    setFlash('An error occurred while fetching pet details. Please try again.', 'danger');
    redirect('/client/pets/index.php');
}

// You might want to include functions for formatting data like dates
// function formatDate($date) {
//     return $date ? date('F j, Y', strtotime($date)) : 'N/A';
// }
// function formatWeight($weight) {
//     return $weight ? $weight . ' kg' : 'N/A';
// }

// Common pet species and breeds (to help with display if needed, though not directly used for display in this view)
$commonSpecies = [
    'Dog' => ['Labrador Retriever', 'German Shepherd', 'Golden Retriever', 'Bulldog', 'Beagle', 'Poodle', 'Rottweiler', 'Yorkshire Terrier', 'Dachshund', 'Siberian Husky', 'Shih Tzu', 'Pomeranian', 'Chihuahua', 'Mixed Breed', 'Other'],
    'Cat' => ['Persian', 'Maine Coon', 'Siamese', 'Ragdoll', 'British Shorthair', 'American Shorthair', 'Scottish Fold', 'Sphynx', 'Russian Blue', 'Bengal', 'Mixed Breed', 'Other'],
    'Bird' => ['Parakeet', 'Cockatiel', 'Parrot', 'Canary', 'Finch', 'Lovebird', 'Cockatoo', 'Conure', 'Other'],
    'Rabbit' => ['Holland Lop', 'Netherland Dwarf', 'Lionhead', 'Rex', 'Flemish Giant', 'Other'],
    'Hamster' => ['Syrian', 'Dwarf', 'Chinese', 'Roborovski', 'Other'],
    'Guinea Pig' => ['American', 'Abyssinian', 'Peruvian', 'Other'],
    'Fish' => ['Goldfish', 'Betta', 'Guppy', 'Tetra', 'Other'],
    'Reptile' => ['Turtle', 'Lizard', 'Snake', 'Gecko', 'Other'],
    'Other' => []
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- FontAwesome for icons, if not already included in style.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reusing and extending styles from add.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            margin-bottom: 0.5rem;
        }

        .sidebar-user {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .sidebar-menu .icon {
            font-size: 1.25rem;
            width: 1.5rem;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .breadcrumb a {
            color: #FF6B6B;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            margin: 0;
            color: #333;
            font-size: 2rem;
        }

        .page-icon {
            font-size: 2.5rem;
        }

        /* View Pet Container */
        .pet-details-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
            padding: 2rem; /* Added padding to the container */
        }

        .pet-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
        }

        .pet-avatar {
            font-size: 5rem; /* Larger icon for the pet */
            margin-bottom: 1rem;
            color: #FF6B6B;
        }

        .pet-name-display {
            font-size: 2.5rem;
            color: #333;
            margin: 0;
        }

        .pet-species-breed {
            color: #666;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        /* Details Sections */
        .details-section {
            margin-bottom: 2.5rem;
        }

        .details-section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: #FF6B6B;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem 2rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #333;
            word-wrap: break-word; /* Ensure long text wraps */
        }
        
        .detail-value-notes {
            white-space: pre-wrap; /* Preserves whitespace and line breaks from textarea */
        }

        /* Actions Section */
        .pet-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        /* Button Styles (reused from add.php) */
        .btn {
            display: inline-flex; /* Use flex for icon alignment */
            align-items: center;
            gap: 0.5rem; /* Space between icon and text */
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            border-color: #999;
            color: #333;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.2);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
        }

        /* Alert Messages (reused from add.php) */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.95rem;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #e6ffe6;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* Mobile Responsive (reused from add.php) */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 101;
            background: #FF6B6B;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .pet-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-client.php'; ?>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="Toggle Navigation">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <nav class="breadcrumb">
                    <a href="../index.php">Dashboard</a>
                    <span>›</span>
                    <a href="index.php">My Pets</a>
                    <span>›</span>
                    <span><?php echo sanitize($pet['name']); ?></span>
                </nav>
                <div class="page-title">
                    <span class="page-icon">
                        <?php
                            // Dynamic icon based on species
                            $speciesIcons = [
                                'Dog' => '🐶',
                                'Cat' => '🐱',
                                'Bird' => '🐦',
                                'Rabbit' => '🐇',
                                'Hamster' => '🐹',
                                'Guinea Pig' => '🐹',
                                'Fish' => '🐠',
                                'Reptile' => '🦎',
                                'Other' => '🐾'
                            ];
                            echo $speciesIcons[$pet['species']] ?? '🐾';
                        ?>
                    </span>
                    <h1><?php echo sanitize($pet['name']); ?>'s Profile</h1>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php $flashMessages = getFlash(); ?>
            <?php if (!empty($flashMessages)): ?>
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?php echo sanitize($type); ?>">
                            <span>
                                <?php 
                                    if ($type == 'success') echo '✅';
                                    else if ($type == 'warning') echo '⚠️';
                                    else if ($type == 'danger') echo '❌';
                                    else echo 'ℹ️';
                                ?>
                            </span>
                            <?php echo sanitize($message); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pet Details Container -->
            <div class="pet-details-container">
                <div class="pet-header">
                    <div class="pet-avatar">
                        <?php echo $speciesIcons[$pet['species']] ?? '🐾'; ?>
                    </div>
                    <h2 class="pet-name-display"><?php echo sanitize($pet['name']); ?></h2>
                    <p class="pet-species-breed">
                        <?php echo sanitize($pet['species']); ?>
                        <?php echo !empty($pet['breed']) ? ' - ' . sanitize($pet['breed']) : ''; ?>
                    </p>
                </div>

                <!-- Basic Information Section -->
                <div class="details-section">
                    <h3 class="details-section-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo sanitize($pet['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Species:</span>
                            <span class="detail-value"><?php echo sanitize($pet['species']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Breed:</span>
                            <span class="detail-value"><?php echo !empty($pet['breed']) ? sanitize($pet['breed']) : 'N/A'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value">
                                <?php echo !empty($pet['date_of_birth']) ? date('F j, Y', strtotime($pet['date_of_birth'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value">
                                <?php echo !empty($pet['gender']) ? ucfirst(sanitize($pet['gender'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Color/Markings:</span>
                            <span class="detail-value">
                                <?php echo !empty($pet['color']) ? sanitize($pet['color']) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Weight:</span>
                            <span class="detail-value">
                                <?php echo !empty($pet['weight']) ? sanitize($pet['weight']) . ' kg' : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Medical Information Section -->
                <div class="details-section">
                    <h3 class="details-section-title">
                        <i class="fas fa-stethoscope"></i>
                        Medical Information
                    </h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Microchip ID:</span>
                            <span class="detail-value">
                                <?php echo !empty($pet['microchip_id']) ? sanitize($pet['microchip_id']) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="detail-item full-width"> <!-- Add full-width class for notes -->
                            <span class="detail-label">Additional Notes:</span>
                            <span class="detail-value detail-value-notes">
                                <?php echo !empty($pet['notes']) ? sanitize($pet['notes']) : 'No specific notes recorded.'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pet-actions">
                    <a href="edit.php?id=<?php echo $pet['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Pet
                    </a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $pet['id']; ?>, '<?php echo sanitize($pet['name']); ?>')">
                        <i class="fas fa-trash-alt"></i> Delete Pet
                    </button>
                    <a href="../appointments/add.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-calendar-plus"></i> Schedule Appointment
                    </a>
                    <a href="../vaccinations/add.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-syringe"></i> Add Vaccination Record
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle logic
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        if (mobileMenuToggle && sidebar) {
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // JavaScript for delete confirmation
        function confirmDelete(petId, petName) {
            if (confirm(`Are you sure you want to delete ${petName}'s profile? This action cannot be undone.`)) {
                window.location.href = `delete.php?id=${petId}`;
            }
        }
    </script>
</body>
</html>