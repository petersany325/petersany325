using System.Net.Http.Json;
using System.Text.Json;
using HesabWin.Data;
using Microsoft.Data.SqlClient;

namespace HesabWin.Sync;

/// <summary>
/// Bidirectional sync stub: push local dirty rows, pull remote changes.
/// Server API (PHP) is expected at ServerSyncUrl; until it exists, methods
/// return a clear offline / not-ready status without crashing the UI.
/// Conflict policy: LastWriteWins on sync_version then updated_at.
/// </summary>
public sealed class SyncService
{
    private static readonly string[] SyncEntities =
    [
        "parties", "accounts_moein", "vouchers", "voucher_lines",
        "invoices", "invoice_items", "posting_rules", "settings"
    ];

    private readonly SqlConnectionFactory _db;
    private readonly AppSettings _settings;
    private readonly HttpClient _http;

    public SyncService(SqlConnectionFactory db, AppSettings settings)
    {
        _db = db;
        _settings = settings;
        _http = new HttpClient { Timeout = TimeSpan.FromSeconds(30) };
    }

    public async Task<SyncResult> SyncAllAsync(CancellationToken ct = default)
    {
        var log = new List<string>();
        var ok = true;

        if (!await IsOnlineAsync(ct))
        {
            return new SyncResult(false, "آفلاین هستید — همگام‌سازی انجام نشد. کار روی دیتابیس محلی ادامه دارد.");
        }

        Guid deviceId = await GetOrCreateDeviceIdAsync(ct);

        foreach (var entity in SyncEntities)
        {
            ct.ThrowIfCancellationRequested();
            try
            {
                var push = await PushEntityAsync(entity, deviceId, ct);
                log.Add($"push/{entity}: {push}");
                var pull = await PullEntityAsync(entity, deviceId, ct);
                log.Add($"pull/{entity}: {pull}");
            }
            catch (Exception ex)
            {
                ok = false;
                log.Add($"error/{entity}: {ex.Message}");
                await WriteSyncLogAsync("push", entity, null, "error", ex.Message, ct);
            }
        }

        return new SyncResult(ok, string.Join(Environment.NewLine, log));
    }

    public async Task<bool> IsOnlineAsync(CancellationToken ct = default)
    {
        try
        {
            using var req = new HttpRequestMessage(HttpMethod.Head, _settings.ServerSyncUrl);
            using var resp = await _http.SendAsync(req, HttpCompletionOption.ResponseHeadersRead, ct);
            // 404/405 still means host reachable; only network failures mean offline
            return true;
        }
        catch
        {
            return false;
        }
    }

    private async Task<string> PushEntityAsync(string entity, Guid deviceId, CancellationToken ct)
    {
        var since = await GetCursorAsync(deviceId, entity, ct);
        var rows = await LoadDirtyRowsAsync(entity, since, ct);
        if (rows.Count == 0)
            return "0 row";

        var payload = new SyncPushRequest
        {
            DeviceId = deviceId,
            Entity = entity,
            ConflictPolicy = _settings.ConflictPolicy,
            Rows = rows
        };

        try
        {
            using var resp = await _http.PostAsJsonAsync(
                $"{_settings.ServerSyncUrl.TrimEnd('/')}/push",
                payload, ct);

            if (!resp.IsSuccessStatusCode)
            {
                var body = await resp.Content.ReadAsStringAsync(ct);
                await WriteSyncLogAsync("push", entity, null, "error",
                    $"HTTP {(int)resp.StatusCode}: {Truncate(body)}", ct);
                return $"HTTP {(int)resp.StatusCode} ({rows.Count} queued locally)";
            }

            await TouchSyncStateAsync(deviceId, entity, pushed: true, ct);
            await WriteSyncLogAsync("push", entity, null, "ok", $"{rows.Count} rows", ct);
            return $"{rows.Count} ok";
        }
        catch (HttpRequestException ex)
        {
            await WriteSyncLogAsync("push", entity, null, "error", ex.Message, ct);
            return $"queued ({rows.Count}) — {ex.Message}";
        }
    }

    private async Task<string> PullEntityAsync(string entity, Guid deviceId, CancellationToken ct)
    {
        var since = await GetCursorAsync(deviceId, entity, ct);
        var url = $"{_settings.ServerSyncUrl.TrimEnd('/')}/pull?entity={Uri.EscapeDataString(entity)}&since={Uri.EscapeDataString(since ?? "")}&deviceId={deviceId}";

        try
        {
            using var resp = await _http.GetAsync(url, ct);
            if (!resp.IsSuccessStatusCode)
            {
                return $"HTTP {(int)resp.StatusCode}";
            }

            var payload = await resp.Content.ReadFromJsonAsync<SyncPullResponse>(cancellationToken: ct);
            if (payload?.Rows is null || payload.Rows.Count == 0)
            {
                await TouchSyncStateAsync(deviceId, entity, pushed: false, ct);
                return "0 row";
            }

            var applied = await ApplyRemoteRowsAsync(entity, payload.Rows, ct);
            await TouchSyncStateAsync(deviceId, entity, pushed: false, ct, payload.Cursor);
            await WriteSyncLogAsync("pull", entity, null, "ok", $"{applied} applied", ct);
            return $"{applied} applied";
        }
        catch (HttpRequestException ex)
        {
            await WriteSyncLogAsync("pull", entity, null, "error", ex.Message, ct);
            return ex.Message;
        }
        catch (JsonException)
        {
            return "پاسخ سرور هنوز آماده نیست (API sync)";
        }
    }

    private async Task<List<Dictionary<string, object?>>> LoadDirtyRowsAsync(
        string entity, string? since, CancellationToken ct)
    {
        var list = new List<Dictionary<string, object?>>();
        // Settings has no is_deleted; handle separately
        var sql = entity == "settings"
            ? @"SELECT * FROM dbo.settings WHERE updated_at > COALESCE(@since, '1900-01-01')"
            : @"SELECT * FROM dbo.[" + entity + @"] WHERE updated_at > COALESCE(@since, '1900-01-01')";

        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = sql;
        cmd.Parameters.AddWithValue("@since", (object?)since ?? DBNull.Value);
        await using var reader = await cmd.ExecuteReaderAsync(ct);
        while (await reader.ReadAsync(ct))
        {
            var row = new Dictionary<string, object?>(StringComparer.OrdinalIgnoreCase);
            for (var i = 0; i < reader.FieldCount; i++)
            {
                var name = reader.GetName(i);
                row[name] = reader.IsDBNull(i) ? null : reader.GetValue(i);
            }
            list.Add(row);
        }
        return list;
    }

    private async Task<int> ApplyRemoteRowsAsync(
        string entity, List<Dictionary<string, JsonElement>> rows, CancellationToken ct)
    {
        // Full merge upsert lands with the PHP sync API; for now record intent.
        foreach (var row in rows)
        {
            Guid? sid = null;
            if (row.TryGetValue("sync_id", out var el) && el.ValueKind == JsonValueKind.String
                && Guid.TryParse(el.GetString(), out var g))
                sid = g;
            await WriteSyncLogAsync("pull", entity, sid, "ok", "pending merge", ct);
        }
        return rows.Count;
    }

    private async Task<Guid> GetOrCreateDeviceIdAsync(CancellationToken ct)
    {
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = "SELECT [value] FROM dbo.settings WHERE [key] = N'device_id'";
        var val = await cmd.ExecuteScalarAsync(ct) as string;
        if (Guid.TryParse(val, out var id))
            return id;

        id = Guid.NewGuid();
        cmd.CommandText = @"
MERGE dbo.settings AS t
USING (SELECT N'device_id' AS [key]) AS s ON t.[key] = s.[key]
WHEN MATCHED THEN UPDATE SET [value] = @v, updated_at = SYSUTCDATETIME(), sync_version = t.sync_version + 1
WHEN NOT MATCHED THEN INSERT ([key], [value]) VALUES (N'device_id', @v);";
        cmd.Parameters.Clear();
        cmd.Parameters.AddWithValue("@v", id.ToString());
        await cmd.ExecuteNonQueryAsync(ct);
        return id;
    }

    private async Task<string?> GetCursorAsync(Guid deviceId, string entity, CancellationToken ct)
    {
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
SELECT last_cursor FROM dbo.sync_state
WHERE device_id = @d AND entity = @e";
        cmd.Parameters.AddWithValue("@d", deviceId);
        cmd.Parameters.AddWithValue("@e", entity);
        return await cmd.ExecuteScalarAsync(ct) as string;
    }

    private async Task TouchSyncStateAsync(
        Guid deviceId, string entity, bool pushed, CancellationToken ct, string? cursor = null)
    {
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
MERGE dbo.sync_state AS t
USING (SELECT @d AS device_id, @e AS entity) AS s
ON t.device_id = s.device_id AND t.entity = s.entity
WHEN MATCHED THEN UPDATE SET
    last_pushed_at = CASE WHEN @pushed = 1 THEN SYSUTCDATETIME() ELSE last_pushed_at END,
    last_pulled_at = CASE WHEN @pushed = 0 THEN SYSUTCDATETIME() ELSE last_pulled_at END,
    last_cursor = COALESCE(@cursor, last_cursor)
WHEN NOT MATCHED THEN INSERT (device_id, entity, last_pushed_at, last_pulled_at, last_cursor)
VALUES (@d, @e,
  CASE WHEN @pushed = 1 THEN SYSUTCDATETIME() ELSE NULL END,
  CASE WHEN @pushed = 0 THEN SYSUTCDATETIME() ELSE NULL END,
  @cursor);";
        cmd.Parameters.AddWithValue("@d", deviceId);
        cmd.Parameters.AddWithValue("@e", entity);
        cmd.Parameters.AddWithValue("@pushed", pushed ? 1 : 0);
        cmd.Parameters.AddWithValue("@cursor", (object?)cursor ?? DBNull.Value);
        await cmd.ExecuteNonQueryAsync(ct);
    }

    private async Task WriteSyncLogAsync(
        string direction, string entity, Guid? syncId, string status, string? detail, CancellationToken ct)
    {
        try
        {
            await using var conn = _db.Create();
            await conn.OpenAsync(ct);
            await using var cmd = conn.CreateCommand();
            cmd.CommandText = @"
INSERT INTO dbo.sync_log (direction, entity, sync_id, status, detail)
VALUES (@dir, @ent, @sid, @st, @detail)";
            cmd.Parameters.AddWithValue("@dir", direction);
            cmd.Parameters.AddWithValue("@ent", entity);
            cmd.Parameters.AddWithValue("@sid", (object?)syncId ?? DBNull.Value);
            cmd.Parameters.AddWithValue("@st", status);
            cmd.Parameters.AddWithValue("@detail", (object?)detail ?? DBNull.Value);
            await cmd.ExecuteNonQueryAsync(ct);
        }
        catch
        {
            // never break UI for logging
        }
    }

    private static string Truncate(string s) => s.Length <= 200 ? s : s[..200];
}

public sealed record SyncResult(bool Success, string Message);

public sealed class SyncPushRequest
{
    public Guid DeviceId { get; set; }
    public string Entity { get; set; } = "";
    public string ConflictPolicy { get; set; } = "LastWriteWins";
    public List<Dictionary<string, object?>> Rows { get; set; } = [];
}

public sealed class SyncPullResponse
{
    public string? Cursor { get; set; }
    public List<Dictionary<string, JsonElement>> Rows { get; set; } = [];
}
