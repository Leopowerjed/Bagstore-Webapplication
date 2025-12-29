-- Database Schema for Bagstore Webapplication
-- Generated from DBML
-- Database Engine: SQL Server (MSSQL)

-- Table: INVENTORY_PART
CREATE TABLE INVENTORY_PART (
    part_no NVARCHAR(50) PRIMARY KEY, -- รหัสสินค้า
    description NVARCHAR(MAX), -- รายละเอียด
    part_family NVARCHAR(50), -- CEMENT / MORTAR / FERTILIZER
    material_type NVARCHAR(50) -- PAPER / PP / FILM
);
GO

-- Table: INVENTORY_PART_LOCATION
CREATE TABLE INVENTORY_PART_LOCATION (
    location_id INT PRIMARY KEY,
    part_no NVARCHAR(50) NOT NULL,
    location_code NVARCHAR(50), -- 3001 / BP001 / OR001
    location_type NVARCHAR(50), -- STORE / WAREHOUSE / TRANSIT
    qty_onhand INT -- จำนวนคงเหลือ
);
GO

-- Table: PURCHASE_REQ_LINE_PART
CREATE TABLE PURCHASE_REQ_LINE_PART (
    req_line_id INT PRIMARY KEY,
    part_no NVARCHAR(50) NOT NULL,
    qty_to_order INT, -- จำนวนที่ขอซื้อ
    state NVARCHAR(50), -- Request Created / Released / Approved
    requisition_no NVARCHAR(50) -- เลข PR
);
GO

-- Table: PURCHASE_ORDER_LINE_PART
CREATE TABLE PURCHASE_ORDER_LINE_PART (
    po_line_id INT PRIMARY KEY,
    part_no NVARCHAR(50) NOT NULL,
    po_no NVARCHAR(50), -- เลข PO
    qty_ordered INT, -- จำนวนที่สั่ง
    qty_arrived INT, -- จำนวนที่รับแล้ว
    state NVARCHAR(50), -- Released / Confirmed / Arrived
    requisition_no NVARCHAR(50) -- อ้างอิง PR
);
GO

-- Table: INVENTORY_ARRIVAL_TRANSACTION
CREATE TABLE INVENTORY_ARRIVAL_TRANSACTION (
    arrival_id INT PRIMARY KEY,
    part_no NVARCHAR(50) NOT NULL,
    po_no NVARCHAR(50),
    location_code NVARCHAR(50),
    qty INT,
    transaction_code NVARCHAR(50), -- ARRIVAL / RETWORK / UNRCPT
    arrival_date DATE
);
GO

-- Table: BAG_TYPE_CONFIG
CREATE TABLE BAG_TYPE_CONFIG (
    bag_type NVARCHAR(50) PRIMARY KEY,
    display_name NVARCHAR(255), -- ชื่อที่แสดง
    category NVARCHAR(50), -- CEMENT / MORTAR / FERTILIZER
    sort_order INT -- ลำดับ
);
GO

-- Table: MANUAL_DATA
CREATE TABLE MANUAL_DATA (
    id INT PRIMARY KEY IDENTITY(1,1),
    bag_type NVARCHAR(50),
    qr_code NVARCHAR(255),
    part_no NVARCHAR(50), -- optional mapping to part
    part_desc NVARCHAR(MAX),
    quantity INT,
    note NVARCHAR(MAX),
    delivery_date DATE,
    created_at DATETIME DEFAULT GETDATE()
);
GO

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

-- Note: In DBML "Ref: PURCHASE_REQ_LINE_PART.requisition_no < PURCHASE_ORDER_LINE_PART.requisition_no"
-- This implies a relationship, but usually requisition_no is not a PK in PURCHASE_REQ_LINE_PART (req_line_id is).
-- Unless we enforce unique constraint on requisition_no in PURCHASE_REQ_LINE_PART or have a separate Header table.
-- For now, adding index or foreign key might be tricky without a Header table.
-- Assuming logical relationship, but strictly SQL FK requires PK/Unique on parent.
-- Skipping FK for requisition_no for now to avoid errors if data is not normalized.

ALTER TABLE MANUAL_DATA
ADD CONSTRAINT FK_MANUAL_DATA_bag_type
FOREIGN KEY (bag_type) REFERENCES BAG_TYPE_CONFIG(bag_type);

ALTER TABLE MANUAL_DATA
ADD CONSTRAINT FK_MANUAL_DATA_part_no
FOREIGN KEY (part_no) REFERENCES INVENTORY_PART(part_no);
GO
