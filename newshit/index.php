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
</head>

<body>

<button id="themeToggle" class="theme-toggle">Switch Theme</button>

<main>

<section class="title">
    <h1>XTronics Ticketing System</h1>
    <p>Logged in as: <?php echo htmlspecialchars(currentUsername()); ?> (<?php echo htmlspecialchars(currentUserRole()); ?>)</p>
    <a href="logout.php">Logout</a> |
    <?php if (currentUserRole() === "admin"): ?>
        <a href="audit.php">Audit Log Viewer</a>
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

    <table id="orderTable">
        <thead>
            <tr>
                <th></th>
                <th>Order Date</th>
                <th>Customer Name</th>
                <th>Buyer</th>
                <th>PO#</th>
                <th>Part#</th>
                <th>Shipping Method</th>
                <th>Notes</th>
                <th>Tracking #</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="orderTableBody"></tbody>
    </table>

    <div class="exportButtons">
        <button id="exportSingle" class="action-btn export">Export Selected Ticket</button>
        <button id="exportAll" class="action-btn export">Export All Tickets</button>
    </div>
</section>

<h3>Create New Ticket</h3>
<form id="orderForm">
    <input type="date" id="orderDate" required>
    <input type="text" id="customerName" required>
    <input type="text" id="buyer" required>
    <input type="text" id="poNumber" required>
    <input type="text" id="partNumber" required>
    <input type="text" id="shippingMethod" required>
    <input type="text" id="notes">
    <input type="text" id="trackingNumber" required>
    <input type="text" id="status" required>

    <button type="submit">Add Ticket</button>
</form>

<div id="editModal" class="modal hidden">
    <div class="modal-content">
        <h1>Edit Ticket</h1>

        <form id="editForm">
            <input type="hidden" id="editId">

            <input type="date" id="editOrderDate" required>
            <input type="text" id="editCustomerName" required>
            <input type="text" id="editBuyer" required>
            <input type="text" id="editPoNumber" required>
            <input type="text" id="editPartNumber" required>
            <input type="text" id="editShippingMethod" required>
            <input type="text" id="editNotes">
            <input type="text" id="editTrackingNumber" required>
            <input type="text" id="editStatus" required>

            <button type="submit">Save Changes</button>
            <button type="button" id="closeModal">Cancel</button>
        </form>
    </div>
</div>

</main>

<script>
const userRole = "<?php echo htmlspecialchars(currentUserRole()); ?>";

/* THEME TOGGLE */
const themeToggleBtn = document.getElementById("themeToggle");
const themeLink = document.getElementById("themeStylesheet");

let currentTheme = localStorage.getItem("xt_theme") || "minimal";

function applyTheme(theme) {
    if (theme === "minimal") {
        themeLink.href = "theme.minimal.css";
        themeToggleBtn.textContent = "Switch to GitHub Dark";
    } else {
        themeLink.href = "theme.github.css";
        themeToggleBtn.textContent = "Switch to Minimal Light";
    }
    currentTheme = theme;
    localStorage.setItem("xt_theme", theme);
}

applyTheme(currentTheme);

themeToggleBtn.addEventListener("click", () => {
    applyTheme(currentTheme === "minimal" ? "github" : "minimal");
});

/* RENDER TICKETS */
function renderTickets(data) {
    const body = document.getElementById("orderTableBody");
    body.innerHTML = "";

    data.forEach(order => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td><button onclick="toggleHistory(${order.id})">▶</button></td>
            <td>${order.orderDate}</td>
            <td>${order.customerName}</td>
            <td>${order.buyer}</td>
            <td>${order.poNumber}</td>
            <td>${order.partNumber}</td>
            <td>${order.shippingMethod}</td>
            <td>${order.notes}</td>
            <td>${order.trackingNumber}</td>
            <td>${order.status}</td>
            <td>
                <button onclick="openEdit(${order.id})">Edit</button>
                ${userRole === "admin" ? `<button onclick="deleteOrder(${order.id})">Delete</button>` : ""}
                <button onclick="toggleAttachments(${order.id})">Attachments</button>
            </td>
        `;

        body.appendChild(row);

        const historyRow = document.createElement("tr");
        historyRow.innerHTML = `
            <td colspan="11">
                <div id="history-${order.id}" class="hidden"></div>
            </td>
        `;
        body.appendChild(historyRow);

        const attachRow = document.createElement("tr");
        attachRow.innerHTML = `
            <td colspan="11">
                <div id="attachments-${order.id}" class="hidden"></div>
            </td>
        `;
        body.appendChild(attachRow);
    });
}

/* LOAD ALL */
function loadTickets() {
    fetch("crud.php?action=read")
        .then(r => r.json())
        .then(renderTickets);
}

loadTickets();

/* SEARCH */
document.getElementById("searchForm").addEventListener("submit", e => {
    e.preventDefault();

    const q         = document.getElementById("searchQuery").value;
    const customer  = document.getElementById("searchCustomer").value;
    const dateFrom  = document.getElementById("searchDateFrom").value;
    const dateTo    = document.getElementById("searchDateTo").value;
    const status    = document.getElementById("searchStatus").value;

    const params = new URLSearchParams({
        action: "search",
        q,
        customer,
        dateFrom,
        dateTo,
        status
    });

    fetch("crud.php?" + params.toString())
        .then(r => r.json())
        .then(renderTickets);
});

document.getElementById("clearFilters").addEventListener("click", () => {
    document.getElementById("searchQuery").value = "";
    document.getElementById("searchCustomer").value = "";
    document.getElementById("searchDateFrom").value = "";
    document.getElementById("searchDateTo").value = "";
    document.getElementById("searchStatus").value = "";
    loadTickets();
});

/* CREATE */
document.getElementById("orderForm").addEventListener("submit", e => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("action", "create");
    formData.append("orderDate", orderDate.value);
    formData.append("customerName", customerName.value);
    formData.append("buyer", buyer.value);
    formData.append("poNumber", poNumber.value);
    formData.append("partNumber", partNumber.value);
    formData.append("shippingMethod", shippingMethod.value);
    formData.append("notes", notes.value);
    formData.append("trackingNumber", trackingNumber.value);
    formData.append("status", status.value);

    fetch("crud.php", { method: "POST", body: formData })
        .then(() => {
            orderForm.reset();
            loadTickets();
        });
});

/* DELETE */
function deleteOrder(id) {
    fetch(`crud.php?action=delete&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                alert(res.error);
            } else {
                loadTickets();
            }
        });
}

/* EDIT */
function openEdit(id) {
    fetch(`crud.php?action=fetch&id=${id}`)
        .then(r => r.json())
        .then(o => {
            editId.value = o.id;
            editOrderDate.value = o.orderDate;
            editCustomerName.value = o.customerName;
            editBuyer.value = o.buyer;
            editPoNumber.value = o.poNumber;
            editPartNumber.value = o.partNumber;
            editShippingMethod.value = o.shippingMethod;
            editNotes.value = o.notes;
            editTrackingNumber.value = o.trackingNumber;
            editStatus.value = o.status;

            editModal.classList.remove("hidden");
        });
}

document.getElementById("editForm").addEventListener("submit", e => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("action", "update");
    formData.append("id", editId.value);
    formData.append("orderDate", editOrderDate.value);
    formData.append("customerName", editCustomerName.value);
    formData.append("buyer", editBuyer.value);
    formData.append("poNumber", editPoNumber.value);
    formData.append("partNumber", editPartNumber.value);
    formData.append("shippingMethod", editShippingMethod.value);
    formData.append("notes", editNotes.value);
    formData.append("trackingNumber", editTrackingNumber.value);
    formData.append("status", editStatus.value);

    fetch("crud.php", { method: "POST", body: formData })
        .then(() => {
            editModal.classList.add("hidden");
            loadTickets();
        });
});

closeModal.onclick = () => editModal.classList.add("hidden");

/* HISTORY */
function toggleHistory(id) {
    const container = document.getElementById(`history-${id}`);

    if (!container.classList.contains("hidden")) {
        container.classList.add("hidden");
        return;
    }

    fetch(`crud.php?action=history&id=${id}`)
        .then(r => r.json())
        .then(history => {
            container.innerHTML = `
                <h4>Ticket History</h4>
                <table class="historyTable">
                    <thead>
                        <tr>
                            <th>Edited At</th>
                            <th>Order Date</th>
                            <th>Customer</th>
                            <th>Buyer</th>
                            <th>PO#</th>
                            <th>Part#</th>
                            <th>Shipping</th>
                            <th>Notes</th>
                            <th>Tracking</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${history.map(h => `
                            <tr>
                                <td>${h.editedAt}</td>
                                <td>${h.orderDate}</td>
                                <td>${h.customerName}</td>
                                <td>${h.buyer}</td>
                                <td>${h.poNumber}</td>
                                <td>${h.partNumber}</td>
                                <td>${h.shippingMethod}</td>
                                <td>${h.notes}</td>
                                <td>${h.trackingNumber}</td>
                                <td>${h.status}</td>
                            </tr>
                        `).join("")}
                    </tbody>
                </table>
            `;

            container.classList.remove("hidden");
        });
}

/* ATTACHMENTS */
function toggleAttachments(id) {
    const container = document.getElementById(`attachments-${id}`);

    if (!container.classList.contains("hidden")) {
        container.classList.add("hidden");
        return;
    }

    fetch(`crud.php?action=files&id=${id}`)
        .then(r => r.json())
        .then(files => {
            container.innerHTML = `
                <h4>Attachments</h4>

                <form id="uploadForm-${id}" enctype="multipart/form-data">
                    <input type="file" name="file" required>
                    <button type="submit">Upload</button>
                </form>

                <ul>
                    ${files.map(f => `
                        <li>
                            <a href="uploads/${f.filename}" target="_blank">${f.originalName}</a>
                            (${f.uploadedAt})
                        </li>
                    `).join("")}
                </ul>
            `;

            document.getElementById(`uploadForm-${id}`).addEventListener("submit", e => {
                e.preventDefault();

                const fd = new FormData();
                fd.append("ticketId", id);
                fd.append("file", e.target.file.files[0]);

                fetch("upload.php", { method: "POST", body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.error) {
                            alert(res.error);
                        } else {
                            toggleAttachments(id);
                            toggleAttachments(id);
                        }
                    });
            });

            container.classList.remove("hidden");
        });
}
</script>

</body>
</html>
