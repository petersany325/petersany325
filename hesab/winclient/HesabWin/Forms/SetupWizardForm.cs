using HesabWin.Data;

namespace HesabWin.Forms;

/// <summary>First-run wizard: pick SQL Server connection and create Hesab database.</summary>
public sealed class SetupWizardForm : Form
{
    private readonly AppSettings _settings;
    private readonly TextBox _serverBox;
    private readonly TextBox _userBox;
    private readonly TextBox _passBox;
    private readonly CheckBox _windowsAuth;
    private readonly Label _status;
    private readonly ProgressBar _bar;
    private readonly Button _testBtn;
    private readonly Button _createBtn;
    private readonly Button _continueBtn;

    public string ConnectionString { get; private set; } = "";
    public bool Ready { get; private set; }

    public SetupWizardForm(AppSettings settings)
    {
        _settings = settings;
        Text = "نصب اولیه — حساب HDD";
        RightToLeft = RightToLeft.Yes;
        RightToLeftLayout = true;
        Font = new Font("Segoe UI", 10f);
        FormBorderStyle = FormBorderStyle.FixedDialog;
        MaximizeBox = false;
        MinimizeBox = false;
        StartPosition = FormStartPosition.CenterScreen;
        ClientSize = new Size(560, 420);
        BackColor = Color.FromArgb(245, 247, 250);

        var title = new Label
        {
            Text = "نرم‌افزار حسابداری حساب HDD",
            Font = new Font("Segoe UI Semibold", 14f),
            AutoSize = true,
            Location = new Point(24, 20)
        };
        var sub = new Label
        {
            Text = "اتصال به SQL Server محلی و ساخت دیتابیس Hesab",
            AutoSize = true,
            ForeColor = Color.FromArgb(70, 80, 95),
            Location = new Point(24, 52)
        };

        _serverBox = Field(24, 100, 500, "localhost");
        _windowsAuth = new CheckBox
        {
            Text = "احراز هویت ویندوز (Windows Authentication)",
            Checked = true,
            AutoSize = true,
            Location = new Point(24, 155)
        };
        _userBox = Field(24, 200, 240, "sa");
        _passBox = Field(290, 200, 234, "");
        _passBox.UseSystemPasswordChar = true;
        _userBox.Enabled = false;
        _passBox.Enabled = false;
        _windowsAuth.CheckedChanged += (_, _) =>
        {
            _userBox.Enabled = !_windowsAuth.Checked;
            _passBox.Enabled = !_windowsAuth.Checked;
        };

        _bar = new ProgressBar
        {
            Location = new Point(24, 270),
            Width = 500,
            Style = ProgressBarStyle.Marquee,
            Visible = false
        };
        _status = new Label
        {
            Location = new Point(24, 300),
            Size = new Size(500, 48),
            Text = "SQL Server 2022 باید روی همین کامپیوتر نصب باشد.",
            ForeColor = Color.FromArgb(50, 60, 75)
        };

        _testBtn = Btn("تست اتصال", 24, 360, Color.FromArgb(55, 90, 120));
        _createBtn = Btn("ساخت دیتابیس", 170, 360, Color.FromArgb(180, 90, 40));
        _continueBtn = Btn("ورود به برنامه", 340, 360, Color.FromArgb(20, 120, 90));
        _continueBtn.Enabled = false;

        Controls.Add(title);
        Controls.Add(sub);
        Controls.Add(LabelAt("سرور SQL", 24, 80));
        Controls.Add(_serverBox);
        Controls.Add(_windowsAuth);
        Controls.Add(LabelAt("کاربر", 24, 180));
        Controls.Add(LabelAt("رمز", 290, 180));
        Controls.Add(_userBox);
        Controls.Add(_passBox);
        Controls.Add(_bar);
        Controls.Add(_status);
        Controls.Add(_testBtn);
        Controls.Add(_createBtn);
        Controls.Add(_continueBtn);

        _testBtn.Click += async (_, _) => await TestAsync();
        _createBtn.Click += async (_, _) => await CreateAsync();
        _continueBtn.Click += (_, _) =>
        {
            Ready = true;
            DialogResult = DialogResult.OK;
            Close();
        };
    }

    private async Task TestAsync()
    {
        SetBusy(true);
        var cs = BuildCs("Hesab");
        var (ok, detail) = await DatabaseBootstrap.ProbeSqlServerAsync(cs);
        _status.Text = ok ? "✓ " + detail : "✗ " + detail;
        _status.ForeColor = ok ? Color.FromArgb(20, 100, 60) : Color.FromArgb(160, 40, 40);
        if (ok)
        {
            ConnectionString = cs;
            _continueBtn.Enabled = true;
        }
        SetBusy(false);
    }

    private async Task CreateAsync()
    {
        SetBusy(true);
        _bar.Visible = true;
        var cs = BuildCs("Hesab");
        var master = DatabaseBootstrap.ToMasterConnectionString(cs);
        var progress = new Progress<string>(m => _status.Text = m);
        var (ok, msg) = await DatabaseBootstrap.EnsureDatabaseAsync(master, cs, progress);
        _status.Text = (ok ? "✓ " : "✗ ") + msg;
        _status.ForeColor = ok ? Color.FromArgb(20, 100, 60) : Color.FromArgb(160, 40, 40);
        if (ok)
        {
            ConnectionString = cs;
            _settings.ConnectionString = cs;
            _settings.Save();
            _continueBtn.Enabled = true;
        }
        _bar.Visible = false;
        SetBusy(false);
    }

    private string BuildCs(string database)
    {
        var server = string.IsNullOrWhiteSpace(_serverBox.Text) ? "localhost" : _serverBox.Text.Trim();
        if (_windowsAuth.Checked)
        {
            return $"Server={server};Database={database};Trusted_Connection=True;TrustServerCertificate=True;";
        }
        return $"Server={server};Database={database};User Id={_userBox.Text.Trim()};Password={_passBox.Text};TrustServerCertificate=True;";
    }

    private void SetBusy(bool busy)
    {
        UseWaitCursor = busy;
        _testBtn.Enabled = !busy;
        _createBtn.Enabled = !busy;
        _continueBtn.Enabled = !busy && !string.IsNullOrEmpty(ConnectionString);
    }

    private static Label LabelAt(string text, int x, int y) =>
        new() { Text = text, AutoSize = true, Location = new Point(x, y), ForeColor = Color.FromArgb(60, 70, 85) };

    private static TextBox Field(int x, int y, int w, string text) =>
        new() { Location = new Point(x, y), Width = w, Text = text };

    private static Button Btn(string text, int x, int y, Color back) =>
        new()
        {
            Text = text,
            Location = new Point(x, y),
            Width = 130,
            Height = 36,
            FlatStyle = FlatStyle.Flat,
            ForeColor = Color.White,
            BackColor = back,
            Cursor = Cursors.Hand
        };
}
