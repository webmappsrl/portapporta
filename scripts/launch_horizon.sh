#!/bin/bash
set -e

source ./.env

ENVIRONMENT=${1:-dev}
SCREEN_NAME="horizon_${ENVIRONMENT}_pap_worker"

echo "SCREEN_NAME: $SCREEN_NAME"

if screen -list | grep -q "$SCREEN_NAME"; then
  echo "Termino Horizon. Eventuali jobs in esecuzione verranno terminati prima di proseguire..."
  php artisan horizon:terminate

  while php artisan horizon:status | grep -q 'running'; do
    echo "Attendere che Horizon termini..."
    sleep 5
  done

  screen -S "$SCREEN_NAME" -X quit
  echo "Horizon terminato."
fi

echo "Avvio Horizon in una nuova sessione screen..."
screen -dmS "$SCREEN_NAME" php artisan horizon

# Verifica se la sessione screen è stata avviata
if screen -list | grep -q "$SCREEN_NAME"; then
  echo "Horizon avviato con successo in una nuova sessione screen."
else
  echo "Errore: Horizon non è stato avviato correttamente."
fi
