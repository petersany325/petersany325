using Microsoft.Data.SqlClient;

namespace HesabWin.Data;

public sealed class SqlConnectionFactory
{
    private readonly string _connectionString;

    public SqlConnectionFactory(string connectionString)
    {
        _connectionString = connectionString;
    }

    public string ConnectionString => _connectionString;

    public SqlConnection Create() => new(_connectionString);

    public async Task<(bool Ok, string Message)> TestAsync(CancellationToken ct = default)
    {
        try
        {
            await using var conn = Create();
            await conn.OpenAsync(ct);
            await using var cmd = conn.CreateCommand();
            cmd.CommandText = "SELECT DB_NAME(), @@VERSION";
            await using var reader = await cmd.ExecuteReaderAsync(ct);
            if (await reader.ReadAsync(ct))
            {
                var db = reader.GetString(0);
                var ver = reader.GetString(1).Split('\n')[0].Trim();
                return (true, $"متصل به «{db}» — {ver}");
            }
            return (true, "اتصال برقرار شد.");
        }
        catch (Exception ex)
        {
            return (false, ex.Message);
        }
    }

    public async Task<int> CountAsync(string table, CancellationToken ct = default)
    {
        await using var conn = Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = $"SELECT COUNT(*) FROM dbo.[{table}] WHERE is_deleted = 0";
        var result = await cmd.ExecuteScalarAsync(ct);
        return Convert.ToInt32(result);
    }

    public async Task<IReadOnlyList<PartyRow>> ListPartiesAsync(CancellationToken ct = default)
    {
        var list = new List<PartyRow>();
        await using var conn = Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
SELECT TOP 200 id, sync_id, code, name, type, phone, updated_at, sync_version
FROM dbo.parties
WHERE is_deleted = 0
ORDER BY code";
        await using var reader = await cmd.ExecuteReaderAsync(ct);
        while (await reader.ReadAsync(ct))
        {
            list.Add(new PartyRow(
                reader.GetInt32(0),
                reader.GetGuid(1),
                reader.GetString(2),
                reader.GetString(3),
                reader.GetString(4),
                reader.IsDBNull(5) ? null : reader.GetString(5),
                reader.GetDateTime(6),
                reader.GetInt64(7)));
        }
        return list;
    }

    public async Task SavePartyAsync(string code, string name, string type, string? phone, CancellationToken ct = default)
    {
        await using var conn = Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
IF EXISTS (SELECT 1 FROM dbo.parties WHERE code = @code AND is_deleted = 0)
BEGIN
    UPDATE dbo.parties
    SET name = @name, type = @type, phone = @phone,
        updated_at = SYSUTCDATETIME(), sync_version = sync_version + 1
    WHERE code = @code AND is_deleted = 0;
END
ELSE
BEGIN
    INSERT INTO dbo.parties (code, name, type, phone)
    VALUES (@code, @name, @type, @phone);
END";
        cmd.Parameters.AddWithValue("@code", code);
        cmd.Parameters.AddWithValue("@name", name);
        cmd.Parameters.AddWithValue("@type", type);
        cmd.Parameters.AddWithValue("@phone", (object?)phone ?? DBNull.Value);
        await cmd.ExecuteNonQueryAsync(ct);
    }

    public async Task SoftDeletePartyAsync(int id, CancellationToken ct = default)
    {
        await using var conn = Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
UPDATE dbo.parties
SET is_deleted=1, updated_at=SYSUTCDATETIME(), sync_version=sync_version+1
WHERE id=@id";
        cmd.Parameters.AddWithValue("@id", id);
        await cmd.ExecuteNonQueryAsync(ct);
    }

    public async Task<string?> GetSettingAsync(string key, CancellationToken ct = default)
    {
        await using var conn = Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = "SELECT [value] FROM dbo.settings WHERE [key]=@k";
        cmd.Parameters.AddWithValue("@k", key);
        var o = await cmd.ExecuteScalarAsync(ct);
        return o is null or DBNull ? null : Convert.ToString(o);
    }

    public async Task SetSettingAsync(string key, string? value, CancellationToken ct = default)
    {
        await using var conn = Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
MERGE dbo.settings AS t
USING (SELECT @k AS [key]) AS s ON t.[key]=s.[key]
WHEN MATCHED THEN UPDATE SET [value]=@v, updated_at=SYSUTCDATETIME(), sync_version=t.sync_version+1
WHEN NOT MATCHED THEN INSERT ([key],[value]) VALUES (@k,@v);";
        cmd.Parameters.AddWithValue("@k", key);
        cmd.Parameters.AddWithValue("@v", (object?)value ?? DBNull.Value);
        await cmd.ExecuteNonQueryAsync(ct);
    }

    public SqlConnectionFactory WithConnectionString(string cs) => new(cs);
}

public sealed record PartyRow(
    int Id,
    Guid SyncId,
    string Code,
    string Name,
    string Type,
    string? Phone,
    DateTime UpdatedAt,
    long SyncVersion);
