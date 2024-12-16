<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;


class FirebaseNotificationsService
{

  /**
   * https://firebase-php.readthedocs.io/en/7.16.0/cloud-messaging.html#initializing-the-messaging-component
   *
   * @param [type] $notificationData
   * @param [type] $tokens
   * @param [type] $messageData
   * @return array
   */
  public function sendNotification($notificationData, $tokens, $messageData = []): array
  {
    $messaging = app('firebase.messaging');
    $message = CloudMessage::fromArray([
      'notification' => $notificationData,
      'data' => $messageData
    ]);

    /**
     * @var \Kreait\Firebase\Messaging\MulticastSendReport
     */
    $result = $messaging->sendMulticast($message, $tokens);

    $responseData = [
      'invalid' => count($result->invalidTokens()),
      'unknown' => count($result->unknownTokens()),
      'valid' => count($result->validTokens())
    ];
    Log::info("Firebase response:" . print_r($responseData, true));
    return $responseData;
  }

  static public function getService(): self
  {
    return app()->make(self::class);
  }
}
