<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmartSuggestionService
{
    public function suggestAssignment(Transaction $transaction): ?array
    {
        // Get similar past transactions
        $similar = $this->findSimilarTransactions($transaction);
        
        if ($similar->isEmpty()) {
            return null;
        }
        
        // Calculate most common assignment
        $mostCommonAssignment = $this->getMostCommonAssignment($similar);
        
        if (!$mostCommonAssignment) {
            return null;
        }
        
        $confidence = $this->calculateConfidence($similar, $mostCommonAssignment);
        
        return [
            'store_id' => $mostCommonAssignment['store_id'],
            'category' => $mostCommonAssignment['category'],
            'subcategory' => $mostCommonAssignment['subcategory'] ?? null,
            'confidence' => $confidence,
            'previous_count' => $similar->count(),
            'auto_assignable' => $confidence >= 90 && $similar->count() >= 5,
        ];
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
}