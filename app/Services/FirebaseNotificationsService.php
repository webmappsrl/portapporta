<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;


class FirebaseNotificationsService
{

  public function sendNotification($data, $tokens): array
  {
    $messaging = app('firebase.messaging');
    $message = CloudMessage::fromArray([
      'notification' => $data,
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
