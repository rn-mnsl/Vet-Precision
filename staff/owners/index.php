<?php
require_once '../../config/init.php';
$pageTitle = 'Patients - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <?php include '../../includes/favicon.php'; ?>
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

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        .main-content .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .main-content .header {
            margin-bottom: 30px;
        }
        .main-content .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .main-content .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        .search-filter {
            display: flex;
            gap: 10px;
            flex: 1;
        }
        .search-box {
            position: relative;
            flex: 1;
            max-width: 300px;
        }
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .search-box::before {
            content: "🔍";
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .filter-btn {
            padding: 10px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
        }
        .action-buttons .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-secondary {
            background: white;
            border: 1px solid #ddd;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        .status.active {
            background: #ffe6e6;
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
        }
        .action-links {
            display: flex;
            gap: 15px;
        }
        .action-links a {
            text-decoration: none;
            font-size: 12px;
        }
        .action-links a.delete {
            color: var(--primary-color);
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background: white;
            font-size: 14px;
            color: #666;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal.hidden {
            display: none;
        }
        .modal-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
        }
        .modal-content {
            position: relative;
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .modal-content label {
            display: block;
            margin-top: 15px;
        }
        .modal-content input,
        .modal-content textarea,
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }


        /* Pagination */
        .pagination-controls {
            display: flex;
            justify-content: center; /* This centers the controls horizontally */
            align-items: center;
            gap: 0.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef; /* Matching table border color */
        }
        .pagination-link {
            display: inline-flex; /* Helps center the icon */
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: var(--primary-color); /* Make sure --primary-color is defined */
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
            min-width: 40px; /* Ensures consistent button size */
            height: 40px;
        }
        .pagination-link:hover {
            background-color: #f5f5f5;
            border-color: #ccc;
        }
        .pagination-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            cursor: default;
        }
        .pagination-link.disabled {
            color: #aaa;
            pointer-events: none;
            background-color: #f8f9fa;
            border-color: #e9ecef;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 1100; position: fixed; top: 0; height: 100vh; margin-top: 0; }
            .main-content { margin-left: 0; }
            body.sidebar-is-open .sidebar { transform: translateX(0); box-shadow: 0 0 20px rgba(0,0,0,0.25); }
            body.sidebar-is-open .sidebar-overlay { opacity: 1; visibility: visible; }
            .main-content { padding-top: 85px; } /* Space for fixed navbar */
        }

    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-staff.php'; ?>
        <?php include '../../includes/navbar.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <div class="header">
                    <h1>Patients Management</h1>
                    <p>Manage your clients and their pets in one place</p>
                </div>

                <div class="controls">
                    <div class="search-filter">
                        <div class="search-box">
                            <input type="text" placeholder="Search..." id="searchInput">
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="createClient()">Create Client</button>
                    </div>
                </div>

                <div class="table-container">
                    <table id="patientsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>City</th>
                                <th>Status</th>
                                <th>Created At ↑</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                    <div class="pagination">
                        <div class="pagination-info">
                            <span></span>
                            <span>Show</span>
                            <select id="entriesPerPage" onchange="changeEntriesPerPage()">
                                <option value="5">5</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div id="pagination-controls">
                            <!-- Pagination buttons will be generated here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

        <!-- Modal -->
    <div id="clientModal" class="modal hidden">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <h2 id="modalTitle">Add New Client</h2>
            <form id="clientForm" novalidate>
                <!-- Hidden input to store user_id during edits -->
                <input type="hidden" name="user_id" id="user_id">

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label for="first_name">First Name*</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Enter first name" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="last_name">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" placeholder="Enter last name" required>
                    </div>
                </div>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter email">
                
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" placeholder="Enter phone number">

                <label for="address">Address</label>
                <input type="text" id="address" name="address" placeholder="Enter address">

                <label for="city">City</label>
                <input type="text" id="city" name="city" placeholder="Enter city">
                
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitButton">Create Client</button>
                </div>
            </form>
        </div>
    </div>

   <script>
        // --- GLOBAL STATE & CONFIG ---
        let currentPage = 1;
        let entriesPerPage = 5;
        let totalRecords = 0;
        let searchTimeout; // For debouncing search input

        const API_URL = 'ajax/owners_handler.php';

        // --- DOM ELEMENTS ---
        const tableBody = document.getElementById('tableBody');
        const searchInput = document.getElementById('searchInput');
        const entriesPerPageSelect = document.getElementById('entriesPerPage');
        const paginationControls = document.getElementById('pagination-controls');
        const clientModal = document.getElementById('clientModal');
        const clientForm = document.getElementById('clientForm');
        const modalTitle = document.getElementById('modalTitle');
        const modalSubmitButton = document.getElementById('modalSubmitButton');

        // --- DATA FETCHING & RENDERING ---

        async function fetchPatients() {
            const searchTerm = searchInput.value;
            const url = new URL(API_URL, window.location.href);
            url.searchParams.append('action', 'fetch');
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);
            url.searchParams.append('search', searchTerm);

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                
                if (result.success) {
                    totalRecords = result.totalRecords;
                    renderTable(result.data);
                    updatePaginationInfo();
                    renderPaginationControls();
                } else {
                    alert('Error fetching data: ' + result.message);
                    tableBody.innerHTML = `<tr><td colspan="7" class="empty-state">${result.message}</td></tr>`;
                }
            } catch (error) {
                console.error('Failed to fetch patients:', error);
                tableBody.innerHTML = `<tr><td colspan="7" class="empty-state">Error loading data. Please try again.</td></tr>`;
            }
        }

        function renderTable(patients) {
            if (patients.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" class="empty-state">No patients found</td></tr>`;
                return;
            }

            tableBody.innerHTML = patients.map(p => {
                const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim();
                const statusText = p.is_active == 1 ? 'Active' : 'Inactive';
                const statusClass = p.is_active == 1 ? 'active' : 'inactive';
                const createdAt = new Date(p.user_created_at).toLocaleDateString('en-CA');

                return `
                    <tr>
                        <td>${fullName || 'N/A'}</td>
                        <td>${p.phone || 'No phone'}</td>
                        <td>${p.email || 'N/A'}</td>
                        <td>${p.city || 'N/A'}</td>
                        <td><span class="status ${statusClass}">${statusText}</span></td>
                        <td>${createdAt}</td>
                        <td class="action-links">
                            <a href="#" onclick="event.preventDefault(); editClient(${p.user_id})">Edit</a>
                            <a href="#" class="delete" onclick="event.preventDefault(); deleteClient(${p.user_id})">Delete</a>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // --- PAGINATION & SEARCH ---

        function updatePaginationInfo() {
            const start = totalRecords === 0 ? 0 : (currentPage - 1) * entriesPerPage + 1;
            const end = Math.min(currentPage * entriesPerPage, totalRecords);
            document.querySelector('.pagination-info span:first-child').textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;
        }

        function renderPaginationControls() {
            const totalPages = Math.ceil(totalRecords / entriesPerPage);
            paginationControls.innerHTML = ''; // Clear previous controls

            if (totalPages <= 1) {
                return; // No need for controls if there's only one page
            }

            // Use an array to build the links, then join. It's cleaner.
            const links = [];

            // Previous Page Link
            const prevClass = (currentPage === 1) ? 'disabled' : '';
            links.push(`<a href="#" class="pagination-link ${prevClass}" onclick="goToPage(${currentPage - 1}, event)"><i class="fas fa-chevron-left"></i></a>`);

            // Page Number Links (Smart Ellipsis Logic)
            const pagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(pagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + pagesToShow - 1);

            if (endPage - startPage + 1 < pagesToShow) {
                startPage = Math.max(1, endPage - pagesToShow + 1);
            }
            
            if (startPage > 1) {
                links.push(`<a href="#" class="pagination-link" onclick="goToPage(1, event)">1</a>`);
                if (startPage > 2) {
                    links.push(`<span class="pagination-link disabled">...</span>`);
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = (i === currentPage) ? 'active' : '';
                links.push(`<a href="#" class="pagination-link ${activeClass}" onclick="goToPage(${i}, event)">${i}</a>`);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    links.push(`<span class="pagination-link disabled">...</span>`);
                }
                links.push(`<a href="#" class="pagination-link" onclick="goToPage(${totalPages}, event)">${totalPages}</a>`);
            }

            // Next Page Link
            const nextClass = (currentPage === totalPages) ? 'disabled' : '';
            links.push(`<a href="#" class="pagination-link ${nextClass}" onclick="goToPage(${currentPage + 1}, event)"><i class="fas fa-chevron-right"></i></a>`);
            
            paginationControls.innerHTML = links.join('');
        }

        function goToPage(page, event) {
            if (event) event.preventDefault(); // Prevent the link from navigating

            // Do nothing if the link is disabled or it's the current page
            if (page === currentPage || page < 1 || page > Math.ceil(totalRecords / entriesPerPage)) {
                return;
            }
            
            currentPage = page;
            fetchPatients();
        }

        function changeEntriesPerPage() {
            entriesPerPage = parseInt(entriesPerPageSelect.value);
            currentPage = 1;
            fetchPatients();
        }

        function handleSearch() {
            clearTimeout(searchTimeout);
            // Debounce: wait 300ms after user stops typing before making the API call
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                fetchPatients();
            }, 300);
        }
        
        // --- MODAL & FORM HANDLING (Largely unchanged, minor tweaks for clarity) ---

        function openModal() { clientModal.classList.remove('hidden'); }
        function closeModal() {
            clientModal.classList.add('hidden');
            clientForm.reset();
            clientForm.removeAttribute('data-editing-id');
        }

        function createClient() {
            clientForm.reset();
            modalTitle.textContent = 'Add New Client';
            modalSubmitButton.textContent = 'Create Client';
            document.getElementById('is_active').value = '1';
            openModal();
        }

        async function editClient(userId) {
            // We fetch the latest data directly instead of relying on a potentially stale local copy
            const client = await getClientById(userId);
            if (!client) return alert("Could not retrieve client data.");
            
            clientForm.reset();
            modalTitle.textContent = 'Edit Client';
            modalSubmitButton.textContent = 'Save Changes';
            
            document.getElementById('user_id').value = client.user_id;
            document.getElementById('first_name').value = client.first_name || '';
            document.getElementById('last_name').value = client.last_name || '';
            document.getElementById('email').value = client.email || '';
            document.getElementById('phone').value = client.phone || '';
            document.getElementById('address').value = client.address || '';
            document.getElementById('city').value = client.city || '';
            document.getElementById('is_active').value = client.is_active;
            
            clientForm.setAttribute('data-editing-id', userId);
            openModal();
        }
        
        // Helper to get a single client's full details for the edit form
        async function getClientById(userId) {
            // This is a simplified approach. In a real app, you might have a dedicated API endpoint `?action=get&id=...`
            // For now, we'll just re-fetch the list and find the user.
            const response = await fetch(`${API_URL}?action=fetch&search=&limit=${totalRecords || 1000}`);
            const result = await response.json();
            return result.success ? result.data.find(p => p.user_id === userId) : null;
        }

        async function deleteClient(userId) {
            if (!confirm("Are you sure you want to deactivate this client?")) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);
            
            try {
                const response = await fetch(API_URL, { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    // If the last item on a page is deleted, go to the previous page
                    if (tableBody.rows.length === 1 && currentPage > 1) {
                        currentPage--;
                    }
                    fetchPatients();
                }
            } catch (error) {
                console.error('Failed to delete client:', error);
                alert('An error occurred. Please try again.');
            }
        }
        
        clientForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(clientForm);
            formData.append('action', clientForm.hasAttribute('data-editing-id') ? 'update' : 'create');
            
            if (!formData.get('first_name') || !formData.get('last_name')) {
                return alert('First Name and Last Name are required.');
            }
            modalSubmitButton.disabled = true;
            modalSubmitButton.textContent = 'Saving...';

            try {
                const response = await fetch(API_URL, { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    closeModal();
                    fetchPatients();
                }
            } catch (error) {
                console.error('Form submission error:', error);
                alert('An error occurred while saving.');
            } finally {
                modalSubmitButton.disabled = false;
            }
        });

        // --- INITIALIZATION ---

        
        document.addEventListener('DOMContentLoaded', () => {
            fetchPatients();
            searchInput.addEventListener('input', handleSearch);
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
                overlay.addEventListener('click', function() {
                    body.classList.remove('sidebar-is-open');
                });
            }
        });
    </script>

</body>
</html>