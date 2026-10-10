using System.Globalization;
using HesabWin.Data;
using HesabWin.Services;
using HesabWin.Sync;

namespace HesabWin.Forms;

public sealed class MainForm : Form
{
    private readonly SqlConnectionFactory _db;
    private readonly HesabRepository _repo;
    private readonly SyncService _sync;
    private readonly LicenseService _license;
    private readonly UpdateService _updates;
    private readonly AppSettings _settings;

    private readonly Panel _content;
    private readonly Label _status;
    private readonly Label _licenseLabel;
    private readonly ToolStripStatusLabel _connLabel;

    public MainForm(SqlConnectionFactory db, SyncService sync, AppSettings settings)
    {
        _db = db;
        _repo = new HesabRepository(db);
        _sync = sync;
        _license = new LicenseService(db);
        _updates = new UpdateService();
        _settings = settings;

        Text = $"{settings.AppName} — نسخه ویندوز {UpdateService.CurrentVersion.ToString(3)}";
        RightToLeft = RightToLeft.Yes;
        RightToLeftLayout = true;
        Font = new Font("Segoe UI", 10f);
        MinimumSize = new Size(1100, 700);
        Size = new Size(1280, 800);
        StartPosition = FormStartPosition.CenterScreen;
        BackColor = Color.FromArgb(236, 240, 244);
        try { Icon = new Icon(Path.Combine(AppContext.BaseDirectory, "Assets", "app.ico")); } catch { /* optional */ }

        var header = new Panel
        {
            Dock = DockStyle.Top,
            Height = 52,
            BackColor = Color.FromArgb(22, 52, 78)
        };
        var brand = new Label
        {
            Text = settings.AppName,
            ForeColor = Color.White,
            Font = new Font("Segoe UI Semibold", 16f),
            AutoSize = true,
            Location = new Point(16, 12)
        };
        _licenseLabel = new Label
        {
            ForeColor = Color.FromArgb(180, 220, 255),
            AutoSize = true,
            Location = new Point(200, 18)
        };
        header.Controls.Add(brand);
        header.Controls.Add(_licenseLabel);

        BuildMenus();

        _content = new Panel { Dock = DockStyle.Fill, Padding = new Padding(8) };

        var statusStrip = new StatusStrip { RightToLeft = RightToLeft.Yes };
        _connLabel = new ToolStripStatusLabel("…") { Spring = true };
        statusStrip.Items.Add(_connLabel);

        _status = new Label
        {
            Dock = DockStyle.Bottom,
            Height = 28,
            TextAlign = ContentAlignment.MiddleRight,
            Padding = new Padding(10, 0, 10, 0),
            BackColor = Color.FromArgb(225, 232, 240),
            Text = "آماده"
        };

        Controls.Add(_content);
        Controls.Add(_status);
        Controls.Add(statusStrip);
        Controls.Add(header);

        Shown += async (_, _) =>
        {
            await RefreshLicenseBadgeAsync();
            await ShowDashboardAsync();
            await TestConnAsync();
            _ = CheckUpdateQuietAsync();
        };
    }

    private void BuildMenus()
    {
        var menu = new MenuStrip { RightToLeft = RightToLeft.Yes, Font = new Font("Segoe UI", 10f) };

        menu.Items.Add(Menu("پرونده",
            Item("پنجره اصلی", async () => await ShowDashboardAsync()),
            Item("بررسی به‌روزرسانی…", async () => await CheckUpdateAsync(true)),
            Item("همگام‌سازی با سایت", async () => await RunSyncAsync()),
            Sep(),
            Item("تنظیمات اتصال / نصب دیتابیس…", () => { OpenSetup(); return Task.CompletedTask; }),
            Sep(),
            Item("خروج", () => { Close(); return Task.CompletedTask; })));

        menu.Items.Add(Menu("اطلاعات پایه",
            Item("کدینگ حساب‌ها", async () => await ShowAccountsAsync()),
            Item("طرف‌حساب‌ها", async () => await ShowPartiesAsync()),
            Item("سال مالی (نمایش)", async () => await ShowFiscalAsync())));

        menu.Items.Add(Menu("حسابداری",
            Item("ثبت سند حسابداری", async () => await ShowVoucherCreateAsync()),
            Item("فهرست اسناد", async () => await ShowVouchersAsync())));

        menu.Items.Add(Menu("فروش",
            Item("فاکتور فروش", async () => await ShowInvoicesAsync()),
            Item("طرف‌حساب‌ها", async () => await ShowPartiesAsync())));

        menu.Items.Add(Menu("خزانه‌داری",
            Item("حساب‌های بانکی / دریافت-پرداخت (به‌زودی)", () =>
            {
                MessageBox.Show(this, "ماژول خزانه در نسخه بعدی تکمیل می‌شود.\nجداول SQL آماده است.", Text);
                return Task.CompletedTask;
            })));

        menu.Items.Add(Menu("گزارش‌ها",
            Item("تراز آزمایشی", async () => await ShowTrialBalanceAsync()),
            Item("مرکز گزارش‌ها", async () => await ShowReportsHubAsync())));

        menu.Items.Add(Menu("سیستم",
            Item("لایسنس و فعال‌سازی", async () => await ShowLicenseAsync()),
            Item("تنظیمات", async () => await ShowSettingsAsync()),
            Item("درباره", () =>
            {
                MessageBox.Show(this,
                    $"{_settings.AppName}\nنسخه {UpdateService.CurrentVersion}\nسورس کامل: hesab/winclient در ریپو\nHDD Land",
                    "درباره");
                return Task.CompletedTask;
            })));

        MainMenuStrip = menu;
        Controls.Add(menu);
    }

    private static ToolStripMenuItem Menu(string text, params ToolStripItem[] items)
    {
        var m = new ToolStripMenuItem(text);
        m.DropDownItems.AddRange(items);
        return m;
    }

    private static ToolStripItem Sep() => new ToolStripSeparator();

    private ToolStripMenuItem Item(string text, Func<Task> action)
    {
        var i = new ToolStripMenuItem(text);
        i.Click += async (_, _) =>
        {
            try { await action(); }
            catch (Exception ex) { MessageBox.Show(this, ex.Message, "خطا", MessageBoxButtons.OK, MessageBoxIcon.Error); }
        };
        return i;
    }

    private void SetContent(Control panel, string title)
    {
        _content.SuspendLayout();
        _content.Controls.Clear();
        panel.Dock = DockStyle.Fill;
        _content.Controls.Add(panel);
        _content.ResumeLayout();
        _status.Text = title;
    }

    private Panel Page(string title, params Control[] body)
    {
        var p = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(16) };
        var h = new Label
        {
            Text = title,
            Font = new Font("Segoe UI Semibold", 14f),
            AutoSize = true,
            Dock = DockStyle.Top,
            Padding = new Padding(0, 0, 0, 12)
        };
        var host = new Panel { Dock = DockStyle.Fill };
        foreach (var c in body)
            host.Controls.Add(c);
        // add in reverse for dock fill first
        p.Controls.Add(host);
        p.Controls.Add(h);
        return p;
    }

    private async Task ShowDashboardAsync()
    {
        var stats = await _repo.GetDashboardAsync();
        var (okLic, licLabel) = await _license.StatusAsync();
        var online = await _sync.IsOnlineAsync();

        var grid = new TableLayoutPanel
        {
            Dock = DockStyle.Top,
            Height = 160,
            ColumnCount = 4,
            RowCount = 1
        };
        for (var i = 0; i < 4; i++) grid.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 25));
        grid.Controls.Add(StatCard("طرف‌حساب", stats.Parties.ToString("N0")), 0, 0);
        grid.Controls.Add(StatCard("معین", stats.Moein.ToString("N0")), 1, 0);
        grid.Controls.Add(StatCard("اسناد", stats.Vouchers.ToString("N0")), 2, 0);
        grid.Controls.Add(StatCard("فاکتور", stats.Invoices.ToString("N0")), 3, 0);

        var info = new Label
        {
            Dock = DockStyle.Top,
            Height = 120,
            Text =
                $"وضعیت لایسنس: {licLabel}\n" +
                $"شبکه: {(online ? "آنلاین — همگام‌سازی فعال" : "آفلاین — کار روی SQL محلی")}\n" +
                $"سال مالی فعال در دیتابیس: {stats.FiscalYears}\n" +
                "از منوی بالا ماژول‌ها را باز کنید (مثل سایت).",
            Padding = new Padding(8)
        };

        var shortcuts = new FlowLayoutPanel
        {
            Dock = DockStyle.Top,
            Height = 48,
            FlowDirection = FlowDirection.RightToLeft
        };
        shortcuts.Controls.Add(Quick("ثبت سند", async () => await ShowVoucherCreateAsync()));
        shortcuts.Controls.Add(Quick("فاکتور", async () => await ShowInvoicesAsync()));
        shortcuts.Controls.Add(Quick("طرف‌حساب", async () => await ShowPartiesAsync()));
        shortcuts.Controls.Add(Quick("تراز آزمایشی", async () => await ShowTrialBalanceAsync()));
        shortcuts.Controls.Add(Quick("همگام‌سازی", async () => await RunSyncAsync()));

        var page = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(16) };
        var title = new Label { Text = "پنجره اصلی", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36 };
        page.Controls.Add(info);
        page.Controls.Add(shortcuts);
        page.Controls.Add(grid);
        page.Controls.Add(title);
        SetContent(page, "پنجره اصلی");
        _ = okLic;
    }

    private Control StatCard(string label, string value)
    {
        var p = new Panel { Dock = DockStyle.Fill, Margin = new Padding(6), BackColor = Color.FromArgb(245, 248, 252), Padding = new Padding(12) };
        p.Controls.Add(new Label { Text = value, Font = new Font("Segoe UI Semibold", 20f), Dock = DockStyle.Fill, TextAlign = ContentAlignment.MiddleCenter });
        p.Controls.Add(new Label { Text = label, Dock = DockStyle.Bottom, Height = 24, TextAlign = ContentAlignment.MiddleCenter, ForeColor = Color.FromArgb(70, 85, 100) });
        return p;
    }

    private Button Quick(string text, Func<Task> act)
    {
        var b = new Button
        {
            Text = text,
            AutoSize = true,
            Margin = new Padding(6),
            FlatStyle = FlatStyle.Flat,
            BackColor = Color.FromArgb(22, 52, 78),
            ForeColor = Color.White,
            Padding = new Padding(12, 6, 12, 6),
            Cursor = Cursors.Hand
        };
        b.Click += async (_, _) => await act();
        return b;
    }

    private async Task ShowAccountsAsync()
    {
        var rows = await _repo.ListAccountsAsync();
        var grid = MakeGrid();
        grid.DataSource = rows.Select(a => new
        {
            گروه = a.GroupCode + " " + a.GroupTitle,
            کل = a.KolCode,
            کد = a.Code,
            عنوان = a.Title,
            ماهیت = a.Nature,
            فعال = a.IsActive ? "بله" : "خیر"
        }).ToList();
        SetContent(WrapGrid("کدینگ حساب‌ها (معین)", grid), $"کدینگ — {rows.Count} حساب");
    }

    private async Task ShowPartiesAsync()
    {
        var root = new Panel { Dock = DockStyle.Fill, BackColor = Color.White };
        var form = new FlowLayoutPanel
        {
            Dock = DockStyle.Top,
            Height = 90,
            FlowDirection = FlowDirection.RightToLeft,
            Padding = new Padding(8),
            BackColor = Color.FromArgb(248, 250, 252)
        };
        var code = Tb(120); var name = Tb(180); var phone = Tb(120);
        var type = new ComboBox { DropDownStyle = ComboBoxStyle.DropDownList, Width = 120 };
        type.Items.AddRange(["customer", "supplier", "both", "other"]);
        type.SelectedIndex = 0;
        var save = ActionBtn("ذخیره", Color.FromArgb(180, 90, 40));
        var del = ActionBtn("حذف انتخاب", Color.FromArgb(140, 50, 50));
        var refresh = ActionBtn("بازخوانی", Color.FromArgb(55, 90, 120));
        form.Controls.Add(Labeled("کد", code));
        form.Controls.Add(Labeled("نام", name));
        form.Controls.Add(Labeled("نوع", type));
        form.Controls.Add(Labeled("تلفن", phone));
        form.Controls.Add(save);
        form.Controls.Add(del);
        form.Controls.Add(refresh);

        var grid = MakeGrid();
        grid.Dock = DockStyle.Fill;
        async Task Load()
        {
            var parties = await _db.ListPartiesAsync();
            grid.DataSource = parties.Select(p => new
            {
                p.Id,
                کد = p.Code,
                نام = p.Name,
                نوع = p.Type,
                تلفن = p.Phone ?? "",
                نسخه = p.SyncVersion
            }).ToList();
            _status.Text = $"طرف‌حساب — {parties.Count}";
        }

        save.Click += async (_, _) =>
        {
            if (string.IsNullOrWhiteSpace(code.Text) || string.IsNullOrWhiteSpace(name.Text))
            {
                MessageBox.Show(this, "کد و نام الزامی است.");
                return;
            }
            await _db.SavePartyAsync(code.Text.Trim(), name.Text.Trim(), type.Text, string.IsNullOrWhiteSpace(phone.Text) ? null : phone.Text.Trim());
            code.Clear(); name.Clear(); phone.Clear();
            await Load();
        };
        del.Click += async (_, _) =>
        {
            if (grid.CurrentRow?.DataBoundItem is null) return;
            var idProp = grid.CurrentRow.DataBoundItem.GetType().GetProperty("Id");
            if (idProp is null) return;
            var id = (int)idProp.GetValue(grid.CurrentRow.DataBoundItem)!;
            if (MessageBox.Show(this, "حذف شود؟", Text, MessageBoxButtons.YesNo) != DialogResult.Yes) return;
            await _db.SoftDeletePartyAsync(id);
            await Load();
        };
        refresh.Click += async (_, _) => await Load();

        var title = new Label { Text = "طرف‌حساب‌ها", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36, Padding = new Padding(8, 8, 8, 0) };
        root.Controls.Add(grid);
        root.Controls.Add(form);
        root.Controls.Add(title);
        SetContent(root, "طرف‌حساب‌ها");
        await Load();
    }

    private async Task ShowVouchersAsync()
    {
        var rows = await _repo.ListVouchersAsync();
        var grid = MakeGrid();
        grid.DataSource = rows.Select(v => new
        {
            v.Id,
            شماره = v.Number,
            تاریخ = v.Date.ToString("yyyy/MM/dd"),
            شرح = v.Description,
            وضعیت = v.Status,
            بدهکار = v.Debit.ToString("N0"),
            بستانکار = v.Credit.ToString("N0")
        }).ToList();

        var bar = new FlowLayoutPanel { Dock = DockStyle.Top, Height = 44, FlowDirection = FlowDirection.RightToLeft, Padding = new Padding(8) };
        var create = ActionBtn("ثبت سند جدید", Color.FromArgb(20, 120, 90));
        var post = ActionBtn("قطعی کردن انتخاب", Color.FromArgb(180, 90, 40));
        create.Click += async (_, _) => await ShowVoucherCreateAsync();
        post.Click += async (_, _) =>
        {
            if (grid.CurrentRow?.DataBoundItem is null) return;
            var id = (int)grid.CurrentRow.DataBoundItem.GetType().GetProperty("Id")!.GetValue(grid.CurrentRow.DataBoundItem)!;
            await _repo.PostVoucherAsync(id);
            await ShowVouchersAsync();
        };
        bar.Controls.Add(create);
        bar.Controls.Add(post);

        var page = new Panel { Dock = DockStyle.Fill, BackColor = Color.White };
        var title = new Label { Text = "فهرست اسناد", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36, Padding = new Padding(8) };
        grid.Dock = DockStyle.Fill;
        page.Controls.Add(grid);
        page.Controls.Add(bar);
        page.Controls.Add(title);
        SetContent(page, $"اسناد — {rows.Count}");
    }

    private async Task ShowVoucherCreateAsync()
    {
        var moeins = await _repo.MoeinLookupAsync();
        var parties = await _repo.PartyLookupAsync();

        var root = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(12) };
        var date = new DateTimePicker { Format = DateTimePickerFormat.Short, Width = 140, Value = DateTime.Today };
        var desc = new TextBox { Width = 420 };
        var lines = new DataGridView
        {
            Dock = DockStyle.Fill,
            AllowUserToAddRows = true,
            AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill,
            BackgroundColor = Color.White,
            RowHeadersVisible = false
        };
        var moeinItems = moeins.Select(m => new LookupItem { Id = m.Id, Text = m.Code + " — " + m.Title }).ToList();
        var partyItems = new List<LookupItem> { new() { Id = null, Text = "—" } };
        partyItems.AddRange(parties.Select(p => new LookupItem { Id = p.Id, Text = p.Code + " — " + p.Name }));
        lines.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "معین",
            Name = "moein",
            DataSource = moeinItems,
            DisplayMember = nameof(LookupItem.Text),
            ValueMember = nameof(LookupItem.Id),
            FlatStyle = FlatStyle.Flat
        });
        lines.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "طرف‌حساب",
            Name = "party",
            DataSource = partyItems,
            DisplayMember = nameof(LookupItem.Text),
            ValueMember = nameof(LookupItem.Id),
            FlatStyle = FlatStyle.Flat
        });
        lines.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "شرح", Name = "desc" });
        lines.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "بدهکار", Name = "debit" });
        lines.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "بستانکار", Name = "credit" });

        var top = new FlowLayoutPanel { Dock = DockStyle.Top, Height = 48, FlowDirection = FlowDirection.RightToLeft };
        top.Controls.Add(Labeled("تاریخ", date));
        top.Controls.Add(Labeled("شرح سند", desc));
        var save = ActionBtn("ثبت سند", Color.FromArgb(20, 120, 90));
        top.Controls.Add(save);
        save.Click += async (_, _) =>
        {
            var inputs = new List<VoucherLineInput>();
            foreach (DataGridViewRow row in lines.Rows)
            {
                if (row.IsNewRow) continue;
                if (row.Cells["moein"].Value is null) continue;
                var mid = Convert.ToInt32(row.Cells["moein"].Value);
                int? pid = row.Cells["party"].Value is null or DBNull ? null : Convert.ToInt32(row.Cells["party"].Value);
                decimal.TryParse(Convert.ToString(row.Cells["debit"].Value)?.Replace(",", ""), NumberStyles.Any, CultureInfo.InvariantCulture, out var deb);
                decimal.TryParse(Convert.ToString(row.Cells["credit"].Value)?.Replace(",", ""), NumberStyles.Any, CultureInfo.InvariantCulture, out var cre);
                var d = Convert.ToString(row.Cells["desc"].Value);
                if (deb == 0 && cre == 0) continue;
                inputs.Add(new VoucherLineInput(mid, pid, d, deb, cre));
            }
            var (ok, msg, _) = await _repo.SaveVoucherAsync(date.Value, desc.Text.Trim(), inputs);
            MessageBox.Show(this, msg, ok ? "ثبت شد" : "خطا", MessageBoxButtons.OK, ok ? MessageBoxIcon.Information : MessageBoxIcon.Warning);
            if (ok) await ShowVouchersAsync();
        };

        var title = new Label { Text = "ثبت سند حسابداری", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36 };
        root.Controls.Add(lines);
        root.Controls.Add(top);
        root.Controls.Add(title);
        SetContent(root, "ثبت سند");
    }

    private async Task ShowInvoicesAsync()
    {
        var parties = await _repo.PartyLookupAsync();
        var root = new Panel { Dock = DockStyle.Fill, BackColor = Color.White };
        var form = new FlowLayoutPanel
        {
            Dock = DockStyle.Top,
            Height = 100,
            FlowDirection = FlowDirection.RightToLeft,
            Padding = new Padding(8),
            BackColor = Color.FromArgb(248, 250, 252)
        };
        var party = new ComboBox
        {
            DropDownStyle = ComboBoxStyle.DropDownList,
            Width = 220,
            DataSource = parties.Select(p => new LookupItem { Id = p.Id, Text = p.Code + " — " + p.Name }).ToList(),
            DisplayMember = nameof(LookupItem.Text),
            ValueMember = nameof(LookupItem.Id)
        };
        var date = new DateTimePicker { Format = DateTimePickerFormat.Short, Width = 120, Value = DateTime.Today };
        var titleBox = Tb(180); titleBox.Text = "کالا / خدمت";
        var qty = Tb(70); qty.Text = "1";
        var price = Tb(100); price.Text = "0";
        var save = ActionBtn("ثبت فاکتور + سند", Color.FromArgb(180, 90, 40));
        form.Controls.Add(Labeled("طرف‌حساب", party));
        form.Controls.Add(Labeled("تاریخ", date));
        form.Controls.Add(Labeled("شرح قلم", titleBox));
        form.Controls.Add(Labeled("تعداد", qty));
        form.Controls.Add(Labeled("فی", price));
        form.Controls.Add(save);

        var grid = MakeGrid();
        grid.Dock = DockStyle.Fill;
        async Task Load()
        {
            var rows = await _repo.ListInvoicesAsync();
            grid.DataSource = rows.Select(i => new
            {
                شماره = i.Number,
                تاریخ = i.Date.ToString("yyyy/MM/dd"),
                طرف‌حساب = i.PartyName,
                مبلغ = i.Total.ToString("N0"),
                وضعیت = i.Status,
                شرح = i.Description
            }).ToList();
            _status.Text = $"فاکتورها — {rows.Count}";
        }
        save.Click += async (_, _) =>
        {
            if (party.SelectedItem is not LookupItem { Id: int pid })
            {
                MessageBox.Show(this, "طرف‌حساب لازم است");
                return;
            }
            decimal.TryParse(qty.Text.Replace(",", ""), out var q);
            decimal.TryParse(price.Text.Replace(",", ""), out var pr);
            var (ok, msg) = await _repo.SaveInvoiceAsync(pid, date.Value, titleBox.Text.Trim(), q, pr, null);
            MessageBox.Show(this, msg, ok ? "ثبت" : "خطا");
            if (ok) await Load();
        };

        var h = new Label { Text = "فاکتور فروش", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36, Padding = new Padding(8) };
        root.Controls.Add(grid);
        root.Controls.Add(form);
        root.Controls.Add(h);
        SetContent(root, "فاکتور فروش");
        await Load();
    }

    private async Task ShowTrialBalanceAsync()
    {
        var rows = await _repo.TrialBalanceAsync();
        var grid = MakeGrid();
        grid.DataSource = rows.Select(r => new
        {
            کد = r.Code,
            عنوان = r.Title,
            بدهکار = r.Debit.ToString("N0"),
            بستانکار = r.Credit.ToString("N0"),
            مانده = r.Balance.ToString("N0")
        }).ToList();
        SetContent(WrapGrid("تراز آزمایشی", grid), $"تراز — {rows.Count} ردیف");
    }

    private Task ShowReportsHubAsync()
    {
        var p = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(16) };
        p.Controls.Add(new Label
        {
            Dock = DockStyle.Fill,
            Text = "مرکز گزارش‌ها\n\n• تراز آزمایشی (فعال)\n• دفتر معین / ترازنامه / سود و زیان — در نسخه‌های بعدی\n• گزارش چک و ویزیتور — هم‌زمان با تکمیل خزانه",
            Font = new Font("Segoe UI", 11f)
        });
        p.Controls.Add(new Label { Text = "مرکز گزارش‌ها", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36 });
        SetContent(p, "گزارش‌ها");
        return Task.CompletedTask;
    }

    private async Task ShowFiscalAsync()
    {
        await using var conn = _db.Create();
        await conn.OpenAsync();
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = "SELECT id, title, start_date, end_date, is_active, is_closed FROM dbo.fiscal_years WHERE is_deleted=0 ORDER BY start_date DESC";
        var table = new List<object>();
        await using var r = await cmd.ExecuteReaderAsync();
        while (await r.ReadAsync())
        {
            table.Add(new
            {
                عنوان = r.GetString(1),
                از = r.GetDateTime(2).ToString("yyyy/MM/dd"),
                تا = r.GetDateTime(3).ToString("yyyy/MM/dd"),
                فعال = r.GetBoolean(4) ? "بله" : "خیر",
                بسته = r.GetBoolean(5) ? "بله" : "خیر"
            });
        }
        var grid = MakeGrid();
        grid.DataSource = table;
        SetContent(WrapGrid("سال و دوره مالی", grid), "سال مالی");
    }

    private async Task ShowLicenseAsync()
    {
        var lic = await _license.CurrentAsync();
        var (valid, label) = await _license.StatusAsync();
        var root = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(20) };
        var info = new Label
        {
            Dock = DockStyle.Top,
            Height = 140,
            Text =
                $"وضعیت: {label}\n" +
                $"نوع: {lic.Type}\n" +
                $"مشتری: {lic.Customer}\n" +
                $"انقضا: {lic.ExpiresAt}\n" +
                $"اثر انگشت این سیستم: {_license.Fingerprint()}\n" +
                $"نشست‌ها: {lic.Seats}"
        };
        var keyBox = new TextBox { Dock = DockStyle.Top, Height = 60, Multiline = true };
        var bar = new FlowLayoutPanel { Dock = DockStyle.Top, Height = 48, FlowDirection = FlowDirection.RightToLeft };
        var act = ActionBtn("فعال‌سازی لایسنس", Color.FromArgb(20, 120, 90));
        var clr = ActionBtn("پاک کردن لایسنس", Color.FromArgb(140, 50, 50));
        act.Click += async (_, _) =>
        {
            var (ok, msg) = await _license.ActivateAsync(keyBox.Text);
            MessageBox.Show(this, msg, ok ? "موفق" : "خطا");
            if (ok) { await RefreshLicenseBadgeAsync(); await ShowLicenseAsync(); }
        };
        clr.Click += async (_, _) =>
        {
            await _license.ClearAsync();
            await RefreshLicenseBadgeAsync();
            await ShowLicenseAsync();
        };
        bar.Controls.Add(act);
        bar.Controls.Add(clr);
        var title = new Label { Text = "لایسنس و فروش", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36 };
        var hint = new Label
        {
            Dock = DockStyle.Top,
            Height = 40,
            Text = "کلید لایسنس را وارد کنید (همان قالب سایت: payload.signature)",
            ForeColor = Color.FromArgb(70, 80, 95)
        };
        root.Controls.Add(bar);
        root.Controls.Add(keyBox);
        root.Controls.Add(hint);
        root.Controls.Add(info);
        root.Controls.Add(title);
        SetContent(root, valid ? "لایسنس معتبر" : "لایسنس نیاز به توجه دارد");
        await RefreshLicenseBadgeAsync();
    }

    private Task ShowSettingsAsync()
    {
        var root = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(20) };
        var syncUrl = new TextBox { Width = 520, Text = _settings.ServerSyncUrl };
        var save = ActionBtn("ذخیره تنظیمات", Color.FromArgb(55, 90, 120));
        save.Click += (_, _) =>
        {
            _settings.ServerSyncUrl = syncUrl.Text.Trim();
            _settings.Save();
            MessageBox.Show(this, "ذخیره شد.");
        };
        var flow = new FlowLayoutPanel { Dock = DockStyle.Top, Height = 120, FlowDirection = FlowDirection.TopDown };
        flow.Controls.Add(new Label { Text = "آدرس همگام‌سازی سایت", AutoSize = true });
        flow.Controls.Add(syncUrl);
        flow.Controls.Add(save);
        flow.Controls.Add(new Label
        {
            AutoSize = true,
            MaximumSize = new Size(700, 0),
            Text = "سورس کامل برنامه در ریپوی GitHub مسیر hesab/winclient است.\nبه‌روزرسانی از منوی پرونده → بررسی به‌روزرسانی یا نصب Setup جدید از Releases.",
            Padding = new Padding(0, 16, 0, 0)
        });
        var title = new Label { Text = "تنظیمات", Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 36 };
        root.Controls.Add(flow);
        root.Controls.Add(title);
        SetContent(root, "تنظیمات");
        return Task.CompletedTask;
    }

    private void OpenSetup()
    {
        using var wizard = new SetupWizardForm(_settings);
        if (wizard.ShowDialog(this) == DialogResult.OK && wizard.Ready)
            MessageBox.Show(this, "برای اعمال ConnectionString جدید، برنامه را ببندید و دوباره باز کنید.", Text);
    }

    private async Task RunSyncAsync()
    {
        _status.Text = "همگام‌سازی…";
        var result = await _sync.SyncAllAsync();
        MessageBox.Show(this, result.Message, "همگام‌سازی");
        _status.Text = "همگام‌سازی تمام شد";
    }

    private async Task TestConnAsync()
    {
        var (ok, msg) = await _db.TestAsync();
        _connLabel.Text = ok ? "✓ " + msg : "✗ " + msg;
        _connLabel.ForeColor = ok ? Color.FromArgb(20, 100, 60) : Color.DarkRed;
    }

    private async Task RefreshLicenseBadgeAsync()
    {
        var (_, label) = await _license.StatusAsync();
        _licenseLabel.Text = "لایسنس: " + label;
    }

    private async Task CheckUpdateQuietAsync()
    {
        var r = await _updates.CheckAsync();
        if (r.UpdateAvailable)
            _status.Text = $"نسخه جدید {r.Remote} آماده است — از منوی پرونده به‌روزرسانی کنید.";
    }

    private async Task CheckUpdateAsync(bool interactive)
    {
        var r = await _updates.CheckAsync();
        if (r.Error is not null)
        {
            if (interactive) MessageBox.Show(this, "بررسی به‌روزرسانی ممکن نشد:\n" + r.Error);
            return;
        }
        if (!r.UpdateAvailable)
        {
            if (interactive)
                MessageBox.Show(this, $"نسخه فعلی {r.Current} به‌روز است.", "به‌روزرسانی");
            return;
        }
        var ans = MessageBox.Show(this,
            $"نسخه جدید {r.Remote} موجود است (فعلی: {r.Current}).\nصفحه دانلود باز شود؟",
            "به‌روزرسانی", MessageBoxButtons.YesNo, MessageBoxIcon.Information);
        if (ans == DialogResult.Yes)
            UpdateService.OpenDownload(r.DownloadUrl);
    }

    private static DataGridView MakeGrid()
    {
        return new DataGridView
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
    }

    private static Panel WrapGrid(string title, DataGridView grid)
    {
        var p = new Panel { Dock = DockStyle.Fill, BackColor = Color.White };
        var h = new Label { Text = title, Font = new Font("Segoe UI Semibold", 14f), Dock = DockStyle.Top, Height = 40, Padding = new Padding(12, 10, 12, 0) };
        grid.Dock = DockStyle.Fill;
        p.Controls.Add(grid);
        p.Controls.Add(h);
        return p;
    }

    private static TextBox Tb(int w) => new() { Width = w, Margin = new Padding(4) };

    private static Button ActionBtn(string text, Color back) =>
        new()
        {
            Text = text,
            AutoSize = true,
            Margin = new Padding(6, 18, 6, 6),
            FlatStyle = FlatStyle.Flat,
            ForeColor = Color.White,
            BackColor = back,
            Padding = new Padding(10, 6, 10, 6),
            Cursor = Cursors.Hand
        };

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
}
