<x-filament-widgets::widget>
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 rounded-2xl">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Ccircle cx="30" cy="30" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
        
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>
        
        <!-- Content -->
        <div class="relative px-8 py-12">
            <div class="max-w-6xl mx-auto">
                <!-- Header Section -->
                <div class="text-center mb-12">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-2xl mb-6 transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        <x-heroicon-o-building-storefront class="w-10 h-10 text-white" />
                    </div>
                    
                    <h1 class="text-4xl font-bold text-white mb-4">
                        Hoş Geldiniz, <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">{{ auth()->user()->name }}</span>!
                    </h1>
                    
                    <p class="text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed">
                        Premium iş yönetimi platformunuza hoş geldiniz. Mağazalarınızı yönetin, 
                        ortaklarınızla işbirliği yapın ve finansal verilerinizi gerçek zamanlı takip edin.
                    </p>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <!-- Create Store -->
                    <div class="group relative">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl blur opacity-25 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
                        <div class="relative bg-white rounded-2xl p-6 hover:shadow-2xl transition-all duration-300">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                                    <x-heroicon-o-plus-circle class="w-6 h-6 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Mağaza Oluştur</h3>
                                    <p class="text-sm text-gray-500">Yeni mağaza ekleyin</p>
                                </div>
                            </div>
                            <a href="{{ route('filament.admin.resources.stores.create') }}" 
                               class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium transition-colors">
                                Başlayın
                                <x-heroicon-o-arrow-right class="w-4 h-4 ml-1" />
                            </a>
                        </div>
                    </div>

                    <!-- Import Data -->
                    <div class="group relative">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-2xl blur opacity-25 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
                        <div class="relative bg-white rounded-2xl p-6 hover:shadow-2xl transition-all duration-300">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4">
                                    <x-heroicon-o-cloud-arrow-up class="w-6 h-6 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Veri İçe Aktar</h3>
                                    <p class="text-sm text-gray-500">Finansal verilerinizi yükleyin</p>
                                </div>
                            </div>
                            <a href="{{ route('filament.admin.resources.transactions.create') }}" 
                               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors">
                                Yükle
                                <x-heroicon-o-arrow-right class="w-4 h-4 ml-1" />
                            </a>
                        </div>
                    </div>

                    <!-- Invite Partners -->
                    <div class="group relative">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl blur opacity-25 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
                        <div class="relative bg-white rounded-2xl p-6 hover:shadow-2xl transition-all duration-300">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mr-4">
                                    <x-heroicon-o-user-group class="w-6 h-6 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Ortak Davet Et</h3>
                                    <p class="text-sm text-gray-500">İş ortaklarınızı ekleyin</p>
                                </div>
                            </div>
                            <a href="{{ route('filament.admin.resources.partnerships.create') }}" 
                               class="inline-flex items-center text-green-600 hover:text-green-700 font-medium transition-colors">
                                Davet Et
                                <x-heroicon-o-arrow-right class="w-4 h-4 ml-1" />
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 text-center">
                        <div class="text-3xl font-bold text-white mb-2">{{ auth()->user()->company->stores()->count() }}</div>
                        <div class="text-gray-300">Aktif Mağaza</div>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 text-center">
                        <div class="text-3xl font-bold text-white mb-2">{{ auth()->user()->company->partnerships()->where('status', 'ACTIVE')->count() }}</div>
                        <div class="text-gray-300">Aktif Ortaklık</div>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 text-center">
                        <div class="text-3xl font-bold text-white mb-2">{{ auth()->user()->company->transactions()->whereMonth('created_at', now()->month)->count() }}</div>
                        <div class="text-gray-300">Bu Ay İşlem</div>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 text-center">
                        <div class="text-3xl font-bold text-white mb-2">{{ auth()->user()->company->users()->count() }}</div>
                        <div class="text-gray-300">Takım Üyesi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>