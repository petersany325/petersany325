using System.Diagnostics;
using System.Text;
using Microsoft.Data.SqlClient;

namespace HesabWin.Data;

/// <summary>
/// Creates the Hesab database on first run by executing the embedded SQL script
/// against the local SQL Server instance (via ADO.NET batches).
/// </summary>
public static class DatabaseBootstrap
{
    public static string FindSchemaScriptPath()
    {
        var candidates = new[]
        {
            Path.Combine(AppContext.BaseDirectory, "Assets", "sqlserver_hesab.sql"),
            Path.Combine(AppContext.BaseDirectory, "sqlserver_hesab.sql"),
            Path.Combine(AppContext.BaseDirectory, "..", "sql", "sqlserver_hesab.sql"),
        };
        foreach (var p in candidates)
        {
            var full = Path.GetFullPath(p);
            if (File.Exists(full))
                return full;
        }
        throw new FileNotFoundException("فایل sqlserver_hesab.sql کنار برنامه پیدا نشد.");
    }

    public static async Task<(bool Ok, string Message)> EnsureDatabaseAsync(
        string masterConnectionString,
        string hesabConnectionString,
        IProgress<string>? progress = null,
        CancellationToken ct = default)
    {
        try
        {
            progress?.Report("بررسی اتصال به SQL Server…");
            await using (var master = new SqlConnection(masterConnectionString))
            {
                await master.OpenAsync(ct);
            }

            // If Hesab already opens, we are done
            try
            {
                await using var test = new SqlConnection(hesabConnectionString);
                await test.OpenAsync(ct);
                await using var cmd = test.CreateCommand();
                cmd.CommandText = "SELECT OBJECT_ID(N'dbo.parties', N'U')";
                var id = await cmd.ExecuteScalarAsync(ct);
                if (id is not null and not DBNull)
                {
                    return (true, "دیتابیس Hesab از قبل آماده است.");
                }
            }
            catch (SqlException)
            {
                // Database missing — continue to create
            }

            progress?.Report("اجرای اسکریپت ساخت دیتابیس…");
            var script = await File.ReadAllTextAsync(FindSchemaScriptPath(), Encoding.UTF8, ct);
            var batches = SplitGoBatches(script);
            var masterCs = masterConnectionString;

            await using var conn = new SqlConnection(masterCs);
            await conn.OpenAsync(ct);

            var n = 0;
            foreach (var batch in batches)
            {
                ct.ThrowIfCancellationRequested();
                if (string.IsNullOrWhiteSpace(batch))
                    continue;
                n++;
                progress?.Report($"دسته SQL {n}/{batches.Count}…");

                // USE Hesab requires connection context; handle via ChangeDatabase after CREATE
                var text = batch.Trim();
                if (text.StartsWith("USE ", StringComparison.OrdinalIgnoreCase))
                {
                    var dbName = text.Split([' ', ';', '\r', '\n'], StringSplitOptions.RemoveEmptyEntries)
                        .Skip(1).FirstOrDefault()?.Trim('[', ']', 'N', '\'') ?? "Hesab";
                    // After CREATE DATABASE, switch
                    try
                    {
                        conn.ChangeDatabase(dbName.Trim());
                    }
                    catch
                    {
                        // Database may not exist yet in this batch order — run as-is
                        await using var useCmd = conn.CreateCommand();
                        useCmd.CommandText = text;
                        useCmd.CommandTimeout = 120;
                        await useCmd.ExecuteNonQueryAsync(ct);
                    }
                    continue;
                }

                await using var cmd = conn.CreateCommand();
                cmd.CommandText = text;
                cmd.CommandTimeout = 180;
                await cmd.ExecuteNonQueryAsync(ct);

                // After CREATE DATABASE, reconnect may be needed for some editions
                if (text.Contains("CREATE DATABASE", StringComparison.OrdinalIgnoreCase))
                {
                    try { conn.ChangeDatabase("Hesab"); } catch { /* later USE batch */ }
                }
            }

            // Final verify
            await using var verify = new SqlConnection(hesabConnectionString);
            await verify.OpenAsync(ct);
            return (true, "دیتابیس Hesab با موفقیت ساخته شد.");
        }
        catch (Exception ex)
        {
            return (false, ex.Message);
        }
    }

    public static string ToMasterConnectionString(string hesabCs)
    {
        var b = new SqlConnectionStringBuilder(hesabCs) { InitialCatalog = "master" };
        return b.ConnectionString;
    }

    public static async Task<(bool Ok, string Detail)> ProbeSqlServerAsync(string hesabCs, CancellationToken ct = default)
    {
        try
        {
            var master = ToMasterConnectionString(hesabCs);
            await using var conn = new SqlConnection(master);
            await conn.OpenAsync(ct);
            await using var cmd = conn.CreateCommand();
            cmd.CommandText = "SELECT @@VERSION, SERVERPROPERTY('Edition')";
            await using var r = await cmd.ExecuteReaderAsync(ct);
            await r.ReadAsync(ct);
            var ver = r.GetString(0).Split('\n')[0].Trim();
            var ed = r.IsDBNull(1) ? "" : Convert.ToString(r.GetValue(1));
            return (true, $"{ver} | {ed}");
        }
        catch (Exception ex)
        {
            return (false, ex.Message);
        }
    }

    /// <summary>Optional: run sqlcmd.exe if present (faster for large scripts).</summary>
    public static bool TryFindSqlCmd(out string path)
    {
        path = "";
        var roots = new[]
        {
            Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles),
            Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86),
        };
        foreach (var root in roots)
        {
            if (string.IsNullOrEmpty(root) || !Directory.Exists(root))
                continue;
            try
            {
                foreach (var dir in Directory.EnumerateDirectories(root, "Microsoft SQL Server", SearchOption.TopDirectoryOnly))
                {
                    foreach (var tools in Directory.EnumerateDirectories(dir, "Tools", SearchOption.AllDirectories))
                    {
                        var candidate = Path.Combine(tools, "Binn", "SQLCMD.EXE");
                        if (File.Exists(candidate))
                        {
                            path = candidate;
                            return true;
                        }
                    }
                }
            }
            catch
            {
                // ignore access
            }
        }
        return false;
    }

    public static async Task<(bool Ok, string Message)> RunViaSqlCmdAsync(
        string scriptPath, string server, CancellationToken ct = default)
    {
        if (!TryFindSqlCmd(out var sqlcmd))
            return (false, "sqlcmd پیدا نشد.");

        var psi = new ProcessStartInfo
        {
            FileName = sqlcmd,
            Arguments = $"-S \"{server}\" -E -I -b -i \"{scriptPath}\"",
            RedirectStandardOutput = true,
            RedirectStandardError = true,
            UseShellExecute = false,
            CreateNoWindow = true,
        };
        using var p = Process.Start(psi) ?? throw new InvalidOperationException("اجرای sqlcmd ممکن نشد.");
        var stdout = await p.StandardOutput.ReadToEndAsync(ct);
        var stderr = await p.StandardError.ReadToEndAsync(ct);
        await p.WaitForExitAsync(ct);
        if (p.ExitCode != 0)
            return (false, string.IsNullOrWhiteSpace(stderr) ? stdout : stderr);
        return (true, "اسکریپت با sqlcmd اجرا شد.");
    }

    private static List<string> SplitGoBatches(string script)
    {
        var lines = script.Replace("\r\n", "\n").Split('\n');
        var batches = new List<string>();
        var sb = new StringBuilder();
        foreach (var line in lines)
        {
            if (line.Trim().Equals("GO", StringComparison.OrdinalIgnoreCase))
            {
                batches.Add(sb.ToString());
                sb.Clear();
            }
            else
            {
                sb.AppendLine(line);
            }
        }
        if (sb.Length > 0)
            batches.Add(sb.ToString());
        return batches;
    }
}
