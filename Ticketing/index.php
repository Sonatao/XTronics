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
    <input type="text" id="vendorInfo" placeholder="Vendor Info">
    <input type="text" id="poNumber" placeholder="PO Number" required>
    <input type="text" id="partNumber" placeholder="Part Number" required>
    <input type="text" id="shippingMethod" placeholder="Shipping Method" required>
    <input type="text" id="notes" placeholder="Notes">
    <input type="text" id="trackingNumber" placeholder="Tracking Number" required>
    <input type="text" id="status" placeholder="Status" required>

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
            <input type="text" id="editVendorInfo" placeholder="Vendor Info">
            <input type="text" id="editPoNumber" placeholder="PO Number" required>
            <input type="text" id="editPartNumber" placeholder="Part Number" required>
            <input type="text" id="editShippingMethod" placeholder="Shipping Method" required>
            <input type="text" id="editNotes" placeholder="Notes">
            <input type="text" id="editTrackingNumber" placeholder="Tracking Number" required>
            <input type="text" id="editStatus" placeholder="Status" required>

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

/* STATUS BADGE CLASS */
function statusClass(status) {
    const s = (status || "").toLowerCase();
    if (s.includes("new")) return "status-badge status-new";
    if (s.includes("process")) return "status-badge status-processing";
    if (s.includes("wait")) return "status-badge status-waiting";
    if (s.includes("ship")) return "status-badge status-shipped";
    if (s.includes("close")) return "status-badge status-closed";
    return "status-badge status-new";
}

/* RENDER TICKETS AS CARDS */
function renderTickets(data) {
    const list = document.getElementById("ticketList");
    list.innerHTML = "";

    data.forEach(order => {
        const card = document.createElement("div");
        card.className = "ticket-card";

        const notesShort = order.notes && order.notes.length > 80
            ? order.notes.substring(0, 80) + "..."
            : (order.notes || "");

        card.innerHTML = `
            <div class="ticket-main">
                <div class="ticket-title">
                    ${order.customerName} &mdash; ${order.poNumber}
                </div>
                <div class="ticket-sub">
                    Part: ${order.partNumber} &bull; Buyer: ${order.buyer}
                </div>
                <div class="ticket-notes">
                    ${notesShort || "<em>No notes</em>"}
                </div>
                <div class="ticket-actions">
                    <button onclick="openEdit(${order.id})">Edit</button>
                    ${userRole === "admin" ? `<button onclick="deleteOrder(${order.id})">Delete</button>` : ""}
                    <button onclick="toggleAttachments(${order.id})">Attachments</button>
                    <button onclick="toggleHistory(${order.id})">History</button>
                </div>
                <div id="panel-history-${order.id}" class="ticket-panel hidden"></div>
                <div id="panel-attachments-${order.id}" class="ticket-panel hidden"></div>
            </div>
            <div class="ticket-meta">
                <div>
                    <span class="${statusClass(order.status)}">${order.status}</span>
                </div>
                <div class="ticket-sub">
                    Order Date: ${order.orderDate}
                </div>
                <div class="ticket-sub">
                    Shipping: ${order.shippingMethod}
                </div>
                <div class="ticket-sub">
                    Tracking: ${order.trackingNumber}
                </div>
            </div>
        `;

        list.appendChild(card);
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

/* HISTORY PANEL */
function toggleHistory(id) {
    const container = document.getElementById(`panel-history-${id}`);

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

/* ATTACHMENTS PANEL WITH PREVIEWS */
function toggleAttachments(id) {
    const container = document.getElementById(`panel-attachments-${id}`);

    if (!container.classList.contains("hidden")) {
        container.classList.add("hidden");
        return;
    }

    fetch(`crud.php?action=files&id=${id}`)
        .then(r => r.json())
        .then(files => {
            container.innerHTML = `
                <h4>Attachments</h4>

                <div class="attachments-list">
                    <form id="uploadForm-${id}" enctype="multipart/form-data">
                        <input type="file" name="file" required>
                        <button type="submit">Upload</button>
                    </form>

                    <ul>
                        ${files.map(f => renderAttachmentItem(f)).join("")}
                    </ul>
                </div>
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

function renderAttachmentItem(f) {
    const ext = f.originalName.split(".").pop().toLowerCase();
    const isImage = ["jpg", "jpeg", "png", "gif"].includes(ext);
    const isPdf = ext === "pdf";

    let preview = "";

    if (isImage) {
        preview = `
            <div class="attachment-thumb">
                <img src="uploads/${f.filename}" alt="${f.originalName}">
            </div>
        `;
    } else if (isPdf) {
        preview = `
            <div class="attachment-pdf">
                <embed src="uploads/${f.filename}" type="application/pdf" width="100%" height="100%">
            </div>
        `;
    } else {
        preview = `<span>[${ext.toUpperCase()}]</span>`;
    }

    const deleteBtn = userRole === "admin"
        ? `<button onclick="deleteAttachment(${f.id})">Delete</button>`
        : "";

    return `
        <li>
            ${preview}
            <div>
                <a href="uploads/${f.filename}" target="_blank">${f.originalName}</a><br>
                <small>${f.uploadedAt}</small><br>
                ${deleteBtn}
            </div>
        </li>
    `;
}

function deleteAttachment(id) {
    if (!confirm("Delete this attachment?")) return;

    const fd = new FormData();
    fd.append("mode", "delete");
    fd.append("id", id);

    fetch("upload.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                alert(res.error);
            } else {
                loadTickets();
            }
        });
}
</script>

</body>
</html>
