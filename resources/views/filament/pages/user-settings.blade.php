<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Profil Bilgileri --}}
        <div>
            <form wire:submit="saveProfile">
                {{ $this->profileForm }}
                
                <div class="mt-4">
                    <x-filament::button type="submit">
                        Profil Bilgilerini Kaydet
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Güvenlik --}}
        <div>
            <form wire:submit="savePassword">
                {{ $this->securityForm }}
                
                <div class="mt-4">
                    <x-filament::button type="submit">
                        Şifreyi Güncelle
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Bildirimler --}}
        <div>
            <form wire:submit="saveNotifications">
                {{ $this->notificationForm }}
                
                <div class="mt-4">
                    <x-filament::button type="submit">
                        Bildirim Tercihlerini Kaydet
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Çalışma Tercihleri --}}
        <div>
            <form wire:submit="savePreferences">
                {{ $this->preferenceForm }}
                
                <div class="mt-4">
                    <x-filament::button type="submit">
                        Tercihleri Kaydet
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Giriş Logları --}}
        <div class="mt-8">
            <h3 class="text-lg font-medium mb-4">Son Giriş Bilgileri</h3>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
