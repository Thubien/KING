<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="space-y-3">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Plan Limitleri</h4>
            
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Mağaza Sayısı</span>
                    <span class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $features['stores'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Kullanıcı Sayısı</span>
                    <span class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $features['users'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Aylık İşlem</span>
                    <span class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ is_numeric($features['transactions']) ? number_format($features['transactions']) : $features['transactions'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Destek Seviyesi</span>
                    <span class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $features['support'] }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Özellikler</h4>
            
            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    @if($features['api'])
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    <span class="text-sm text-gray-600 dark:text-gray-400">API Erişimi</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    @if($features['webhooks'])
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    <span class="text-sm text-gray-600 dark:text-gray-400">Webhooks</span>
                </div>

                @if($settings)
                    <div class="flex items-center space-x-2">
                        @if($settings['multi_currency_enabled'] ?? false)
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        <span class="text-sm text-gray-600 dark:text-gray-400">Çoklu Para Birimi</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if($settings['advanced_analytics_enabled'] ?? false)
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        <span class="text-sm text-gray-600 dark:text-gray-400">Gelişmiş Analizler</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($plan !== 'enterprise')
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Plan yükseltme avantajları:</p>
            <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                @if($plan === 'free')
                    <li>• Daha fazla mağaza ve kullanıcı ekleyin</li>
                    <li>• API entegrasyonlarını aktifleştirin</li>
                    <li>• Öncelikli destek alın</li>
                @elseif($plan === 'starter')
                    <li>• 10 mağazaya kadar genişletin</li>
                    <li>• Webhook entegrasyonları</li>
                    <li>• Gelişmiş raporlama özellikleri</li>
                @elseif($plan === 'professional')
                    <li>• Sınırsız mağaza ve kullanıcı</li>
                    <li>• 7/24 özel destek</li>
                    <li>• Özel entegrasyonlar</li>
                @endif
            </ul>
        </div>
    @endif
</div>