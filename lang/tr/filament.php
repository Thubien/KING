<?php

return [
    'categories' => [
        'SALES' => 'Satışlar',
        'RETURNS' => 'İadeler',
        'PAY-PRODUCT' => 'Ürün Ödemeleri',
        'PAY-DELIVERY' => 'Kargo Ödemeleri',
        'INVENTORY' => 'Stok',
        'WITHDRAW' => 'Para Çekme',
        'END' => 'Transfer Komisyonu',
        'BANK_COM' => 'Banka Komisyonu',
        'FEE' => 'İşlem Ücreti',
        'ADS' => 'Reklamlar',
        'OTHER_PAY' => 'Diğer Ödemeler',
    ],
    
    'transaction_types' => [
        'income' => 'Gelir',
        'expense' => 'Gider',
    ],
    
    'transaction_status' => [
        'PENDING' => 'Beklemede',
        'APPROVED' => 'Onaylandı',
        'REJECTED' => 'Reddedildi',
    ],
    
    'sales_channels' => [
        'shopify' => 'Shopify',
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'website' => 'Website',
        'marketplace' => 'Pazaryeri',
        'retail' => 'Perakende',
        'wholesale' => 'Toptan',
        'other' => 'Diğer',
    ],
    
    'payment_methods' => [
        'credit_card' => 'Kredi Kartı',
        'debit_card' => 'Banka Kartı',
        'bank_transfer' => 'Havale/EFT',
        'cash' => 'Nakit',
        'cash_on_delivery' => 'Kapıda Ödeme',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'crypto' => 'Kripto Para',
        'store_credit' => 'Mağaza Kredisi',
        'other' => 'Diğer',
    ],
];