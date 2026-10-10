using System.Text.Json;

namespace HesabWin;

public sealed class AppSettings
{
    public string ConnectionString { get; set; } =
        "Server=localhost;Database=Hesab;Trusted_Connection=True;TrustServerCertificate=True;";

    public string ServerSyncUrl { get; set; } = "https://hdd-land.ir/hesab/api/sync";
    public string AppName { get; set; } = "حساب HDD";
    public string ConflictPolicy { get; set; } = "LastWriteWins";

    public static AppSettings Load()
    {
        var path = Path.Combine(AppContext.BaseDirectory, "appsettings.json");
        if (!File.Exists(path))
            return new AppSettings();

        try
        {
            var json = File.ReadAllText(path);
            return JsonSerializer.Deserialize<AppSettings>(json) ?? new AppSettings();
        }
        catch
        {
            return new AppSettings();
        }
    }

    public void Save()
    {
        var path = Path.Combine(AppContext.BaseDirectory, "appsettings.json");
        var json = JsonSerializer.Serialize(this, new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(path, json);
    }
}
