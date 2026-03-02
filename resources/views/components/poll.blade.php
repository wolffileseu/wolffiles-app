{{-- Poll Component --}}
{{-- Usage: @include('components.poll', ['poll' => $poll]) or @include('components.poll') for latest active --}}
@php
    $poll = $poll ?? \App\Models\Poll::where('is_active', true)
        ->where(function($q) { $q->whereNull('ends_at')->orWhere('ends_at', '>', now()); })
        ->with('options')
        ->latest()
        ->first();
    $totalVotes = $poll ? $poll->options->sum('votes_count') : 0;
    $hasVoted = $poll ? $poll->hasVoted() : false;
@endphp

@if($poll)
<div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
    <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider mb-1 flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span>{{ __('messages.poll') }}</span>
    </h3>

    <p class="text-white font-medium mb-4 mt-2">{{ $poll->question }}</p>

    @if($hasVoted || !$poll->isOpen())
        {{-- Show results --}}
        <div class="space-y-2">
            @foreach($poll->options as $option)
                @php $pct = $option->percentage($totalVotes); @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-300">{{ $option->text }}</span>
                        <span class="text-gray-500">{{ $pct }}%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="bg-amber-500 rounded-full h-2 transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-3">{{ $totalVotes }} {{ trans_choice('messages.votes', $totalVotes) }}</p>
    @else
        {{-- Show voting form --}}
        @auth
            <form method="POST" action="{{ route('polls.vote', $poll) }}">
                @csrf
                <div class="space-y-2">
                    @foreach($poll->options as $option)
                        <label class="flex items-center space-x-3 p-2 rounded hover:bg-gray-700/50 cursor-pointer transition-colors">
                            <input type="{{ $poll->multiple_choice ? 'checkbox' : 'radio' }}"
                                   name="{{ $poll->multiple_choice ? 'options[]' : 'option' }}"
                                   value="{{ $option->id }}"
                                   class="text-amber-500 bg-gray-700 border-gray-600 focus:ring-amber-500">
                            <span class="text-sm text-gray-300">{{ $option->text }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="mt-3 w-full bg-amber-600 hover:bg-amber-700 text-white py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('messages.vote') }}
                </button>
            </form>
        @else
            <p class="text-sm text-gray-400">
                <a href="{{ route('login') }}" class="text-amber-400 hover:underline">{{ __('messages.login') }}</a> {{ __('messages.to_vote') }}
            </p>
        @endauth
    @endif

    @if($poll->ends_at)
        <p class="text-xs text-gray-500 mt-2">{{ __('messages.poll_ends') }}: {{ $poll->ends_at->diffForHumans() }}</p>
    @endif
</div>
@endif
