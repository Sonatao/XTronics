ALTER TABLE orders
    ADD COLUMN vendorOrder VARCHAR(255) NULL;

ALTER TABLE order_history
    ADD COLUMN vendorOrder VARCHAR(255) NULL;

-- If the legacy schema still has vendorInfo, copy the old data forward for compatibility.
UPDATE orders
SET vendorOrder = vendorInfo
WHERE vendorOrder IS NULL AND vendorInfo IS NOT NULL;

UPDATE order_history
SET vendorOrder = vendorInfo
WHERE vendorOrder IS NULL AND vendorInfo IS NOT NULL;
