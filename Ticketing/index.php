<?php
require "auth.php";
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XTronics Ticketing</title>

    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <link rel="stylesheet" href="styles.base.css">
    <link id="themeStylesheet" rel="stylesheet" href="theme.minimal.css">
    <script src="app.js" defer></script>
</head>

<body>

<button id="themeToggle" class="theme-toggle">Switch Theme</button>

<main>

<section class="title">
    <h1>XTronics Ticketing System</h1>
    <p>Logged in as: <?php echo htmlspecialchars(currentUsername()); ?> (<?php echo htmlspecialchars(currentUserRole()); ?>)</p>
    <a href="logout.php">Logout</a> |
    <?php if (currentUserRole() === "admin"): ?>
        <a href="audit.php">Audit Log Viewer</a> |
        <a href="users.php">User Management</a>
    <?php endif; ?>
</section>

<section class="searchFilters">
    <h2>Search & Filters</h2>
    <form id="searchForm">
        <input type="text" id="searchQuery" placeholder="Search text (customer, PO, part, notes, status)">
        <input type="text" id="searchCustomer" placeholder="Customer name">
        <input type="date" id="searchDateFrom" placeholder="From date">
        <input type="date" id="searchDateTo" placeholder="To date">
        <input type="text" id="searchStatus" placeholder="Status (e.g. New, Shipped)">
        <button type="submit">Apply Filters</button>
        <button type="button" id="clearFilters">Clear</button>
    </form>
</section>

<section class="informationTable">
    <h2>Tickets</h2>

    <div class="ticket-list" id="ticketList"></div>

    <div class="exportButtons">
        <button id="exportSingle" class="action-btn export">Export Selected Ticket</button>
        <button id="exportAll" class="action-btn export">Export All Tickets</button>
    </div>
</section>

<h3>Create New Ticket</h3>
<form id="orderForm">
    <input type="date" id="orderDate" required>
    <input type="text" id="customerName" placeholder="Customer Name" required>
    <input type="text" id="buyer" placeholder="Buyer" required>
    <input type="text" id="vendorOrder" placeholder="Vendor / Vendor Order #">
    <input type="text" id="poNumber" placeholder="PO Number" required>
    <input type="text" id="partNumber" placeholder="Part Number" required>
    <input type="text" id="shippingMethod" placeholder="Shipping Method" required>
    <input type="text" id="notes" placeholder="Notes">
    <input type="text" id="trackingNumber" placeholder="Tracking Number" required>
    <textarea id="status" placeholder="Status" rows="4"></textarea>

    <button type="submit">Add Ticket</button>
</form>

<div id="editModal" class="modal hidden">
    <div class="modal-content">
        <h1>Edit Ticket</h1>

        <form id="editForm">
            <input type="hidden" id="editId">

            <input type="date" id="editOrderDate" required>
            <input type="text" id="editCustomerName" placeholder="Customer Name" required>
            <input type="text" id="editBuyer" placeholder="Buyer" required>
            <input type="text" id="editVendorOrder" placeholder="Vendor / Vendor Order #">
            <input type="text" id="editPoNumber" placeholder="PO Number" required>
            <input type="text" id="editPartNumber" placeholder="Part Number" required>
            <input type="text" id="editShippingMethod" placeholder="Shipping Method" required>
            <input type="text" id="editNotes" placeholder="Notes">
            <input type="text" id="editTrackingNumber" placeholder="Tracking Number" required>
            <textarea id="editStatus" placeholder="Status" rows="4"></textarea>

            <button type="submit">Save Changes</button>
            <button type="button" id="closeModal">Cancel</button>
        </form>
    </div>
</div>

</main>

<script>
    window.XTronics = window.XTronics || {};
    window.XTronics.userRole = "<?php echo htmlspecialchars(currentUserRole()); ?>";
</script>


</body>
</html>
