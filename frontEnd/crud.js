const orderForm = document.getElementById("orderForm");
const orderTableBody = document.getElementById("orderTableBody");

let orders = [];
let editingId = null;

/* ============================
   CREATE ORDER
   ============================ */
orderForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const newOrder = {
        id: Math.floor(1000000 + Math.random() * 9000000),
        orderDate: document.getElementById("orderDate").value,
        customerName: document.getElementById("customerName").value,
        buyer: document.getElementById("buyer").value,
        poNumber: document.getElementById("poNumber").value,
        partNumber: document.getElementById("partNumber").value,
        shippingMethod: document.getElementById("shippingMethod").value,
        notes: document.getElementById("notes").value,
        trackingNumber: document.getElementById("trackingNumber").value,
        status: document.getElementById("status").value,
        history: []
    };

    orders.push(newOrder);
    renderTable();
    orderForm.reset();
});

/* ============================
   RENDER TABLE
   ============================ */
function renderTable() {
    orderTableBody.innerHTML = "";

    orders.forEach(order => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>
                <button class="arrowBtn" onclick="toggleHistory(${order.id})">
                    ▶
                </button>
            </td>
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
                <button onclick="editOrder(${order.id})">Edit</button>
                <button onclick="deleteOrder(${order.id})">Delete</button>
            </td>
        `;

        orderTableBody.appendChild(row);

        /* ============================
           HISTORY DROPDOWN ROW
           ============================ */
        const historyRow = document.createElement("tr");
        historyRow.classList.add("historyRow");

        historyRow.innerHTML = `
            <td colspan="11">
                <div id="history-${order.id}" class="historyContainer hidden">
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
                            ${order.history.map(h => `
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
                </div>
            </td>
        `;

        orderTableBody.appendChild(historyRow);
    });
}

/* ============================
   OPEN EDIT MODAL
   ============================ */
function editOrder(id) {
    const order = orders.find(o => o.id === id);
    editingId = id;

    document.getElementById("editOrderDate").value = order.orderDate;
    document.getElementById("editCustomerName").value = order.customerName;
    document.getElementById("editBuyer").value = order.buyer;
    document.getElementById("editPoNumber").value = order.poNumber;
    document.getElementById("editPartNumber").value = order.partNumber;
    document.getElementById("editShippingMethod").value = order.shippingMethod;
    document.getElementById("editNotes").value = order.notes;
    document.getElementById("editTrackingNumber").value = order.trackingNumber;
    document.getElementById("editStatus").value = order.status;

    document.getElementById("editModal").classList.remove("hidden");
}

/* ============================
   SAVE EDIT + PUSH HISTORY
   ============================ */
document.getElementById("editForm").addEventListener("submit", (e) => {
    e.preventDefault();

    const order = orders.find(o => o.id === editingId);

    const oldVersion = {
        editedAt: new Date().toLocaleString(),
        orderDate: order.orderDate,
        customerName: order.customerName,
        buyer: order.buyer,
        poNumber: order.poNumber,
        partNumber: order.partNumber,
        shippingMethod: order.shippingMethod,
        notes: order.notes,
        trackingNumber: order.trackingNumber,
        status: order.status
    };

    order.history.push(oldVersion);

    order.orderDate = document.getElementById("editOrderDate").value;
    order.customerName = document.getElementById("editCustomerName").value;
    order.buyer = document.getElementById("editBuyer").value;
    order.poNumber = document.getElementById("editPoNumber").value;
    order.partNumber = document.getElementById("editPartNumber").value;
    order.shippingMethod = document.getElementById("editShippingMethod").value;
    order.notes = document.getElementById("editNotes").value;
    order.trackingNumber = document.getElementById("editTrackingNumber").value;
    order.status = document.getElementById("editStatus").value;

    renderTable();
    closeModal();
});

/* ============================
   CLOSE MODAL
   ============================ */
function closeModal() {
    document.getElementById("editModal").classList.add("hidden");
}

document.getElementById("closeModal").addEventListener("click", closeModal);

/* ============================
   TOGGLE HISTORY DROPDOWN
   ============================ */
function toggleHistory(id) {
    const container = document.getElementById(`history-${id}`);
    const arrow = event.target;

    container.classList.toggle("hidden");

    // Rotate arrow
    if (container.classList.contains("hidden")) {
        arrow.textContent = "▶";
    } else {
        arrow.textContent = "▼";
    }
}

/* ============================
   DELETE ORDER
   ============================ */
function deleteOrder(id) {
    orders = orders.filter(o => o.id !== id);
    renderTable();
}

/* ============================
   DARK / LIGHT MODE TOGGLE
   ============================ */

const themeToggle = document.getElementById("themeToggle");

// When the user clicks the toggle button
themeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");

    const isDark = document.body.classList.contains("dark-mode");

    // Update button text
    themeToggle.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";

    // Save preference
    localStorage.setItem("theme", isDark ? "dark" : "light");
});

// Load saved theme on startup
window.addEventListener("DOMContentLoaded", () => {
    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark-mode");
        themeToggle.textContent = "☀️ Light Mode";
    }
});

/* ============================
   EXPORT TO EXCEL (Single Sheet, Auto Width, Bold Headers, Custom Filename)
   ============================ */

function buildExportSheet() {
    const sheetData = [];

    orders.forEach(order => {

        // Fresh ticket header (bold)
        sheetData.push([
            { v: "Ticket ID", s: { font: { bold: true } } },
            { v: "Order Date", s: { font: { bold: true } } },
            { v: "Customer", s: { font: { bold: true } } },
            { v: "Buyer", s: { font: { bold: true } } },
            { v: "PO#", s: { font: { bold: true } } },
            { v: "Part#", s: { font: { bold: true } } },
            { v: "Shipping", s: { font: { bold: true } } },
            { v: "Notes", s: { font: { bold: true } } },
            { v: "Tracking#", s: { font: { bold: true } } },
            { v: "Status", s: { font: { bold: true } } }
        ]);

        // Fresh ticket row
        sheetData.push([
            order.id,
            order.orderDate,
            order.customerName,
            order.buyer,
            order.poNumber,
            order.partNumber,
            order.shippingMethod,
            order.notes,
            order.trackingNumber,
            order.status
        ]);

        // Blank spacer row
        sheetData.push([]);

        // History header (bold)
        sheetData.push([
            { v: "Edited At", s: { font: { bold: true } } },
            { v: "Order Date", s: { font: { bold: true } } },
            { v: "Customer", s: { font: { bold: true } } },
            { v: "Buyer", s: { font: { bold: true } } },
            { v: "PO#", s: { font: { bold: true } } },
            { v: "Part#", s: { font: { bold: true } } },
            { v: "Shipping", s: { font: { bold: true } } },
            { v: "Notes", s: { font: { bold: true } } },
            { v: "Tracking#", s: { font: { bold: true } } },
            { v: "Status", s: { font: { bold: true } } }
        ]);

        // History rows
        order.history.forEach(h => {
            sheetData.push([
                h.editedAt,
                h.orderDate,
                h.customerName,
                h.buyer,
                h.poNumber,
                h.partNumber,
                h.shippingMethod,
                h.notes,
                h.trackingNumber,
                h.status
            ]);
        });

        // Spacer between tickets
        sheetData.push([]);
        sheetData.push([]);
    });

    return sheetData;
}

/* Auto-size columns */
function autoSizeColumns(ws, data) {
    const colWidths = [];

    data.forEach(row => {
        row.forEach((cell, i) => {
            const value = cell && cell.v !== undefined ? cell.v : cell;
            const length = value ? value.toString().length : 10;
            colWidths[i] = Math.max(colWidths[i] || 10, length + 2);
        });
    });

    ws['!cols'] = colWidths.map(w => ({ wch: w }));
}

/* Export ALL tickets in ONE sheet */
document.getElementById("exportAll").addEventListener("click", () => {
    const filename = prompt("Enter a filename:", "All_Tickets.xlsx");
    if (!filename) return;

    const data = buildExportSheet();
    const ws = XLSX.utils.aoa_to_sheet(data);

    autoSizeColumns(ws, data);

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Tickets");

    XLSX.writeFile(wb, filename, { cellStyles: true });
});

/* Export SINGLE ticket */
document.getElementById("exportSingle").addEventListener("click", () => {
    if (!editingId) {
        alert("Select or edit a ticket first.");
        return;
    }

    const filename = prompt("Enter a filename:", `Ticket_${editingId}.xlsx`);
    if (!filename) return;

    const order = orders.find(o => o.id === editingId);

    const data = buildExportSheet([order]);
    const ws = XLSX.utils.aoa_to_sheet(data);

    autoSizeColumns(ws, data);

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, `Ticket_${order.id}`);

    XLSX.writeFile(wb, filename, { cellStyles: true });
});
