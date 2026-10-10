using System.Globalization;
using System.Text;
using HesabWin.Data;
using HesabWin.Forms;
using HesabWin.Sync;

namespace HesabWin;

internal static class Program
{
    [STAThread]
    static void Main()
    {
        Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);
        ApplicationConfiguration.Initialize();

        var culture = new CultureInfo("fa-IR");
        CultureInfo.DefaultThreadCurrentCulture = culture;
        CultureInfo.DefaultThreadCurrentUICulture = culture;
        Application.CurrentCulture = culture;

        var settings = AppSettings.Load();
        var db = new SqlConnectionFactory(settings.ConnectionString);
        var sync = new SyncService(db, settings);

        Application.Run(new MainForm(db, sync, settings));
    }
}
