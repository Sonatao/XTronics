const userRole = window.XTronics && window.XTronics.userRole ? window.XTronics.userRole : "";

/* DOM NODE REFERENCES */
const orderForm = document.getElementById("orderForm");
const orderDate = document.getElementById("orderDate");
const customerName = document.getElementById("customerName");
const buyer = document.getElementById("buyer");
const vendorOrder = document.getElementById("vendorOrder");
const poNumber = document.getElementById("poNumber");
const partNumber = document.getElementById("partNumber");
const shippingMethod = document.getElementById("shippingMethod");
const notes = document.getElementById("notes");
const trackingNumber = document.getElementById("trackingNumber");
const status = document.getElementById("status");

const editModal = document.getElementById("editModal");
const closeModal = document.getElementById("closeModal");
const editForm = document.getElementById("editForm");
const editId = document.getElementById("editId");
const editOrderDate = document.getElementById("editOrderDate");
const editCustomerName = document.getElementById("editCustomerName");
const editBuyer = document.getElementById("editBuyer");
const editVendorOrder = document.getElementById("editVendorOrder");
const editPoNumber = document.getElementById("editPoNumber");
const editPartNumber = document.getElementById("editPartNumber");
const editShippingMethod = document.getElementById("editShippingMethod");
const editNotes = document.getElementById("editNotes");
const editTrackingNumber = document.getElementById("editTrackingNumber");
const editStatus = document.getElementById("editStatus");

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

function escapeHtml(value) {
    const raw = String(value ?? "");
    return raw.replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function statusClass(status) {
    const s = (status || "").toLowerCase();
    if (s.includes("new")) return "status-badge status-new";
    if (s.includes("process") || s.includes("working") || s.includes("build") || s.includes("in progress")) return "status-badge status-processing";
    if (s.includes("wait") || s.includes("hold") || s.includes("pending") || s.includes("await")) return "status-badge status-waiting";
    if (s.includes("ship") || s.includes("dispatch") || s.includes("deliver") || s.includes("sent")) return "status-badge status-shipped";
    if (s.includes("close") || s.includes("done") || s.includes("complete") || s.includes("finish")) return "status-badge status-closed";
    return "status-badge status-generic";
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

        const safeCustomerName = escapeHtml(order.customerName || "");
        const safePoNumber = escapeHtml(order.poNumber || "");
        const safePartNumber = escapeHtml(order.partNumber || "");
        const safeBuyer = escapeHtml(order.buyer || "");
        const safeVendorOrder = escapeHtml(order.vendorOrder || "");
        const safeOrderDate = escapeHtml(order.orderDate || "");
        const safeShippingMethod = escapeHtml(order.shippingMethod || "");
        const safeTrackingNumber = escapeHtml(order.trackingNumber || "");
        const safeStatus = escapeHtml(order.status || "");
        const safeNotes = escapeHtml(notesShort || "");

        card.innerHTML = `
            <div class="ticket-main">
                <div class="ticket-title">
                    ${safeCustomerName} &mdash; ${safePoNumber}
                </div>
                <div class="ticket-sub">
                    Part: ${safePartNumber} &bull; Buyer: ${safeBuyer}
                </div>
                <div class="ticket-sub">
                    Vendor / Vendor Order #: ${safeVendorOrder}
                </div>
                <div class="ticket-notes">
                    ${safeNotes || "<em>No notes</em>"}
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
                    <span class="${statusClass(order.status)}">${safeStatus}</span>
                </div>
                <div class="ticket-sub">
                    Order Date: ${safeOrderDate}
                </div>
                <div class="ticket-sub">
                    Shipping: ${safeShippingMethod}
                </div>
                <div class="ticket-sub">
                    Tracking: ${safeTrackingNumber}
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
    const statusValue = document.getElementById("searchStatus").value;

    const params = new URLSearchParams({
        action: "search",
        q,
        customer,
        dateFrom,
        dateTo,
        status: statusValue
    });

    fetch("crud.php?" + params.toString())
        .then(r => r.json())
        .then(renderTickets)
        .catch(err => {
            console.error("Unable to run search", err);
            alert("Search failed. Please try again.");
        });
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
orderForm.addEventListener("submit", e => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("action", "create");
    formData.append("orderDate", orderDate.value);
    formData.append("customerName", customerName.value);
    formData.append("buyer", buyer.value);
    formData.append("vendorOrder", vendorOrder.value);
    formData.append("poNumber", poNumber.value);
    formData.append("partNumber", partNumber.value);
    formData.append("shippingMethod", shippingMethod.value);
    formData.append("notes", notes.value);
    formData.append("trackingNumber", trackingNumber.value);
    formData.append("status", status.value);

    fetch("crud.php", { method: "POST", body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                alert(res.error);
                return;
            }
            orderForm.reset();
            loadTickets();
        })
        .catch(err => {
            console.error("Create failed", err);
            alert("Unable to add ticket. Please try again.");
        });
});

/* DELETE */
function deleteOrder(id) {
    if (!window.confirm("Are you sure you want to delete this ticket? This action cannot be undone.")) {
        return;
    }

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
            editVendorOrder.value = o.vendorOrder || "";
            editPoNumber.value = o.poNumber;
            editPartNumber.value = o.partNumber;
            editShippingMethod.value = o.shippingMethod;
            editNotes.value = o.notes;
            editTrackingNumber.value = o.trackingNumber;
            editStatus.value = o.status;

            editModal.classList.remove("hidden");
        });
}

editForm.addEventListener("submit", e => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("action", "update");
    formData.append("id", editId.value);
    formData.append("orderDate", editOrderDate.value);
    formData.append("customerName", editCustomerName.value);
    formData.append("buyer", editBuyer.value);
    formData.append("vendorOrder", editVendorOrder.value);
    formData.append("poNumber", editPoNumber.value);
    formData.append("partNumber", editPartNumber.value);
    formData.append("shippingMethod", editShippingMethod.value);
    formData.append("notes", editNotes.value);
    formData.append("trackingNumber", editTrackingNumber.value);
    formData.append("status", editStatus.value);

    fetch("crud.php", { method: "POST", body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                alert(res.error);
                return;
            }
            editModal.classList.add("hidden");
            loadTickets();
        })
        .catch(err => {
            console.error("Update failed", err);
            alert("Unable to save ticket changes. Please try again.");
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
                            <th>Vendor / Order #</th>
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
                                <td>${escapeHtml(h.editedAt || "")}</td>
                                <td>${escapeHtml(h.orderDate || "")}</td>
                                <td>${escapeHtml(h.customerName || "")}</td>
                                <td>${escapeHtml(h.buyer || "")}</td>
                                <td>${escapeHtml(h.vendorOrder || "")}</td>
                                <td>${escapeHtml(h.poNumber || "")}</td>
                                <td>${escapeHtml(h.partNumber || "")}</td>
                                <td>${escapeHtml(h.shippingMethod || "")}</td>
                                <td>${escapeHtml(h.notes || "")}</td>
                                <td>${escapeHtml(h.trackingNumber || "")}</td>
                                <td>${escapeHtml(h.status || "")}</td>
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

                const fileInput = e.target.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert("Please choose a file to upload.");
                    return;
                }

                const fd = new FormData();
                fd.append("ticketId", id);
                fd.append("file", fileInput.files[0]);

                fetch("upload.php", { method: "POST", body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.error) {
                            alert(res.error);
                        } else {
                            toggleAttachments(id);
                            toggleAttachments(id);
                        }
                    })
                    .catch(err => {
                        console.error("Upload failed", err);
                        alert("Unable to upload the file.");
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
