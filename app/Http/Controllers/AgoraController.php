<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AgoraController extends Controller
{
    /**
     * Handles audio call with a specific user.
     */
    public function indexWithUser($user_id)
    {
        $channelName = $user_id; // Use the provided user_id as the channel name
      

      
        return view('audio-call', compact( 'channelName'));
    }

    /**
     * Handles audio call where the channel name is the authenticated user's ID.
     */
    public function indexWithoutUser()
    {
        $user = auth()->user();  // Get the authenticated user
    $channelName = $user->user_id;
        return view('audio-call', compact('user', 'channelName'));
    }
}
