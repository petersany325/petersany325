@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if(isset($errors) && $errors->any())
    <div class="alert alert-error">
        <ul style="margin:0;padding-right:1.1rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(session('backup_download_file') && auth()->check() && auth()->user()->canAccess('system.tools'))
    @php
        $backupDl = session('backup_download_file');
    @endphp
    <div class="alert alert-success" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span>فایل بکاپ آماده ذخیره روی کامپیوتر:</span>
        <a id="backup-auto-dl" class="btn btn-primary" href="{{ route('system-tools.backups.download', $backupDl) }}">دانلود و ذخیره روی کامپیوتر</a>
        <span class="muted" dir="ltr">{{ $backupDl }}</span>
    </div>
    @if(session('backup_auto_download'))
        <script>
            (function () {
                var a = document.getElementById('backup-auto-dl');
                if (!a) return;
                setTimeout(function () {
                    try { a.click(); } catch (e) {}
                }, 350);
            })();
        </script>
    @endif
@endif
