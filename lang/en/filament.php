<?php

return [
    'categories' => [
        'SALES' => 'Sales',
        'RETURNS' => 'Returns',
        'PAY-PRODUCT' => 'Product Payments',
        'PAY-DELIVERY' => 'Delivery Payments',
        'INVENTORY' => 'Inventory',
        'WITHDRAW' => 'Withdrawals',
        'END' => 'Transfer Commission',
        'BANK_COM' => 'Bank Commission',
        'FEE' => 'Transaction Fee',
        'ADS' => 'Advertising',
        'OTHER_PAY' => 'Other Payments',
    ],
    
    'transaction_types' => [
        'income' => 'Income',
        'expense' => 'Expense',
    ],
    
    'transaction_status' => [
        'PENDING' => 'Pending',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
    ],
    
    'sales_channels' => [
        'shopify' => 'Shopify',
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'website' => 'Website',
        'marketplace' => 'Marketplace',
        'retail' => 'Retail',
        'wholesale' => 'Wholesale',
        'other' => 'Other',
    ],
    
    'payment_methods' => [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'cash_on_delivery' => 'Cash on Delivery',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'crypto' => 'Cryptocurrency',
        'store_credit' => 'Store Credit',
        'other' => 'Other',
    ],
];