using HesabWin.Data;
using HesabWin.Sync;

namespace HesabWin.Forms;

public sealed class MainForm : Form
{
    private readonly SqlConnectionFactory _db;
    private readonly SyncService _sync;
    private readonly AppSettings _settings;

    private readonly Label _statusLabel;
    private readonly Label _statsLabel;
    private readonly DataGridView _grid;
    private readonly TextBox _codeBox;
    private readonly TextBox _nameBox;
    private readonly ComboBox _typeBox;
    private readonly TextBox _phoneBox;
    private readonly Button _saveBtn;
    private readonly Button _refreshBtn;
    private readonly Button _syncBtn;
    private readonly Button _testBtn;

    public MainForm(SqlConnectionFactory db, SyncService sync, AppSettings settings)
    {
        _db = db;
        _sync = sync;
        _settings = settings;

        Text = settings.AppName + " — نسخه ویندوز";
        RightToLeft = RightToLeft.Yes;
        RightToLeftLayout = true;
        Font = new Font("Segoe UI", 10f);
        MinimumSize = new Size(960, 640);
        Size = new Size(1100, 720);
        StartPosition = FormStartPosition.CenterScreen;
        BackColor = Color.FromArgb(245, 247, 250);

        var menu = new MenuStrip { RightToLeft = RightToLeft.Yes };
        var fileMenu = new ToolStripMenuItem("پرونده");
        var setupItem = new ToolStripMenuItem("تنظیمات اتصال / نصب دیتابیس…");
        setupItem.Click += (_, _) =>
        {
            using var wizard = new SetupWizardForm(_settings);
            if (wizard.ShowDialog(this) == DialogResult.OK && wizard.Ready)
            {
                MessageBox.Show(this,
                    "تنظیمات ذخیره شد. برنامه را یک‌بار ببندید و دوباره باز کنید.",
                    Text, MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        };
        var exitItem = new ToolStripMenuItem("خروج");
        exitItem.Click += (_, _) => Close();
        fileMenu.DropDownItems.Add(setupItem);
        fileMenu.DropDownItems.Add(new ToolStripSeparator());
        fileMenu.DropDownItems.Add(exitItem);
        var helpMenu = new ToolStripMenuItem("راهنما");
        var aboutItem = new ToolStripMenuItem("درباره…");
        aboutItem.Click += (_, _) =>
            MessageBox.Show(this,
                $"{_settings.AppName}\nنسخه ۱٫۰٫۰\nنرم‌افزار ویندوز آفلاین + همگام‌سازی با سایت\nHDD Land",
                "درباره", MessageBoxButtons.OK, MessageBoxIcon.Information);
        helpMenu.DropDownItems.Add(aboutItem);
        menu.Items.Add(fileMenu);
        menu.Items.Add(helpMenu);
        MainMenuStrip = menu;
        Controls.Add(menu);

        var header = new Panel
        {
            Dock = DockStyle.Top,
            Height = 64,
            BackColor = Color.FromArgb(22, 52, 78),
            Padding = new Padding(16, 12, 16, 12)
        };
        var title = new Label
        {
            Text = settings.AppName,
            ForeColor = Color.White,
            Font = new Font("Segoe UI Semibold", 16f),
            AutoSize = true,
            Location = new Point(16, 16)
        };
        header.Controls.Add(title);

        var toolbar = new FlowLayoutPanel
        {
            Dock = DockStyle.Top,
            Height = 52,
            Padding = new Padding(12, 8, 12, 8),
            FlowDirection = FlowDirection.RightToLeft,
            WrapContents = false,
            BackColor = Color.FromArgb(232, 238, 244)
        };

        _testBtn = MakeButton("تست اتصال SQL", Color.FromArgb(55, 90, 120));
        _refreshBtn = MakeButton("بازخوانی", Color.FromArgb(55, 90, 120));
        _syncBtn = MakeButton("همگام‌سازی با سایت", Color.FromArgb(20, 120, 90));
        toolbar.Controls.AddRange([_testBtn, _refreshBtn, _syncBtn]);

        var formPanel = new Panel
        {
            Dock = DockStyle.Top,
            Height = 100,
            Padding = new Padding(16),
            BackColor = Color.White
        };

        _codeBox = MakeTextBox();
        _nameBox = MakeTextBox();
        _phoneBox = MakeTextBox();
        _typeBox = new ComboBox
        {
            DropDownStyle = ComboBoxStyle.DropDownList,
            Width = 140,
            Margin = new Padding(4)
        };
        _typeBox.Items.AddRange(["customer", "supplier", "both", "other"]);
        _typeBox.SelectedIndex = 0;

        _saveBtn = MakeButton("ذخیره طرف‌حساب", Color.FromArgb(180, 90, 40));

        var formFlow = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.RightToLeft,
            WrapContents = true,
            Padding = new Padding(0)
        };
        formFlow.Controls.Add(Labeled("کد", _codeBox));
        formFlow.Controls.Add(Labeled("نام", _nameBox));
        formFlow.Controls.Add(Labeled("نوع", _typeBox));
        formFlow.Controls.Add(Labeled("تلفن", _phoneBox));
        formFlow.Controls.Add(_saveBtn);
        formPanel.Controls.Add(formFlow);

        _grid = new DataGridView
        {
            Dock = DockStyle.Fill,
            ReadOnly = true,
            AllowUserToAddRows = false,
            AllowUserToDeleteRows = false,
            AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill,
            BackgroundColor = Color.White,
            BorderStyle = BorderStyle.None,
            RowHeadersVisible = false,
            SelectionMode = DataGridViewSelectionMode.FullRowSelect
        };

        _statsLabel = new Label
        {
            Dock = DockStyle.Bottom,
            Height = 28,
            TextAlign = ContentAlignment.MiddleRight,
            Padding = new Padding(12, 0, 12, 0),
            BackColor = Color.FromArgb(232, 238, 244),
            Text = "آماده"
        };

        _statusLabel = new Label
        {
            Dock = DockStyle.Bottom,
            Height = 56,
            TextAlign = ContentAlignment.TopRight,
            Padding = new Padding(12, 6, 12, 6),
            BackColor = Color.FromArgb(250, 250, 252),
            Text = "نرم‌افزار آماده است. طرف‌حساب را محلی ذخیره کنید؛ با اینترنت، همگام‌سازی با سایت را بزنید."
        };

        Controls.Add(_grid);
        Controls.Add(formPanel);
        Controls.Add(toolbar);
        Controls.Add(header);
        Controls.Add(_statusLabel);
        Controls.Add(_statsLabel);

        _testBtn.Click += async (_, _) => await TestDbAsync();
        _refreshBtn.Click += async (_, _) => await RefreshAsync();
        _saveBtn.Click += async (_, _) => await SavePartyAsync();
        _syncBtn.Click += async (_, _) => await RunSyncAsync();

        Shown += async (_, _) =>
        {
            await TestDbAsync();
            await RefreshAsync();
        };
    }

    private static Button MakeButton(string text, Color back)
    {
        return new Button
        {
            Text = text,
            AutoSize = true,
            Padding = new Padding(12, 6, 12, 6),
            Margin = new Padding(6, 4, 6, 4),
            FlatStyle = FlatStyle.Flat,
            ForeColor = Color.White,
            BackColor = back,
            Cursor = Cursors.Hand
        };
    }

    private static TextBox MakeTextBox() =>
        new() { Width = 160, Margin = new Padding(4) };

    private static Control Labeled(string label, Control control)
    {
        var p = new FlowLayoutPanel
        {
            AutoSize = true,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            Margin = new Padding(8, 0, 8, 0)
        };
        p.Controls.Add(new Label { Text = label, AutoSize = true });
        p.Controls.Add(control);
        return p;
    }

    private async Task TestDbAsync()
    {
        SetBusy(true);
        var (ok, msg) = await _db.TestAsync();
        _statusLabel.Text = ok ? "✓ " + msg : "✗ اتصال ناموفق: " + msg;
        _statusLabel.ForeColor = ok ? Color.FromArgb(20, 100, 60) : Color.FromArgb(160, 40, 40);
        SetBusy(false);
    }

    private async Task RefreshAsync()
    {
        SetBusy(true);
        try
        {
            var parties = await _db.ListPartiesAsync();
            _grid.DataSource = parties.Select(p => new
            {
                کد = p.Code,
                نام = p.Name,
                نوع = p.Type,
                تلفن = p.Phone ?? "",
                به‌روزرسانی = p.UpdatedAt.ToLocalTime().ToString("yyyy/MM/dd HH:mm"),
                نسخه_همگام = p.SyncVersion
            }).ToList();

            var partyCount = await _db.CountAsync("parties");
            var moeinCount = await _db.CountAsync("accounts_moein");
            var voucherCount = await _db.CountAsync("vouchers");
            var online = await _sync.IsOnlineAsync();
            _statsLabel.Text =
                $"طرف‌حساب: {partyCount} | معین: {moeinCount} | سند: {voucherCount} | شبکه: {(online ? "آنلاین" : "آفلاین")}";
        }
        catch (Exception ex)
        {
            _statusLabel.Text = "خطا در خواندن داده: " + ex.Message;
            _statusLabel.ForeColor = Color.FromArgb(160, 40, 40);
        }
        SetBusy(false);
    }

    private async Task SavePartyAsync()
    {
        var code = _codeBox.Text.Trim();
        var name = _nameBox.Text.Trim();
        if (string.IsNullOrWhiteSpace(code) || string.IsNullOrWhiteSpace(name))
        {
            MessageBox.Show("کد و نام الزامی است.", Text, MessageBoxButtons.OK, MessageBoxIcon.Warning);
            return;
        }

        SetBusy(true);
        try
        {
            await _db.SavePartyAsync(code, name, _typeBox.SelectedItem?.ToString() ?? "customer",
                string.IsNullOrWhiteSpace(_phoneBox.Text) ? null : _phoneBox.Text.Trim());
            _codeBox.Clear();
            _nameBox.Clear();
            _phoneBox.Clear();
            _statusLabel.Text = "طرف‌حساب ذخیره شد (محلی — برای ارسال به سایت همگام‌سازی کنید).";
            _statusLabel.ForeColor = Color.FromArgb(20, 100, 60);
            await RefreshAsync();
        }
        catch (Exception ex)
        {
            MessageBox.Show(ex.Message, "خطا", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
        SetBusy(false);
    }

    private async Task RunSyncAsync()
    {
        SetBusy(true);
        _statusLabel.Text = "در حال همگام‌سازی…";
        _statusLabel.ForeColor = Color.FromArgb(40, 70, 110);
        try
        {
            var result = await _sync.SyncAllAsync();
            _statusLabel.Text = result.Message;
            _statusLabel.ForeColor = result.Success
                ? Color.FromArgb(20, 100, 60)
                : Color.FromArgb(140, 90, 20);
            await RefreshAsync();
        }
        catch (Exception ex)
        {
            _statusLabel.Text = "خطای همگام‌سازی: " + ex.Message;
            _statusLabel.ForeColor = Color.FromArgb(160, 40, 40);
        }
        SetBusy(false);
    }

    private void SetBusy(bool busy)
    {
        UseWaitCursor = busy;
        _testBtn.Enabled = !busy;
        _refreshBtn.Enabled = !busy;
        _syncBtn.Enabled = !busy;
        _saveBtn.Enabled = !busy;
    }
}
