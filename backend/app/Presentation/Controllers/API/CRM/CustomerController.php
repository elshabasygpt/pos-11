<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\API\CRM;

use App\Presentation\Controllers\API\BaseController;
use App\Infrastructure\Eloquent\Models\CustomerModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 15);
        $search = $request->query('search');

        $query = CustomerModel::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('phone', 'ilike', '%' . $search . '%')
                  ->orWhere('email', 'ilike', '%' . $search . '%');
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate((int) $limit);

        return $this->paginated($customers->toArray(), 'Customers retrieved successfully');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $customer = CustomerModel::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'balance' => 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->success($customer, 'Customer created successfully', 201);
    }

    /**
     * Display the specified customer.
     */
    public function show(string $id): JsonResponse
    {
        $customer = CustomerModel::find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($customer, 'Customer retrieved successfully');
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = CustomerModel::find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return $this->success($customer, 'Customer updated successfully');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = CustomerModel::find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        // Ideally check if customer has invoices before deleting, soft delete or prevent deletion
        $customer->delete();

        return $this->success(null, 'Customer deleted successfully');
    }

    /**
     * Export all customers to XLSX format
     */
    public function export(Request $request)
    {
        $customers = CustomerModel::all(['name', 'email', 'phone', 'address', 'tax_number', 'balance']);
        
        $data = [
            ['Name', 'Email', 'Phone', 'Address', 'Tax Number', 'Opening Balance']
        ];
        
        foreach ($customers as $customer) {
            $data[] = [
                $customer->name,
                $customer->email,
                $customer->phone,
                $customer->address,
                $customer->tax_number,
                $customer->balance
            ];
        }

        $xlsx = \App\Infrastructure\Helpers\SimpleXLSXGen::fromArray($data);
        return response($xlsx->content(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="customers.xlsx"'
        ]);
    }

    /**
     * Import customers from XLSX format
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        $file = $request->file('file')->getPathname();
        
        if ($xlsx = \App\Infrastructure\Helpers\SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            $header = array_shift($rows); // Remove header
            
            $imported = 0;
            foreach ($rows as $row) {
                // Ensure row has basic data (Name)
                if (empty($row[0])) continue;
                
                CustomerModel::updateOrCreate(
                    ['email' => $row[1] ?: null], // If email is empty, we might have matching issues. Ideally match by phone/email
                    [
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'name' => $row[0],
                        'phone' => $row[2] ?? null,
                        'address' => $row[3] ?? null,
                        'tax_number' => $row[4] ?? null,
                        'balance' => $row[5] ?? 0,
                    ]
                );
                $imported++;
            }
            return $this->success(['imported' => $imported], "Successfully imported $imported customers.");
        } else {
            return $this->error('Failed to parse Excel file', 400);
        }
    }

    /**
     * Get Customer Ledger Statement (كشف حساب تفصيلي)
     */
    public function statement(string $id): JsonResponse
    {
        $customer = CustomerModel::with(['invoices', 'vouchers'])->find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $ledger = [];
        $runningBalance = (float) $customer->balance; // Opening balance
        
        // Add Opening Balance as first entry
        $ledger[] = [
            'type' => 'opening_balance',
            'reference' => '-',
            'date' => $customer->created_at->format('Y-m-d'),
            'description' => 'رصيد افتتاحي (سابق)',
            'debit' => $runningBalance > 0 ? $runningBalance : 0,
            'credit' => $runningBalance < 0 ? abs($runningBalance) : 0,
            'balance' => $runningBalance,
        ];

        $transactions = collect();

        // 1. Invoices (Debit - Customer owes us)
        foreach ($customer->invoices as $invoice) {
            $transactions->push([
                '_date' => $invoice->invoice_date,
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'description' => 'فاتورة مبيعات',
                'debit' => (float) $invoice->total_amount,
                'credit' => 0,
            ]);
        }

        // 2. Vouchers (Credit/Debit based on type)
        foreach ($customer->vouchers as $voucher) {
            $isCredit = in_array($voucher->type, ['receipt', 'discount']); // Customer pays us or gets discount
            $transactions->push([
                '_date' => $voucher->date,
                'type' => 'voucher_' . $voucher->type,
                'reference' => $voucher->reference_number,
                'date' => $voucher->date->format('Y-m-d'),
                'description' => $voucher->notes ?: 'سند ' . $voucher->type,
                'debit' => $isCredit ? 0 : (float) $voucher->amount,
                'credit' => $isCredit ? (float) $voucher->amount : 0,
            ]);
        }

        // Sorting by Date
        $transactions = $transactions->sortBy('_date')->values();

        // Compute running balance chronologically
        foreach ($transactions as $tx) {
            $runningBalance += $tx['debit'];
            $runningBalance -= $tx['credit'];
            
            $tx['balance'] = $runningBalance;
            unset($tx['_date']); // remove sorting key
            $ledger[] = $tx;
        }

        return $this->success([
            'customer' => $customer->name,
            'opening_balance' => (float) $customer->balance,
            'current_balance' => $runningBalance,
            'statement' => $ledger
        ], 'Customer statement generated successfully.');
    }
}
