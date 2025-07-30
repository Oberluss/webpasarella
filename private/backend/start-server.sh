#!/bin/bash
# Script para mantener el servidor ejecutándose

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Directorio del servidor
SERVER_DIR="/home/Oberlus/web/webpasarella.dnns.es/private/backend"
LOG_FILE="$SERVER_DIR/server.log"

# Función para verificar si el servidor está corriendo
check_server() {
    if pgrep -f "node server.js" > /dev/null; then
        return 0
    else
        return 1
    fi
}

# Detener servidor si está corriendo
if check_server; then
    echo -e "${RED}Deteniendo servidor anterior...${NC}"
    pkill -f "node server.js"
    sleep 2
fi

# Iniciar servidor
echo -e "${GREEN}Iniciando servidor WebPasarella...${NC}"
cd $SERVER_DIR
nohup node server.js >> $LOG_FILE 2>&1 &

# Esperar y verificar
sleep 3
if check_server; then
    echo -e "${GREEN}✓ Servidor iniciado correctamente${NC}"
    echo "PID: $(pgrep -f 'node server.js')"
    echo "Logs en: $LOG_FILE"
else
    echo -e "${RED}✗ Error al iniciar el servidor${NC}"
    tail -10 $LOG_FILE
fi
