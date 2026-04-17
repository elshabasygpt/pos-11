<?php

declare(strict_types=1);

namespace App\Application\Accounting\Services;

use App\Domain\Accounting\Repositories\AccountRepositoryInterface;
use App\Domain\Accounting\Repositories\JournalEntryRepositoryInterface;

final class AccountingService
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private JournalEntryRepositoryInterface $journalEntryRepository,
    ) {}

    /**
     * Generate Income Statement (Profit & Loss)
     */
    public function generateIncomeStatement(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $revenueAccounts = $this->accountRepository->getByType('revenue');
        $expenseAccounts = $this->accountRepository->getByType('expense');

        $ledger = $this->journalEntryRepository->getGeneralLedger($from, $to);

        $revenues = [];
        $expenses = [];
        $totalRevenue = 0;
        $totalExpenses = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->calculateAccountBalance($account['id'], $ledger);
            $revenues[] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $totalRevenue += $balance;
        }

        foreach ($expenseAccounts as $account) {
            $balance = $this->calculateAccountBalance($account['id'], $ledger);
            $expenses[] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $totalExpenses += $balance;
        }

        return [
            'period' => ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')],
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $totalRevenue - $totalExpenses,
        ];
    }

    /**
     * Generate Balance Sheet
     */
    public function generateBalanceSheet(\DateTimeImmutable $asOf): array
    {
        $assets = $this->accountRepository->getByType('asset');
        $liabilities = $this->accountRepository->getByType('liability');
        $equity = $this->accountRepository->getByType('equity');

        $ledger = $this->journalEntryRepository->getGeneralLedger(
            new \DateTimeImmutable('1970-01-01'),
            $asOf
        );

        $assetItems = $this->buildAccountBalances($assets, $ledger);
        $liabilityItems = $this->buildAccountBalances($liabilities, $ledger);
        $equityItems = $this->buildAccountBalances($equity, $ledger);

        $totalAssets = array_sum(array_column($assetItems, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilityItems, 'balance'));
        $totalEquity = array_sum(array_column($equityItems, 'balance'));

        return [
            'as_of' => $asOf->format('Y-m-d'),
            'assets' => ['items' => $assetItems, 'total' => $totalAssets],
            'liabilities' => ['items' => $liabilityItems, 'total' => $totalLiabilities],
            'equity' => ['items' => $equityItems, 'total' => $totalEquity],
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
        ];
    }

    private function buildAccountBalances(array $accounts, array $ledger): array
    {
        $result = [];
        foreach ($accounts as $account) {
            $result[] = [
                'account' => $account,
                'balance' => $this->calculateAccountBalance($account['id'], $ledger),
            ];
        }
        return $result;
    }

    private function calculateAccountBalance(string $accountId, array $ledger): float
    {
        $balance = 0;
        foreach ($ledger as $entry) {
            if ($entry['account_id'] === $accountId) {
                $balance += ($entry['debit'] ?? 0) - ($entry['credit'] ?? 0);
            }
        }
        return $balance;
    }
}
