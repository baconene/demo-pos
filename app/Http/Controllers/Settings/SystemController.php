<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    /** Transactional records — cleared by both reset types */
    private const TRANSACTIONAL_TABLES = [
        'audit_logs',
        'order_item_modifiers',
        'refunds',
        'payments',
        'order_items',
        'queue_numbers',
        'orders',
        'inventory_transactions',
        'financial_transactions',
        'payroll_records',
        'purchase_order_items',
        'purchase_orders',
        'bill_installments',
        'bills',
    ];

    /** Menu/setup data — only cleared by factory reset */
    private const SETUP_TABLES = [
        'recipes',
        'product_modifier',
        'modifiers',
        'products',
        'categories',
        'ingredients',
        'employees',
        'suppliers',
        'payment_tenders',
    ];

    public function index(): Response
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        return Inertia::render('settings/System');
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $request->validate([
            'confirmation' => ['required', 'in:RESET'],
        ], [
            'confirmation.in' => 'Type RESET exactly to confirm.',
        ]);

        DB::transaction(function () {
            $this->wipeTables(self::TRANSACTIONAL_TABLES);
            Ingredient::query()->update(['current_quantity' => 0]);
        });

        return back()->with('success', 'System has been reset. All transaction history cleared and inventory zeroed.');
    }

    public function factoryReset(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $request->validate([
            'confirmation' => ['required', 'in:FACTORY RESET'],
        ], [
            'confirmation.in' => 'Type FACTORY RESET exactly to confirm.',
        ]);

        DB::transaction(function () {
            $this->wipeTables(self::TRANSACTIONAL_TABLES);
            $this->wipeTables(self::SETUP_TABLES);
        });

        return back()->with('success', 'Factory reset complete. The system is now clean and ready for a fresh setup.');
    }

    private function wipeTables(array $tables): void
    {
        try { DB::statement('PRAGMA foreign_keys=OFF'); } catch (\Throwable) {}

        foreach ($tables as $table) {
            DB::table($table)->delete();
            try {
                DB::statement("DELETE FROM sqlite_sequence WHERE name='{$table}'");
            } catch (\Throwable) {}
        }

        try { DB::statement('PRAGMA foreign_keys=ON'); } catch (\Throwable) {}
    }
}
