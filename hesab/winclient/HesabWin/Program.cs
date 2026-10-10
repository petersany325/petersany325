using System.Globalization;
using System.Text;
using HesabWin.Data;
using HesabWin.Forms;
using HesabWin.Sync;

namespace HesabWin;

internal static class Program
{
    [STAThread]
    static void Main(string[] args)
    {
        Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);
        ApplicationConfiguration.Initialize();

        var culture = new CultureInfo("fa-IR");
        CultureInfo.DefaultThreadCurrentCulture = culture;
        CultureInfo.DefaultThreadCurrentUICulture = culture;
        Application.CurrentCulture = culture;

        Application.SetUnhandledExceptionMode(UnhandledExceptionMode.CatchException);
        Application.ThreadException += (_, e) =>
            MessageBox.Show(e.Exception.Message, "خطای برنامه", MessageBoxButtons.OK, MessageBoxIcon.Error);

        var settings = AppSettings.Load();
        var forceSetup = args.Any(a => a.Equals("--setup", StringComparison.OrdinalIgnoreCase));

        var db = new SqlConnectionFactory(settings.ConnectionString);
        var needSetup = forceSetup || !settings.SetupCompleted;

        if (!needSetup)
        {
            var (ok, _) = db.TestAsync().GetAwaiter().GetResult();
            needSetup = !ok;
        }

        if (needSetup)
        {
            using var wizard = new SetupWizardForm(settings);
            if (wizard.ShowDialog() != DialogResult.OK || !wizard.Ready)
                return;

            if (!string.IsNullOrWhiteSpace(wizard.ConnectionString))
                settings.ConnectionString = wizard.ConnectionString;
            settings.SetupCompleted = true;
            settings.Save();
            db = new SqlConnectionFactory(settings.ConnectionString);
        }

        var sync = new SyncService(db, settings);
        Application.Run(new MainForm(db, sync, settings));
    }
}
