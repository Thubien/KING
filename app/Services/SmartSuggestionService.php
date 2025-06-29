<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmartSuggestionService
{
    public function suggestAssignment(Transaction $transaction): ?array
    {
        // Get similar past transactions
        $similar = $this->findSimilarTransactions($transaction);
        
        // Also get pattern-based suggestions
        $patternSuggestion = $this->getPatternBasedSuggestion($transaction);
        
        if ($similar->isEmpty() && !$patternSuggestion) {
            return null;
        }
        
        // If we have historical data, use it
        if (!$similar->isEmpty()) {
            $mostCommonAssignment = $this->getMostCommonAssignment($similar);
            
            if ($mostCommonAssignment) {
                $confidence = $this->calculateConfidence($similar, $mostCommonAssignment);
                
                return [
                    'store_id' => $mostCommonAssignment['store_id'],
                    'category' => $mostCommonAssignment['category'],
                    'subcategory' => $mostCommonAssignment['subcategory'] ?? null,
                    'confidence' => $confidence,
                    'previous_count' => $similar->count(),
                    'auto_assignable' => false, // Never auto-assign, always let user confirm
                    'suggestion_reason' => "Based on {$similar->count()} similar transactions",
                ];
            }
        }
        
        // Otherwise use pattern suggestion
        if ($patternSuggestion) {
            return array_merge($patternSuggestion, [
                'auto_assignable' => false, // Never auto-assign
                'suggestion_reason' => 'Based on transaction description pattern',
            ]);
        }
        
        return null;
    }
    
    private function findSimilarTransactions(Transaction $transaction): Collection
    {
        // Extract key words from description
        $keywords = $this->extractKeywords($transaction->description);
        
        if (empty($keywords)) {
            return collect();
        }
        
        $query = Transaction::where('assignment_status', 'assigned')
            ->where('id', '!=', $transaction->id);
        
        // Match by amount type (income/expense)
        if ($transaction->amount >= 0) {
            $query->where('amount', '>=', 0);
        } else {
            $query->where('amount', '<', 0);
        }
        
        // Match by keywords
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('description', 'like', '%' . $keyword . '%');
            }
        });
        
        return $query->with('store')->take(20)->get();
    }
    
    private function extractKeywords(string $description): array
    {
        // Common patterns to extract
        $patterns = [
            '/facebook|fb|meta/i',
            '/google|gads/i',
            '/stripe|payment|fee/i',
            '/payoneer|transfer/i',
            '/shopify|shop/i',
            '/refund|return/i',
            '/salary|wage|payroll/i',
            '/rent|lease/i',
            '/alibaba|supplier|vendor/i',
            '/dhl|fedex|ups|shipping/i',
        ];
        
        $keywords = [];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $keywords[] = strtolower($matches[0]);
            }
        }
        
        // Also extract significant words (length > 4)
        $words = preg_split('/\s+/', $description);
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z0-9]/', '', $word);
            if (strlen($word) > 4 && !is_numeric($word)) {
                $keywords[] = strtolower($word);
            }
        }
        
        return array_unique($keywords);
    }
    
    private function getMostCommonAssignment(Collection $transactions): ?array
    {
        $assignments = $transactions->groupBy(function ($transaction) {
            return $transaction->store_id . '-' . $transaction->category . '-' . ($transaction->subcategory ?? 'null');
        })->map(function ($group) {
            $first = $group->first();
            return [
                'store_id' => $first->store_id,
                'category' => $first->category,
                'subcategory' => $first->subcategory,
                'count' => $group->count(),
            ];
        })->sortByDesc('count');
        
        return $assignments->first();
    }
    
    private function calculateConfidence(Collection $similar, array $assignment): int
    {
        $totalSimilar = $similar->count();
        $matchingAssignment = $similar->filter(function ($transaction) use ($assignment) {
            return $transaction->store_id == $assignment['store_id'] 
                && $transaction->category == $assignment['category']
                && $transaction->subcategory == ($assignment['subcategory'] ?? null);
        })->count();
        
        $baseConfidence = ($matchingAssignment / $totalSimilar) * 100;
        
        // Boost confidence based on total count
        if ($totalSimilar >= 10) {
            $baseConfidence = min(100, $baseConfidence + 10);
        } elseif ($totalSimilar >= 5) {
            $baseConfidence = min(100, $baseConfidence + 5);
        }
        
        return (int) round($baseConfidence);
    }
    
    private function getPatternBasedSuggestion(Transaction $transaction): ?array
    {
        $description = strtolower($transaction->description);
        $amount = $transaction->amount;
        
        // Common patterns with categories
        $patterns = [
            // Income patterns
            ['pattern' => '/shopify\s*(payment|payout)|stripe\s*payout/i', 'category' => 'SALES', 'type' => 'income'],
            ['pattern' => '/sales|revenue|order/i', 'category' => 'SALES', 'type' => 'income'],
            ['pattern' => '/partner.*repay|loan.*repay|debt.*payment/i', 'category' => 'PARTNER_REPAYMENT', 'subcategory' => 'PERSONAL_LOAN', 'type' => 'income'],
            ['pattern' => '/advance.*return|advance.*repay/i', 'category' => 'PARTNER_REPAYMENT', 'subcategory' => 'ADVANCE_RETURN', 'type' => 'income'],
            ['pattern' => '/dividend|stock.*return|fund.*return/i', 'category' => 'INVESTMENT_RETURN', 'subcategory' => 'STOCK_DIVIDEND', 'type' => 'income'],
            ['pattern' => '/interest|yield/i', 'category' => 'INVESTMENT_RETURN', 'subcategory' => 'INTEREST', 'type' => 'income'],
            ['pattern' => '/investment|funding|capital/i', 'category' => 'INVESTMENT_INCOME', 'subcategory' => 'PARTNER_INVESTMENT', 'type' => 'income'],
            ['pattern' => '/angel|vc\s*fund/i', 'category' => 'INVESTMENT_INCOME', 'subcategory' => 'ANGEL_INVESTMENT', 'type' => 'income'],
            ['pattern' => '/refund|reimburse/i', 'category' => 'OTHER_INCOME', 'subcategory' => 'REFUND', 'type' => 'income'],
            
            // Expense patterns
            ['pattern' => '/facebook|fb\s*ads|meta\s*(business|ads)/i', 'category' => 'ADS', 'subcategory' => 'FACEBOOK', 'type' => 'expense'],
            ['pattern' => '/google\s*ads|gads|adwords/i', 'category' => 'ADS', 'subcategory' => 'GOOGLE', 'type' => 'expense'],
            ['pattern' => '/tiktok\s*ads/i', 'category' => 'ADS', 'subcategory' => 'OTHER', 'type' => 'expense'],
            ['pattern' => '/stripe\s*fee|processing\s*fee/i', 'category' => 'FEE', 'type' => 'expense'],
            ['pattern' => '/paypal\s*fee/i', 'category' => 'FEE', 'type' => 'expense'],
            ['pattern' => '/transfer\s*fee|wire\s*fee/i', 'category' => 'BANK_FEE', 'subcategory' => 'TRANSFER_FEE', 'type' => 'expense'],
            ['pattern' => '/monthly\s*fee|account\s*fee/i', 'category' => 'BANK_FEE', 'subcategory' => 'MONTHLY_FEE', 'type' => 'expense'],
            ['pattern' => '/exchange\s*fee|fx\s*fee/i', 'category' => 'BANK_FEE', 'subcategory' => 'EXCHANGE_FEE', 'type' => 'expense'],
            ['pattern' => '/alibaba|supplier|vendor|manufacturer/i', 'category' => 'PAY-PRODUCT', 'type' => 'expense'],
            ['pattern' => '/dhl|fedex|ups|shipping|delivery/i', 'category' => 'PAY-DELIVERY', 'type' => 'expense'],
            ['pattern' => '/salary|wage|payroll|partner.*withdraw/i', 'category' => 'WITHDRAW', 'type' => 'expense'],
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern['pattern'], $description)) {
                // Check if amount type matches
                if (($amount >= 0 && $pattern['type'] === 'income') || 
                    ($amount < 0 && $pattern['type'] === 'expense')) {
                    
                    // Get the most used store for this user
                    $defaultStore = $this->getMostUsedStore();
                    
                    return [
                        'store_id' => $defaultStore?->id,
                        'category' => $pattern['category'],
                        'subcategory' => $pattern['subcategory'] ?? null,
                        'confidence' => 75, // Pattern matching has medium confidence
                        'previous_count' => 0,
                    ];
                }
            }
        }
        
        return null;
    }
    
    private function getMostUsedStore(): ?Store
    {
        return Store::whereHas('transactions', function ($query) {
            $query->where('created_by', auth()->id())
                ->where('assignment_status', 'assigned');
        })
        ->withCount(['transactions' => function ($query) {
            $query->where('created_by', auth()->id())
                ->where('assignment_status', 'assigned');
        }])
        ->orderByDesc('transactions_count')
        ->first();
    }
    
    public function applySuggestion(Transaction $transaction, array $suggestion): void
    {
        $transaction->update([
            'store_id' => $suggestion['store_id'],
            'category' => $suggestion['category'],
            'subcategory' => $suggestion['subcategory'] ?? null,
            'assignment_status' => 'assigned',
            'suggestion_confidence' => $suggestion['confidence'],
            'suggested_assignment' => $suggestion,
        ]);
    }
    
    public function learnFromAssignment(Transaction $transaction): void
    {
        // Store the assignment pattern for future learning
        // This helps the system learn from user corrections
        DB::table('transaction_learning_patterns')->insert([
            'description_pattern' => $this->extractMainPattern($transaction->description),
            'amount_type' => $transaction->amount >= 0 ? 'income' : 'expense',
            'assigned_category' => $transaction->category,
            'assigned_subcategory' => $transaction->subcategory,
            'assigned_store_id' => $transaction->store_id,
            'user_id' => auth()->id(),
            'confidence' => 100, // User assignment has full confidence
            'created_at' => now(),
        ]);
    }
    
    private function extractMainPattern(string $description): string
    {
        // Extract the main identifier from description
        $description = strtolower($description);
        
        // Remove common words and numbers
        $pattern = preg_replace('/\b(the|and|or|for|with|from|to)\b/i', '', $description);
        $pattern = preg_replace('/[0-9]+/', '', $pattern);
        $pattern = preg_replace('/\s+/', ' ', trim($pattern));
        
        // Get first 3 significant words
        $words = array_filter(explode(' ', $pattern), fn($word) => strlen($word) > 3);
        return implode(' ', array_slice($words, 0, 3));
    }
}