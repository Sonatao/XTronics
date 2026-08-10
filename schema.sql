CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orderDate DATE NOT NULL,
    customerName VARCHAR(255),
    buyer VARCHAR(255),
    poNumber VARCHAR(255),
    partNumber VARCHAR(255),
    shippingMethod VARCHAR(255),
    notes TEXT,
    trackingNumber VARCHAR(255),
    status VARCHAR(255),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orderId INT NOT NULL,
    editedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    orderDate DATE,
    customerName VARCHAR(255),
    buyer VARCHAR(255),
    poNumber VARCHAR(255),
    partNumber VARCHAR(255),
    shippingMethod VARCHAR(255),
    notes TEXT,
    trackingNumber VARCHAR(255),
    status VARCHAR(255),
    FOREIGN KEY (orderId) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','employee') DEFAULT 'employee',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ticket_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticketId INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    originalName VARCHAR(255) NOT NULL,
    uploadedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticketId) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    ticketId INT,
    details TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
