<?php

namespace App\Jobs;

use App\Models\System\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use App\Models\System\UserLoginLog;
use App\Models\System\Role;
use Illuminate\Support\Facades\Log;

class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $title;
    public $message;
    public $data;
    public $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $title, $message, $data, $type = 'User')
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ($this->type == 'User') {
                $deviceDetails = UserLoginLog::whereUserId($this->user['id'])->first()->toArray();

                $payload = [
                    'data' => $this->data
                ];

                if ($deviceDetails) {
                    $msg_display =  [
                        'receiver_id'   => $this->user['id'],
                        'title'         => $this->title,
                        'body'          => $this->message
                    ];

                    $token = $deviceDetails['device_token'];

                    UserLoginLog::sendPushNotification($token, $msg_display, $payload, $this->type);
                }

                Notification::create([
                    'user_id' => $this->user['id'],
                    'title' => $this->title,
                    'message' => $this->message,
                    'payload' => !empty($payload) ? json_encode($payload) : '',
                    'created_by' => $this->user['created_by'],
                    'is_read' => false,
                    'type' => Notification::TYPE['User']
                ]);
            } 
            else {
                Notification::create([
                    'user_id' => $this->user['id'],
                    'title' => $this->title,
                    'message' => $this->message,
                    'created_by' => $this->user['created_by'],
                    'is_read' => false,
                    'type' => Notification::TYPE['Admin']
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
