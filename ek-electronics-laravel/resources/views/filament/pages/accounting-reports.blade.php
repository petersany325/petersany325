<x-filament-panels::page>
    @php($r = $this->report)
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <x-slot name="heading">Shop revenue</x-slot>
                <p class="text-2xl font-bold">R {{ number_format($r['revenue'], 2) }}</p>
                <p class="text-sm text-gray-500">{{ $r['ordersCount'] }} paid / dispatched / collected orders</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Cost of goods</x-slot>
                <p class="text-2xl font-bold">R {{ number_format($r['cogs'], 2) }}</p>
                <p class="text-sm text-gray-500">From shop order lines × product cost</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Gross profit</x-slot>
                <p class="text-2xl font-bold">R {{ number_format($r['gross'], 2) }}</p>
                <p class="text-sm text-gray-500">Revenue − COGS</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Net profit / loss</x-slot>
                <p class="text-2xl font-bold {{ $r['net'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">R {{ number_format($r['net'], 2) }}</p>
                <p class="text-sm text-gray-500">Gross − expenses (R {{ number_format($r['expenses'], 2) }})</p>
            </x-filament::section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Invoices & stock</x-slot>
                <ul class="space-y-2 text-sm">
                    <li>Open / late invoices: <strong>R {{ number_format($r['openInvoices'], 2) }}</strong></li>
                    <li>Paid invoices: <strong>R {{ number_format($r['paidInvoices'], 2) }}</strong></li>
                    <li>Inventory at cost: <strong>R {{ number_format($r['inventoryValue'], 2) }}</strong></li>
                    <li>Inventory at retail: <strong>R {{ number_format($r['inventoryRetail'], 2) }}</strong></li>
                </ul>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Orders by status</x-slot>
                <ul class="space-y-2 text-sm">
                    @forelse ($r['byStatus'] as $row)
                        <li>{{ ucfirst($row->status) }}: <strong>{{ $row->cnt }}</strong> · R {{ number_format((float) $row->total, 2) }}</li>
                    @empty
                        <li>No orders yet.</li>
                    @endforelse
                </ul>
            </x-filament::section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Expenses by category</x-slot>
                <ul class="space-y-2 text-sm">
                    @forelse ($r['expensesByCategory'] as $row)
                        <li>{{ ucfirst($row->category) }}: <strong>R {{ number_format((float) $row->total, 2) }}</strong></li>
                    @empty
                        <li>No expenses recorded. Add them under Accounting → Expenses.</li>
                    @endforelse
                </ul>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Top products (paid orders)</x-slot>
                <ul class="space-y-2 text-sm">
                    @forelse ($r['topProducts'] as $row)
                        <li>{{ $row->product_name }} — {{ $row->units }} units · R {{ number_format((float) $row->revenue, 2) }}</li>
                    @empty
                        <li>No paid product sales yet.</li>
                    @endforelse
                </ul>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Recent expenses</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pe-3">Date</th>
                            <th class="py-2 pe-3">Category</th>
                            <th class="py-2 pe-3">Description</th>
                            <th class="py-2 pe-3">Vendor</th>
                            <th class="py-2 text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($r['recentExpenses'] as $expense)
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pe-3">{{ $expense->spent_on?->format('Y-m-d') }}</td>
                                <td class="py-2 pe-3">{{ $expense->category }}</td>
                                <td class="py-2 pe-3">{{ $expense->description }}</td>
                                <td class="py-2 pe-3">{{ $expense->vendor ?: '—' }}</td>
                                <td class="py-2 text-end">R {{ number_format((float) $expense->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-gray-500">No expenses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
