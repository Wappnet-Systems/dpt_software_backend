<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UserLoginLog extends Model
{
    use HasFactory;

    protected $table = "users_login_logs";

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $appends = ['device_type_name'];

    const DEVICE_TYPE = [
        'Android' => 1,
        'Apple' => 2,
    ];

    /**
     * Get the status name.
     *
     * @return string
     */
    public function getDeviceTypeNameAttribute()
    {
        $flipDeviceTypes = array_flip(self::DEVICE_TYPE);

        if (isset($flipDeviceTypes[$this->device_type]) && !empty($flipDeviceTypes[$this->device_type])) {
            return "{$flipDeviceTypes[$this->device_type]}";
        }

        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public static function sendPushNotification($token = array(), $message = array(), $data = array(), $userType = null)
    {
        try {
            if (!empty($message['receiver_id'])) {
                if (!empty($token)) {
                    $url = env('FCM_URL');

                    $serverKey = env('FCM_SERVER_KEY');
                    $senderId = env('FCM_SENDER_ID');

                    $notification = array(
                        'title' => $message['title'],
                        'body'  => $message['body']
                    );

                    $arrayToSend = array(
                        'notification' => $notification,
                        'priority' => 'high',
                        // 'data' => $data
                    );

                    if (is_array($token)) {
                        $arrayToSend['registration_ids'] = (array)$token;
                    } else {
                        $arrayToSend['to'] = current([$token]);
                    }

                    $json = json_encode($arrayToSend);

                    $headers = [
                        'Authorization: key=' . $serverKey,
                        'Content-Type: application/json',
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

                    $response = curl_exec($ch);

                    if ($response === FALSE) {
                        $response = array('type' => 'failure', 'error' => curl_error($ch));

                        Log::info('Push Error :- ' . json_encode($response));

                        return $response;
                    }

                    curl_close($ch);

                    return json_decode($response);
                }
            } else {
                return;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
