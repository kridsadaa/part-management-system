-- --------------------------------------------------------
-- SQL Script สำหรับสร้างฐานข้อมูลและตารางทั้งหมดใน MS SQL Server
-- --------------------------------------------------------

-- สร้างฐานข้อมูล
CREATE DATABASE [infinity_parts_db];
GO

USE [infinity_parts_db];
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [users] (ข้อมูลผู้ใช้งาน)
-- --------------------------------------------------------
CREATE TABLE [users] (
  [user_id] INT IDENTITY(1,1) PRIMARY KEY,
  [username] NVARCHAR(50) NOT NULL UNIQUE,
  [password] NVARCHAR(255) NOT NULL,
  [first_name] NVARCHAR(100) NOT NULL,
  [last_name] NVARCHAR(100) NOT NULL,
  [email] NVARCHAR(100) NOT NULL UNIQUE,
  [role] NVARCHAR(10) NOT NULL CHECK ([role] IN ('admin', 'staff', 'viewer')) DEFAULT 'viewer',
  [status] NVARCHAR(10) NOT NULL CHECK ([status] IN ('active', 'inactive')) DEFAULT 'active',
  [created_at] DATETIME DEFAULT GETDATE(),
  [updated_at] DATETIME DEFAULT GETDATE()
);
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [companies] (ข้อมูลบริษัทลูกค้า)
-- --------------------------------------------------------
CREATE TABLE [companies] (
  [company_id] INT IDENTITY(1,1) PRIMARY KEY,
  [company_name] NVARCHAR(100) NOT NULL,
  [company_name_th] NVARCHAR(100) NULL,
  [contact_person] NVARCHAR(100) NULL,
  [phone] NVARCHAR(20) NULL,
  [email] NVARCHAR(100) NULL,
  [address] NVARCHAR(MAX) NULL,
  [created_at] DATETIME DEFAULT GETDATE(),
  [updated_at] DATETIME DEFAULT GETDATE()
);
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [parts] (ข้อมูลชิ้นส่วน)
-- --------------------------------------------------------
CREATE TABLE [parts] (
  [part_id] INT IDENTITY(1,1) PRIMARY KEY,
  [company_id] INT NOT NULL,
  [part_no] NVARCHAR(50) NOT NULL,
  [notes] NVARCHAR(MAX) NULL,
  [created_by] INT NOT NULL,
  [created_at] DATETIME DEFAULT GETDATE(),
  [updated_at] DATETIME DEFAULT GETDATE(),
  CONSTRAINT [FK_parts_companies] FOREIGN KEY ([company_id]) REFERENCES [companies]([company_id]) ON DELETE CASCADE,
  CONSTRAINT [FK_parts_users] FOREIGN KEY ([created_by]) REFERENCES [users]([user_id]),
  CONSTRAINT [UQ_parts] UNIQUE ([company_id], [part_no])
);
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [file_types] (ประเภทไฟล์)
-- --------------------------------------------------------
CREATE TABLE [file_types] (
  [file_type_id] INT IDENTITY(1,1) PRIMARY KEY,
  [type_name] NVARCHAR(50) NOT NULL UNIQUE,
  [description] NVARCHAR(MAX) NULL,
  [icon] NVARCHAR(50) NULL
);
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [files] (ข้อมูลไฟล์)
-- --------------------------------------------------------
CREATE TABLE [files] (
  [file_id] INT IDENTITY(1,1) PRIMARY KEY,
  [part_id] INT NOT NULL,
  [file_type_id] INT NOT NULL,
  [file_name] NVARCHAR(255) NOT NULL,
  [file_path] NVARCHAR(255) NOT NULL,
  [file_size] INT NULL,
  [file_extension] NVARCHAR(10) NULL,
  [uploaded_by] INT NOT NULL,
  [upload_date] DATETIME DEFAULT GETDATE(),
  [updated_at] DATETIME DEFAULT GETDATE(),
  CONSTRAINT [FK_files_parts] FOREIGN KEY ([part_id]) REFERENCES [parts]([part_id]) ON DELETE CASCADE,
  CONSTRAINT [FK_files_file_types] FOREIGN KEY ([file_type_id]) REFERENCES [file_types]([file_type_id]),
  CONSTRAINT [FK_files_users] FOREIGN KEY ([uploaded_by]) REFERENCES [users]([user_id])
);
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [file_access_logs] (ประวัติการเข้าถึงไฟล์)
-- --------------------------------------------------------
CREATE TABLE [file_access_logs] (
  [log_id] INT IDENTITY(1,1) PRIMARY KEY,
  [file_id] INT NOT NULL,
  [user_id] INT NOT NULL,
  [access_time] DATETIME DEFAULT GETDATE(),
  [access_type] NVARCHAR(10) NOT NULL CHECK ([access_type] IN ('view', 'download', 'print')),
  [ip_address] NVARCHAR(45) NULL,
  [user_agent] NVARCHAR(MAX) NULL,
  CONSTRAINT [FK_file_access_logs_files] FOREIGN KEY ([file_id]) REFERENCES [files]([file_id]) ON DELETE CASCADE,
  CONSTRAINT [FK_file_access_logs_users] FOREIGN KEY ([user_id]) REFERENCES [users]([user_id])
);
GO

-- --------------------------------------------------------
-- โครงสร้างตาราง [user_sessions] (ข้อมูลเซสชันการเข้าใช้งาน)
-- --------------------------------------------------------
CREATE TABLE [user_sessions] (
  [session_id] INT IDENTITY(1,1) PRIMARY KEY,
  [user_id] INT NOT NULL,
  [login_time] DATETIME DEFAULT GETDATE(),
  [logout_time] DATETIME NULL,
  [ip_address] NVARCHAR(45) NULL,
  [user_agent] NVARCHAR(MAX) NULL,
  [status] NVARCHAR(10) DEFAULT 'active' CHECK ([status] IN ('active', 'expired', 'logged_out')),
  CONSTRAINT [FK_user_sessions_users] FOREIGN KEY ([user_id]) REFERENCES [users]([user_id])
);
GO

-- --------------------------------------------------------
-- สร้าง Trigger สำหรับอัพเดทเวลา [updated_at] ในตาราง users
-- --------------------------------------------------------
CREATE TRIGGER [trg_users_update] ON [users]
AFTER UPDATE
AS
BEGIN
    UPDATE [users]
    SET [updated_at] = GETDATE()
    FROM [users] u
    INNER JOIN inserted i ON u.[user_id] = i.[user_id]
END;
GO

-- --------------------------------------------------------
-- สร้าง Trigger สำหรับอัพเดทเวลา [updated_at] ในตาราง companies
-- --------------------------------------------------------
CREATE TRIGGER [trg_companies_update] ON [companies]
AFTER UPDATE
AS
BEGIN
    UPDATE [companies]
    SET [updated_at] = GETDATE()
    FROM [companies] c
    INNER JOIN inserted i ON c.[company_id] = i.[company_id]
END;
GO

-- --------------------------------------------------------
-- สร้าง Trigger สำหรับอัพเดทเวลา [updated_at] ในตาราง parts
-- --------------------------------------------------------
CREATE TRIGGER [trg_parts_update] ON [parts]
AFTER UPDATE
AS
BEGIN
    UPDATE [parts]
    SET [updated_at] = GETDATE()
    FROM [parts] p
    INNER JOIN inserted i ON p.[part_id] = i.[part_id]
END;
GO

-- --------------------------------------------------------
-- สร้าง Trigger สำหรับอัพเดทเวลา [updated_at] ในตาราง files
-- --------------------------------------------------------
CREATE TRIGGER [trg_files_update] ON [files]
AFTER UPDATE
AS
BEGIN
    UPDATE [files]
    SET [updated_at] = GETDATE()
    FROM [files] f
    INNER JOIN inserted i ON f.[file_id] = i.[file_id]
END;
GO

-- --------------------------------------------------------
-- ข้อมูลเริ่มต้นสำหรับตาราง [file_types]
-- --------------------------------------------------------
INSERT INTO [file_types] ([type_name], [description], [icon]) VALUES
(N'Step bending', N'ไฟล์ Step bending', N'fa-file-pdf'),
(N'Punch V-Die', N'ไฟล์ Punch V-Die', N'fa-tools'),
(N'Drawing', N'ไฟล์ Drawing', N'fa-drafting-compass'),
(N'IQS', N'ไฟล์ IQS', N'fa-file-alt');
GO

-- --------------------------------------------------------
-- ข้อมูลเริ่มต้นสำหรับตาราง [users] (ผู้ดูแลระบบเริ่มต้น)
-- --------------------------------------------------------
INSERT INTO [users] ([username], [password], [first_name], [last_name], [email], [role]) VALUES
(N'admin', N'$2y$10$3Yx9SgpZCQCKFhJ0IyZ5m.xQT9KVmZ7nYJ9HgwYICJ6jkM8N.6udC', N'ผู้ดูแล', N'ระบบ', N'admin@infinitypart.com', N'admin');
-- รหัสผ่านเริ่มต้น: admin123
GO

-- --------------------------------------------------------
-- ข้อมูลเริ่มต้นสำหรับตาราง [companies] (บริษัทเริ่มต้น)
-- --------------------------------------------------------
INSERT INTO [companies] ([company_name], [company_name_th]) VALUES
(N'MITSUBISHI', N'มิตซูบิชิ'),
(N'DAIKIN', N'ไดกิ้น'),
(N'ELECTROLUX', N'อีเลคโทรลักซ์'),
(N'FISHER&PAYKAL', N'ฟิชเชอร์แอนด์พายเคิล'),
(N'FUJI', N'ฟูจิ'),
(N'FUJITSU', N'ฟูจิตสึ');
GO

-- --------------------------------------------------------
-- ข้อมูลเริ่มต้นสำหรับตาราง [parts] (ชิ้นส่วนเริ่มต้น)
-- --------------------------------------------------------
-- สมมติให้ user_id = 1 คือ admin
DECLARE @admin_id INT = 1;
DECLARE @mitsubishi_id INT = 1;

INSERT INTO [parts] ([company_id], [part_no], [notes], [created_by]) VALUES
(@mitsubishi_id, N'VR02DG54G03', N'ชิ้นส่วนตัวอย่าง MITSUBISHI', @admin_id),
(@mitsubishi_id, N'VR02DG54G04', N'ชิ้นส่วนตัวอย่าง MITSUBISHI', @admin_id);
GO