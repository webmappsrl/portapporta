<?php

use Kreait\Firebase\Messaging\CloudMessage;


class FirebaseNotificationsService
{

  public function sendNotifications($title, $body, $tokens)
  {
    $messaging = app('firebase.messaging');
    $message = CloudMessage::fromArray([
      'notification' => ['title' => $title, 'body' => $body],
    ]);

    $result = $messaging->sendMulticast($message, $tokens);
    return ! $result->hasFailures();
  }

  static public function getService()
  {
    return app()->make(self::class);
  }
}
