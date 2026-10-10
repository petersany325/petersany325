/*
  Hesab — SQL Server local database (offline Windows client)
  Run in SSMS against localhost (Windows Auth or sa).

  Steps:
    1) Connect to (local) / localhost in SSMS
    2) File → Open → this script  OR  New Query → paste
    3) Execute (F5)
    4) Refresh Object Explorer → Databases → Hesab
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

IF DB_ID(N'Hesab') IS NULL
BEGIN
    CREATE DATABASE Hesab COLLATE Persian_100_CI_AS;
END
GO

USE Hesab;
GO

/* ---------- Sync helpers (all business tables carry these) ----------
   sync_id      : stable GUID across local ↔ server
   updated_at   : last local/server change (UTC)
   is_deleted   : soft delete for sync tombstones
   sync_version : increments on each change; conflict = higher wins (LWW)
----------------------------------------------------------------- */

IF OBJECT_ID(N'dbo.users', N'U') IS NULL
CREATE TABLE dbo.users (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_users_sync DEFAULT NEWID(),
    name            NVARCHAR(120) NOT NULL,
    email           NVARCHAR(190) NOT NULL,
    password_hash   NVARCHAR(255) NOT NULL,
    role            NVARCHAR(40) NOT NULL CONSTRAINT DF_users_role DEFAULT N'accountant',
    created_at      DATETIME2(0) NOT NULL CONSTRAINT DF_users_created DEFAULT SYSUTCDATETIME(),
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_users_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_users_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_users_ver DEFAULT 1,
    CONSTRAINT UQ_users_email UNIQUE (email),
    CONSTRAINT UQ_users_sync UNIQUE (sync_id),
    CONSTRAINT CK_users_role CHECK (role IN (N'admin', N'accountant', N'viewer'))
);
GO

IF OBJECT_ID(N'dbo.account_groups', N'U') IS NULL
CREATE TABLE dbo.account_groups (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_ag_sync DEFAULT NEWID(),
    code            NVARCHAR(10) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    nature          NVARCHAR(10) NOT NULL CONSTRAINT DF_ag_nature DEFAULT N'debit',
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_ag_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_ag_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_ag_ver DEFAULT 1,
    CONSTRAINT UQ_ag_code UNIQUE (code),
    CONSTRAINT UQ_ag_sync UNIQUE (sync_id),
    CONSTRAINT CK_ag_nature CHECK (nature IN (N'debit', N'credit'))
);
GO

IF OBJECT_ID(N'dbo.accounts_kol', N'U') IS NULL
CREATE TABLE dbo.accounts_kol (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_kol_sync DEFAULT NEWID(),
    code            NVARCHAR(20) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    group_id        INT NOT NULL,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_kol_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_kol_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_kol_ver DEFAULT 1,
    CONSTRAINT UQ_kol_code UNIQUE (code),
    CONSTRAINT UQ_kol_sync UNIQUE (sync_id),
    CONSTRAINT FK_kol_group FOREIGN KEY (group_id) REFERENCES dbo.account_groups(id)
);
GO

IF OBJECT_ID(N'dbo.accounts_moein', N'U') IS NULL
CREATE TABLE dbo.accounts_moein (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_moein_sync DEFAULT NEWID(),
    code            NVARCHAR(20) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    kol_id          INT NOT NULL,
    nature          NVARCHAR(10) NOT NULL CONSTRAINT DF_moein_nature DEFAULT N'neutral',
    allow_debit     BIT NOT NULL CONSTRAINT DF_moein_ad DEFAULT 1,
    allow_credit    BIT NOT NULL CONSTRAINT DF_moein_ac DEFAULT 1,
    is_active       BIT NOT NULL CONSTRAINT DF_moein_active DEFAULT 1,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_moein_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_moein_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_moein_ver DEFAULT 1,
    CONSTRAINT UQ_moein_code UNIQUE (code),
    CONSTRAINT UQ_moein_sync UNIQUE (sync_id),
    CONSTRAINT FK_moein_kol FOREIGN KEY (kol_id) REFERENCES dbo.accounts_kol(id),
    CONSTRAINT CK_moein_nature CHECK (nature IN (N'debit', N'credit', N'neutral'))
);
GO

IF OBJECT_ID(N'dbo.parties', N'U') IS NULL
CREATE TABLE dbo.parties (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_parties_sync DEFAULT NEWID(),
    code            NVARCHAR(40) NOT NULL,
    name            NVARCHAR(190) NOT NULL,
    type            NVARCHAR(20) NOT NULL CONSTRAINT DF_parties_type DEFAULT N'customer',
    phone           NVARCHAR(40) NULL,
    created_at      DATETIME2(0) NOT NULL CONSTRAINT DF_parties_created DEFAULT SYSUTCDATETIME(),
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_parties_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_parties_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_parties_ver DEFAULT 1,
    CONSTRAINT UQ_parties_code UNIQUE (code),
    CONSTRAINT UQ_parties_sync UNIQUE (sync_id),
    CONSTRAINT CK_parties_type CHECK (type IN (N'customer', N'supplier', N'both', N'other'))
);
GO

IF OBJECT_ID(N'dbo.fiscal_years', N'U') IS NULL
CREATE TABLE dbo.fiscal_years (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_fy_sync DEFAULT NEWID(),
    title           NVARCHAR(120) NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    is_active       BIT NOT NULL CONSTRAINT DF_fy_active DEFAULT 0,
    is_closed       BIT NOT NULL CONSTRAINT DF_fy_closed DEFAULT 0,
    closed_at       DATETIME2(0) NULL,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_fy_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_fy_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_fy_ver DEFAULT 1,
    CONSTRAINT UQ_fy_sync UNIQUE (sync_id)
);
GO

IF OBJECT_ID(N'dbo.voucher_types', N'U') IS NULL
CREATE TABLE dbo.voucher_types (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_vt_sync DEFAULT NEWID(),
    code            NVARCHAR(40) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    description_template NVARCHAR(500) NULL,
    is_active       BIT NOT NULL CONSTRAINT DF_vt_active DEFAULT 1,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_vt_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_vt_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_vt_ver DEFAULT 1,
    CONSTRAINT UQ_vt_code UNIQUE (code),
    CONSTRAINT UQ_vt_sync UNIQUE (sync_id)
);
GO

IF OBJECT_ID(N'dbo.vouchers', N'U') IS NULL
CREATE TABLE dbo.vouchers (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_v_sync DEFAULT NEWID(),
    fiscal_year_id  INT NOT NULL,
    voucher_type_id INT NULL,
    number          INT NOT NULL,
    voucher_date    DATE NOT NULL,
    description     NVARCHAR(500) NULL,
    status          NVARCHAR(20) NOT NULL CONSTRAINT DF_v_status DEFAULT N'draft',
    created_by      INT NULL,
    posted_at       DATETIME2(0) NULL,
    source_module   NVARCHAR(80) NULL,
    source_id       INT NULL,
    created_at      DATETIME2(0) NOT NULL CONSTRAINT DF_v_created DEFAULT SYSUTCDATETIME(),
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_v_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_v_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_v_ver DEFAULT 1,
    CONSTRAINT UQ_v_year_num UNIQUE (fiscal_year_id, number),
    CONSTRAINT UQ_v_sync UNIQUE (sync_id),
    CONSTRAINT FK_v_year FOREIGN KEY (fiscal_year_id) REFERENCES dbo.fiscal_years(id),
    CONSTRAINT FK_v_type FOREIGN KEY (voucher_type_id) REFERENCES dbo.voucher_types(id),
    CONSTRAINT FK_v_user FOREIGN KEY (created_by) REFERENCES dbo.users(id),
    CONSTRAINT CK_v_status CHECK (status IN (N'draft', N'operational', N'reviewed', N'locked', N'void', N'posted'))
);
GO

IF OBJECT_ID(N'dbo.voucher_lines', N'U') IS NULL
CREATE TABLE dbo.voucher_lines (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_vl_sync DEFAULT NEWID(),
    voucher_id      INT NOT NULL,
    line_no         SMALLINT NOT NULL,
    moein_id        INT NOT NULL,
    party_id        INT NULL,
    description     NVARCHAR(500) NULL,
    debit           DECIMAL(18,0) NOT NULL CONSTRAINT DF_vl_debit DEFAULT 0,
    credit          DECIMAL(18,0) NOT NULL CONSTRAINT DF_vl_credit DEFAULT 0,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_vl_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_vl_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_vl_ver DEFAULT 1,
    CONSTRAINT UQ_vl_sync UNIQUE (sync_id),
    CONSTRAINT FK_vl_v FOREIGN KEY (voucher_id) REFERENCES dbo.vouchers(id) ON DELETE CASCADE,
    CONSTRAINT FK_vl_m FOREIGN KEY (moein_id) REFERENCES dbo.accounts_moein(id),
    CONSTRAINT FK_vl_p FOREIGN KEY (party_id) REFERENCES dbo.parties(id)
);
GO

IF OBJECT_ID(N'dbo.invoices', N'U') IS NULL
CREATE TABLE dbo.invoices (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_inv_sync DEFAULT NEWID(),
    number          INT NOT NULL,
    invoice_date    DATE NOT NULL,
    party_id        INT NOT NULL,
    total           DECIMAL(18,0) NOT NULL CONSTRAINT DF_inv_total DEFAULT 0,
    description     NVARCHAR(500) NULL,
    voucher_id      INT NULL,
    status          NVARCHAR(20) NOT NULL CONSTRAINT DF_inv_status DEFAULT N'draft',
    created_at      DATETIME2(0) NOT NULL CONSTRAINT DF_inv_created DEFAULT SYSUTCDATETIME(),
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_inv_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_inv_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_inv_ver DEFAULT 1,
    CONSTRAINT UQ_inv_number UNIQUE (number),
    CONSTRAINT UQ_inv_sync UNIQUE (sync_id),
    CONSTRAINT FK_inv_party FOREIGN KEY (party_id) REFERENCES dbo.parties(id),
    CONSTRAINT FK_inv_v FOREIGN KEY (voucher_id) REFERENCES dbo.vouchers(id),
    CONSTRAINT CK_inv_status CHECK (status IN (N'draft', N'confirmed'))
);
GO

IF OBJECT_ID(N'dbo.invoice_items', N'U') IS NULL
CREATE TABLE dbo.invoice_items (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_ii_sync DEFAULT NEWID(),
    invoice_id      INT NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    qty             DECIMAL(18,3) NOT NULL CONSTRAINT DF_ii_qty DEFAULT 1,
    unit_price      DECIMAL(18,0) NOT NULL CONSTRAINT DF_ii_price DEFAULT 0,
    amount          DECIMAL(18,0) NOT NULL CONSTRAINT DF_ii_amt DEFAULT 0,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_ii_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_ii_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_ii_ver DEFAULT 1,
    CONSTRAINT UQ_ii_sync UNIQUE (sync_id),
    CONSTRAINT FK_ii_inv FOREIGN KEY (invoice_id) REFERENCES dbo.invoices(id) ON DELETE CASCADE
);
GO

IF OBJECT_ID(N'dbo.posting_rules', N'U') IS NULL
CREATE TABLE dbo.posting_rules (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_pr_sync DEFAULT NEWID(),
    code            NVARCHAR(60) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    moein_code      NVARCHAR(40) NULL,
    is_active       BIT NOT NULL CONSTRAINT DF_pr_active DEFAULT 1,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_pr_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_pr_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_pr_ver DEFAULT 1,
    CONSTRAINT UQ_pr_code UNIQUE (code),
    CONSTRAINT UQ_pr_sync UNIQUE (sync_id)
);
GO

IF OBJECT_ID(N'dbo.tafsili_types', N'U') IS NULL
CREATE TABLE dbo.tafsili_types (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_tt_sync DEFAULT NEWID(),
    code            NVARCHAR(40) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    is_active       BIT NOT NULL CONSTRAINT DF_tt_active DEFAULT 1,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_tt_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_tt_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_tt_ver DEFAULT 1,
    CONSTRAINT UQ_tt_code UNIQUE (code),
    CONSTRAINT UQ_tt_sync UNIQUE (sync_id)
);
GO

IF OBJECT_ID(N'dbo.tafsili_items', N'U') IS NULL
CREATE TABLE dbo.tafsili_items (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_ti_sync DEFAULT NEWID(),
    type_id         INT NOT NULL,
    code            NVARCHAR(60) NOT NULL,
    title           NVARCHAR(190) NOT NULL,
    is_active       BIT NOT NULL CONSTRAINT DF_ti_active DEFAULT 1,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_ti_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_ti_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_ti_ver DEFAULT 1,
    CONSTRAINT UQ_ti_type_code UNIQUE (type_id, code),
    CONSTRAINT UQ_ti_sync UNIQUE (sync_id),
    CONSTRAINT FK_ti_type FOREIGN KEY (type_id) REFERENCES dbo.tafsili_types(id)
);
GO

IF OBJECT_ID(N'dbo.settings', N'U') IS NULL
CREATE TABLE dbo.settings (
    [key]           NVARCHAR(80) NOT NULL PRIMARY KEY,
    [value]         NVARCHAR(MAX) NULL,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_set_updated DEFAULT SYSUTCDATETIME(),
    sync_version    BIGINT NOT NULL CONSTRAINT DF_set_ver DEFAULT 1
);
GO

IF OBJECT_ID(N'dbo.bank_accounts', N'U') IS NULL
CREATE TABLE dbo.bank_accounts (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    sync_id         UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_ba_sync DEFAULT NEWID(),
    title           NVARCHAR(190) NOT NULL,
    bank_name       NVARCHAR(120) NULL,
    account_no      NVARCHAR(80) NULL,
    moein_id        INT NULL,
    is_active       BIT NOT NULL CONSTRAINT DF_ba_active DEFAULT 1,
    updated_at      DATETIME2(0) NOT NULL CONSTRAINT DF_ba_updated DEFAULT SYSUTCDATETIME(),
    is_deleted      BIT NOT NULL CONSTRAINT DF_ba_del DEFAULT 0,
    sync_version    BIGINT NOT NULL CONSTRAINT DF_ba_ver DEFAULT 1,
    CONSTRAINT UQ_ba_sync UNIQUE (sync_id),
    CONSTRAINT FK_ba_moein FOREIGN KEY (moein_id) REFERENCES dbo.accounts_moein(id)
);
GO

IF OBJECT_ID(N'dbo.sync_state', N'U') IS NULL
CREATE TABLE dbo.sync_state (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    device_id       UNIQUEIDENTIFIER NOT NULL,
    entity          NVARCHAR(80) NOT NULL,
    last_pulled_at  DATETIME2(0) NULL,
    last_pushed_at  DATETIME2(0) NULL,
    last_cursor     NVARCHAR(100) NULL,
    CONSTRAINT UQ_sync_state UNIQUE (device_id, entity)
);
GO

IF OBJECT_ID(N'dbo.sync_log', N'U') IS NULL
CREATE TABLE dbo.sync_log (
    id              BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    direction       NVARCHAR(10) NOT NULL, -- push | pull
    entity          NVARCHAR(80) NOT NULL,
    sync_id         UNIQUEIDENTIFIER NULL,
    status          NVARCHAR(20) NOT NULL, -- ok | conflict | error
    detail          NVARCHAR(MAX) NULL,
    created_at      DATETIME2(0) NOT NULL CONSTRAINT DF_slog_created DEFAULT SYSUTCDATETIME(),
    CONSTRAINT CK_slog_dir CHECK (direction IN (N'push', N'pull')),
    CONSTRAINT CK_slog_status CHECK (status IN (N'ok', N'conflict', N'error'))
);
GO

/* ---------- Seed: admin user (password: admin123 — change after first login) ---------- */
IF NOT EXISTS (SELECT 1 FROM dbo.users WHERE email = N'admin@hesab.local')
INSERT INTO dbo.users (name, email, password_hash, role)
VALUES (N'مدیر سیستم', N'admin@hesab.local',
        N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt placeholder; client will rehash
        N'admin');
GO

/* ---------- Seed: chart of accounts (minimal Iranian structure) ---------- */
IF NOT EXISTS (SELECT 1 FROM dbo.account_groups)
BEGIN
    INSERT INTO dbo.account_groups (code, title, nature) VALUES
    (N'1', N'دارایی‌ها', N'debit'),
    (N'2', N'بدهی‌ها', N'credit'),
    (N'3', N'حقوق صاحبان سهام', N'credit'),
    (N'4', N'درآمدها', N'credit'),
    (N'5', N'بهای تمام‌شده', N'debit'),
    (N'6', N'هزینه‌ها', N'debit');

    DECLARE @g1 INT = (SELECT id FROM dbo.account_groups WHERE code = N'1');
    DECLARE @g2 INT = (SELECT id FROM dbo.account_groups WHERE code = N'2');
    DECLARE @g3 INT = (SELECT id FROM dbo.account_groups WHERE code = N'3');
    DECLARE @g4 INT = (SELECT id FROM dbo.account_groups WHERE code = N'4');
    DECLARE @g5 INT = (SELECT id FROM dbo.account_groups WHERE code = N'5');
    DECLARE @g6 INT = (SELECT id FROM dbo.account_groups WHERE code = N'6');

    INSERT INTO dbo.accounts_kol (code, title, group_id) VALUES
    (N'11', N'موجودی نقد و بانک', @g1),
    (N'13', N'حساب‌ها و اسناد دریافتنی', @g1),
    (N'21', N'حساب‌ها و اسناد پرداختنی', @g2),
    (N'31', N'بدهی‌های جاری سایر', @g2),
    (N'41', N'سرمایه', @g3),
    (N'61', N'فروش', @g4),
    (N'51', N'بهای کالای فروش‌رفته', @g5),
    (N'71', N'هزینه‌های عملیاتی', @g6);

    DECLARE @k11 INT = (SELECT id FROM dbo.accounts_kol WHERE code = N'11');
    DECLARE @k13 INT = (SELECT id FROM dbo.accounts_kol WHERE code = N'13');
    DECLARE @k21 INT = (SELECT id FROM dbo.accounts_kol WHERE code = N'21');
    DECLARE @k31 INT = (SELECT id FROM dbo.accounts_kol WHERE code = N'31');
    DECLARE @k61 INT = (SELECT id FROM dbo.accounts_kol WHERE code = N'61');
    DECLARE @k51 INT = (SELECT id FROM dbo.accounts_kol WHERE code = N'51');

    INSERT INTO dbo.accounts_moein (code, title, kol_id, nature) VALUES
    (N'1101', N'صندوق', @k11, N'debit'),
    (N'1102', N'بانک', @k11, N'debit'),
    (N'1302', N'حساب‌های دریافتنی مشتریان', @k13, N'debit'),
    (N'1303', N'اسناد در جریان وصول', @k13, N'debit'),
    (N'1304', N'اسناد / چک نزد صندوق', @k13, N'debit'),
    (N'2101', N'حساب‌های پرداختنی تأمین‌کنندگان', @k21, N'credit'),
    (N'3109', N'چک‌های صادره تحویل‌شده', @k31, N'credit'),
    (N'6101', N'فروش کالا', @k61, N'credit'),
    (N'5101', N'بهای کالای فروش‌رفته', @k51, N'debit');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.voucher_types)
INSERT INTO dbo.voucher_types (code, title, description_template) VALUES
(N'GENERAL', N'سند عمومی', N'سند حسابداری'),
(N'OPENING', N'افتتاحیه', N'سند افتتاحیه دوره'),
(N'CLOSING', N'اختتامیه', N'سند اختتامیه دوره'),
(N'BANK', N'بانکی', N'سند عملیات بانکی'),
(N'AUTO', N'اتوماتیک', N'صادر شده از سایر محیط‌ها'),
(N'CHK', N'اسناد چک', N'چک');
GO

IF NOT EXISTS (SELECT 1 FROM dbo.tafsili_types)
INSERT INTO dbo.tafsili_types (code, title) VALUES
(N'01', N'مشتریان'),
(N'02', N'تأمین‌کنندگان'),
(N'03', N'بانک‌ها'),
(N'PERSON', N'اشخاص (سازگاری)'),
(N'BANK', N'بانک/صندوق (سازگاری)'),
(N'OTHER', N'سایر تفصیلی');
GO

IF NOT EXISTS (SELECT 1 FROM dbo.posting_rules)
INSERT INTO dbo.posting_rules (code, title, moein_code) VALUES
(N'SALE', N'فروش کالا', N'6101'),
(N'AR_CUSTOMER', N'دریافتنی مشتری', N'1302'),
(N'AP_SUPPLIER', N'پرداختنی تأمین‌کننده', N'2101'),
(N'CHECK_RECV', N'چک دریافتی نزد صندوق', N'1304'),
(N'CHECK_PAY', N'چک پرداختنی صادره', N'3109'),
(N'CHECK_IN_COLLECTION', N'چک در جریان وصول', N'1303');
GO

IF NOT EXISTS (SELECT 1 FROM dbo.settings)
INSERT INTO dbo.settings ([key], [value]) VALUES
(N'coding_pattern', N'1/2/4/6'),
(N'inventory_method', N'perpetual'),
(N'auto_post_subsystems', N'1'),
(N'device_id', CONVERT(NVARCHAR(36), NEWID())),
(N'server_sync_url', N'https://hdd-land.ir/hesab/api/sync'),
(N'app_build', N'win-1');
GO

IF NOT EXISTS (SELECT 1 FROM dbo.fiscal_years)
INSERT INTO dbo.fiscal_years (title, start_date, end_date, is_active)
VALUES (N'سال مالی ۱۴۰۴', '2025-03-21', '2026-03-20', 1);
GO

PRINT N'Hesab database ready.';
PRINT N'Tables: users, COA, parties, vouchers, invoices, sync_state, sync_log.';
PRINT N'Next: open hesab/winclient and run the Windows client against this DB.';
GO
