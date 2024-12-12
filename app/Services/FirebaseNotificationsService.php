<?php

namespace App\Services;


use Kreait\Firebase\Messaging\CloudMessage;


class FirebaseNotificationsService
{

  public function sendNotification($data, $tokens)
  {
    $messaging = app('firebase.messaging');
    $message = CloudMessage::fromArray([
      'notification' => $data,
    ]);

    $result = $messaging->sendMulticast($message, $tokens);
    return ! $result->hasFailures();
  }

  static public function getService()
  {
    return app()->make(self::class);
  }
}
