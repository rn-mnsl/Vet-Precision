<?php
require_once '../../config/init.php';

// REMINDER: Added authentication and data fetching for the owner list.
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: ../../login.php');
    exit();
}

// Fetch all owners to populate the dropdown in the add/edit form.
$owners = [];
try {
    $stmt = $pdo->query("
        SELECT o.owner_id, CONCAT(u.first_name, ' ', u.last_name) AS full_name
        FROM owners o
        JOIN users u ON o.user_id = u.user_id
        WHERE u.role = 'client'
        ORDER BY u.last_name, u.first_name
    ");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error if needed, maybe show a message on the page
}

$pageTitle = 'Pets Management - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include '../../includes/favicon.php'; ?>
    <!-- REMINDER: The CSS is the same as your original file, no changes needed here. -->
    <style>
        /* Dashboard specific styles */
        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar */
        .sidebar {
            background: var(--dark-color);
            color: white;
            padding: 2rem 0;
            width: 250px;
            min-width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
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
            margin-bottom: 1rem;
        }

        .sidebar-logo:hover {
            color: white;
            text-decoration: none;
        }

        .sidebar-user {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 0.25rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all var(--transition-base);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--primary-color);
        }

        .sidebar-menu .icon {
            font-size: 1.25rem;
            width: 1.5rem;
            text-align: center;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-title-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .page-title-section p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .add-pet-btn {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            box-shadow: var(--shadow-primary);
            cursor: pointer;
        }

        .add-pet-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            color: var(--white);
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #ff6b6b;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .search-box .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 1.1rem;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            font-size: 0.95rem;
            background: #f8f9fa;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #ff6b6b;
            background: white;
        }

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .pet-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }

        .pet-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .pet-header {
            background: var(--gradient-secondary);
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .pet-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 3px solid rgba(255,255,255,0.3);
            font-size: 2rem;
        }

        .pet-name {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .pet-breed {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .pet-details {
            padding: 2rem;
            background: var(--gray-50);
        }

        .pet-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            text-align: left;
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--gray-600);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .pet-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-width: 100px;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-primary);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
            color: var(--white);
            text-decoration: none;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: transparent;
            color: var(--danger-color);
            border: 2px solid var(--danger-color);
        }

        .btn-danger:hover {
            background: var(--danger-color);
            color: var(--white);
            text-decoration: none;
            transform: translateY(-1px);
        }


        /* --- NEW: Enhanced & Styled Pagination Controls --- */
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 2.5rem 0 1rem; /* Added more space above */
            padding: 1rem;
            flex-wrap: wrap;
        }

        .pagination-controls button {
            /* Base Style */
            background-color: var(--white);
            border: 2px solid var(--gray-200);
            color: var(--gray-600); /* Softer text color */
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 42px; /* Ensures even single digits look good */
            height: 42px;    /* Makes them perfectly square */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px; /* A more modern, rounded look */
            cursor: pointer;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); /* Subtle lift */
        }

        /* Hover State (for non-active buttons) */
        .pagination-controls button:not(.active):not(:disabled):hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        /* Active Page Style */
        .pagination-controls button.active {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.45);
            cursor: default; /* No need to click the active page */
        }

        /* Disabled State (for Prev/Next) */
        .pagination-controls button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: var(--gray-100); /* A slightly off-white to show it's inactive */
            box-shadow: none; /* No lift for disabled buttons */
        }

        /* --- NEW: Modal Styles for Add/Edit Pet Forms --- */
        .modal {
            position: fixed;
            z-index: 1050;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none; /* Hidden by default */
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.is-open {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 700px;
            max-height: 90vh; /* Crucial for small screens */
            overflow-y: auto; /* Adds scrollbar inside modal if needed */
            position: relative;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        .modal.is-open .modal-content {
            transform: scale(1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: var(--gray-600);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .modal-close-btn:hover {
            color: var(--text-dark);
        }

        /* Form inside the modal */
        #petForm {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        #petForm input, #petForm textarea, #petForm select {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        #petForm input:focus, #petForm textarea:focus, #petForm select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(29, 186, 168, 0.1);
        }
        #petForm .full-width {
            grid-column: 1 / -1; /* Makes an element span both columns */
        }
        #petForm textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .form-actions button {
            flex: 1;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .form-actions button[type="submit"] { background: var(--gradient-primary); color: var(--white); }
        .form-actions button[type="button"] { background: var(--gray-500); color: var(--white); }


        /* Form Styles */
        #inlinePetForm {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        #inlinePetForm h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        #inlinePetForm form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        #inlinePetForm input,
        #inlinePetForm textarea,
        #inlinePetForm select {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        #inlinePetForm input:focus,
        #inlinePetForm textarea:focus,
        #inlinePetForm select:focus {
            outline: none;
            border-color: #ff6b6b;
        }

        #inlinePetForm textarea {
            grid-column: 1 / -1;
            resize: vertical;
            min-height: 100px;
        }

        #inlinePetForm button {
            grid-column: 1 / -1;
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #inlinePetForm button[type="submit"] {
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            color: white;
        }

        #inlinePetForm button[type="button"] {
            background: #6c757d;
            color: white;
        }

        /* Hide sections when showing form */
        .form-active .search-filters,
        .form-active .pets-grid {
            display: none;
        }

        /* Animation */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pet-avatar:hover {
            animation: pulse 0.6s ease-in-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 1100; position: fixed; top: 0; height: 100vh; margin-top: 0; }
            .main-content { margin-left: 0; }
            body.sidebar-is-open .sidebar { transform: translateX(0); box-shadow: 0 0 20px rgba(0,0,0,0.25); }
            body.sidebar-is-open .sidebar-overlay { opacity: 1; visibility: visible; }
            .main-content { padding-top: 85px; } /* Space for fixed navbar */
        }


        @media (max-width: 768px) {
            .main-content { padding: 1rem; padding-top: 70px; /* Space for fixed navbar */ }
            .page-header { flex-direction: column; gap: 1.5rem; text-align: center; padding: 1.5rem; }
            .page-title-section h1 { font-size: 2rem; }
            .page-title-section p { font-size: 1rem; }
            .add-pet-btn { padding: 0.8rem 1.5rem; font-size: 0.9rem; }
            
            .search-filters { flex-direction: column; gap: 1.5rem; align-items: stretch; }
            .filter-group { justify-content: space-between; }
            
            .pets-grid { grid-template-columns: 1fr; }
            
            .pet-actions { flex-direction: column; }
            .btn { flex: auto; }
            
            /* --- THIS IS THE KEY FIX FOR THE FORM'S HORIZONTAL SCROLLBAR --- */
            #petForm {
                grid-template-columns: 1fr; /* Stack form fields vertically */
            }
            .modal-content { padding: 1.5rem; }
            .modal-title { font-size: 1.5rem; }
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Universal Sidebar -->
        <?php include '../../includes/sidebar-staff.php'; ?>
        <?php include '../../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container" id="mainContainer">
                <div class="page-header">
                    <div class="page-title-section">
                        <h1>Pet Management</h1>
                        <p>Manage their pets in one place</p>
                    </div>
                    <button class="add-pet-btn" onclick="openPetForm()">
                        <i class="fas fa-plus"></i>
                        Add New Pet
                    </button>
                </div>

                <div class="search-filters">
                    <div class="search-box">
                        <i class="search-icon fas fa-search"></i>
                        <input type="text" placeholder="Search pets by name, breed, or owner..." id="searchInput" onkeyup="handleFilterChange()">
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Species:</span>
                        <select class="filter-select" id="speciesFilter" onchange="handleFilterChange()">
                            <option value="">All Species</option>
                            <option value="dog">Dog</option>
                            <option value="cat">Cat</option>
                            <option value="bird">Bird</option>
                            <option value="rabbit">Rabbit</option>
                            <option value="reptile">Reptile</option>
                            <option value="fish">Fish</option>
                            <option value="small mammal">Small Mammal</option>
                            <option value="horse">Horse</option>
                            <option value="farm">Farm Animal</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Status:</span>
                        <select class="filter-select" id="statusFilter" onchange="handleFilterChange()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Inline Pet Form -->
                <div id="inlinePetForm" style="display: none;">
                    <!-- Pet form content will be injected here by JavaScript -->
                </div>

                <div class="pets-grid" id="petsGrid">
                    <!-- Pet cards will be populated by JavaScript -->
                </div>

                <!-- NEW: Container for Pagination Controls -->
                <div id="paginationControls" class="pagination-controls"></div>
            </div>
        </main>
    </div>

    <!-- NEW: Modal for Add/Edit Pet -->
    <div class="modal" id="petModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Pet</h2>
                <button class="modal-close-btn" onclick="closePetForm()">×</button>
            </div>
            <div id="modalBody">
                <!-- Pet form will be injected here by JavaScript -->
            </div>
        </div>
    </div>

    <!-- REMINDER: This is the fully modified JavaScript section. -->
        <script>
        const allOwners = <?php echo json_encode($owners); ?>;
        
        let pets = [];
        let filteredPets = [];
        
        // --- Pagination State ---
        let currentPage = 1;
        const petsPerPage = 6;

        // --- Core AJAX Functions ---
        async function fetchAllPets() {
            try {
                const response = await fetch('ajax/pets_handler.php?action=fetch_all');
                if (!response.ok) { // Check for HTTP errors like 404 or 500
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();

                if (result.success) {
                    pets = result.data;
                    // FIX #1: This now calls the correct central function to render everything.
                    handleFilterChange(); 
                } else {
                    console.error('Failed to fetch pets:', result.error);
                    document.getElementById('petsGrid').innerHTML = '<p>Error loading pets. Please try again.</p>';
                }
            } catch (error) {
                console.error('Network or JSON parsing error:', error);
                document.getElementById('petsGrid').innerHTML = '<p>Could not connect to the server or process the data.</p>';
            }
        }

        async function savePet(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const action = formData.get('pet_id') ? 'update' : 'add';
            formData.append('action', action);

            try {
                const response = await fetch('ajax/pets_handler.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    // FIX #2: Replaced the old cancelForm() with the new closePetForm().
                    closePetForm(); 
                    fetchAllPets();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('An unexpected network error occurred.');
                console.error(error);
            }
        }
        
        async function deletePet(id) {
            if (!confirm('Are you sure you want to delete this pet? This action cannot be undone.')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('pet_id', id);

            try {
                const response = await fetch('ajax/pets_handler.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    fetchAllPets();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('An unexpected network error occurred.');
                console.error(error);
            }
        }

        // --- Helper Functions ---
        function calculateAge(dob) {
            if (!dob) return 'N/A';
            const diff = Date.now() - new Date(dob).getTime();
            const ageDate = new Date(diff);
            return Math.abs(ageDate.getUTCFullYear() - 1970);
        }
        function getPetEmoji(species) {
            switch(species.toLowerCase()) {
                case 'dog': return '🐕'; case 'cat': return '🐱'; case 'bird': return '🐦'; case 'rabbit': return '🐇'; default: return '🐾';
            }
        }

        // --- Central Filter & Render Logic ---
        function handleFilterChange() {
            currentPage = 1; // Reset to first page on any filter change
            applyFiltersAndRender();
        }

        function applyFiltersAndRender() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            const speciesFilter = document.getElementById('speciesFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;

            filteredPets = pets.filter(pet => {
                const isActive = pet.is_active == 1;
                const searchMatch = pet.name.toLowerCase().includes(searchQuery) ||
                                    pet.breed.toLowerCase().includes(searchQuery) ||
                                    (pet.owner_name && pet.owner_name.toLowerCase().includes(searchQuery));
                const speciesMatch = !speciesFilter || pet.species.toLowerCase() === speciesFilter;
                const statusMatch = statusFilter === '' || (statusFilter === 'active' && isActive) || (statusFilter === 'inactive' && !isActive);
                return searchMatch && speciesMatch && statusMatch;
            });
            
            renderPage();
            renderPagination();
        }
        
        // Renders only the current page's pets
        function renderPage() {
            const grid = document.getElementById('petsGrid');
            grid.innerHTML = '';

            if (filteredPets.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center;">No pets found matching your criteria.</p>';
                return;
            }
            
            const startIndex = (currentPage - 1) * petsPerPage;
            const endIndex = startIndex + petsPerPage;
            const petsOnPage = filteredPets.slice(startIndex, endIndex);

            petsOnPage.forEach(pet => {
                const age = calculateAge(pet.date_of_birth);
                const emoji = getPetEmoji(pet.species);
                const avatarHtml = pet.photo_url ?
                    `<div class="pet-avatar" style="background-image: url('${pet.photo_url}'); background-size: cover; background-position: center;"></div>` :
                    `<div class="pet-avatar">${emoji}</div>`;

                const card = document.createElement('div');
                card.className = 'pet-card';
                card.innerHTML = `
                    <div class="pet-header">
                        ${avatarHtml}
                        <div class="pet-name">${pet.name}</div>
                        <div class="pet-breed">${pet.breed}</div>
                    </div>
                    <div class="pet-details">
                        <div class="pet-info">
                            <div class="info-item"><div class="info-label">Owner</div><div class="info-value">${pet.owner_name || 'N/A'}</div></div>
                            <div class="info-item"><div class="info-label">Age</div><div class="info-value">${age} years</div></div>
                            <div class="info-item"><div class="info-label">Weight</div><div class="info-value">${pet.weight ? pet.weight + ' kg' : 'N/A'}</div></div>
                            <div class="info-item"><div class="info-label">Gender</div><div class="info-value">${pet.gender}</div></div>
                        </div>
                        <div class="pet-actions">
                            <a href="../appointments/index.php?action=create&owner_id=${pet.owner_id}&pet_id=${pet.pet_id}" class="btn btn-primary">Book Appointment</a>
                            <button class="btn btn-outline" onclick="editPet(${pet.pet_id})">Edit Info</button>
                            <button class="btn btn-danger" onclick="deletePet(${pet.pet_id})">Delete</button>
                        </div>
                    </div>`;
                grid.appendChild(card);
            });
        }
        
        // Renders Pagination Controls
        function renderPagination() {
            const controlsContainer = document.getElementById('paginationControls');
            controlsContainer.innerHTML = '';
            const totalPages = Math.ceil(filteredPets.length / petsPerPage);

            if (totalPages <= 1) return;

            let buttonsHtml = '';
            buttonsHtml += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>« Prev</button>`;
            for (let i = 1; i <= totalPages; i++) {
                buttonsHtml += `<button onclick="changePage(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
            }
            buttonsHtml += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Next »</button>`;
            controlsContainer.innerHTML = buttonsHtml;
        }
        
        function changePage(page) {
            if (page < 1 || page > Math.ceil(filteredPets.length / petsPerPage)) return;
            currentPage = page;
            renderPage();
            renderPagination();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // --- Modal Control Functions ---
        const petModal = document.getElementById('petModal');
        
        // FIX #3: Removed the duplicate, old openPetForm function. This is the only one now.
        function openPetForm(pet = null) {
            document.getElementById('modalTitle').innerText = pet ? `Edit ${pet.name}` : 'Add New Pet';
            document.getElementById('modalBody').innerHTML = generatePetForm(pet);
            petModal.classList.add('is-open');
        }

        function closePetForm() {
            petModal.classList.remove('is-open');
        }

        function editPet(id) {
            const pet = pets.find(p => p.pet_id === id);
            if (pet) openPetForm(pet);
        }

        // FIX #4: Removed the duplicate generatePetForm function. This is the only one now.
        function generatePetForm(pet) {
            const ownerOptions = allOwners.map(owner => 
                `<option value="${owner.owner_id}" ${pet && pet.owner_id == owner.owner_id ? 'selected' : ''}>${owner.full_name}</option>`
            ).join('');
            
            return `
                <form id="petForm" onsubmit="savePet(event)">
                    <input type="hidden" name="pet_id" value="${pet ? pet.pet_id : ''}">
                    <input type="text" name="name" placeholder="Pet Name" value="${pet ? pet.name : ''}" required>
                    <select name="owner_id" required>
                        <option value="">Select Owner</option>
                        ${ownerOptions}
                    </select>
                    <select name="species" required>
                        <option value="">Select Species</option>
                        <option value="Dog" ${pet && pet.species === 'Dog' ? 'selected' : ''}>Dog</option>
                        <option value="Cat" ${pet && pet.species === 'Cat' ? 'selected' : ''}>Cat</option>
                        <option value="Bird" ${pet && pet.species === 'Bird' ? 'selected' : ''}>Bird</option>
                        <option value="Rabbit" ${pet && pet.species === 'Rabbit' ? 'selected' : ''}>Rabbit</option>
                        <option value="Reptile" ${pet && pet.species === 'Reptile' ? 'selected' : ''}>Reptile</option>
                        <option value="Fish" ${pet && pet.species === 'Fish' ? 'selected' : ''}>Fish</option>
                        <option value="Small Mammal" ${pet && pet.species === 'Small Mammal' ? 'selected' : ''}>Small Mammal</option>
                        <option value="Horse" ${pet && pet.species === 'Horse' ? 'selected' : ''}>Horse</option>
                        <option value="Farm Animal" ${pet && pet.species === 'Farm Animal' ? 'selected' : ''}>Farm Animal</option>
                        <option value="Other" ${pet && pet.species === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                    <input type="text" name="breed" placeholder="Breed" value="${pet ? pet.breed : ''}" required>
                    <input type="date" name="date_of_birth" value="${pet ? pet.date_of_birth : ''}" required>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" ${pet && pet.gender === 'Male' ? 'selected' : ''}>Male</option>
                        <option value="Female" ${pet && pet.gender === 'Female' ? 'selected' : ''}>Female</option>
                    </select>
                    <input type="text" name="microchip_id" placeholder="Microchip ID (optional)" value="${pet ? pet.microchip_id : ''}">
                    <input type="text" name="color" placeholder="Color" value="${pet ? pet.color : ''}">
                    <input type="number" step="0.01" name="weight" placeholder="Weight in kg (optional)" value="${pet ? pet.weight : ''}">
                    <textarea name="notes" placeholder="Medical Notes (e.g., allergies, conditions)" class="full-width">${pet && pet.notes ? pet.notes : ''}</textarea>
                    <div class="form-actions">
                        <button type="submit">${pet ? 'Update Pet' : 'Save New Pet'}</button>
                        <button type="button" onclick="closePetForm()">Cancel</button>
                    </div>
                </form>
            `;
        }
        
        // --- Event Listeners ---
        document.addEventListener('DOMContentLoaded', fetchAllPets);
        
        petModal.addEventListener('click', (event) => {
            if (event.target === petModal) {
                closePetForm();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.querySelector('.hamburger-menu');
            const overlay = document.querySelector('.sidebar-overlay');
            const body = document.body;

            if (hamburgerBtn && body) {
                hamburgerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    body.classList.toggle('sidebar-is-open');
                });
            }
            if (overlay && body) {
                overlay.addEventListener('click', function() { body.classList.remove('sidebar-is-open'); });
            }
        });
    </script>
</body>
</html>