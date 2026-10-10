using Microsoft.Data.SqlClient;

namespace HesabWin.Data;

public sealed class HesabRepository
{
    private readonly SqlConnectionFactory _db;
    public HesabRepository(SqlConnectionFactory db) => _db = db;

    public async Task<DashboardStats> GetDashboardAsync(CancellationToken ct = default)
    {
        return new DashboardStats(
            await _db.CountAsync("parties", ct),
            await _db.CountAsync("accounts_moein", ct),
            await _db.CountAsync("vouchers", ct),
            await _db.CountAsync("invoices", ct),
            await CountFiscalYearsAsync(ct));
    }

    private async Task<int> CountFiscalYearsAsync(CancellationToken ct)
    {
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = "SELECT COUNT(*) FROM dbo.fiscal_years WHERE is_deleted = 0";
        return Convert.ToInt32(await cmd.ExecuteScalarAsync(ct));
    }

    public async Task<IReadOnlyList<AccountRow>> ListAccountsAsync(CancellationToken ct = default)
    {
        var list = new List<AccountRow>();
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
SELECT m.id, m.code, m.title, m.nature, m.is_active, k.code AS kol_code, k.title AS kol_title, g.code AS group_code, g.title AS group_title
FROM dbo.accounts_moein m
JOIN dbo.accounts_kol k ON k.id = m.kol_id
JOIN dbo.account_groups g ON g.id = k.group_id
WHERE m.is_deleted = 0
ORDER BY m.code";
        await using var r = await cmd.ExecuteReaderAsync(ct);
        while (await r.ReadAsync(ct))
        {
            list.Add(new AccountRow(
                r.GetInt32(0), r.GetString(1), r.GetString(2), r.GetString(3), r.GetBoolean(4),
                r.GetString(5), r.GetString(6), r.GetString(7), r.GetString(8)));
        }
        return list;
    }

    public async Task<IReadOnlyList<VoucherRow>> ListVouchersAsync(CancellationToken ct = default)
    {
        var list = new List<VoucherRow>();
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
SELECT TOP 300 v.id, v.number, v.voucher_date, v.description, v.status,
       (SELECT ISNULL(SUM(debit),0) FROM dbo.voucher_lines vl WHERE vl.voucher_id=v.id AND vl.is_deleted=0) AS total_debit,
       (SELECT ISNULL(SUM(credit),0) FROM dbo.voucher_lines vl WHERE vl.voucher_id=v.id AND vl.is_deleted=0) AS total_credit
FROM dbo.vouchers v
WHERE v.is_deleted = 0
ORDER BY v.voucher_date DESC, v.number DESC";
        await using var r = await cmd.ExecuteReaderAsync(ct);
        while (await r.ReadAsync(ct))
        {
            list.Add(new VoucherRow(
                r.GetInt32(0), r.GetInt32(1), r.GetDateTime(2),
                r.IsDBNull(3) ? "" : r.GetString(3), r.GetString(4),
                r.GetDecimal(5), r.GetDecimal(6)));
        }
        return list;
    }

    public async Task<(bool Ok, string Message, int? Id)> SaveVoucherAsync(
        DateTime date, string description, IReadOnlyList<VoucherLineInput> lines, CancellationToken ct = default)
    {
        if (lines.Count < 2)
            return (false, "حداقل دو ردیف سند لازم است.", null);
        var debit = lines.Sum(l => l.Debit);
        var credit = lines.Sum(l => l.Credit);
        if (debit != credit)
            return (false, $"سند تراز نیست. بدهکار={debit:N0} بستانکار={credit:N0}", null);
        if (debit <= 0)
            return (false, "مبلغ سند باید بزرگ‌تر از صفر باشد.", null);

        await using var conn = _db.Create();
        await conn.OpenAsync(ct);

        int fyId;
        await using (var fy = conn.CreateCommand())
        {
            fy.CommandText = "SELECT TOP 1 id FROM dbo.fiscal_years WHERE is_active=1 AND is_deleted=0 ORDER BY id DESC";
            var o = await fy.ExecuteScalarAsync(ct);
            if (o is null)
                return (false, "سال مالی فعال تعریف نشده است.", null);
            fyId = Convert.ToInt32(o);
        }

        await using var tx = (SqlTransaction)await conn.BeginTransactionAsync(ct);
        try
        {

            int number;
            await using (var num = conn.CreateCommand())
            {
                num.Transaction = tx;
                num.CommandText = "SELECT ISNULL(MAX(number),0)+1 FROM dbo.vouchers WHERE fiscal_year_id=@fy AND is_deleted=0";
                num.Parameters.AddWithValue("@fy", fyId);
                number = Convert.ToInt32(await num.ExecuteScalarAsync(ct));
            }

            int voucherId;
            await using (var ins = conn.CreateCommand())
            {
                ins.Transaction = tx;
                ins.CommandText = @"
INSERT INTO dbo.vouchers (fiscal_year_id, number, voucher_date, description, status)
OUTPUT INSERTED.id
VALUES (@fy, @num, @dt, @desc, N'draft')";
                ins.Parameters.AddWithValue("@fy", fyId);
                ins.Parameters.AddWithValue("@num", number);
                ins.Parameters.AddWithValue("@dt", date.Date);
                ins.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                voucherId = Convert.ToInt32(await ins.ExecuteScalarAsync(ct));
            }

            short lineNo = 1;
            foreach (var line in lines)
            {
                await using var li = conn.CreateCommand();
                li.Transaction = tx;
                li.CommandText = @"
INSERT INTO dbo.voucher_lines (voucher_id, line_no, moein_id, party_id, description, debit, credit)
VALUES (@v, @n, @m, @p, @d, @deb, @cre)";
                li.Parameters.AddWithValue("@v", voucherId);
                li.Parameters.AddWithValue("@n", lineNo++);
                li.Parameters.AddWithValue("@m", line.MoeinId);
                li.Parameters.AddWithValue("@p", (object?)line.PartyId ?? DBNull.Value);
                li.Parameters.AddWithValue("@d", (object?)line.Description ?? DBNull.Value);
                li.Parameters.AddWithValue("@deb", line.Debit);
                li.Parameters.AddWithValue("@cre", line.Credit);
                await li.ExecuteNonQueryAsync(ct);
            }

            await tx.CommitAsync(ct);
            return (true, $"سند شماره {number} ثبت شد.", voucherId);
        }
        catch (Exception ex)
        {
            await tx.RollbackAsync(ct);
            return (false, ex.Message, null);
        }
    }

    public async Task PostVoucherAsync(int id, CancellationToken ct = default)
    {
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
UPDATE dbo.vouchers
SET status=N'posted', posted_at=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME(), sync_version=sync_version+1
WHERE id=@id AND is_deleted=0";
        cmd.Parameters.AddWithValue("@id", id);
        await cmd.ExecuteNonQueryAsync(ct);
    }

    public async Task<IReadOnlyList<InvoiceRow>> ListInvoicesAsync(CancellationToken ct = default)
    {
        var list = new List<InvoiceRow>();
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
SELECT TOP 300 i.id, i.number, i.invoice_date, p.name, i.total, i.status, i.description
FROM dbo.invoices i
JOIN dbo.parties p ON p.id = i.party_id
WHERE i.is_deleted = 0
ORDER BY i.invoice_date DESC, i.number DESC";
        await using var r = await cmd.ExecuteReaderAsync(ct);
        while (await r.ReadAsync(ct))
        {
            list.Add(new InvoiceRow(
                r.GetInt32(0), r.GetInt32(1), r.GetDateTime(2), r.GetString(3),
                r.GetDecimal(4), r.GetString(5), r.IsDBNull(6) ? "" : r.GetString(6)));
        }
        return list;
    }

    public async Task<(bool Ok, string Message)> SaveInvoiceAsync(
        int partyId, DateTime date, string title, decimal qty, decimal unitPrice, string? description, CancellationToken ct = default)
    {
        if (partyId <= 0) return (false, "طرف‌حساب را انتخاب کنید.");
        if (unitPrice < 0 || qty <= 0) return (false, "مقدار/فی نامعتبر است.");
        var amount = Math.Round(qty * unitPrice, 0);

        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var tx = (SqlTransaction)await conn.BeginTransactionAsync(ct);
        try
        {
            int number;
            await using (var n = conn.CreateCommand())
            {
                n.Transaction = tx;
                n.CommandText = "SELECT ISNULL(MAX(number),0)+1 FROM dbo.invoices WHERE is_deleted=0";
                number = Convert.ToInt32(await n.ExecuteScalarAsync(ct));
            }

            int invId;
            await using (var ins = conn.CreateCommand())
            {
                ins.Transaction = tx;
                ins.CommandText = @"
INSERT INTO dbo.invoices (number, invoice_date, party_id, total, description, status)
OUTPUT INSERTED.id
VALUES (@n, @d, @p, @t, @desc, N'draft')";
                ins.Parameters.AddWithValue("@n", number);
                ins.Parameters.AddWithValue("@d", date.Date);
                ins.Parameters.AddWithValue("@p", partyId);
                ins.Parameters.AddWithValue("@t", amount);
                ins.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                invId = Convert.ToInt32(await ins.ExecuteScalarAsync(ct));
            }

            await using (var item = conn.CreateCommand())
            {
                item.Transaction = tx;
                item.CommandText = @"
INSERT INTO dbo.invoice_items (invoice_id, title, qty, unit_price, amount)
VALUES (@i, @t, @q, @u, @a)";
                item.Parameters.AddWithValue("@i", invId);
                item.Parameters.AddWithValue("@t", title);
                item.Parameters.AddWithValue("@q", qty);
                item.Parameters.AddWithValue("@u", unitPrice);
                item.Parameters.AddWithValue("@a", amount);
                await item.ExecuteNonQueryAsync(ct);
            }

            // Auto voucher: AR debit / SALE credit via posting_rules
            var ar = await MoeinIdByRuleAsync(conn, tx, "AR_CUSTOMER", ct);
            var sale = await MoeinIdByRuleAsync(conn, tx, "SALE", ct);
            if (ar is not null && sale is not null && amount > 0)
            {
                var (ok, msg, vid) = await SaveVoucherInTxAsync(conn, tx, date,
                    $"فاکتور فروش {number}",
                    [
                        new VoucherLineInput(ar.Value, partyId, "بدهکار مشتری", amount, 0),
                        new VoucherLineInput(sale.Value, null, "فروش", 0, amount)
                    ], ct);
                if (ok && vid is not null)
                {
                    await using var link = conn.CreateCommand();
                    link.Transaction = tx;
                    link.CommandText = "UPDATE dbo.invoices SET voucher_id=@v, status=N'confirmed', updated_at=SYSUTCDATETIME(), sync_version=sync_version+1 WHERE id=@i";
                    link.Parameters.AddWithValue("@v", vid.Value);
                    link.Parameters.AddWithValue("@i", invId);
                    await link.ExecuteNonQueryAsync(ct);
                }
                else if (!ok)
                {
                    // keep invoice draft if voucher failed
                    _ = msg;
                }
            }

            await tx.CommitAsync(ct);
            return (true, $"فاکتور {number} ثبت شد.");
        }
        catch (Exception ex)
        {
            await tx.RollbackAsync(ct);
            return (false, ex.Message);
        }
    }

    private async Task<(bool Ok, string Message, int? Id)> SaveVoucherInTxAsync(
        SqlConnection conn, SqlTransaction tx, DateTime date, string description,
        IReadOnlyList<VoucherLineInput> lines, CancellationToken ct)
    {
        await using var fy = conn.CreateCommand();
        fy.Transaction = tx;
        fy.CommandText = "SELECT TOP 1 id FROM dbo.fiscal_years WHERE is_active=1 AND is_deleted=0 ORDER BY id DESC";
        var o = await fy.ExecuteScalarAsync(ct);
        if (o is null) return (false, "سال مالی فعال نیست", null);
        var fyId = Convert.ToInt32(o);

        await using var num = conn.CreateCommand();
        num.Transaction = tx;
        num.CommandText = "SELECT ISNULL(MAX(number),0)+1 FROM dbo.vouchers WHERE fiscal_year_id=@fy AND is_deleted=0";
        num.Parameters.AddWithValue("@fy", fyId);
        var number = Convert.ToInt32(await num.ExecuteScalarAsync(ct));

        await using var ins = conn.CreateCommand();
        ins.Transaction = tx;
        ins.CommandText = @"
INSERT INTO dbo.vouchers (fiscal_year_id, number, voucher_date, description, status, source_module)
OUTPUT INSERTED.id
VALUES (@fy, @num, @dt, @desc, N'posted', N'invoice')";
        ins.Parameters.AddWithValue("@fy", fyId);
        ins.Parameters.AddWithValue("@num", number);
        ins.Parameters.AddWithValue("@dt", date.Date);
        ins.Parameters.AddWithValue("@desc", description);
        var voucherId = Convert.ToInt32(await ins.ExecuteScalarAsync(ct));

        short lineNo = 1;
        foreach (var line in lines)
        {
            await using var li = conn.CreateCommand();
            li.Transaction = tx;
            li.CommandText = @"
INSERT INTO dbo.voucher_lines (voucher_id, line_no, moein_id, party_id, description, debit, credit)
VALUES (@v, @n, @m, @p, @d, @deb, @cre)";
            li.Parameters.AddWithValue("@v", voucherId);
            li.Parameters.AddWithValue("@n", lineNo++);
            li.Parameters.AddWithValue("@m", line.MoeinId);
            li.Parameters.AddWithValue("@p", (object?)line.PartyId ?? DBNull.Value);
            li.Parameters.AddWithValue("@d", (object?)line.Description ?? DBNull.Value);
            li.Parameters.AddWithValue("@deb", line.Debit);
            li.Parameters.AddWithValue("@cre", line.Credit);
            await li.ExecuteNonQueryAsync(ct);
        }
        return (true, "ok", voucherId);
    }

    private static async Task<int?> MoeinIdByRuleAsync(SqlConnection conn, SqlTransaction tx, string rule, CancellationToken ct)
    {
        await using var cmd = conn.CreateCommand();
        cmd.Transaction = tx;
        cmd.CommandText = @"
SELECT TOP 1 m.id
FROM dbo.posting_rules r
JOIN dbo.accounts_moein m ON m.code = r.moein_code AND m.is_deleted = 0
WHERE r.code = @c AND r.is_deleted = 0 AND r.is_active = 1";
        cmd.Parameters.AddWithValue("@c", rule);
        var o = await cmd.ExecuteScalarAsync(ct);
        return o is null or DBNull ? null : Convert.ToInt32(o);
    }

    public async Task<IReadOnlyList<TrialBalanceRow>> TrialBalanceAsync(CancellationToken ct = default)
    {
        var list = new List<TrialBalanceRow>();
        await using var conn = _db.Create();
        await conn.OpenAsync(ct);
        await using var cmd = conn.CreateCommand();
        cmd.CommandText = @"
SELECT m.code, m.title,
       ISNULL(SUM(vl.debit),0) AS debit,
       ISNULL(SUM(vl.credit),0) AS credit
FROM dbo.accounts_moein m
LEFT JOIN dbo.voucher_lines vl ON vl.moein_id = m.id AND vl.is_deleted = 0
LEFT JOIN dbo.vouchers v ON v.id = vl.voucher_id AND v.is_deleted = 0 AND v.status IN (N'posted', N'locked', N'reviewed')
WHERE m.is_deleted = 0
GROUP BY m.code, m.title
HAVING ISNULL(SUM(vl.debit),0) <> 0 OR ISNULL(SUM(vl.credit),0) <> 0
ORDER BY m.code";
        await using var r = await cmd.ExecuteReaderAsync(ct);
        while (await r.ReadAsync(ct))
        {
            var d = r.GetDecimal(2);
            var c = r.GetDecimal(3);
            list.Add(new TrialBalanceRow(r.GetString(0), r.GetString(1), d, c, d - c));
        }
        return list;
    }

    public async Task<IReadOnlyList<(int Id, string Code, string Name)>> PartyLookupAsync(CancellationToken ct = default)
    {
        var list = new List<(int, string, string)>();
        foreach (var p in await _db.ListPartiesAsync(ct))
            list.Add((p.Id, p.Code, p.Name));
        return list;
    }

    public async Task<IReadOnlyList<(int Id, string Code, string Title)>> MoeinLookupAsync(CancellationToken ct = default)
    {
        var list = new List<(int, string, string)>();
        foreach (var a in await ListAccountsAsync(ct))
            list.Add((a.Id, a.Code, a.Title));
        return list;
    }
}

public sealed record DashboardStats(int Parties, int Moein, int Vouchers, int Invoices, int FiscalYears);
public sealed record AccountRow(int Id, string Code, string Title, string Nature, bool IsActive, string KolCode, string KolTitle, string GroupCode, string GroupTitle);
public sealed record VoucherRow(int Id, int Number, DateTime Date, string Description, string Status, decimal Debit, decimal Credit);
public sealed record VoucherLineInput(int MoeinId, int? PartyId, string? Description, decimal Debit, decimal Credit);
public sealed record InvoiceRow(int Id, int Number, DateTime Date, string PartyName, decimal Total, string Status, string Description);
public sealed record TrialBalanceRow(string Code, string Title, decimal Debit, decimal Credit, decimal Balance);

public sealed class LookupItem
{
    public int? Id { get; set; }
    public string Text { get; set; } = "";
    public override string ToString() => Text;
}
