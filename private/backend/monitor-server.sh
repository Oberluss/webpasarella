#!/bin/bash
# Monitor para reiniciar el servidor si se cae

SERVER_DIR="/home/Oberlus/web/webpasarella.dnns.es/private/backend"
LOG_FILE="$SERVER_DIR/monitor.log"

# Verificar si el servidor está corriendo
if ! pgrep -f "node server.js" > /dev/null; then
    echo "[$(date)] Servidor caído, reiniciando..." >> $LOG_FILE
    cd $SERVER_DIR
    ./start-server.sh >> $LOG_FILE 2>&1
fi
