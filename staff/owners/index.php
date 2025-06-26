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
    <link rel="stylesheet" href="../../assets/css/style.css">
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-staff.php'; ?>
        
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
                        <button class="filter-btn" onclick="toggleFilters()">🔽 Filters</button>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="createClient()">Create Client</button>
                        <button class="btn btn-secondary" onclick="importData()">📤 Import</button>
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
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div>
                            <span></span>
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
            <h2>Add New Client</h2>
            <form id="clientForm">
                <label>Name*</label>
                <input type="text" placeholder="Enter name" required>

                <label>Alternate Phone</label>
                <input type="text" placeholder="+1">

                <label>City</label>
                <input type="text" placeholder="Enter city">

                <label>Purpose of Visit</label>
                <input type="text" placeholder="Enter purpose of visit">

                <label>Status</label>
                <select>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <label>Phone</label>
                <input type="text" placeholder="+1">

                <label>Email</label>
                <input type="email" placeholder="Enter email">

                <label>Address</label>
                <input type="text" placeholder="Enter address">

                <label>Notes</label>
                <textarea placeholder="Enter notes"></textarea>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Client</button>
                    <button type="button" class="btn btn-primary" onclick="saveAndAddPet()">Save & Add Pet</button>
                </div>
            </form>
        </div>
    </div>

   <script>
    let patients = [
        {
            id: 1,
            name: "Roan Manansala",
            phone: "No phone",
            email: "mananasalaroan1@gmail.com",
            city: "Lubao",
            status: "Active",
            createdAt: "Jun 24, 2025"
        }
    ];

    let currentPage = 1;
    let entriesPerPage = 10;
    let filteredPatients = [...patients];
    let clientIdCounter = patients.length + 1;

    function renderTable() {
        const tbody = document.getElementById('tableBody');
        const start = (currentPage - 1) * entriesPerPage;
        const end = start + entriesPerPage;
        const paginated = filteredPatients.slice(start, end);

        if (paginated.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-state">No patients found</td></tr>`;
            return;
        }

        tbody.innerHTML = paginated.map(p => `
            <tr>
                <td>${p.name}</td>
                <td>${p.phone}</td>
                <td>${p.email}</td>
                <td>${p.city}</td>
                <td><span class="status ${p.status.toLowerCase()}">${p.status}</span></td>
                <td>${p.createdAt}</td>
                <td class="action-links">
                    <a href="#" onclick="editClient(${p.id})">Edit</a>
                    <a href="#" class="delete" onclick="deleteClient(${p.id})">Delete</a>
                </td>
            </tr>
        `).join('');
        updatePaginationInfo();
    }

    function updatePaginationInfo() {
        const total = filteredPatients.length;
        const start = (currentPage - 1) * entriesPerPage + 1;
        const end = Math.min(currentPage * entriesPerPage, total);
        const totalPages = Math.ceil(total / entriesPerPage);

        document.querySelector('.pagination-info span').textContent =
            `Showing ${start} to ${end} of ${total} entries`;
        document.querySelector('.pagination div:last-child span').textContent =
            `Page ${currentPage} of ${totalPages}`;
    }

    function changeEntriesPerPage() {
        entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
        currentPage = 1;
        renderTable();
    }

    function searchPatients() {
        const term = document.getElementById('searchInput').value.toLowerCase();
        filteredPatients = patients.filter(p =>
            p.name.toLowerCase().includes(term) ||
            p.city.toLowerCase().includes(term) ||
            p.email.toLowerCase().includes(term) ||
            p.phone.toLowerCase().includes(term)
        );
        currentPage = 1;
        renderTable();
    }

    function createClient() {
        document.getElementById('clientModal').classList.remove('hidden');
        document.getElementById('clientForm').reset();
    }

    function closeModal() {
        document.getElementById('clientModal').classList.add('hidden');
    }

    function importData() {
        alert('Import Data functionality triggered.');
    }

    function toggleFilters() {
        alert('Filters toggle triggered.');
    }

    function editClient(id) {
        const client = patients.find(p => p.id === id);
        if (!client) return alert("Client not found.");

        // Populate form with client data
        const form = document.getElementById('clientForm');
        form.elements[0].value = client.name;
        form.elements[1].value = client.phone !== "No phone" ? client.phone : "";
        form.elements[2].value = client.city;
        form.elements[3].value = client.purpose || "";
        form.elements[4].value = client.status;
        form.elements[5].value = client.phone !== "No phone" ? client.phone : "";
        form.elements[6].value = client.email;
        form.elements[7].value = client.address || "";
        form.elements[8].value = client.notes || "";

        form.dataset.editingId = id; // Flag to know we're editing
        document.getElementById('clientModal').classList.remove('hidden');
    }

    function deleteClient(id) {
        if (confirm("Are you sure you want to delete this client?")) {
            patients = patients.filter(p => p.id !== id);
            filteredPatients = filteredPatients.filter(p => p.id !== id);
            renderTable();
        }
    }

    function saveAndAddPet() {
        // Same logic as submit but simulate redirect
        document.getElementById('clientForm').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        alert("Client saved. Redirecting to pet registration...");
    }

    document.getElementById('clientForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        const newClient = {
            id: form.dataset.editingId ? parseInt(form.dataset.editingId) : clientIdCounter++,
            name: formData.get(form[0].name || '') || form[0].value,
            phone: formData.get(form[5].name || '') || form[5].value || "No phone",
            email: formData.get(form[6].name || '') || form[6].value,
            city: formData.get(form[2].name || '') || form[2].value,
            status: formData.get(form[4].name || '') || form[4].value,
            createdAt: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        };

        if (form.dataset.editingId) {
            patients = patients.map(p => p.id === newClient.id ? newClient : p);
            delete form.dataset.editingId;
        } else {
            patients.push(newClient);
        }

        filteredPatients = [...patients];
        renderTable();
        form.reset();
        closeModal();
    });

    document.getElementById('searchInput').addEventListener('input', searchPatients);
    renderTable();
</script>

</body>
</html>