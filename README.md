create table "users" (
id INTEGER PRIMARY KEY AUTOINCREMENT,
username TEXT UNIQUE NOT NULL,
passwordHash TEXT NOT NULL,
roleId INTEGER NOT NULL,
created_at TEXT DEFAULT(datetime('now')),
FOREIGN KEY (roleId) REFERENCES roles(id)
);

create Table roles (
id INTEGER PRIMARY Key AUTOINCREMENT,
name TEXT UNIQUE NOT NULL,
description TEXT
);

create Table permissions (
id INTEGER PRIMARY KEY AUTOINCREMENT,
name TEXT UNIQUE NOT NULL,
description TEXT
);

CREATE TABLE rolePermissions (
roleId INTEGER NOT NULL,
permissionId INTEGER NOT NULL,
PRIMARY KEY (roleId, permissionId),
FOREIGN KEY (roleId) REFERENCES roles(id),
FOREIGN KEY (permissionId) REFERENCES permissions(id)
);

CREATE TABLE files (
id INTEGER PRIMARY KEY AUTOINCREMENT,
filename TEXT NOT NULL,
storedPath TEXT NOT NULL,
uploaderId INTEGER NOT NULL,
uploadedAt TEXT DEFAULT (datetime('now')),
permissionLevel TEXT DEFAULT 'staff',
FOREIGN KEY (uploaderId) REFERENCES users(id)
);

create table auditLogs (
id INTEGER PRIMARY KEY AUTOINCREMENT,
userId INTEGER NOT NULL,
action TEXT NOT NULL,
target Text,
timestamp TEXT DEFAULT (datetime('now')),
FOREIGN KEY (userId) REFERENCES users(id)
);

CREATE TABLE orders (
id INTEGER PRIMARY KEY,
orderDate TEXT NOT NULL,
customerName TEXT NOT NULL,
buyer TEXT NOT NULL,
poNumber TEXT NOT NULL,
partNumber TEXT NOT NULL,
shippingMethod TEXT NOT NULL,
notes TEXT,
trackingNumber TEXT NOT NULL,
status TEXT NOT NULL,
created_at TEXT DEFAULT (datetime('now'))
);

create table orderHistory (
id INTEGER PRIMARY KEY AUTOINCREMENT,
orderId INTEGER NOT NULL,
editedAt TEXT NOT NULL,
orderDate TEXT NOT NULL,
customerName TEXT NOT NULL,
buyer TEXT NOT NULL,
poNumber TEXT NOT NULL,
partNumber TEXT NOT NULL,
shippingMethod TEXT NOT NULL,
notes Text,
trackingNumber TEXT NOT NULL,
status TEXT NOT NULL,
FOREIGN KEY(orderId) REFERENCES orders(id)
);
