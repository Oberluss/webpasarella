#!/bin/bash
# Script unificado de inicio para WebPasarella

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuración
SERVER_DIR="$(dirname "$0")"
MODE=${1:-production}

cd "$SERVER_DIR"

echo -e "${YELLOW}WebPasarella Starter${NC}"
echo "======================="

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js no está instalado${NC}"
    exit 1
fi

# Instalar dependencias si no existen
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias..."
    npm install
fi

# Verificar .env
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "📝 Creando archivo .env..."
        cp .env.example .env
        echo -e "${YELLOW}⚠️  IMPORTANTE: Edita el archivo .env con tus credenciales${NC}"
    else
        echo -e "${RED}❌ No se encuentra .env ni .env.example${NC}"
        exit 1
    fi
fi

# Modo de inicio
if [ "$MODE" = "dev" ] || [ "$MODE" = "development" ]; then
    echo -e "${GREEN}🔧 Modo DESARROLLO${NC}"
    
    # Detener servidor anterior
    if pgrep -f "node server.js" > /dev/null; then
        echo "Deteniendo servidor anterior..."
        pkill -f "node server.js"
        sleep 2
    fi
    
    # Iniciar con nohup
    echo "Iniciando servidor..."
    nohup node server.js >> server.log 2>&1 &
    
    sleep 3
    if pgrep -f "node server.js" > /dev/null; then
        echo -e "${GREEN}✓ Servidor iniciado${NC}"
        echo "PID: $(pgrep -f 'node server.js')"
        echo "Logs: tail -f server.log"
    else
        echo -e "${RED}✗ Error al iniciar${NC}"
        tail -10 server.log
    fi
    
else
    echo -e "${GREEN}🚀 Modo PRODUCCIÓN${NC}"
    
    # Verificar PM2
    if ! command -v pm2 &> /dev/null; then
        echo "📦 Instalando PM2..."
        npm install -g pm2
    fi
    
    # Iniciar con PM2
    echo "Iniciando con PM2..."
    pm2 start ecosystem.config.js
    pm2 save
    
    echo -e "${GREEN}✅ Servidor iniciado con PM2${NC}"
    echo "📊 Ver logs: pm2 logs webpasarella"
    echo "📊 Ver estado: pm2 status"
    echo "🔄 Reiniciar: pm2 restart webpasarella"
    echo "🛑 Detener: pm2 stop webpasarella"
fi

echo "======================="
