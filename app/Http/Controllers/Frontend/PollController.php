<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;

class PollController extends Controller
{
    public function vote(Request $request, Poll $poll)
    {
        if (!$poll->isOpen()) {
            return back()->with('error', __('messages.poll_closed'));
        }

        if ($poll->hasVoted()) {
            return back()->with('error', __('messages.already_voted'));
        }

        if ($poll->multiple_choice) {
            $request->validate(['options' => 'required|array|min:1']);
            $optionIds = $request->input('options');
        } else {
            $request->validate(['option' => 'required|exists:poll_options,id']);
            $optionIds = [$request->input('option')];
        }

        foreach ($optionIds as $optionId) {
            PollVote::create([
                'poll_id' => $poll->id,
                'poll_option_id' => $optionId,
                'user_id' => auth()->id(),
            ]);

            // Increment counter
            $poll->options()->where('id', $optionId)->increment('votes_count');
        }

        ActivityLogger::pollVote($poll, $optionIds[0] ?? null);

        return back()->with('success', __('messages.vote_recorded'));
    }
}
