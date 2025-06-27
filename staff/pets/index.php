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
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            color: white;
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
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            cursor: pointer;
        }

        .add-pet-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            text-decoration: none;
            color: white;
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
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .pet-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }

        .pet-header {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
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
            color: #95a5a6;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
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
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            color: white;
            box-shadow: 0 2px 10px rgba(255, 107, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-outline {
            background: transparent;
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
        }

        .btn-outline:hover {
            background: #ff6b6b;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: transparent;
            color: #e74c3c;
            border: 2px solid #e74c3c;
        }

        .btn-danger:hover {
            background: #e74c3c;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

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
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .search-filters {
                flex-direction: column;
                gap: 1rem;
            }

            .pets-grid {
                grid-template-columns: 1fr;
            }

            .pet-actions {
                flex-direction: column;
            }

            .btn {
                flex: none;
            }

            #inlinePetForm form {
                grid-template-columns: 1fr;
            }
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
                        <input type="text" placeholder="Search pets by name, breed, or owner..." id="searchInput" onkeyup="renderPets()">
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Species:</span>
                        <select class="filter-select" id="speciesFilter" onchange="renderPets()">
                            <option value="">All Species</option>
                            <option value="dog">Dog</option>
                            <option value="cat">Cat</option>
                            <option value="bird">Bird</option>
                            <option value="rabbit">Rabbit</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Status:</span>
                        <select class="filter-select" id="statusFilter" onchange="renderPets()">
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
            </div>
        </main>
    </div>

    <!-- REMINDER: This is the fully modified JavaScript section. -->
    <script>
        // REMINDER: This is the list of owners fetched by PHP.
        const allOwners = <?php echo json_encode($owners); ?>;
        
        // REMINDER: The 'pets' array is now empty. It will be filled by our AJAX call.
        let pets = [];
        let currentEditingPet = null;

        // --- Core AJAX Functions ---

        async function fetchAllPets() {
            try {
                const response = await fetch('ajax/pets_handler.php?action=fetch_all');
                const result = await response.json();

                if (result.success) {
                    pets = result.data; // Update the global pets array
                    renderPets(); // Re-render the UI with the new data
                } else {
                    console.error('Failed to fetch pets:', result.error);
                    document.getElementById('petsGrid').innerHTML = '<p>Error loading pets. Please try again.</p>';
                }
            } catch (error) {
                console.error('Network error:', error);
                document.getElementById('petsGrid').innerHTML = '<p>Could not connect to the server.</p>';
            }
        }

        async function savePet(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const petId = formData.get('pet_id');

            // Determine if we are adding or updating
            const action = petId ? 'update' : 'add';
            formData.append('action', action);

            try {
                const response = await fetch('ajax/pets_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message); // Show success message
                    cancelForm(); // Hide the form
                    fetchAllPets(); // Refresh the list from the database
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('An unexpected network error occurred.');
                console.error(error);
            }
        }
        
        async function deletePet(id) {
            if (!confirm('Are you sure you want to delete this pet? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('pet_id', id);

            try {
                const response = await fetch('ajax/pets_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    fetchAllPets(); // Refresh the list
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('An unexpected network error occurred.');
                console.error(error);
            }
        }

        // --- UI Rendering and Helper Functions (Modified to use real data) ---

        // REMINDER: No changes needed to calculateAge or getPetEmoji
        function calculateAge(dateOfBirth) {
            const today = new Date();
            const birthDate = new Date(dateOfBirth);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age;
        }
        function getPetEmoji(species) {
            switch(species.toLowerCase()) {
                case 'dog': return '🐕';
                case 'cat': return '🐱';
                case 'bird': return '🐦';
                case 'rabbit': return '🐇';
                default: return '🐾';
            }
        }

        function renderPets() {
            const grid = document.getElementById('petsGrid');
            grid.innerHTML = '';
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            const speciesFilter = document.getElementById('speciesFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;

            const filteredPets = pets.filter(pet => {
                const isActive = pet.is_active == 1;
                const searchMatch = pet.name.toLowerCase().includes(searchQuery) ||
                                    pet.breed.toLowerCase().includes(searchQuery) ||
                                    (pet.owner_name && pet.owner_name.toLowerCase().includes(searchQuery));

                const speciesMatch = speciesFilter === '' || pet.species.toLowerCase() === speciesFilter;
                const statusMatch = statusFilter === '' || (statusFilter === 'active' && isActive) || (statusFilter === 'inactive' && !isActive);
                
                return searchMatch && speciesMatch && statusMatch;
            });

            if (filteredPets.length === 0) {
                grid.innerHTML = '<p>No pets found matching your criteria.</p>';
                return;
            }
            
            filteredPets.forEach(pet => {
                const age = calculateAge(pet.date_of_birth);

                // --- START: NEW AVATAR LOGIC ---
                let avatarHtml = '';
                
                // This simple 'if' works because the PHP handler now sends 'null' for missing photos.
                if (pet.photo_url) {
                    // A valid photo exists. Use it as a background image.
                    // The path from the DB is relative, but we need it relative to the main HTML page.
                    // e.g., ../../uploads/pets/image.jpg
                    const imagePath = pet.photo_url
                    console.log(imagePath);
                    avatarHtml = `<div class="pet-avatar" style="background-image: url('${imagePath}'); background-size: cover; background-position: center;"></div>`;
                } else {
                    // No valid photo. Use the emoji as the content.
                    const emoji = getPetEmoji(pet.species);
                    avatarHtml = `<div class="pet-avatar">${emoji}</div>`;
                }
                // --- END: NEW AVATAR LOGIC ---

                const card = document.createElement('div');
                card.className = 'pet-card';
                // We use the `avatarHtml` variable here
                card.innerHTML = `
                    <div class="pet-header">
                        ${avatarHtml} 
                        <div class="pet-name">${pet.name}</div>
                        <div class="pet-breed">${pet.breed}</div>
                    </div>
                    <div class="pet-details">
                        <div class="pet-info">
                            <div class="info-item">
                                <div class="info-label">Owner</div>
                                <div class="info-value">${pet.owner_name || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Age</div>
                                <div class="info-value">${age} years</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Weight</div>
                                <div class="info-value">${pet.weight ? pet.weight + ' kg' : 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value">${pet.gender}</div>
                            </div>
                        </div>
                        <div class="pet-actions">
                            <a href="../appointments/index.php?action=create&owner_id=${pet.owner_id}&pet_id=${pet.pet_id}" class="btn btn-primary">Book Appointment</a>
                            <button class="btn btn-outline" onclick="editPet(${pet.pet_id})">Edit Info</button>
                            <button class="btn btn-danger" onclick="deletePet(${pet.pet_id})">Delete</button>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function editPet(id) {
            // REMINDER: Find pet from the global array fetched from DB
            const pet = pets.find(p => p.pet_id === id);
            if (pet) {
                currentEditingPet = pet;
                openPetForm(pet);
            }
        }

        function openPetForm(pet = null) {
            const formContainer = document.getElementById('inlinePetForm');
            const mainContainer = document.getElementById('mainContainer');
            
            // Hide the pet cards and search filters
            mainContainer.classList.add('form-active');
            
            formContainer.style.display = 'block';
            formContainer.innerHTML = generatePetForm(pet);
            formContainer.scrollIntoView({ behavior: 'smooth' });
        }

        // REMINDER: This function is now dynamic and includes the owner dropdown.
        function generatePetForm(pet) {
            let ownerOptions = allOwners.map(owner => 
                `<option value="${owner.owner_id}" ${pet && pet.owner_id == owner.owner_id ? 'selected' : ''}>
                    ${owner.full_name}
                </option>`
            ).join('');

            return `
                <h2>${pet ? 'Edit ' + pet.name : 'Add New Pet'}</h2>
                <form onsubmit="savePet(event)">
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
                        <option value="Other" ${pet && pet.species === 'Other' ? 'selected' : ''}>Other</option>
                    </select>

                    <input type="text" name="breed" placeholder="Breed" value="${pet ? pet.breed : ''}" required>
                    <input type="date" name="date_of_birth" value="${pet ? pet.date_of_birth : ''}" required>
                    
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" ${pet && pet.gender === 'Male' ? 'selected' : ''}>Male</option>
                        <option value="Female" ${pet && pet.gender === 'Female' ? 'selected' : ''}>Female</option>
                    </select>

                    <input type="text" name="microchip_id" placeholder="Microchip Number" value="${pet ? pet.microchip_id : ''}">
                    <input type="text" name="color" placeholder="Color" value="${pet ? pet.color : ''}">
                    <input type="number" step="0.01" name="weight" placeholder="Weight (kg)" value="${pet ? pet.weight : ''}">
                    
                    <textarea name="notes" placeholder="Notes (e.g., allergies, conditions)">${pet ? pet.notes : ''}</textarea>
                    
                    <button type="submit">${pet ? 'Update Pet' : 'Add Pet'}</button>
                    <button type="button" onclick="cancelForm()">Cancel</button>
                </form>
            `;
        }
        
        function cancelForm() {
            const formContainer = document.getElementById('inlinePetForm');
            const mainContainer = document.getElementById('mainContainer');
            
            // Show the pet cards and search filters again
            mainContainer.classList.remove('form-active');
            
            formContainer.style.display = 'none';
            currentEditingPet = null;
        }

        // REMINDER: This is the starting point. It fetches data from the server instead of just rendering.
        document.addEventListener('DOMContentLoaded', fetchAllPets);
    </script>
</body>
</html>