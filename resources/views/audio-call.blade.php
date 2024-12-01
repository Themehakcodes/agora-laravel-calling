@extends('layouts.app')
@section('content')

<div class="flex justify-center w-full p-4">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg">
        <!-- Audio call UI -->
        <div class="text-center mb-6">
            <h3 class="text-3xl font-semibold text-gray-700">Audio Call</h3>
        </div>
        
        <div class="text-center mb-6">
            <h3 class="text-3xl font-semibold text-gray-700">Channel Name {{$channelName}}</h3>
        </div>

        <!-- Calling UI -->
        @if (auth()->user()->user_id != $channelName)
            <div id="calling-ui" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-70"
                style="display: flex;">
                <div class="cc-card bg-white p-8 rounded-lg shadow-lg flex flex-col items-center w-full max-w-md">
                    <cometchat-label class="cc-card__title text-2xl font-semibold mb-4 text-center">Calling...</cometchat-label>
                    <div class="cc-card__subtitle-view mb-4">
                        <span id="receiver-name" class="text-lg font-medium text-gray-700">Receiver Name</span>
                    </div>
                    <img
                        alt="Receiver's Profile Photo"
                        class="w-24 h-24 rounded-full mb-4 object-cover border-2 border-gray-300" />
                    <div class="cc-card__bottom-view w-full mt-6">
                        <button id="end-call" onclick="endCallByCaller()"
                            class="bg-red-500 text-white py-3 px-6 rounded-lg hover:bg-red-600 transition duration-200 w-full focus:outline-none focus:ring-2 focus:ring-red-600">
                            End Call
                        </button>
                    </div>
                </div>
            </div>
        @endif
        
        <div id="local" class="flex justify-center items-center bg-gray-100 p-4 rounded-xl shadow-lg" style="display: none;">
            <!-- Local audio track (user's own audio) -->
            <i class="fa fa-microphone text-xl text-green-500" id="localMic" aria-hidden="true"></i>
        </div>
        
        <div id="remote" class="flex flex-col justify-center items-center bg-gray-100 p-4 rounded-xl shadow-lg">
            <!-- Remote audio track (other user's audio) -->
            <i class="fa fa-user-circle text-4xl text-gray-500" aria-hidden="true"></i>
            <p class="mt-2 text-gray-600">Waiting for the other participant...</p>
        </div>

        <div class="flex justify-center mt-6">
            <button class="btn btn-secondary bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-full shadow-md" id="btnEnd">
                <i class="fas fa-phone-slash mr-2"></i> End Call
            </button>
        </div>

        <div class="flex justify-center mt-4">
            <p id="timer" class="text-lg font-medium text-gray-700" style="display: none;"></p>
        </div>

        <div class="flex justify-center mt-4">
            <button class="btn btn-warning bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-full shadow-md" id="btnMic">
                <i class="fa fa-microphone" aria-hidden="true"></i> Mute/Unmute
            </button>
        </div>
    </div>
</div>

<script>
    const config = {
        mode: 'rtc',
        codec: 'vp8'
    };

    const options = {
        appId: '0c05a8f344274429b0b3dd112cced017',
        channel: '{{ $channelName }}',
        token: null,
    };

    const rtc = {
        client: null,
        localAudioTrack: null,
        remoteAudioTrack: null,
    };

    const btnEnd = $('#btnEnd');
    const btnMic = $('#btnMic');
    const remote = $('#remote');
    const local = $('#local');
    const timerDisplay = $('#timer');
    const callingUI = $('#calling-ui');
    const ringtone = $('#ringtone');
    let isMuted = false;
    let timerInterval;
    let seconds = 0;

    // Auto join call on page load
    $(document).ready(async function () {
        await startAudioCall();
    });

    const join = async () => {
        rtc.client = AgoraRTC.createClient(config);
        await rtc.client.join(options.appId, options.channel, options.token || null);
    };

    const startAudio = async () => {
        rtc.localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        rtc.client.publish(rtc.localAudioTrack);
        local.show();
    };

    const stopAudio = () => {
        if (rtc.localAudioTrack) {
            rtc.localAudioTrack.close();
            rtc.localAudioTrack.stop();
            rtc.client.unpublish(rtc.localAudioTrack);
        }
        local.hide();
    };

    const startTimer = () => {
        timerDisplay.show();
        timerInterval = setInterval(() => {
            seconds++;
            const minutes = Math.floor(seconds / 60);
            const displaySeconds = seconds % 60;
            timerDisplay.text(`${minutes}:${displaySeconds < 10 ? '0' + displaySeconds : displaySeconds}`);
        }, 1000);
    };

    const stopTimer = () => {
        clearInterval(timerInterval);
        timerDisplay.hide();
        seconds = 0;
    };

    const startAudioCall = async () => {
        await join();
        await startAudio();

        rtc.client.on('user-published', async (user, mediaType) => {
            if (mediaType === 'audio') {
                await rtc.client.subscribe(user, mediaType);
                const remoteAudioTrack = user.audioTrack;
                remoteAudioTrack.play();
                remote.html('<i class="fa fa-user-circle text-4xl text-gray-500" aria-hidden="true"></i><p class="mt-2 text-gray-600">Audio Call Connected</p>');
                startTimer();

                // Hide calling UI when call is connected
                ringtone[0].pause();
                callingUI.hide();
            }
        });

        rtc.client.on('user-left', () => {
            remote.html('<i class="fa fa-user-circle text-4xl text-gray-500" aria-hidden="true"></i><p class="mt-2 text-gray-600">Waiting for the other participant...</p>');
            stopTimer();
        });
    };

    const endCall = () => {
        rtc.client.leave();
        stopAudio();
        stopTimer();
        remote.html('<p class="mt-2 text-gray-600">Waiting for the other participant...</p>');
        btnEnd.hide();
        console.log('Call ended');
        
        // Redirect to dashboard after ending call
        window.location.href = '/dashboard';
    };

    const endCallByCaller = () => {
        endCall(); // Ensure the call ends correctly
        console.log('Call ended by caller');
    };

    btnEnd.click(function () {
        endCall();
        console.log('Call ended by user');
    });

    btnMic.click(function () {
        if (!rtc.localAudioTrack) {
            console.error('Local audio track is not initialized.');
            return;
        }
        
        if (isMuted) {
            rtc.localAudioTrack.setMuted(false);
            $(this).html('<i class="fa fa-microphone" aria-hidden="true"></i> Mute/Unmute');
            $(this).css('color', 'black');
        } else {
            rtc.localAudioTrack.setMuted(true);
            $(this).html('<i class="fa fa-microphone-slash" aria-hidden="true"></i> Mute/Unmute');
            $(this).css('color', 'red');
        }
        isMuted = !isMuted;
    });

</script>

@endsection
