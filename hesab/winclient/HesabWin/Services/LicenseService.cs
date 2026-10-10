using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using HesabWin.Data;

namespace HesabWin.Services;

/// <summary>Same key format as web License.php (HESAB-HDD-2026).</summary>
public sealed class LicenseService
{
    public const string Product = "HESAB-HDD-2026";
    private const string DefaultSecret = "HsbLic#HESAB-HDD-2026#Kx9mQ2pL";

    private readonly SqlConnectionFactory _db;

    public LicenseService(SqlConnectionFactory db) => _db = db;

    public string Fingerprint()
    {
        var machine = Environment.MachineName + "|" + Environment.UserName + "|win";
        var hash = SHA256.HashData(Encoding.UTF8.GetBytes(machine.ToLowerInvariant()));
        return Convert.ToHexString(hash)[..16].ToLowerInvariant();
    }

    public async Task<LicenseInfo> CurrentAsync(CancellationToken ct = default)
    {
        var json = await _db.GetSettingAsync("license_payload", ct);
        if (string.IsNullOrWhiteSpace(json))
            return Trial();

        try
        {
            var info = JsonSerializer.Deserialize<LicenseInfo>(json);
            return info ?? Trial();
        }
        catch
        {
            return Trial();
        }
    }

    public LicenseInfo Trial()
    {
        return new LicenseInfo
        {
            Type = "trial",
            Customer = "نسخه آزمایشی",
            Seats = 3,
            Modules = ["accounting", "treasury", "reports", "invoices", "sms"],
            ExpiresAt = DateTime.UtcNow.Date.AddDays(45).ToString("yyyy-MM-dd"),
            IssuedAt = DateTime.UtcNow.Date.ToString("yyyy-MM-dd"),
            Fingerprint = Fingerprint(),
            Status = "trial"
        };
    }

    public async Task<(bool Ok, string Message)> ActivateAsync(string key, CancellationToken ct = default)
    {
        key = key.Trim();
        var parts = key.Split('.');
        if (parts.Length != 2)
            return (false, "قالب کلید لایسنس نادرست است");

        var json = Ub64(parts[0]);
        var sig = Ub64Bytes(parts[1]);
        if (json is null || sig is null)
            return (false, "کلید قابل خواندن نیست");

        var expect = HMACSHA256.HashData(Encoding.UTF8.GetBytes(Secret()), Encoding.UTF8.GetBytes(json));
        if (!CryptographicOperations.FixedTimeEquals(expect, sig))
            return (false, "امضای لایسنس معتبر نیست");

        LicenseInfo? payload;
        try
        {
            payload = JsonSerializer.Deserialize<LicenseInfo>(json);
        }
        catch
        {
            return (false, "محتوای لایسنس خراب است");
        }

        if (payload is null)
            return (false, "محتوای لایسنس خراب است");
        if (!string.Equals(payload.Product, Product, StringComparison.Ordinal) &&
            !string.IsNullOrEmpty(payload.Product))
            return (false, "این لایسنس برای محصول دیگری است");

        var fp = payload.Fingerprint ?? "ANY";
        if (fp is not ("ANY" or "") && !string.Equals(fp, Fingerprint(), StringComparison.OrdinalIgnoreCase))
            return (false, "این لایسنس برای این نصب صادر نشده است");

        payload.Status = "active";
        payload.Product = Product;
        payload.ActivatedAt = DateTime.UtcNow.ToString("o");
        payload.ActivatedHost = Environment.MachineName;

        await _db.SetSettingAsync("license_payload", JsonSerializer.Serialize(payload), ct);
        await _db.SetSettingAsync("license_key_last", key.Length > 24 ? key[..24] + "…" : key, ct);
        return (true, "لایسنس فعال شد");
    }

    public async Task ClearAsync(CancellationToken ct = default)
    {
        await _db.SetSettingAsync("license_payload", "", ct);
        await _db.SetSettingAsync("license_key_last", "", ct);
    }

    public async Task<(bool Valid, string Label)> StatusAsync(CancellationToken ct = default)
    {
        var lic = await CurrentAsync(ct);
        if (string.Equals(lic.Status, "revoked", StringComparison.OrdinalIgnoreCase))
            return (false, "باطل‌شده");
        if (!string.IsNullOrEmpty(lic.ExpiresAt) &&
            DateTime.TryParse(lic.ExpiresAt, out var exp) && exp.Date < DateTime.UtcNow.Date)
            return (false, "منقضی");
        var fp = lic.Fingerprint ?? "";
        if (fp is not ("" or "ANY") && !string.Equals(fp, Fingerprint(), StringComparison.OrdinalIgnoreCase)
            && lic.Type != "trial")
            return (false, "نامعتبر");
        return (true, lic.Type == "trial" ? "آزمایشی" : "فعال");
    }

    public async Task<bool> HasModuleAsync(string code, CancellationToken ct = default)
    {
        var (valid, _) = await StatusAsync(ct);
        if (!valid) return false;
        var lic = await CurrentAsync(ct);
        if (lic.Modules is null || lic.Modules.Count == 0) return true;
        return lic.Modules.Contains(code) || lic.Modules.Contains("*");
    }

    /// <summary>Issue key for sales (same algorithm as PHP).</summary>
    public static string Issue(LicenseInfo data, string? secret = null)
    {
        data.Product = Product;
        data.Status = "active";
        data.IssuedAt ??= DateTime.UtcNow.ToString("yyyy-MM-dd");
        data.Fingerprint ??= "ANY";
        data.Modules ??= ["*"];
        var json = JsonSerializer.Serialize(data);
        var body = B64(Encoding.UTF8.GetBytes(json));
        var sig = B64(HMACSHA256.HashData(Encoding.UTF8.GetBytes(secret ?? DefaultSecret), Encoding.UTF8.GetBytes(json)));
        return body + "." + sig;
    }

    private static string Secret() => DefaultSecret;

    private static string B64(byte[] bin) =>
        Convert.ToBase64String(bin).TrimEnd('=').Replace('+', '-').Replace('/', '_');

    private static string? Ub64(string txt)
    {
        var bytes = Ub64Bytes(txt);
        return bytes is null ? null : Encoding.UTF8.GetString(bytes);
    }

    private static byte[]? Ub64Bytes(string txt)
    {
        try
        {
            var s = txt.Replace('-', '+').Replace('_', '/');
            switch (s.Length % 4)
            {
                case 2: s += "=="; break;
                case 3: s += "="; break;
            }
            return Convert.FromBase64String(s);
        }
        catch
        {
            return null;
        }
    }
}

public sealed class LicenseInfo
{
    public string Type { get; set; } = "trial";
    public string Customer { get; set; } = "";
    public string? Domain { get; set; }
    public int Seats { get; set; } = 5;
    public List<string>? Modules { get; set; }
    public string? ExpiresAt { get; set; }
    public string? IssuedAt { get; set; }
    public string? Fingerprint { get; set; }
    public string Status { get; set; } = "trial";
    public string? Product { get; set; }
    public string? ActivatedAt { get; set; }
    public string? ActivatedHost { get; set; }
}
