using System.Text.Json;

namespace HesabWin;

public sealed class AppSettings
{
    public string ConnectionString { get; set; } =
        "Server=localhost;Database=Hesab;Trusted_Connection=True;TrustServerCertificate=True;";

    public string ServerSyncUrl { get; set; } = "https://hdd-land.ir/hesab/api/sync";
    public string AppName { get; set; } = "حساب HDD";
    public string ConflictPolicy { get; set; } = "LastWriteWins";
    public bool SetupCompleted { get; set; }

    private static string UserSettingsPath =>
        Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "HesabHDD",
            "appsettings.json");

    private static string BundledSettingsPath =>
        Path.Combine(AppContext.BaseDirectory, "appsettings.json");

    public static AppSettings Load()
    {
        foreach (var path in new[] { UserSettingsPath, BundledSettingsPath })
        {
            if (!File.Exists(path))
                continue;
            try
            {
                var json = File.ReadAllText(path);
                var s = JsonSerializer.Deserialize<AppSettings>(json);
                if (s != null)
                    return s;
            }
            catch
            {
                // try next
            }
        }
        return new AppSettings();
    }

    public void Save()
    {
        var dir = Path.GetDirectoryName(UserSettingsPath)!;
        Directory.CreateDirectory(dir);
        var json = JsonSerializer.Serialize(this, new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(UserSettingsPath, json);
    }
}
