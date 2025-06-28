<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Ukrainian Banking Information
        </x-slot>
        
        <x-slot name="description">
            Important information for Ukrainian bank accounts and current banking status
        </x-slot>

        <div class="space-y-6">
            {{-- Current Status Alert --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Special Considerations for Ukrainian Banks
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Due to the ongoing conflict, some Ukrainian banking services may experience limitations. Please verify bank status and available services before proceeding.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Popular Banks --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Popular Ukrainian Banks</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($popular_banks as $bank)
                        <div class="bg-gray-50 rounded-lg p-3 border">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="font-medium text-gray-900">{{ $bank['name'] }}</h5>
                                    <p class="text-sm text-gray-600">MFO: {{ $bank['mfo'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $bank['note'] }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ ucfirst($bank['status']) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Important Information --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Important Banking Information</h4>
                <div class="bg-blue-50 rounded-lg p-4">
                    <ul class="space-y-2">
                        @foreach($important_notes as $note)
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                <span class="text-sm text-blue-800">{{ $note }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- IBAN Example --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Ukrainian IBAN Format Example</h4>
                <div class="bg-gray-100 rounded-lg p-4 font-mono text-sm">
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div class="bg-red-100 p-2 rounded">
                            <div class="font-bold text-red-700">UA</div>
                            <div class="text-xs text-red-600">Country</div>
                        </div>
                        <div class="bg-orange-100 p-2 rounded">
                            <div class="font-bold text-orange-700">21</div>
                            <div class="text-xs text-orange-600">Check</div>
                        </div>
                        <div class="bg-blue-100 p-2 rounded">
                            <div class="font-bold text-blue-700">305299</div>
                            <div class="text-xs text-blue-600">MFO Code</div>
                        </div>
                        <div class="bg-green-100 p-2 rounded">
                            <div class="font-bold text-green-700">0000026007233566001</div>
                            <div class="text-xs text-green-600">Account Number</div>
                        </div>
                    </div>
                    <div class="mt-3 text-center text-gray-600">
                        Complete IBAN: <span class="font-bold">UA213052990000026007233566001</span>
                    </div>
                </div>
            </div>

            {{-- Support Resources --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Support Resources</h4>
                <div class="text-sm text-gray-600 space-y-1">
                    <p>• National Bank of Ukraine: <a href="https://bank.gov.ua" class="text-blue-600 hover:underline" target="_blank">bank.gov.ua</a></p>
                    <p>• Ukrainian Banking Association: <a href="https://uba.ua" class="text-blue-600 hover:underline" target="_blank">uba.ua</a></p>
                    <p>• For international transfers, contact your bank for current procedures</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>