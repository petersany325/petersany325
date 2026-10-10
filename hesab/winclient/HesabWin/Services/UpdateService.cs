using System.Diagnostics;
using System.Reflection;
using System.Text.Json;

namespace HesabWin.Services;

/// <summary>Checks GitHub Releases for newer Hesab Windows builds and opens the download page.</summary>
public sealed class UpdateService
{
    public const string ReleasesApi =
        "https://api.github.com/repos/petersany325/petersany325/releases/latest";
    public const string ReleasesPage =
        "https://github.com/petersany325/petersany325/releases/latest";

    private readonly HttpClient _http = new()
    {
        Timeout = TimeSpan.FromSeconds(20)
    };

    public UpdateService()
    {
        _http.DefaultRequestHeaders.UserAgent.ParseAdd("HesabWin-Updater");
        _http.DefaultRequestHeaders.Accept.ParseAdd("application/vnd.github+json");
    }

    public static Version CurrentVersion
    {
        get
        {
            var v = Assembly.GetExecutingAssembly().GetName().Version;
            return v ?? new Version(1, 0, 0);
        }
    }

    public async Task<UpdateCheckResult> CheckAsync(CancellationToken ct = default)
    {
        try
        {
            using var resp = await _http.GetAsync(ReleasesApi, ct);
            if (!resp.IsSuccessStatusCode)
                return new UpdateCheckResult(false, CurrentVersion, null, null, $"HTTP {(int)resp.StatusCode}");

            await using var stream = await resp.Content.ReadAsStreamAsync(ct);
            using var doc = await JsonDocument.ParseAsync(stream, cancellationToken: ct);
            var root = doc.RootElement;
            var tag = root.GetProperty("tag_name").GetString() ?? "";
            var html = root.TryGetProperty("html_url", out var u) ? u.GetString() : ReleasesPage;
            var remote = ParseVersion(tag);
            string? setupUrl = null;
            if (root.TryGetProperty("assets", out var assets))
            {
                foreach (var a in assets.EnumerateArray())
                {
                    var name = a.GetProperty("name").GetString() ?? "";
                    if (name.Contains("Setup", StringComparison.OrdinalIgnoreCase) &&
                        name.EndsWith(".exe", StringComparison.OrdinalIgnoreCase))
                    {
                        setupUrl = a.GetProperty("browser_download_url").GetString();
                        break;
                    }
                }
            }

            var newer = remote > CurrentVersion;
            return new UpdateCheckResult(newer, CurrentVersion, remote, setupUrl ?? html, null);
        }
        catch (Exception ex)
        {
            return new UpdateCheckResult(false, CurrentVersion, null, null, ex.Message);
        }
    }

    public static void OpenDownload(string? url)
    {
        var target = string.IsNullOrWhiteSpace(url) ? ReleasesPage : url;
        Process.Start(new ProcessStartInfo { FileName = target, UseShellExecute = true });
    }

    private static Version ParseVersion(string tag)
    {
        // hesab-win-1.1.0 or v1.1.0
        var digits = new string(tag.SkipWhile(c => !char.IsDigit(c)).ToArray());
        if (Version.TryParse(digits, out var v))
            return v;
        return new Version(0, 0, 0);
    }
}

public sealed record UpdateCheckResult(
    bool UpdateAvailable,
    Version Current,
    Version? Remote,
    string? DownloadUrl,
    string? Error);
