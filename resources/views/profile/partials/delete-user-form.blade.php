<div>
    <p class="text-sm text-gray-400 mb-4">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</p>
    <button type="button" onclick="document.getElementById('deleteModal').classList.remove('hidden')"
            class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-700 hover:bg-red-600 transition-colors"
            style="font-family:'Rajdhani',sans-serif;">
        ⚠️ {{ __('Delete Account') }}
    </button>
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.7);">
        <div class="bg-gray-800 border border-red-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-white mb-2">{{ __('Delete Account') }}</h3>
            <p class="text-sm text-gray-400 mb-4">{{ __('Please enter your password to confirm.') }}</p>
            <form method="POST" action="{{ route('profile.destroy.post') }}">
                @csrf
                <input type="password" name="password" placeholder="{{ __('Password') }}"
                       class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm mb-4 outline-none focus:border-red-600">
                @error('password', 'userDeletion')
                    <p class="text-red-400 text-xs mb-3">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg text-sm text-gray-400 border border-gray-600 hover:border-gray-400 transition-all">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-700 hover:bg-red-600 transition-colors">
                        {{ __('Delete Account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
