-- Database Schema for Bagstore Webapplication
-- Converted to MySQL for phpMyAdmin
-- Database Engine: MySQL / MariaDB

-- Table: INVENTORY_PART
CREATE TABLE INVENTORY_PART (
    part_no VARCHAR(50) PRIMARY KEY, -- รหัสสินค้า
    description TEXT, -- รายละเอียด
    part_family VARCHAR(50), -- CEMENT / MORTAR / FERTILIZER
    material_type VARCHAR(50) -- PAPER / PP / FILM
);

-- Table: INVENTORY_PART_LOCATION
CREATE TABLE INVENTORY_PART_LOCATION (
    location_id INT PRIMARY KEY,
    part_no VARCHAR(50) NOT NULL,
    location_code VARCHAR(50), -- 3001 / BP001 / OR001
    location_type VARCHAR(50), -- STORE / WAREHOUSE / TRANSIT
    qty_onhand INT -- จำนวนคงเหลือ
);

-- Table: PURCHASE_REQ_LINE_PART
CREATE TABLE PURCHASE_REQ_LINE_PART (
    req_line_id INT PRIMARY KEY,
    part_no VARCHAR(50) NOT NULL,
    qty_to_order INT, -- จำนวนที่ขอซื้อ
    state VARCHAR(50), -- Request Created / Released / Approved
    requisition_no VARCHAR(50) -- เลข PR
);

-- Table: PURCHASE_ORDER_LINE_PART
CREATE TABLE PURCHASE_ORDER_LINE_PART (
    po_line_id INT PRIMARY KEY,
    part_no VARCHAR(50) NOT NULL,
    po_no VARCHAR(50), -- เลข PO
    qty_ordered INT, -- จำนวนที่สั่ง
    qty_arrived INT, -- จำนวนที่รับแล้ว
    state VARCHAR(50), -- Released / Confirmed / Arrived
    requisition_no VARCHAR(50) -- อ้างอิง PR
);

-- Table: INVENTORY_ARRIVAL_TRANSACTION
CREATE TABLE INVENTORY_ARRIVAL_TRANSACTION (
    arrival_id INT PRIMARY KEY,
    part_no VARCHAR(50) NOT NULL,
    po_no VARCHAR(50),
    location_code VARCHAR(50),
    qty INT,
    transaction_code VARCHAR(50), -- ARRIVAL / RETWORK / UNRCPT
    arrival_date DATE
);

-- Table: BAG_TYPE_CONFIG
CREATE TABLE BAG_TYPE_CONFIG (
    bag_type VARCHAR(50) PRIMARY KEY,
    display_name VARCHAR(255), -- ชื่อที่แสดง
    category VARCHAR(50), -- CEMENT / MORTAR / FERTILIZER
    sort_order INT -- ลำดับ
);

-- Table: MANUAL_DATA
CREATE TABLE MANUAL_DATA (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_type VARCHAR(50),
    qr_code VARCHAR(255),
    part_no VARCHAR(50), -- optional mapping to part
    part_desc TEXT,
    quantity INT,
    note TEXT,
    delivery_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Foreign Keys

ALTER TABLE INVENTORY_PART_LOCATION
ADD CONSTRAINT FK_INVENTORY_PART_LOCATION_part_no
FOREIGN KEY (part_no) REFERENCES INVENTORY_PART(part_no);

ALTER TABLE PURCHASE_REQ_LINE_PART
ADD CONSTRAINT FK_PURCHASE_REQ_LINE_PART_part_no
FOREIGN KEY (part_no) REFERENCES INVENTORY_PART(part_no);

ALTER TABLE PURCHASE_ORDER_LINE_PART
ADD CONSTRAINT FK_PURCHASE_ORDER_LINE_PART_part_no
FOREIGN KEY (part_no) REFERENCES INVENTORY_PART(part_no);

ALTER TABLE INVENTORY_ARRIVAL_TRANSACTION
ADD CONSTRAINT FK_INVENTORY_ARRIVAL_TRANSACTION_part_no
FOREIGN KEY (part_no) REFERENCES INVENTORY_PART(part_no);

ALTER TABLE MANUAL_DATA
ADD CONSTRAINT FK_MANUAL_DATA_bag_type
FOREIGN KEY (bag_type) REFERENCES BAG_TYPE_CONFIG(bag_type);

ALTER TABLE MANUAL_DATA
ADD CONSTRAINT FK_MANUAL_DATA_part_no
FOREIGN KEY (part_no) REFERENCES INVENTORY_PART(part_no);
