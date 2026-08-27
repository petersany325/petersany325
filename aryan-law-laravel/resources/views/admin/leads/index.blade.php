@extends('layouts.admin-app')
@section('title', 'درخواست‌ها')
@section('heading', 'درخواست‌های مشاوره')
@section('content')
<div class="card">
  <table>
    <thead>
      <tr>
        <th>نام</th><th>تلفن</th><th>موضوع</th><th>پیام</th><th>وضعیت</th><th></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($leads as $lead)
        <tr>
          <td>{{ $lead->name }}</td>
          <td>{{ $lead->phone }}</td>
          <td>{{ $lead->topic }}</td>
          <td>{{ \Illuminate\Support\Str::limit($lead->message, 80) }}</td>
          <td>
            <form method="post" action="{{ route('admin.leads.status', $lead) }}">
              @csrf @method('PATCH')
              <select name="status" onchange="this.form.submit()">
                @foreach (['new'=>'جدید','contacted'=>'تماس‌گرفته','closed'=>'بسته'] as $val => $label)
                  <option value="{{ $val }}" @selected($lead->status === $val)>{{ $label }}</option>
                @endforeach
              </select>
            </form>
          </td>
          <td>
            <form method="post" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('حذف شود؟')">
              @csrf @method('DELETE')
              <button class="btn btn-danger" type="submit">حذف</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div style="margin-top:1rem">{{ $leads->links() }}</div>
</div>
@endsection
